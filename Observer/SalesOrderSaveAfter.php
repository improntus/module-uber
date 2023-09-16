<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2023 Improntus (http://www.improntus.com/)
 */

namespace Improntus\Uber\Observer;

use Improntus\Uber\Api\OrderShipmentRepositoryInterface;
use Improntus\Uber\Helper\Data;
use Improntus\Uber\Model\Carrier\Uber;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;

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
     * @param Data $data
     * @param OrderShipmentRepositoryInterface $orderShipmentRepository
     */
    public function __construct(
        Data $data,
        OrderShipmentRepositoryInterface $orderShipmentRepository
    ) {
        $this->helper = $data;
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
            }
        }
    }
}
