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
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\Order\Shipment\TrackFactory;
use Magento\Sales\Model\OrderRepository;
use Magento\Shipping\Controller\Adminhtml\Order\ShipmentLoader;
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
     * @var WarehouseRepository $warehouseRepository
     */
    protected WarehouseRepository $warehouseRepository;

    /**
     * @var TimezoneInterface $timezone
     */
    protected TimezoneInterface $timezone;

    /**
     * @var Uber $uber
     */
    protected Uber $uber;

    /**
     * @var OrderShipmentFactory $orderShipmentFactory
     */
    protected OrderShipmentFactory $orderShipmentFactory;

    /**
     * @var ShipmentLoader $shipmentLoader
     */
    protected ShipmentLoader $shipmentLoader;

    /**
     * @var TrackFactory $trackFactory
     */
    protected TrackFactory $trackFactory;

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
     * @param Uber $uber
     * @param Data $helper
     * @param Registry $registry
     * @param TrackFactory $trackFactory
     * @param TimezoneInterface $timezone
     * @param ShipmentLoader $shipmentLoader
     * @param OrderRepository $orderRepository
     * @param ShipmentNotifier $shipmentNotifier
     * @param TransactionFactory $transactionFactory
     * @param WarehouseRepository $warehouseRepository
     * @param OrderShipmentFactory $orderShipmentFactory
     * @param OrderShipmentRepository $orderShipmentRepository
     */
    public function __construct(
        Uber $uber,
        Data $helper,
        Registry $registry,
        TrackFactory $trackFactory,
        TimezoneInterface $timezone,
        ShipmentLoader $shipmentLoader,
        OrderRepository $orderRepository,
        ShipmentNotifier $shipmentNotifier,
        TransactionFactory $transactionFactory,
        WarehouseRepository $warehouseRepository,
        OrderShipmentFactory $orderShipmentFactory,
        OrderShipmentRepository $orderShipmentRepository
    ) {
        $this->uber = $uber;
        $this->helper = $helper;
        $this->registry = $registry;
        $this->timezone = $timezone;
        $this->trackFactory = $trackFactory;
        $this->shipmentLoader = $shipmentLoader;
        $this->orderRepository = $orderRepository;
        $this->shipmentNotifier = $shipmentNotifier;
        $this->transactionFactory = $transactionFactory;
        $this->warehouseRepository = $warehouseRepository;
        $this->orderShipmentFactory = $orderShipmentFactory;
        $this->orderShipmentRepository = $orderShipmentRepository;
    }

    /**
     * create
     * @param $orderId
     * @return void
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function create($orderId)
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

                // Validate Order Status
                $orderStatus = $order->getStatus();
                $uberStatusAllowed = ['pending', 'canceled'];
                $uberShipmentStatus = $uberOrderShipmentRepository->getStatus();
                $statusNotAllowed = [Order::STATE_CANCELED, Order::STATE_CLOSED, Data::UBER_DELIVERED_STATUS];
                if (in_array($orderStatus, $statusNotAllowed) or !in_array($uberShipmentStatus, $uberStatusAllowed)) {
                    throw new Exception(__('The order status does not allow generating a shipment'));
                }

                // Get Warehouse
                $warehouseId = $uberOrderShipmentRepository->getSourceWaypoint() ?? $uberOrderShipmentRepository->getSourceMsi();
                $warehouse = $this->warehouseRepository->getWarehouse($warehouseId);

                // Generate DeliveryTime and Check Warehouse Work Schedule
                $deliveryTimeLocal = $this->getDeliveryTime($order->getStoreId());
                if (!$this->warehouseRepository->checkWarehouseWorkSchedule($warehouse, $deliveryTimeLocal)) {
                    throw new Exception(__('The preparation point is outside working hours'));
                }

                /**
                 * Prepare Delivery Data
                 */
                $warehouseData = $this->warehouseRepository->getWarehousePickupData($warehouse);
                $dropoffData = $this->getDropoffData($order);
                $deliveryItems = $this->getDeliveryItems($order);

                /**
                 * Generate dates with minutes of differences required by Uber
                 */
                $pickupReady = $this->getDateTimeUTC($deliveryTimeLocal);
                $pickupDeadLine = $this->getDateTimeUTC($pickupReady, 20); // Add 20 Minutes
                $dropoffReady = $this->getDateTimeUTC($pickupDeadLine); // REQUIRED Same $pickupDeadLine
                $dropoffDeadLine = $this->getDateTimeUTC($dropoffReady, 40); // Add 40 Minutes

                $deliveryAdditionalData = [
                    'pickup_ready_dt' => $pickupReady->format('Y-m-d\TH:i:s.000\Z'),
                    'pickup_deadline_dt' => $pickupDeadLine->format('Y-m-d\TH:i:s.000\Z'),
                    'dropoff_ready_dt' => $dropoffReady->format('Y-m-d\TH:i:s.000\Z'),
                    'dropoff_deadline_dt' => $dropoffDeadLine->format('Y-m-d\TH:i:s.000\Z'),
                    'manifest_total_value' => (int)$order->getGrandTotal(),
                    'manifest_reference' => $order->getIncrementId(),
                    'external_id' => $order->getIncrementId(),
                    'external_store_id' => $this->helper->getStoreName($order->getStoreId()),
                    'undeliverable_action' => 'return'
                ];

                // Add Verification Methods
                $deliveryAdditionalData['pickup_verification'] = $this->getVerificationMethod($order);
                $deliveryAdditionalData['return_verification'] = $this->getVerificationMethod($order);

                // Apply Cash on Delivery?
                if ($this->helper->isCashOnDeliveryEnabled($order->getStoreId()) &&
                    $order->getPayment()->getMethod() === 'cashondelivery') {
                    $deliveryAdditionalData['dropoff_payment']['requirements'] = [
                          [
                            'paying_party' => 'recipient',
                            'amount' => (int)$order->getGrandTotal(),
                            'payment_methods' => [
                                'cash' => [
                                    'enabled' => true
                                ]
                            ]
                        ]
                    ];
                }

                // Prepara Request
                $shippingData = array_merge($warehouseData, $dropoffData, $deliveryItems, $deliveryAdditionalData);

                // Get Organization ID (Customer ID)
                $organizationId = $this->warehouseRepository->getWarehouseOrganization($warehouse);

                // Send Request to Uber
                $uberResponse = $this->uber->createShipping($shippingData, $organizationId, $order->getStoreId());
                if (!isset($uberResponse['id'])) {
                    return false;
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
                    $this->createShipment($order, $uberResponse);
                } catch (MailException|LocalizedException $e) {
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
     * getDateTimeUTC
     *
     * Return DateTime UTC
     * @param $dateTime
     * @param $interval
     */
    private function getDateTimeUTC($dateTime, $interval = null)
    {
        $dateTimeClone = clone $dateTime;
        $dateTimeUTC = $dateTimeClone->setTimezone(new \DateTimeZone('UTC'));
        if (!is_null($interval)) {
            $dateTimeUTC->add(new \DateInterval("PT{$interval}M"));
        }
        return $dateTimeUTC;
    }

    /**
     * getDeliveryItems
     *
     * Return all items
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
            if (!$_product->getCanShipUber()) {
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
            $itemPrice = (int)$_item->getPrice();
            $itemWeight = (int)($itemQty * $itemWeightGrams) ?: 1;
            $itemDepth = (int)$_product->getData($productDepthAttribute) ?? 1;
            $itemWidth = (int)$_product->getData($productWidthAttribute) ?? 1;
            $itemHeight = (int)$_product->getData($productHeightAttribute) ?? 1;

            // Valid Item
            $uberItems[] = [
                'name' => $itemName,
                'quantity' => $itemQty,
                'price' => $itemPrice,
                'weight' => $itemWeight,
                'must_be_upright' => true,
                'dimensions' => [
                    'length' => $itemWidth,
                    'height' => $itemHeight,
                    'depth'  => $itemDepth
                ]
            ];
        }

        return ['manifest_items' => $uberItems];
    }

    /**
     * getDropoffData
     *
     * Return array with DropOff Data
     * @param $order
     * @return array
     */
    private function getDropoffData($order): array
    {
        // Basic Data
        $dropoffData = [
            'dropoff_name' => $order->getShippingAddress()->getName(),
            'dropoff_phone_number' => $order->getShippingAddress()->getTelephone(),
            'dropoff_business_name' => $order->getShippingAddress()->getName(),
            'dropoff_verification' => $this->getVerificationMethod($order)
        ];

        // Has Customer Notes?
        if (!is_null($order->getCustomerNote())) {
            $dropoffData['dropoff_notes'] = $order->getCustomerNote();
        }

        // Add DropOff Address
        $dropoffData['dropoff_address'] = json_encode([
            'street_address' => $order->getShippingAddress()->getStreet(),
            'city' => $order->getShippingAddress()->getCity(),
            'state' => $order->getShippingAddress()->getRegion(),
            'zip_code' => $order->getShippingAddress()->getPostcode(),
            'country' => $order->getShippingAddress()->getCountryId()
        ], JSON_UNESCAPED_SLASHES);

        // Return DropOff data
        return $dropoffData;
    }

    /**
     * addCommentConfirmation
     * @param int $orderId
     * @param array $confirmationData
     * @return void
     * @throws InputException
     * @throws NoSuchEntityException
     */
    private function addCommentConfirmation(int $orderId, array $confirmationData): void
    {
        $order = $this->orderRepository->get($orderId);
        if (is_null($order->getEntityId())) {
            return;
        }

        try {
            $orderComment = __('<b>Uber Shipping ID</b>: %1', $confirmationData['id']) . '<br>';
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
     * getDeliveryTime
     *
     * Return Estimated shipping time based on store time zone
     * @param $storeId
     * @return \DateTime
     */
    private function getDeliveryTime($storeId): \DateTime
    {
        // Get Preparation Time (Window Delivery)
        $preparationTime = $this->helper->getPreparationTime($storeId);
        $currentTime = $this->timezone->date();
        $interval = new \DateInterval("PT{$preparationTime}M");
        return $currentTime->add($interval);
    }

    /**
     * getVerificationMethod
     *
     * Return Verification Method
     * @param $order
     * @return array[]
     */
    private function getVerificationMethod($order): array
    {
        $verificationParams = [];
        $storeId = $order->getStoreId();
        $incrementId = $order->getIncrementId();

        // Get Verification Method
        $verificationMethod = $this->helper->getVerificationType($storeId);

        // Build Requirements
        switch ($verificationMethod) {
            case "identification":
                $verificationParams['identification'] = [
                    'min_age' =>  $this->helper->getIdentificationAge($storeId) ?: 21
                ];
                break;
            case "picture":
                $verificationParams['picture'] = [
                    'enabled' => true
                ];
                break;
            case "pincode":
                $verificationParams['pincode'] = [
                    'enabled' => true
                ];
                break;
            case "barcodes":
                $verificationParams['barcodes'] = [
                    'type' => 'CODE39',
                    'value' => $incrementId
                ];
                break;
            default:
                $verificationParams['signature_requirement'] = [
                    'enabled' => true,
                    'collect_signer_name' => true, // Flag for if the signer's name is required at this waypoint
                    'collect_signer_relationship' => true // Flag for if the signer's relationship to the intended recipient is required at this waypoint.
                ];
                break;
        }

        return $verificationParams;
    }

    /**
     * createShipment
     * @param $order
     * @param array $uberData
     * @return bool|Shipment
     * @throws LocalizedException
     * @throws MailException
     */
    private function createShipment($order, array $uberData)
    {
        $trackNumber = $this->formatTrackingNumber($uberData['uuid']);
        $trackURL = $uberData['tracking_url'];
        $carrierTitle = $this->helper->getShippingTitle($order->getStoreId()) ?: 'Uber Direct';

        if ($order->canShip()) {
            $items = [];
            foreach ($order->getAllItems() as $orderItem) {
                if (!$orderItem->getQtyToShip() || $orderItem->getIsVirtual()) {
                    continue;
                }
                $items[$orderItem->getId()] = $orderItem->getQtyToShip();
                /**
                 * Set MSI Source?
                 */
                /*if ($this->helper->getSourceOrigin($order->getStoreId())) {
                    $orderSourceMSI = "";
                    $shipment->getExtensionAttributes()->setSourceCode($orderSourceMSI);
                }*/
            }
            $this->shipmentLoader->setOrderId($order->getId());
            $this->shipmentLoader->setShipmentId(null);
            $this->shipmentLoader->setShipment(['items' => $items]);
            $trackingInfo = null;
            if ($trackNumber) {
                $trackingInfo = [[
                    'title'        => $carrierTitle,
                    'number'       => $trackNumber,
                    'carrier_code' => $order->getShippingMethod(),
                ]];
            }

            $this->shipmentLoader->setTracking($trackingInfo);

            /** If "current_shipment" is already registered, we need to unregister it. */
            if ($this->registry->registry('current_shipment')) {
                $this->registry->unregister('current_shipment');
            }

            $shipment = $this->shipmentLoader->load();
            $shipment->register();
            $shipment->getOrder()->setIsInProcess(true);
            $transaction = $this->transactionFactory->create();
            $transaction->addObject($shipment)->addObject($shipment->getOrder())->save();
            if ($trackNumber) {
                $this->shipmentNotifier->notify($shipment);
            }
        } else {
            /** @var Order\Shipment $shipment */
            $shipment = $order->getShipmentsCollection()->getFirstItem();
            if ($trackNumber) {
                $shipment->addTrack(
                    $this->trackFactory->create()
                        ->setNumber($trackNumber)
                        ->setCarrierCode($shipment->getOrder()->getShippingMethod())
                        ->setTitle($carrierTitle)
                        ->setUrl($trackURL)
                );
            }
        }
        return $shipment;
    }

    /**
     * formatTrackingNumber
     *
     * Return TrackNumber formatted
     * @param string $trackingNumber
     * @return string
     */
    private function formatTrackingNumber(string $trackingNumber): string
    {
        return preg_replace('/^([\da-f]{8})([\da-f]{4})([\da-f]{4})([\da-f]{4})([\da-f]{12})$/i', '$1-$2-$3-$4-$5', $trackingNumber);
    }
}
