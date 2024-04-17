<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2024 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Observer;

use Improntus\Uber\Api\OrderShipmentRepositoryInterface;
use Improntus\Uber\Helper\Data;
use Improntus\Uber\Model\Carrier\Uber;
use Improntus\Uber\Model\CreateShipment;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;

class SalesOrderSaveAfter implements ObserverInterface
{
    protected const CARRIER_CODE = Uber::CARRIER_CODE . '_' . Uber::CARRIER_CODE;

    /**
     * @var Data
     */
    protected Data $helper;

    /**
     * @var OrderShipmentRepositoryInterface $uberOrderShipmentRepository
     */
    protected OrderShipmentRepositoryInterface $uberOrderShipmentRepository;

    /**
     * @var CreateShipment $createShipment
     */
    protected CreateShipment $createShipment;

    /**
     * @var OrderRepository $orderRepository
     */
    protected OrderRepository $orderRepository;

    /**
     * @param Data $data
     * @param CreateShipment $createShipment
     * @param OrderRepository $orderRepository
     * @param OrderShipmentRepositoryInterface $uberOrderShipmentRepository
     */
    public function __construct(
        Data $data,
        CreateShipment $createShipment,
        OrderRepository $orderRepository,
        OrderShipmentRepositoryInterface $uberOrderShipmentRepository
    ) {
        $this->helper = $data;
        $this->createShipment = $createShipment;
        $this->orderRepository = $orderRepository;
        $this->uberOrderShipmentRepository = $uberOrderShipmentRepository;
    }

    /**
     * execute
     * @param Observer $observer
     * @return $this|void
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        if ($order->getShippingMethod() == self::CARRIER_CODE && $this->helper->isModuleEnabled()) {
            try {
                // Get Order by IncrementalId and Update OrderId
                $uberOrderShipment = $this->uberOrderShipmentRepository->getByIncrementId($order->getIncrementId());
                if ($uberOrderShipment->getOrderId() === null) {
                    $uberOrderShipment->setOrderId($order->getId());
                    $this->uberOrderShipmentRepository->save($uberOrderShipment);
                }

                /**
                 * Is Enabled Automatic Shipping?
                 */
                if ($this->helper->isAutomaticShipmentGenerationEnabled($order->getStoreId())) {
                    $statusAllowed = $this->helper->getAutomaticShipmentGenerationStatus($order->getStoreId());
                    if (count($statusAllowed) > 0 && in_array($order->getStatus(), $statusAllowed) &&
                        $uberOrderShipment->getUberShippingId() === null
                    ) {
                        try {
                            $this->createShipment->create($order->getId());
                        } catch (\Exception $e) {
                            $this->helper->log("UBER ERROR SalesOrderSaveAfter: " . $e->getMessage());
                            $order->addCommentToStatusHistory(
                                __('<b>Uber Automatic Shipping Create ERROR</b>: %1', $e->getMessage()),
                                'uber_pending'
                            );
                            // Save Order
                            $order->save();
                        }
                    }
                }
            } catch (NoSuchEntityException $e) {
                $this->helper->log("UBER ERROR SalesOrderSaveAfter: " . $e->getMessage());
            }
        }
    }
}
