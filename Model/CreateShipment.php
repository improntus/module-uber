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
use Improntus\Uber\Model\OrderShipmentRepository as UberOrderShipmentRepository;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Model\Convert\Order as Converter;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\Order\Shipment\TrackFactory;
use Magento\Sales\Model\Order\ShipmentRepository;
use Magento\Sales\Model\OrderRepository;
use Magento\Shipping\Model\ShipmentNotifier;

class CreateShipment
{
    protected const CARRIER_CODE = UberCarrier::CARRIER_CODE . '_' . UberCarrier::CARRIER_CODE;

    protected const DEFAULT_UBER_STATUS = 'uber_pending';

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
     * @var TimezoneInterface $timezone
     */
    protected TimezoneInterface $timezone;

    /**
     * @var Uber $uber
     */
    protected Uber $uber;

    /**
     * @var ShipmentRepository $shipmentRepository
     */
    protected ShipmentRepository $shipmentRepository;

    /**
     * @var UberOrderShipmentRepository $uberOrderShipmentRepository
     */
    protected UberOrderShipmentRepository $uberOrderShipmentRepository;

    /**
     * @var Registry $registry
     */
    protected Registry $registry;

    /**
     * @var ShipmentNotifier $shipmentNotifier
     */
    protected ShipmentNotifier $shipmentNotifier;

    /**
     * @var TransactionFactory $transactionFactory
     */
    protected TransactionFactory $transactionFactory;

    /**
     * @var Converter $converter
     */
    protected Converter $converter;

    /**
     * @var TrackFactory $trackFactory
     */
    protected TrackFactory $trackFactory;

    /**
     * @param Uber $uber
     * @param Data $helper
     * @param Registry $registry
     * @param Converter $converter
     * @param TrackFactory $trackFactory
     * @param TimezoneInterface $timezone
     * @param OrderRepository $orderRepository
     * @param ShipmentNotifier $shipmentNotifier
     * @param TransactionFactory $transactionFactory
     * @param ShipmentRepository $shipmentRepository
     * @param OrderShipmentRepository $orderShipmentRepository
     * @param array $warehouseRepositories
     * @param OrderShipmentRepository $uberOrderShipmentRepository
     */
    public function __construct(
        Uber                        $uber,
        Data                        $helper,
        Registry                    $registry,
        Converter                   $converter,
        TrackFactory                $trackFactory,
        TimezoneInterface           $timezone,
        OrderRepository             $orderRepository,
        ShipmentNotifier            $shipmentNotifier,
        TransactionFactory          $transactionFactory,
        ShipmentRepository          $shipmentRepository,
        OrderShipmentRepository     $orderShipmentRepository,
        array                       $warehouseRepositories,
        UberOrderShipmentRepository $uberOrderShipmentRepository
    ) {
        $this->uber = $uber;
        $this->helper = $helper;
        $this->registry = $registry;
        $this->timezone = $timezone;
        $this->converter = $converter;
        $this->trackFactory = $trackFactory;
        $this->orderRepository = $orderRepository;
        $this->shipmentNotifier = $shipmentNotifier;
        $this->transactionFactory = $transactionFactory;
        $this->shipmentRepository = $shipmentRepository;
        $this->warehouseRepositories = $warehouseRepositories;
        $this->orderShipmentRepository = $orderShipmentRepository;
        $this->uberOrderShipmentRepository = $uberOrderShipmentRepository;
    }

    /**
     * create
     *
     * @param int|null $orderId
     * @return void
     * @throws InputException
     * @throws NoSuchEntityException
     * @throws Exception
     */
    public function create(?int $orderId)
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

                // Validate Order Status
                $orderStatus = $order->getStatus();
                $uberStatusAllowed = ['pending', 'canceled'];
                $uberShipmentStatus = $uberOrderShipmentRepository->getStatus();
                $statusNotAllowed = [Order::STATE_CANCELED, Order::STATE_CLOSED, Data::UBER_DELIVERED_STATUS];
                if (in_array($orderStatus, $statusNotAllowed) || !in_array($uberShipmentStatus, $uberStatusAllowed)) {
                    throw new Exception(__('The order status does not allow generating a shipment'));
                }

