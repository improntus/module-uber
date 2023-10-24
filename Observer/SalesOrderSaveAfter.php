<?php
/**
 *  @author Improntus Dev Team
 *  @copyright Copyright (c) 2023 Improntus (http://www.improntus.com)
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
     * @var OrderShipmentRepositoryInterface $orderShipmentRepository
     */
    protected OrderShipmentRepositoryInterface $orderShipmentRepository;

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
     * @param OrderShipmentRepositoryInterface $orderShipmentRepository
     */
    public function __construct(
        Data $data,
        CreateShipment $createShipment,
        OrderRepository $orderRepository,
        OrderShipmentRepositoryInterface $orderShipmentRepository
    ) {
        $this->helper = $data;
        $this->createShipment = $createShipment;
        $this->orderRepository = $orderRepository;
        $this->orderShipmentRepository = $orderShipmentRepository;
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
                $orderShipment = $this->orderShipmentRepository->getByIncrementId($order->getIncrementId());
                $orderShipment->setOrderId($order->getId());
                $this->orderShipmentRepository->save($orderShipment);
            } catch (NoSuchEntityException $e) {
                // TODO Logger message
                $this->helper->log(__("UBER ERROR LOG SalesOrderSaveAfter: %1", $e->getMessage()));
            }

            /**
             * Is Enabled Automatic Shipping?
             */
            if ($this->helper->isAutomaticShipmentGenerationEnabled($order->getStoreId())) {
                $statusAllowed = $this->helper->getAutomaticShipmentGenerationStatus($order->getStoreId());
                if (count($statusAllowed) > 0 && in_array($order->getStatus(), $statusAllowed)) {
                    try {
                        $this->createShipment->create($order->getId());
                    } catch (\Exception $e) {
                        $order->addCommentToStatusHistory(
                            __('<b>Uber Automatic Shipping Create ERROR</b>: %1', $e->getMessage()),
                            'uber_pending'
                        );
                        // Save Order
                        $order->save();
                    }
                }
            }
        }
    }
}
