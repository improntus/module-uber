<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2024 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Observer;

use Improntus\Uber\Api\OrderShipmentRepositoryInterface;
use Improntus\Uber\Helper\Data;
use Improntus\Uber\Model\Carrier\Uber;
use Improntus\Uber\Model\OrderShipmentFactory;
use Magento\Checkout\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Api\CartRepositoryInterface;

class SalesOrderPlaceBefore implements ObserverInterface
{
    protected const CARRIER_CODE = Uber::CARRIER_CODE . '_' . Uber::CARRIER_CODE;

    /**
     * @var CartRepositoryInterface $quoteRepository
     */
    protected CartRepositoryInterface $quoteRepository;

    /**
     * @var Session $checkoutSession
     */
    protected Session $checkoutSession;

    /**
     * @var OrderShipmentRepositoryInterface $orderShipmentRepositoryInterface
     */
    protected OrderShipmentRepositoryInterface $orderShipmentRepositoryInterface;

    /**
     * @var OrderShipmentFactory $orderShipmentFactory
     */
    protected OrderShipmentFactory $orderShipmentFactory;

    /**
     * @var Data $helper
     */
    protected Data $helper;

    public function __construct(
        Session $checkoutSession,
        CartRepositoryInterface $quoteRepository,
        OrderShipmentFactory $orderShipmentFactory,
        OrderShipmentRepositoryInterface $orderShipmentRepositoryInterface,
        Data $helper
    ) {
        $this->helper = $helper;
        $this->quoteRepository = $quoteRepository;
        $this->checkoutSession = $checkoutSession;
        $this->orderShipmentFactory = $orderShipmentFactory;
        $this->orderShipmentRepositoryInterface = $orderShipmentRepositoryInterface;
    }

    /**
     * execute
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        if ($order->getShippingMethod() == self::CARRIER_CODE && $this->helper->isModuleEnabled()) {
            try {
                // Get Data from Checkout Session
                $orderShipping = $this->orderShipmentFactory->create();
                $warehouseId = $this->checkoutSession->getUberWarehouseId() ?: null;
                $warehouseName = $this->checkoutSession->getUberWarehouseName() ?: 'n/a';

                // Get Current Shipping Description
                $orderShippingDescription = $order->getShippingDescription() ?: '';

                // Warehouse is Waypoints or MSI
                if ($this->helper->getSourceOrigin() == 'msi') {
                    $orderShipping->setSourceMsi($warehouseId);
                } else {
                    $orderShipping->setSourceWaypoint($warehouseId);
                }
                $orderShipping->setStoreId($order->getStoreId());
                $orderShipping->setIncrementId($order->getIncrementId());
                $orderShipping->setStatus('pending');
                $this->orderShipmentRepositoryInterface->save($orderShipping);

                // Update Order
                $orderShippingDescription.= __(" | Warehouse Assigned: %1", $warehouseName);
                $order->setShippingDescription($orderShippingDescription);
                $order->addStatusHistoryComment($this->getOrderHistoryInformation($warehouseName));
                $order->save();
            } catch (\Exception $e) {
                $this->helper->log(__("UBER ERROR LOG SalesOrderSaveAfter: %1", $e->getMessage()));
            }
        }
        return $this;
    }

    /**
     * @param $warehouseName
     * @return string
     */
    private function getOrderHistoryInformation($warehouseName): string
    {
        $orderHistoryInformation = __('<b>Uber Information:</b><br>');
        $orderHistoryInformation.= __('The order has been assigned to the Warehouse: <b>%1</b>', $warehouseName);
        return $orderHistoryInformation;
    }
}