                // Get Warehouse
                $warehouseId = $uberOrderShipmentRepository->getSourceWaypoint();
                if ($warehouseId === null) {
                    // Get Source Code MSI
                    $warehouseId = $uberOrderShipmentRepository->getSourceMsi();
                }
                $warehouse = $warehouseRepository->getWarehouse($warehouseId);

                /**
                 * I retrieve the Store Id from the Uber Stores table. I do this because if the External Store Id is
                 * different from the one used in the Uber Quote, Uber will attempt to generate a new store,
                 * which will trigger errors when estimating future orders.
                 */
                $warehouseStoreId = $warehouseRepository->getWarehouseStore($warehouse);

                // Generate DeliveryTime and Check Warehouse Work Schedule
                $deliveryTimeLocal = $this->helper->getDeliveryTime($order->getStoreId());
                if (!$warehouseRepository->checkWarehouseWorkSchedule($warehouse, $deliveryTimeLocal)) {
                    throw new Exception(__('The preparation point is outside working hours'));
                }

                /**
                 * Prepare Delivery Data
                 */
                $warehouseData = $warehouseRepository->getWarehousePickupData($warehouse);
                $dropoffData = $this->getDropoffData($order);
                $deliveryItems = $this->getDeliveryItems($order);

                /**
                 * Generate dates with minutes of differences required by Uber
                 */
                $promiseTime = $this->helper->getPromiseTime($order->getStoreId());
                $pickupReady = $this->getDateTimeUTC($deliveryTimeLocal);
                $pickupDeadLine = $this->getDateTimeUTC($pickupReady, $promiseTime); // Add $promiseTime
                $dropoffReady = $this->getDateTimeUTC($pickupDeadLine); // REQUIRED Same $pickupDeadLine
                $dropoffDeadLine = $this->getDateTimeUTC($dropoffReady, $promiseTime); // Add $promiseTime

                $deliveryAdditionalData = [
                    'pickup_ready_dt'      => $pickupReady->format('Y-m-d\TH:i:s.000\Z'),
                    'pickup_deadline_dt'   => $pickupDeadLine->format('Y-m-d\TH:i:s.000\Z'),
                    'dropoff_ready_dt'     => $dropoffReady->format('Y-m-d\TH:i:s.000\Z'),
                    'dropoff_deadline_dt'  => $dropoffDeadLine->format('Y-m-d\TH:i:s.000\Z'),
                    'manifest_total_value' => (int)$order->getGrandTotal(),
                    'manifest_reference'   => $order->getIncrementId(),
                    'external_id'          => $order->getIncrementId(),
                    'external_store_id'    => $warehouseStoreId,
                    'undeliverable_action' => 'return',
                ];

                // Add Verification Methods
                $deliveryAdditionalData['pickup_verification'] = $this->getVerificationMethod($order, 'pickup');
                $deliveryAdditionalData['return_verification'] = $this->getVerificationMethod($order, 'return');

                // Apply Cash on Delivery?
                if ($this->helper->isCashOnDeliveryEnabled($order->getStoreId()) &&
                    $order->getPayment()->getMethod() === 'cashondelivery') {
                    $deliveryAdditionalData['dropoff_payment']['requirements'] = [
                        [
                            'paying_party'    => 'recipient',
                            'amount'          => (int)$order->getGrandTotal(),
                            'payment_methods' => [
                                'cash' => [
                                    'enabled' => true,
                                ],
                            ],
                        ],
                    ];
                }

                // Sandbox Mode Webhooks
                if (!$this->helper->getIntegrationMode($order->getStoreId()) &&
                    $this->helper->isWebhooksEnabled($order->getStoreId())) {
                    $deliveryAdditionalData['test_specifications'] = [
                        'robo_courier_specification' => [
                            'mode' => 'auto',
                        ],
                    ];
                }

