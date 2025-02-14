<?php
/**
 *  @author Improntus Dev Team
 *  @copyright Copyright (c) 2025 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Cron;

use Improntus\Uber\Helper\Data as Helper;
use Improntus\Uber\Model\Uber;
use Improntus\Uber\Model\ResourceModel\OrderShipment\Collection as UberOrderShipment;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Model\OrderRepository;
use Improntus\Uber\Api\OrderShipmentRepositoryInterface;
use Improntus\Uber\Model\Api\Webhook;
use Improntus\Uber\Model\EmailSender;
use Magento\Sales\Model\OrderFactory;

class OrderUpdate
{
    /**
     * @var Helper $helper
     */
    protected Helper $helper;

    /**
     * @var TimezoneInterface $timezoneInterface
     */
    protected TimezoneInterface $timezoneInterface;

    /**
     * @var UberOrderShipment $uberOrderShipment
     */
    protected UberOrderShipment $uberOrderShipment;

    /**
     * @var Uber $uber
     */
    protected Uber $uber;

    /**
     * @var OrderRepository $orderRepository
     */
    protected OrderRepository $orderRepository;

    /**
     * @var OrderShipmentRepositoryInterface $orderShipmentRepository
     */
    protected OrderShipmentRepositoryInterface $orderShipmentRepository;

    /**
     * @var Webhook $webhook
     */
    protected Webhook $webhook;

    /**
     * @var EmailSender $emailSender
     */
    protected EmailSender $emailSender;

    /**
     * @var OrderFactory $orderFactory
     */
    protected OrderFactory $orderFactory;

    /**
     * @param Uber $uber
     * @param Helper $helper
     * @param EmailSender $emailSender
     * @param OrderFactory $orderFactory
     * @param OrderRepository $orderRepository
     * @param UberOrderShipment $uberOrderShipment
     * @param TimezoneInterface $timezoneInterface
     * @param OrderShipmentRepositoryInterface $orderShipmentRepository
     */
    public function __construct(
        Uber $uber,
        Helper $helper,
        EmailSender $emailSender,
        OrderFactory $orderFactory,
        OrderRepository $orderRepository,
        UberOrderShipment $uberOrderShipment,
        TimezoneInterface $timezoneInterface,
        OrderShipmentRepositoryInterface $orderShipmentRepository
    )
    {
        $this->uber = $uber;
        $this->helper = $helper;
        $this->emailSender = $emailSender;
        $this->orderFactory = $orderFactory;
        $this->orderRepository = $orderRepository;
        $this->uberOrderShipment = $uberOrderShipment;
        $this->timezoneInterface = $timezoneInterface;
        $this->orderShipmentRepository = $orderShipmentRepository;
    }

    public function execute(): int
    {
        if (!$this->helper->isModuleEnabled()) {
            return 1;
        }

        try {
            $uberOrderShipmentCollection = $this->gerOrderCollection();
            if($uberOrderShipmentCollection->count() > 0){
                // Process Shipments
                foreach($uberOrderShipmentCollection->getItems() as $uberOrderShipment){
                    $shippingId = $uberOrderShipment->getUberShippingId();
                    $orderId = $uberOrderShipment->getOrderId() ?? $this->getOrderData($uberOrderShipment->getIncrementId());

                    // Get Order Data
                    $order = $this->orderRepository->get($orderId);
                    $storeId = $order->getStoreId();
                    $orderStatus = $order->getStatus();

                    // Get Customer ID
                    $organizationId = $this->helper->getCustomerId($storeId);

                    // Get Shipping Information from Uber
                    $shipmentData = $this->uber->getShipping($shippingId, $organizationId, $storeId);
                    $uberShippingStatus = $shipmentData['status'];
                    if (in_array($uberShippingStatus, array_keys(Helper::UBER_ORDER_STATUS))) {
                        /**
                         * This should ONLY be executed when WEBHOOKS are DISABLED. Otherwise, this information is
                         * populated through webhooks
                         */
                        if(!$this->helper->isWebhooksEnabled($storeId)){
                            $orderCommentHistory = __('Uber Information') . '<br>';
                            $orderNewStatus = Helper::UBER_ORDER_STATUS[$uberShippingStatus];
                            // The states uber_pickup / uber_dropoff require the flag courier_imminent must be true
                            if (($uberShippingStatus === 'dropoff' || $uberShippingStatus === 'pickup') && $shipmentData['courier_imminent'] === true) {
                                // Write Comment in History
                                if ($uberShippingStatus === 'pickup' && isset($shipmentData['courier'])) {
                                    $orderCommentHistory .= $this->helper->getDriverAndEstimatedInfo($shipmentData);
                                }
                            } else {
                                // Determine action by Status
                                switch ($uberShippingStatus) {
                                    case 'canceled':
                                        // Add Cancellation Details
                                        $cancellationDetail = $this->helper->getCancellationDescription($shipmentData['undeliverable_reason']);
                                        $orderCommentHistory .= __('<b>Canceled shipment:</b> %1', $cancellationDetail) . '<br>';
                                        break;
                                    case 'delivered':
                                        $orderCommentHistory .= $this->helper->getDeliveredDetails($shipmentData) . '<br>';
                                        break;
                                    default:
                                        break;
                                }
                            }

                            /**
                             * Save Changes
                             */
                            if (Helper::UBER_ORDER_STATUS[$uberShippingStatus] !== $orderStatus) {
                                // Add Change Status
                                $orderCommentHistory .= __('Delivery status has changed to <b>%1</b>', ucfirst($uberShippingStatus));

                                // Update Order
                                $order->setStatus($orderNewStatus);
                                $order->addCommentToStatusHistory($orderCommentHistory);
                                $this->orderRepository->save($order);

                                //Update Uber Shipment Data
                                $orderShipment = $this->orderShipmentRepository->getByIncrementId($order->getIncrementId());
                                $orderShipment->setStatus($uberShippingStatus);
                                switch ($uberShippingStatus) {
                                    case 'canceled':
                                    case 'returned':
                                        // Unset Shipping ID / Verification Data
                                        $orderShipment->setUberShippingId(null);
                                        $orderShipment->setVerification(null);
                                        break;
                                    case 'delivered':
                                        // Save Drop-off Verification Data
                                        $orderShipment->setVerification(json_encode($shipmentData['dropoff']['verification']));
                                        break;
                                    default:
                                        break;
                                }
                                $this->orderShipmentRepository->save($orderShipment);

                                /**
                                 * Send Email
                                 */
                                if($this->helper->getEnableEmailUpdate($order->getStoreId())){
                                    $this->emailSender->sendEmail($order, $uberShippingStatus, $shipmentData['tracking_url']);
                                }
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->helper->log($e->getMessage());
            return 0;
        }
        return 1;
    }

    public function gerOrderCollection(): UberOrderShipment
    {
        return $this->uberOrderShipment->addFieldToFilter('status', ['nin' => ['delivered']])
            ->addFieldToFilter('uber_shipping_id', ['notnull' => true])
            ->setOrder('entity_id','ASC');
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
        return $order->getId();
    }
}
