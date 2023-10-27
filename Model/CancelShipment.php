<?php
/**
 *  @author Improntus Dev Team
 *  @copyright Copyright (c) 2023 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Model;

use Exception;
use Improntus\Uber\Helper\Data;
use Improntus\Uber\Model\Carrier\Uber as UberCarrier;
use Improntus\Uber\Model\Warehouse\WarehouseRepository;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\OrderRepository;

class CancelShipment
{
    protected const CARRIER_CODE = UberCarrier::CARRIER_CODE . '_' . UberCarrier::CARRIER_CODE;

    /**
     * @var OrderRepository $orderRepository
     */
    protected OrderRepository $orderRepository;

    /**
     * @var OrderShipmentRepository $orderShipmentRepository
     */
    protected OrderShipmentRepository $orderShipmentRepository;

    /**
     * @var Data $helper
     */
    protected Data $helper;

    /**
     * @var WarehouseRepository $warehouseRepository
     */
    protected WarehouseRepository $warehouseRepository;

    /**
     * @var Uber $uber
     */
    protected Uber $uber;

    /**
     * @var OrderShipmentFactory $orderShipmentFactory
     */
    protected OrderShipmentFactory $orderShipmentFactory;

    /**
     * @param Uber $uber
     * @param Data $helper
     * @param OrderRepository $orderRepository
     * @param WarehouseRepository $warehouseRepository
     * @param OrderShipmentFactory $orderShipmentFactory
     * @param OrderShipmentRepository $orderShipmentRepository
     */
    public function __construct(
        Uber $uber,
        Data $helper,
        OrderRepository $orderRepository,
        WarehouseRepository $warehouseRepository,
        OrderShipmentFactory $orderShipmentFactory,
        OrderShipmentRepository $orderShipmentRepository
    ) {
        $this->uber = $uber;
        $this->helper = $helper;
        $this->orderRepository = $orderRepository;
        $this->warehouseRepository = $warehouseRepository;
        $this->orderShipmentFactory = $orderShipmentFactory;
        $this->orderShipmentRepository = $orderShipmentRepository;
    }

    /**
     * cancel
     * @param $orderId
     * @return void
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function cancel($orderId)
    {
        if ($this->helper->isModuleEnabled() && !is_null($orderId)) {
            // Get Order
            $order = $this->orderRepository->get($orderId);
            if (is_null($order->getId())) {
                throw new Exception(__('The requested Order does not exist'));
            }

            // Validate Shipping Method
            if ($order->getShippingMethod() == self::CARRIER_CODE) {
                // Get Shipping Data
                $uberOrderShipmentRepository = $this->orderShipmentRepository->getByOrderId($orderId);

                // Get Warehouse
                $warehouseId = $uberOrderShipmentRepository->getSourceWaypoint() ?? $uberOrderShipmentRepository->getSourceMsi();
                $warehouse = $this->warehouseRepository->getWarehouse($warehouseId);
                $warehouseData = $this->warehouseRepository->getWarehousePickupData($warehouse);

                // Get Organization ID (Customer ID)
                $organizationId = $this->warehouseRepository->getWarehouseOrganization($warehouse);

                // Get Shipping Id
                $uberShippingId = $uberOrderShipmentRepository->getUberShippingId();

                // Send Request to Uber
                $uberResponse = $this->uber->cancelShipping($uberShippingId, $organizationId, $order->getStoreId());
                if (!isset($uberResponse['id'])) {
                    return false;
                }

                // Update Uber Shipment
                try {
                    $uberOrderShipmentRepository->setStatus('canceled');
                    $uberOrderShipmentRepository->setUberShippingId(null);// Save Data
                    $this->orderShipmentRepository->save($uberOrderShipmentRepository);
                } catch (CouldNotSaveException $e) {
                    throw new Exception($e->getMessage());
                }

                // Add Comment to Order
                try {
                    $this->addCommentConfirmationCancel($orderId, $uberResponse);
                } catch (Exception $e) {
                    throw new Exception($e->getMessage());
                }

                return $uberResponse;
            }
        }
    }

    /**
     * addCommentConfirmationCancel
     * @param int $orderId
     * @param array $cancellationData
     * @return void
     * @throws InputException
     * @throws NoSuchEntityException
     */
    private function addCommentConfirmationCancel(int $orderId, array $cancellationData): void
    {
        $order = $this->orderRepository->get($orderId);
        if (is_null($order->getEntityId())) {
            return;
        }
        try {
            $order->addCommentToStatusHistory(
                __('<b>Uber Cancellation ID</b>: %1', $cancellationData['id']),
                'uber_canceled'
            );
            $this->orderRepository->save($order);
        } catch (Exception $e) {
            $this->helper->log(__("Uber Shipping Cancel ERROR: %1", $e->getMessage()));
        }
    }
}