                // Prepara Request
                $shippingData = array_merge($warehouseData, $dropoffData, $deliveryItems, $deliveryAdditionalData);

                // Get Organization ID (Customer ID)
                $organizationId = $warehouseRepository->getWarehouseOrganization($warehouse);

                // Send Request to Uber
                try {
                    $uberResponse = $this->uber->createShipping($shippingData, $organizationId, $order->getStoreId());
                    if (!isset($uberResponse['id'])) {
                        return;
                    }
                } catch (Exception $e) {
                    throw new Exception($e->getMessage());
                }

                // Save Uber Shipping ID
                try {
                    $uberOrderShipmentRepository->setStatus('pending');
                    $uberOrderShipmentRepository->setUberShippingId($uberResponse['id']);// Save Data
                    $this->orderShipmentRepository->save($uberOrderShipmentRepository);
                } catch (CouldNotSaveException $e) {
                    throw new Exception($e->getMessage());
                }

                // Create Shipment / Track
                try {
                    $this->createMagentoShipment($orderId, $uberResponse);
                } catch (Exception $e) {
                    throw new Exception($e->getMessage());
                }

                // Add Comment to Order
                try {
                    $this->addCommentConfirmation($orderId, $uberResponse);
                } catch (Exception $e) {
                    throw new Exception($e->getMessage());
                }

