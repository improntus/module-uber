<?php

/**
 *  @author Improntus Dev Team
 *  @copyright Copyright (c) 2023 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Model\Api;

use Exception;
use Improntus\Uber\Api\OrderShipmentRepositoryInterface;
use Improntus\Uber\Api\WebhookInterface;
use Improntus\Uber\Helper\Data;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\OrderRepository;

class Webhook implements WebhookInterface
{
    /**
     * UBER_STATUS => MAGENTO_STATUS
     */
    protected const UBER_ORDER_STATUS = [
        'pending' => 'uber_pending',
        'canceled' => 'uber_canceled',
        'delivered' => 'uber_delivered',
        'dropoff' => 'uber_dropoff',
        'pickup' => 'uber_pickup',
        'pickup_complete' => 'uber_pickup_complete',
        'returned' => 'uber_returned'
    ];

    /**
     * @var OrderRepository $orderRepository
     */
    protected OrderRepository $orderRepository;

    /**
     * @var OrderFactory $orderFactory
     */
    protected OrderFactory $orderFactory;

    /**
     * @var Data $helper
     */
    protected Data $helper;

    /**
     * @var Request $request
     */
    protected Request $request;

    /**
     * @var OrderShipmentRepositoryInterface $orderShipmentRepository
     */
    protected OrderShipmentRepositoryInterface $orderShipmentRepository;

    /**
     * @param Data $helper
     * @param Request $request
     * @param OrderFactory $orderFactory
     * @param OrderRepository $orderRepository
     * @param OrderShipmentRepositoryInterface $orderShipmentRepository
     */
    public function __construct(
        Data $helper,
        Request $request,
        OrderFactory $orderFactory,
        OrderRepository $orderRepository,
        OrderShipmentRepositoryInterface $orderShipmentRepository
    ) {
        $this->helper = $helper;
        $this->request = $request;
        $this->orderFactory = $orderFactory;
        $this->orderRepository = $orderRepository;
        $this->orderShipmentRepository = $orderShipmentRepository;
    }

    /**
     * updateStatus
     *
     * @param mixed $data
     * @param mixed $kind
     * @param mixed $status
     * @param mixed $account_id
     * @param mixed $delivery_id
     * @param mixed $customer_id
     * @return array
     */
    public function updateStatus(
        mixed $data,
        mixed $kind,
        mixed $status,
        mixed $account_id,
        mixed $customer_id,
        mixed $delivery_id,
    ): array {

        /**
         * Has Enabled hooks integration?
         */
        if (!$this->helper->isWebhooksEnabled() && !$this->helper->isModuleEnabled()) {
            return [["error" => false, "msg" => __("Integration Disabled")]];
        }

        try {
            if (!isset($data['external_id'])) {
                throw new Exception(__('Missing External ID'));
            }

            $incrementId = $data['external_id'];
            $order = $this->getOrderData($incrementId);
            if (is_null($order['orderId'])) {
                throw new Exception(__('Order not found'));
            }

            /**
             * Validate Webhook Signature
             */
            $this->validateHookSignature($order['orderStore']);

            // Get Order
            $order = $this->orderRepository->get($order['orderId']);
            $orderStatus = $order->getStatus();

            /**
             * Compare Current Status
             */
            if (in_array($status, array_keys(self::UBER_ORDER_STATUS))) {

                /**
                 * Comment History
                 */
                $orderCommentHistory = __('Uber Notification') . '<br>';

                /**
                 * The states uber_pickup / uber_dropoff require the flag courier_imminent must be true
                 */
                $orderNewStatus = self::UBER_ORDER_STATUS[$status];
                if (($status === 'dropoff' or $status === 'pickup') && $data['courier_imminent'] === true) {
                    /**
                     * Inset Driver Info in Order History
                     */
                    if ($status === 'pickup' && isset($data['courier'])) {
                        $orderCommentHistory .= $this->getDriverAndEstimatedInfo($data);
                    }
                } else {
                    /**
                     * Determine action by Status
                     */
                    switch ($status) {
                        case 'dropoff':
                        case 'pickup':
                            // Ignore this event
                            return [["error" => false, "msg" => "ok"]];
                            break;
                        case 'canceled':
                            // Add Cancellation Details
                            $cancellationDetail = $this->getCancellationDescription($data['undeliverable_reason']);
                            $orderCommentHistory .= __('<b>Canceled shipment:</b> %1', $cancellationDetail) . '<br>';
                            break;
                        case 'delivered':
                            $orderCommentHistory .= $this->getDeliveredDetails($data) . '<br>';
                            break;
                        default:
                            break;
                    }
                }

                /**
                 * Save Changes
                 */
                if (self::UBER_ORDER_STATUS[$status] !== $orderStatus) {
                    // Add Change Status
                    $orderCommentHistory .= __('Delivery status has changed to <b>%1</b>', ucfirst($status));

                    // Update Order
                    $order->setStatus($orderNewStatus);
                    $order->addCommentToStatusHistory($orderCommentHistory);
                    $this->orderRepository->save($order);

                    //Update Uber Shipment Data
                    $orderShipment = $this->orderShipmentRepository->getByIncrementId($order->getIncrementId());
                    $orderShipment->setStatus($status);
                    switch ($status) {
                        case 'canceled':
                        case 'returned':
                            // Unset Shipping ID / Verification Data
                            $orderShipment->setUberShippingId(null);
                            $orderShipment->setVerification(null);
                            break;
                        case 'delivered':
                            // Save Dropoff Verification Data
                            $orderShipment->setVerification(json_encode($data['dropoff']['verification']));
                            break;
                        default:
                            break;
                    }
                    $this->orderShipmentRepository->save($orderShipment);
                }
            }
        } catch (Exception $e) {
            $this->helper->log(__('Webhooks ERROR: %1', $e->getMessage()));
            return [['error' => true, 'msg' => $e->getMessage()]];
        }

        return [['error' => false, 'msg' => 'ok']];
    }

    /**
     * getOrderData
     *
     * Return OrderId from IncrementId
     * @param $incrementId
     * @return mixed
     */
    protected function getOrderData($incrementId): mixed
    {
        $orderModel = $this->orderFactory->create();
        $order = $orderModel->loadByIncrementId($incrementId);
        return [
            'orderId' => $order->getId(),
            'orderStore' => $order->getStoreId()
        ];
    }

    /**
     * validateHookSignature
     *
     * Check if the webhook is genuine or not
     * @param int $storeId
     * @throws Exception
     */
    private function validateHookSignature(int $storeId): void
    {
        $requestBody = $this->request->getContent();
        $magentoWebhookSignatureKey = $this->helper->getWebhookSignature($storeId);
        $uberWebhookSignature = $this->request->getHeader('X-Postmates-Signature') ?: null;
        if (is_null($magentoWebhookSignatureKey) or is_null($uberWebhookSignature)) {
            throw new Exception(__('Missing Webhook Signature'));
        }

        // Compare Hashes
        $hashGenerated = hash_hmac('sha256', $requestBody, $magentoWebhookSignatureKey);
        if ($hashGenerated !== $uberWebhookSignature) {
            throw new Exception(__('Webhook Signature Invalid'));
        }
    }

    /**
     * getDriverAndEstimatedInfo
     *
     * Return Driver Info and Estimated Time (Pick/Drop)
     * @param $data
     * @return string
     */
    protected function getDriverAndEstimatedInfo($data): string
    {
        $driverInfoComment = __('<b>Driver Name:</b> %1', $data['courier']['name'] ?? 'n/a') . '<br>';
        $driverInfoComment .= __('<b>Vehicle Type:</b> %1', ucfirst($data['courier']['vehicle_type']) ?? 'n/a') . '<br>';
        $driverInfoComment .= __('<b>Pickup estimated time:</b> %1', $data['pickup_ready'] ?? 'n/a') . '<br>';
        $driverInfoComment .= __('<b>Dropoff estimated time:</b> %1', $data['dropoff_ready'] ?? 'n/a') . '<br>';
        return $driverInfoComment;
    }

    /**
     * getDeliveredDetails
     *
     * Return Delivered Details and Verification
     * @param $data
     * @return string
     */
    protected function getDeliveredDetails($data): string
    {
        $deliveredComment = __('The order was delivered at <b>%1</b>', $data['dropoff']['status_timestamp'] ?? 'n/a') . '<br>';
        // Get Verification Info
        if (isset($data['dropoff']['verification']) &&
            count($data['dropoff']['verification']) > 0) {
            $verificationDetails = $this->getVerificationDetails($data['dropoff']['verification']);
            $deliveredComment .= __('<br><b>Verification Details</b><br>%1', $verificationDetails) . '<br>';
        }
        return $deliveredComment;
    }

    /**
     * getVerificationDetails
     *
     * Return Verification Details
     * @param $data
     * @return string
     */
    protected function getVerificationDetails($data): string
    {
        $verificationMethod = '';
        $verificationInfo = '';

        // Barcode
        if (isset($data['barcodes'])) {
            $verificationInfo .= __('Status: <b>%1</b>', $data['barcodes'][0]['scan_result']['outcome'] ?? 'n/a') . '<br>';
            $verificationInfo .= __('Date: <b>%1</b>', $data['barcodes'][0]['scan_result']['timestamp'] ?? 'n/a') . '<br>';
            $verificationMethod = __('Barcode');
        }

        // Picture
        if (isset($data['picture']) && !empty($data['picture']['image_url'])) {
            $verificationInfo .= __('<a href="%1" target="_blank">View Picture</a>', $data['picture']['image_url']) . '<br>';
            $verificationMethod = __('Picture');
        }

        // Pincode
        if (isset($data['pincode'])) {
            $verificationInfo .= __('Status: <b>Successfully</b>') . '<br>';
            $verificationMethod = __('Pincode');
        }

        // Signature
        if (isset($data['signature']) && !empty($data['signature']['image_url'])) {
            $verificationInfo .= __('Signer Name: <b>%1</b>', $data['signature']['signer_name'] ?? 'n/a') . '<br>';
            $verificationInfo .= __('Signer Relationship: <b>%1</b>', $data['signature']['signer_relationship'] ?? 'n/a') . '<br>';
            $verificationInfo .= '<a href="' . $data['signature']['image_url'] . '" target="_blank">' . __('View Signature') . '</a>';
            $verificationMethod = __('Signature');
        }

        // Add details
        $verificationDetails = __('Method: <b>%1</b>', $verificationMethod) . '<br>';
        $verificationDetails .= $verificationInfo;
        return $verificationDetails;
    }

    /**
     * getCancellationDescription
     *
     * Return cancellation description
     * @param $cancelationReason
     * @return string
     */
    protected function getCancellationDescription($cancellationReason = 'UNKNOWN_CANCEL'): string
    {
        /**
         * Define Reason Code => Description
         */
        $uberCancellationReason = [
            "MERCHANT_CANCEL" => "Marchant cancelled",
            "cancelled_by_merchant_api" => "Marchant cancelled",
            "no_secure_location_to_dropoff" => "Courier doesn't have a safe area to deliver the product",
            "customer_unavailable" => "Customer wasn't available to receive the delivery",
            "customer_not_available" => "Customer wasn't available to receive the delivery",
            "customer_rejected_order" => "Customer refused to receive the delivery",
            "cannot_find_customer_address" => "Courier can't find the correct Customer's address",
            "wrong_address" => "The Customer's address is wrong",
            "cannot_access_customer_location" => "The Customer's dropoff location is not in an accessible area",
            "recipient_intoxicated" => "The Customer isn't sober to receive the delivery",
            "recipient_id" => "The Customer's ID doesn't match with the one required",
            "customer_id_check_failed" => "The Customer's ID doesn't match with the one required",
            "recipient_age" => "The Customer is not overage to receive the delivery",
            "pin_match_issue" => "The Customer's address is wrong",
            "excessive_wait_time" => "Courier waited until the timeout to deliver the product",
            "unable_to_find_pickup" => "Courier wasn't able to find the pickup point",
            "restaurant_closed" => "Merchant store was closed",
            "merchant_closed" => "Merchant store was closed",
            "merchant_refused" => "Merchant refused to deliver the product",
            "oversized_item" => "Items too big to complete the pickup",
            "Other" => "Other reasons",
            "item_lost" => "Items were lost in the return trip process",
            "supplier_closed" => "Merchant store was closed",
            "other_return" => "Other reasons",
            "UBER_CANCEL" => "Uber Cancellation",
            "batch_force_ended_expired_order" => "Uber tried to allocate a courier but the delivery reached timeout",
            "cannot_dispatch_courier" => "Uber wasn't able to allocate a courier to complete the delivery",
            "order_task_failed" => "Uber internal operational issues",
            "courier_report_crash" => "Courier was involved in an accident",
            "Unfulfillment" => "Uber wasn't able to allocate a courier to complete the delivery",
            "UNFULFILLED"  => "Uber wasn't able to allocate a courier to complete the delivery",
            "CUSTOMER_CANCEL" => "Customer cancelled",
            "UNKNOWN_CANCEL" => "Cancelled party not detected"
        ];

        // Return Description
        if (array_key_exists($cancellationReason, $uberCancellationReason)) {
            return __($uberCancellationReason[$cancellationReason]);
        } else {
            return $cancellationReason;
        }
    }
}
