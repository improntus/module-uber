<?php

/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2024 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Model\Api;

use Exception;
use Improntus\Uber\Api\OrderShipmentRepositoryInterface;
use Improntus\Uber\Api\WebhookInterface;
use Improntus\Uber\Helper\Data;
use Improntus\Uber\Model\EmailSender;
use Improntus\Uber\Model\Uber;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\OrderRepository;

class Webhook implements WebhookInterface
{

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
     * @var EmailSender $emailSender
     */
    protected EmailSender $emailSender;

    /**
     * @var Uber $uber
     */
    protected Uber $uber;

    /**
     * @var OrderShipmentRepositoryInterface $orderShipmentRepository
     */
    protected OrderShipmentRepositoryInterface $orderShipmentRepository;

    /**
     * @param Uber $uber
     * @param Data $helper
     * @param Request $request
     * @param EmailSender $emailSender
     * @param OrderFactory $orderFactory
     * @param OrderRepository $orderRepository
     * @param OrderShipmentRepositoryInterface $orderShipmentRepository
     */
    public function __construct(
        Uber                             $uber,
        Data                             $helper,
        Request                          $request,
        EmailSender                      $emailSender,
        OrderFactory                     $orderFactory,
        OrderRepository                  $orderRepository,
        OrderShipmentRepositoryInterface $orderShipmentRepository
    )
    {
        $this->uber = $uber;
        $this->helper = $helper;
        $this->request = $request;
        $this->emailSender = $emailSender;
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
    ): array
    {

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
            if ($order['orderId'] === null) {
                // If the order associated with the shipment does not exist, we proceed to cancel it
                try {
                    $uberOrderShipment = $this->orderShipmentRepository->getByIncrementId($incrementId);
                    if($uberOrderShipment->getUberShippingId() === null ||
                        in_array($uberOrderShipment->getStatus(),['delivered','cancelled'])){
                        throw new Exception(__('The order does not have active / in-progress shipments'));
                    }
                    // Cancel Uber Shipment
                    $uberResponse = $this->uber->cancelShipping($delivery_id, $customer_id, $uberOrderShipment->getStoreId());
                    if (!isset($uberResponse['id'])) {
                        throw new Exception(__("Increment ID: $incrementId - Delivery ID: $delivery_id - Cannot cancel shipping order"));
                    }
                    throw new Exception(__("MISSING ORDER - Driver Call CANCELED - Increment ID: $incrementId - Delivery ID: $delivery_id - Cancellation ID: {$uberResponse['id']}"));
                } catch (Exception $e) {
                    throw new Exception($e->getMessage());
                }
                throw new Exception(__('Order not found - Cannot cancel shipping order'));
            }

            /**
             * Validate Webhook Signature
             */
            $this->validateHookSignature($order['orderStore']);

            // Get Order
            $order = $this->orderRepository->get($order['orderId']);
            $orderStatus = $order->getStatus();

            // Get Tracking URL from Webhook
            $trackingUrl = $data['tracking_url'] ?: "";

            /**
             * Compare Current Status
             */
            if (in_array($status, array_keys(Data::UBER_ORDER_STATUS))) {

                /**
                 * Comment History
                 */
                $orderCommentHistory = __('Uber Notification') . '<br>';

                /**
                 * The states uber_pickup / uber_dropoff require the flag courier_imminent must be true
                 */
                $orderNewStatus = Data::UBER_ORDER_STATUS[$status];
                if (($status === 'dropoff' || $status === 'pickup') && $data['courier_imminent'] === true) {
                    /**
                     * Inset Driver Info in Order History
                     */
                    if ($status === 'pickup' && isset($data['courier'])) {
                        $orderCommentHistory .= $this->helper->getDriverAndEstimatedInfo($data);
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
                        case 'canceled':
                            // Add Cancellation Details
                            $cancellationDetail = $this->helper->getCancellationDescription($data['undeliverable_reason']);
                            $orderCommentHistory .= __('<b>Canceled shipment:</b> %1', $cancellationDetail) . '<br>';
                            break;
                        case 'delivered':
                            $orderCommentHistory .= $this->helper->getDeliveredDetails($data) . '<br>';
                            break;
                        default:
                            break;
                    }
                }

                /**
                 * Save Changes
                 */
                if (Data::UBER_ORDER_STATUS[$status] !== $orderStatus) {
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

                /**
                 * Send Email
                 */
                if($this->helper->getEnableEmailUpdate($order->getStoreId())){
                    $this->emailSender->sendEmail($order, $status, $trackingUrl);
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
        if ($magentoWebhookSignatureKey === null || $uberWebhookSignature === null) {
            throw new Exception(__('Missing Webhook Signature'));
        }

        // Compare Hashes
        $hashGenerated = hash_hmac('sha256', $requestBody, $magentoWebhookSignatureKey);
        if ($hashGenerated !== $uberWebhookSignature) {
            $this->helper->logDebug(json_encode([
                'signature' => $magentoWebhookSignatureKey,
                'webhookHash' => $uberWebhookSignature,
                'hashGenerated' => $hashGenerated,
            ]));
            throw new Exception(__('Webhook Signature Invalid'));
        }
    }
}