                // Return Response
                return $uberResponse;
            }
        }
    }

    /**
     * createMagentoShipment
     *
     * @param int $orderId
     * @param array $uberData
     */
    protected function createMagentoShipment(int $orderId, array $uberData)
    {
        $order = $this->orderRepository->get($orderId);
        $shipment = $this->prepareShipment($order);

        // Add Tracking to Shipment
        try {
            $this->addTrackingToShipment(
                $order,
                $this->formatTrackingNumber($uberData['uuid']),
                $uberData['tracking_url'],
                $shipment
            );
            if ($shipment !== null) {
                $this->shipmentNotifier->notify($shipment);
            }
        } catch (\Exception $e) {
            $this->helper->log($e->getMessage());
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @param $order
     * @return false|Shipment
     * @throws LocalizedException
     */
    private function prepareShipment($order)
    {
        if (!$order->canShip()) {
            return null;
        }

        $shipment = $this->converter->toShipment($order);
        foreach ($order->getAllItems() as $orderItem) {
            if (!$orderItem->getQtyToShip() || $orderItem->getIsVirtual()) {
                continue;
            }
            $qtyShipped = $orderItem->getQtyToShip();
            $shipmentItem = $this->converter->itemToShipmentItem($orderItem)->setQty($qtyShipped);
            $shipment->addItem($shipmentItem);
        }

        /**
         * Multi Source Inventory Logic
         */
        if ($this->helper->hasMsiInstalled()) {
            // Get Source Code from UberOrderShipmentRepository
            if ($this->helper->getSourceOrigin() === 'msi') {
                $uberOrderShipmentRepository = $this->uberOrderShipmentRepository->getByOrderId($order->getId());
                if ($uberOrderShipmentRepository->getSourceMsi() !== null) {
                    $sourceCode = $uberOrderShipmentRepository->getSourceMsi();
                }
            } else {
                // Get Source Code from Config
                $sourceCode = $this->helper->getSourceDefault($order->getStoreId());
            }
            $shipment->getExtensionAttributes()->setSourceCode($sourceCode);
        }
        $shipment->register();
        return $shipment;
    }

    /**
     * addTrackingToShipment
     *
     * @param $order
     * @param $trackNumber
     * @param $trackURL
     * @param $shipment
     */
    protected function addTrackingToShipment($order, $trackNumber, $trackURL, $shipment = null)
    {
        $carrierTitle = $this->helper->getShippingTitle($order->getStoreId()) ?: 'Uber Direct';
        if ($shipment === null) {
            $orderShipment = $this->trackFactory->create()->getCollection()
                ->addFieldToFilter('order_id', ['eq' => $order->getEntityId()])
                ->getFirstItem();
            $orderShipment->setTrackNumber($trackNumber);
            $orderShipment->save();
        } else {
            $shipment->addTrack(
                $this->trackFactory->create()
                    ->setNumber($trackNumber)
                    ->setCarrierCode(self::CARRIER_CODE)
                    ->setTitle($carrierTitle)
            );
            $shipment->save();
        }
    }

    /**
     * getDateTimeUTC
     *
     * Return DateTime UTC
     *
     * @param $dateTime
     * @param $interval
     */
    private function getDateTimeUTC($dateTime, $interval = null)
    {
        $dateTimeClone = clone $dateTime;
        $dateTimeUTC = $dateTimeClone->setTimezone(new \DateTimeZone('UTC'));
        if ($interval !== null) {
            $dateTimeUTC->add(new \DateInterval("PT{$interval}M"));
        }
        return $dateTimeUTC;
    }

    /**
     * getDeliveryItems
     *
     * Return all items
     *
     * @param $order
     * @return array
     * @throws Exception
     */
    private function getDeliveryItems($order): array
    {
        $uberItems = [];

        // Get Fields Product Dimension
        $productWidthAttribute = $this->helper->getProductWidthAttribute($order->getStoreId());
        $productHeightAttribute = $this->helper->getProductHeightAttribute($order->getStoreId());
        $productDepthAttribute = $this->helper->getProductDepthAttribute($order->getStoreId());
        $storeWeightUnit = $this->helper->getStoreWeightUnit($order->getStoreId());

        // Prepare Items
        foreach ($order->getAllItems() as $_item) {
            // Exclude Configurable Items
            if ($_item->getProductType() == 'configurable') {
                continue;
            }
            $_product = $_item->getProduct();
            if ($_item->getParentItem()) {
                $_item = $_item->getParentItem();
            }

            // Item can ship with Uber?
            if ($_product->getDisableUberShipping()) {
                throw new Exception(__('The cart contains items that cannot be shipped with Uber'));
            }

            // Convert to Grams
            if ($storeWeightUnit === 'lbs') {
                // Lbs to Grams
                $itemWeightGrams = $_product->getWeight() * 453.592;
            } else {
                // KG to Grams
                $itemWeightGrams = $_product->getWeight() / 1000;
            }

            // Get Item Props
            $itemQty = (int)$_item->getQty() ?: 1;
            $itemName = $_item->getName();
            $itemPrice = (int)($_item->getPrice() * 100); // Price in Cents
            $itemWeight = (int)($itemQty * $itemWeightGrams) ?: 1; // Wight in Grams
            $itemDepth = (int)$_product->getData($productDepthAttribute) ?: 1;
            $itemWidth = (int)$_product->getData($productWidthAttribute) ?: 1;
            $itemHeight = (int)$_product->getData($productHeightAttribute) ?: 1;

            // Valid Item
            $uberItems[] = [
                'name'       => $itemName,
                'quantity'   => $itemQty,
                'price'      => $itemPrice,
                'weight'     => $itemWeight,
                'dimensions' => [
                    'length' => $itemWidth,
                    'height' => $itemHeight,
                    'depth'  => $itemDepth,
                ],
            ];
        }

        return ['manifest_items' => $uberItems];
    }

    /**
     * getDropoffData
     *
     * Return array with DropOff Data
     *
     * @param $order
     * @return array
     */
    private function getDropoffData($order): array
    {
        // Basic Data
        $dropoffData = [
            'dropoff_name'          => $order->getShippingAddress()->getName(),
            'dropoff_phone_number'  => $order->getShippingAddress()->getTelephone(),
            'dropoff_business_name' => $order->getShippingAddress()->getName(),
            'dropoff_verification'  => $this->getVerificationMethod($order),
        ];

        // Has Customer Notes?
        if ($order->getCustomerNote() !== null) {
            $dropoffData['dropoff_notes'] = $order->getCustomerNote();
        }

        // Add DropOff Address
        $dropoffData['dropoff_address'] = json_encode([
            'street_address' => $order->getShippingAddress()->getStreet(),
            'city'           => $order->getShippingAddress()->getCity(),
            'state'          => $order->getShippingAddress()->getRegion(),
            'zip_code'       => $order->getShippingAddress()->getPostcode(),
            'country'        => $order->getShippingAddress()->getCountryId(),
        ], JSON_UNESCAPED_SLASHES);

        // Return DropOff data
        return $dropoffData;
    }

    /**
     * addCommentConfirmation
     *
     * @param int $orderId
     * @param array $confirmationData
     * @return void
     * @throws InputException
     * @throws NoSuchEntityException
     */
    private function addCommentConfirmation(int $orderId, array $confirmationData): void
    {
        $order = $this->orderRepository->get($orderId);
        if ($order->getEntityId() === null) {
            return;
        }
        try {
            $orderComment = __('<b>Uber Shipping UUID</b>: %1', $this->formatTrackingNumber($confirmationData['uuid'], true)) . '<br>';
            $orderComment .= __('<b>Tracking URL</b>: <a href="%1">%1</a>', $confirmationData['tracking_url']) . '<br>';
            $order->addCommentToStatusHistory(
                $orderComment,
                self::DEFAULT_UBER_STATUS
            );
            $this->orderRepository->save($order);
        } catch (Exception $e) {
            $this->helper->log(__("Uber Shipping Cancel ERROR: %1", $e->getMessage()));
        }
    }

    /**
     * getVerificationMethod
     *
     * Return Verification Method
     *
     * @param $order
     * @param string $area
     * @return array[]
     */
    private function getVerificationMethod($order, string $area = "dropoff"): array
    {
        $verificationParams = [];
        $storeId = $order->getStoreId();
        $incrementId = $order->getIncrementId();

        // Get Verification Method
        $excludeIdentificationMethod = ($area == 'return');
        $area = ($area == 'return') ? 'pickup' : $area;
        $verificationMethod = $this->helper->getVerificationType($storeId, $area);

        // Exclude Identificacion Method
        if ($excludeIdentificationMethod && $verificationMethod == 'identification') {
            $verificationMethod = 'signature';
        }

        // Build Requirements
        switch ($verificationMethod) {
            case "identification":
                $verificationParams['identification'] = [
                    'min_age' => $this->helper->getIdentificationAge($storeId, $area) ?: 21,
                ];
                break;
            case "picture":
                $verificationParams['picture'] = [
                    'enabled' => true,
                ];
                break;
            case "pincode":
                $verificationParams['pincode'] = [
                    'enabled' => true,
                ];
                break;
            case "barcodes":
                $verificationParams['barcodes'] = [
                    'type'  => 'CODE39',
                    'value' => $incrementId,
                ];
                break;
            default:
                $verificationParams['signature_requirement'] = [
                    'enabled'                     => true,
                    'collect_signer_name'         => true, // Flag for if the signer's name is required at this waypoint
                    'collect_signer_relationship' => true, // Flag for if the signer's relationship to the intended recipient is required at this waypoint.
                ];
                break;
        }

        return $verificationParams;
    }

    /**
     * formatTrackingNumber
     *
     * Return TrackNumber formatted
     *
     * @param string $trackingNumber
     * @param bool $getLastDigits
     * @return string
     */
    private function formatTrackingNumber(string $trackingNumber, bool $getLastDigits = false): string
    {
        $pattern = '/^([\da-f]{8})([\da-f]{4})([\da-f]{4})([\da-f]{4})([\da-f]{12})$/i';
        $replacement = '$1-$2-$3-$4-$5';
        if ($getLastDigits) {
            $pattern = '/^.*(.{5})$/';
            $replacement = '$1';
        }
        return preg_replace($pattern, $replacement, $trackingNumber);
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
