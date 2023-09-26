<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2023 Improntus (http://www.improntus.com/)
 */

namespace Improntus\Uber\Model;

use Exception;
use Improntus\Uber\Helper\Data;
use Improntus\Uber\Model\Carrier\Uber as UberCarrier;
use Improntus\Uber\Model\Warehouse\WarehouseRepository;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Model\OrderRepository;

class CreateShipment
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
     * @var DateTime $dateTime
     */
    protected DateTime $dateTime;

    /**
     * @var TimezoneInterface $timezone
     */
    protected TimezoneInterface $timezone;

    /**
     * @var Uber $uber
     */
    protected Uber $uber;

    public function __construct(
        Uber $uber,
        Data $helper,
        DateTime $dateTime,
        TimezoneInterface $timezone,
        OrderRepository $orderRepository,
        WarehouseRepository $warehouseRepository,
        OrderShipmentRepository $orderShipmentRepository
    ) {
        $this->uber = $uber;
        $this->helper = $helper;
        $this->dateTime = $dateTime;
        $this->timezone = $timezone;
        $this->orderRepository = $orderRepository;
        $this->warehouseRepository = $warehouseRepository;
        $this->orderShipmentRepository = $orderShipmentRepository;
    }

    public function create($orderId)
    {
        if ($this->helper->isModuleEnabled() && !is_null($orderId)) {
            // Get Order
            $order = $this->orderRepository->get($orderId);
            if (is_null($order->getId())) {
                // Todo MSG
                throw new Exception(__('The requested Order does not exist'));
            }

            // Validate Shipping Method
            if ($order->getShippingMethod() == self::CARRIER_CODE) {
                // Get Shipping Data
                $uberOrderShipment = $this->orderShipmentRepository->getByOrderId($orderId);

                // Get Warehouse
                $warehouseId = $uberOrderShipment->getSourceWaypoint() ?? $uberOrderShipment->getSourceMsi();
                $warehouse = $this->warehouseRepository->getWarehouse($warehouseId);

                // Generate DeliveryTime and Check Warehouse Work Schedule
                $deliveryTime = $this->getDeliveryTime($order->getStoreId());
                if (!$this->warehouseRepository->checkWarehouseWorkSchedule($warehouse, $deliveryTime)) {
                    // Todo MSG
                    throw new Exception(__('The preparation point is outside working hours'));
                }

                /**
                 * Prepare Data
                 */
                $warehouseData = $this->warehouseRepository->getWarehousePickupData($warehouse);
                $dropoffData = $this->getDropoffData($order);
                $deliveryItems = $this->getDeliveryItems($order);
                $deliveryAdditionalData = [
                    'pickup_ready_dt' => $deliveryTime->format('Y-m-d\TH:i:s.000\Z'),
                    'manifest_total_value' => (int)$order->getGrandTotal(),
                    'manifest_reference' => 'Testea',
                    'external_id' => 'TesteB',
                    'return_verification' => [
                        'picture' => true,
                        'signature_requirement' => [
                            'enabled' => true,
                            'collect_signer_name' => true,
                            'collect_signer_relationship' => true
                        ]
                    ]
                ];

                // Prepara Request
                $shippingData = array_merge($warehouseData, $dropoffData, $deliveryItems, $deliveryAdditionalData);

                // Get Organization ID (Customer ID)
                $organizationId = $this->warehouseRepository->getWarehouseOrganization($warehouse);

                // Send Request to Uber
                $uberResponse = $this->uber->createShipping($shippingData, $organizationId, $order->getStoreId());
                die;
                return '';//$requestData;
            }
        }
    }

    /**
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

            // Get Weight
            $itemWeight = $_item->getQtyOrdered() * $_product->getWeight();

            // Valid Item
            $uberItems[] = [
                'name' => $_item->getName(),
                'quantity' => (int)$_item->getQtyOrdered(),
                'price' => (int)$_item->getPrice(),
                'must_be_upright' => true,
                'weight' => (float)$itemWeight,
                'dimensions' => [
                    'length' => (int)$_product->getData($productWidthAttribute),
                    'height' => (int)$_product->getData($productHeightAttribute),
                    'depth'  => (int)$_product->getData($productDepthAttribute)
                ]
            ];
        }

        return ['manifest_items' => $uberItems];
    }

    /**
     * getDropoffData
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
            'dropoff_verification' => $this->getDropoffVerification($order)
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
     * getDeliveryTime
     * @param $storeId
     * @return \DateTime
     */
    private function getDeliveryTime($storeId): \DateTime
    {
        // Get Preparation Time (Window Delivery)
        $preparationTime = $this->helper->getPreparationTime($storeId);
        // Get Current DateTime
        $currentTime = $this->timezone->date();
        // Add Preparation Time
        $interval = new \DateInterval("PT{$preparationTime}M");
        return $currentTime->add($interval);
    }

    /**
     * getDropoffVerification
     * @param $order
     * @return array[]
     */
    private function getDropoffVerification($order): array
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
}
