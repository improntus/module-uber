<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2024 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Model;

use Exception;
use Improntus\Uber\Api\WarehouseRepositoryInterface;
use Improntus\Uber\Helper\Data;
use Improntus\Uber\Model\Carrier\Uber as UberCarrier;
use Magento\Framework\Event\ManagerInterface;
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
     * @var WarehouseRepositoryInterface[]
     */
    protected array $warehouseRepositories;

    /**
     * @var Uber $uber
     */
    protected Uber $uber;

    /**
     * @var OrderShipmentFactory $orderShipmentFactory
     */
    protected OrderShipmentFactory $orderShipmentFactory;

    /**
     * @var ManagerInterface $eventManager
     */
    protected ManagerInterface $eventManager;

    /**
     * @param Uber $uber
     * @param Data $helper
     * @param ManagerInterface $eventManager
     * @param OrderRepository $orderRepository
     * @param array $warehouseRepositories
     * @param OrderShipmentFactory $orderShipmentFactory
     * @param OrderShipmentRepository $orderShipmentRepository
     */
    public function __construct(
        Uber                    $uber,
        Data                    $helper,
        ManagerInterface        $eventManager,
        OrderRepository         $orderRepository,
        array                   $warehouseRepositories,
        OrderShipmentFactory    $orderShipmentFactory,
        OrderShipmentRepository $orderShipmentRepository
    ) {
        $this->uber = $uber;
        $this->helper = $helper;
        $this->eventManager = $eventManager;
        $this->orderRepository = $orderRepository;
        $this->warehouseRepositories = $warehouseRepositories;
        $this->orderShipmentFactory = $orderShipmentFactory;
        $this->orderShipmentRepository = $orderShipmentRepository;
    }

    /**
     * cancel
     *
     * @param int|null $orderId
     * @return void
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function cancel(?int $orderId)
    {
        if ($this->helper->isModuleEnabled() && $orderId !== null) {
            // Get Order
            $order = $this->orderRepository->get($orderId);
            if ($order->getId() === null) {
                throw new Exception(__('The requested Order does not exist'));
            }
            $warehouseRepository = $this->getWarehouseRepository();

            // Validate Shipping Method
            if ($order->getShippingMethod() == self::CARRIER_CODE) {
                // Get Shipping Data
                $uberOrderShipmentRepository = $this->orderShipmentRepository->getByOrderId($orderId);

                // Get Warehouse
                $warehouseId = $uberOrderShipmentRepository->getSourceWaypoint();
                if ($warehouseId === null) {
                    // Get Source Code MSI
                    $warehouseId = $uberOrderShipmentRepository->getSourceMsi();
                }
                $warehouse = $warehouseRepository->getWarehouse($warehouseId);

                // Get Organization ID (Customer ID)
                $organizationId = $warehouseRepository->getWarehouseOrganization($warehouse);

                // Get Shipping Id
                $uberShippingId = $uberOrderShipmentRepository->getUberShippingId();

                // Send Request to Uber
                try {
                    $uberResponse = $this->uber->cancelShipping($uberShippingId, $organizationId, $order->getStoreId());
                    if (!isset($uberResponse['id'])) {
                        return;
                    }
                } catch (Exception $e) {
                    throw new Exception($e->getMessage());
                }

                // Update Uber Shipment
                try {
                    $uberOrderShipmentRepository->setStatus('canceled');
                    $uberOrderShipmentRepository->setUberShippingId(null);
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

                // Dispatch Event
                $this->eventManager->dispatch('uber_shipment_cancel', [
                    'order' => $order,
                    'shipment' => $uberOrderShipmentRepository
                ]);

                // Return Response
                return $uberResponse;
            }
        }
    }

    /**
     * addCommentConfirmationCancel
     *
     * @param int $orderId
     * @param array $cancellationData
     * @return void
     * @throws InputException
     * @throws NoSuchEntityException
     */
    private function addCommentConfirmationCancel(int $orderId, array $cancellationData): void
    {
        $order = $this->orderRepository->get($orderId);
        if ($order->getEntityId() === null) {
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

    /**
     * @return WarehouseRepositoryInterface
     */
    protected function getWarehouseRepository(): WarehouseRepositoryInterface
    {
        $warehouseConfig = $this->helper->getSourceOrigin();
        return $this->warehouseRepositories[$warehouseConfig];
    }
}
