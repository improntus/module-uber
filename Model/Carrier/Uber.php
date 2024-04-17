<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2024 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Model\Carrier;

use Exception;
use Improntus\Uber\Api\WarehouseRepositoryInterface;
use Improntus\Uber\Helper\Data as UberHelper;
use Improntus\Uber\Model\Uber as UberModel;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Directory\Helper\Data;
use Magento\Directory\Model\CountryFactory;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Xml\Security;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\Method;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Shipping\Model\Carrier\AbstractCarrierOnline;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\Result;
use Magento\Shipping\Model\Rate\ResultFactory;
use Magento\Shipping\Model\Simplexml\ElementFactory;
use Magento\Shipping\Model\Tracking\Result\StatusFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class Uber extends AbstractCarrierOnline implements CarrierInterface
{

    /**
     * @var string
     */
    public const CARRIER_CODE = 'uber';

    /**
     * @var string
     */
    protected $_code = self::CARRIER_CODE;

    /**
     * @var bool
     */
    protected $_isFixed = false;

    /**
     * @var UberHelper $uberHelper
     */
    protected UberHelper $uberHelper;

    /**
     * @var SearchCriteriaBuilder $searchCriteriaBuilder
     */
    protected SearchCriteriaBuilder $searchCriteriaBuilder;

    /**
     * @var StoreManagerInterface $storeManager
     */
    protected StoreManagerInterface $storeManager;

    /**
     * @var WarehouseRepositoryInterface[]
     */
    protected array $warehouseRepositories;

    /**
     * @var UberModel $uber
     */
    protected UberModel $uber;

    /**
     * @var CheckoutSession $checkoutSession
     */
    protected CheckoutSession $checkoutSession;

    /**
     * @param UberModel $uber
     * @param ScopeConfigInterface $scopeConfig
     * @param ErrorFactory $rateErrorFactory
     * @param LoggerInterface $logger
     * @param Security $xmlSecurity
     * @param ElementFactory $xmlElFactory
     * @param ResultFactory $rateFactory
     * @param MethodFactory $rateMethodFactory
     * @param \Magento\Shipping\Model\Tracking\ResultFactory $trackFactory
     * @param \Magento\Shipping\Model\Tracking\Result\ErrorFactory $trackErrorFactory
     * @param StatusFactory $trackStatusFactory
     * @param RegionFactory $regionFactory
     * @param CountryFactory $countryFactory
     * @param CurrencyFactory $currencyFactory
     * @param Data $directoryData
     * @param StockRegistryInterface $stockRegistry
     * @param UberHelper $uberHelper
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param StoreManagerInterface $storeManager
     * @param WarehouseRepositoryInterface $warehouseRepository
     * @param CheckoutSession $checkoutSession
     * @param array $data
     */
    public function __construct(
        UberModel                                            $uber,
        ScopeConfigInterface                                 $scopeConfig,
        ErrorFactory                                         $rateErrorFactory,
        LoggerInterface                                      $logger,
        Security                                             $xmlSecurity,
        ElementFactory                                       $xmlElFactory,
        ResultFactory                                        $rateFactory,
        MethodFactory                                        $rateMethodFactory,
        \Magento\Shipping\Model\Tracking\ResultFactory       $trackFactory,
        \Magento\Shipping\Model\Tracking\Result\ErrorFactory $trackErrorFactory,
        StatusFactory                                        $trackStatusFactory,
        RegionFactory                                        $regionFactory,
        CountryFactory                                       $countryFactory,
        CurrencyFactory                                      $currencyFactory,
        Data                                                 $directoryData,
        StockRegistryInterface                               $stockRegistry,
        UberHelper                                           $uberHelper,
        SearchCriteriaBuilder                                $searchCriteriaBuilder,
        StoreManagerInterface                                $storeManager,
        array                                                $warehouseRepositories,
        CheckoutSession                                      $checkoutSession,
        array                                                $data = []
    ) {
        parent::__construct(
            $scopeConfig,
            $rateErrorFactory,
            $logger,
            $xmlSecurity,
            $xmlElFactory,
            $rateFactory,
            $rateMethodFactory,
            $trackFactory,
            $trackErrorFactory,
            $trackStatusFactory,
            $regionFactory,
            $countryFactory,
            $currencyFactory,
            $directoryData,
            $stockRegistry,
            $data
        );

        $this->uber = $uber;
        $this->uberHelper = $uberHelper;
        $this->storeManager = $storeManager;
        $this->checkoutSession = $checkoutSession;
        $this->warehouseRepositories = $warehouseRepositories;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Collect and get rates
     *
     * @param RateRequest $request
     * @return Result|bool
     */
    public function collectRates(RateRequest $request): Result|bool
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }

        // Return Rate
        $result = $this->_rateFactory->create();

        try {
            $warehouseRepository = $this->getWarehouseRepository();
            // Create Method Factory
            $uberMethod = $this->createMethod();

            // Validate Street & Postcode / Zipcode
            if (empty($request->getDestStreet()) && empty($request->getDestPostcode())) {
                throw new Exception(__('This shipping method is not available. Please specify the zip code.'));
            }

            // Validate Cart
            $cartValidation = $this->isValidCart($request);
            if (!$cartValidation['isValidCart']) {
                throw new Exception(
                    $cartValidation['validationMsg'] ?? __('This shipping method is not available')
                );
            }

            // Get Current StoreId
            $orderStoreId = $this->storeManager->getStore()->getStoreId();

            // Get Warehouses
            $warehousesCollection = $warehouseRepository->getAvailableSources(
                $orderStoreId,
                $cartValidation['cartItemsSku'],
                $request->getDestCountryId(),
                $request->getDestRegionId()
            );
            if ($warehousesCollection === null) {
                $this->uberHelper->logDebug('There are no warehouses available to process the order');
                throw new Exception(__('The cart contains products that are out of stock for express delivery'));
            }

            // Get Customer Region Name
            $customerState = $this->_regionFactory->create()->load($request->getDestRegionId());

            // Get Geolocation of Client
            $addressData = explode("\n", $request->getDestStreet());
            $customerStreet = $addressData[0];
            $customerAddress = "{$customerStreet}, {$request->getDestPostcode()}, {$request->getDestCity()}, {$customerState->getName()}, {$request->getDestCountryId()}";
            $uberStores = $this->uber->checkWarehouseClosest($customerAddress, $orderStoreId);

            // Determine the closest waypoint
            $warehouse = $warehouseRepository->checkWarehouseClosest($uberStores, $warehousesCollection);

            // Has Results?
            if ($warehouse === null) {
                $this->uberHelper->logDebug('There are no deposits near the customer');
                throw new Exception(__('This shipping method is not available'));
            }

            // Get Warehouse Address
            $warehouseAddress = $warehouseRepository->getWarehouseAddressData($warehouse);
            $warehouseId = $warehouseRepository->getWarehouseId($warehouse);

            // Prepare Request Data
            $shippingData = [
                'pickup_address'    => $warehouseAddress,
                'dropoff_address'   => json_encode([
                    'street_address' => [$request->getDestStreet()],
                    'city'           => $request->getDestCity(),
                    'state'          => $customerState->getName(),
                    'zip_code'       => $request->getDestPostcode(),
                    'country'        => $request->getDestCountryId(),
                ], JSON_UNESCAPED_SLASHES),
                'external_store_id' => $warehouse->getId(),
            ];

            // Get Organization ID for Estimate
            $organizationId = $warehouseRepository->getWarehouseOrganization($warehouse);

            // Check Coverage
            $estimateData = $this->uber->getEstimateShipping($shippingData, $organizationId, $orderStoreId);
            if ($estimateData === null) {
                throw new Exception(__('This shipping method is not available'));
            }

            // Apply Free Ship?
            $isFreeShipping = $this->getConfigFlag('free_shipping') && $request->getFreeShipping();

            // Set Shipping Price
            if (!$isFreeShipping) {
                // Set Price
                $uberMethod->setPrice($estimateData['fee']);
            }

            // Set Warehouse ID on Checkout Session
            $this->checkoutSession->setUberWarehouseId($warehouseId);

            // Append Rate
            $result->append($uberMethod);
        } catch (Exception $e) {
            // Set error message
            $erroMsg = $e->getMessage() ?: __('No estimates available for the address entered');
            // Show method on error?
            if ($this->getConfigFlag('showmethod')) {
                $error = $this->_rateErrorFactory->create();
                $error->setCarrier($this->_code);
                $error->setCarrierTitle($this->getConfigData('title'));
                $error->setErrorMessage($erroMsg);
                $result->append($error);
            }
            // Log error?
            if ($this->getConfigFlag('debug')) {
                $this->uberHelper->log(__('Uber Estimate ERROR: %1', $erroMsg));
            }
        }
        // Return Rate
        return $result;
    }

    /**
     * @return WarehouseRepositoryInterface
     */
    protected function getWarehouseRepository(): WarehouseRepositoryInterface
    {
        $warehouseConfig = $this->uberHelper->getSourceOrigin();
        return $this->warehouseRepositories[$warehouseConfig];
    }

    /**
     * createMethod
     *
     * Create and Set basic data
     *
     * @return Method
     */
    private function createMethod(): Method
    {
        $method = $this->_rateMethodFactory->create();
        $method->setCarrier($this->_code);
        $method->setCarrierTitle($this->getConfigData('title'));
        $method->setMethod($this->_code);
        $method->setMethodTitle($this->getConfigData('description'));
        $method->setSortOrder($this->getConfigData('sort_order'));
        return $method;
    }

    /**
     * isValidCart
     *
     * We check if the cart has items that cannot be sent with Uber
     *
     * @param RateRequest $request
     * @return array
     */
    private function isValidCart(RateRequest $request): array
    {
        $itemsSku = [];
        $cartValid = true;
        $validationMsg = false;
        foreach ($request->getAllItems() as $_item) {
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
                $validationMsg = __('The cart contains items that cannot be shipped with Uber');
                $cartValid = false;
                break;
            }

            // Add Sku => Qty to Array
            $itemsSku[$_product->getSku()] = $_item->getQty();
        }

        // Return
        return [
            'isValidCart'   => $cartValid,
            'validationMsg' => $validationMsg,
            'cartItemsSku'  => $itemsSku,
        ];
    }

    /**
     * Method not Implemented
     *
     * @param DataObject $request
     * @return DataObject
     */
    protected function _doShipmentRequest(DataObject $request)
    {
        return false;
    }

    /**
     * @return true
     */
    public function isTrackingAvailable()
    {
        return true;
    }

    /**
     * @return true
     */
    public function getAllowedMethods()
    {
        return true;
    }

    /**
     * Method not Implemented
     *
     * @param DataObject $request
     * @return DataObject
     */
    public function processAdditionalValidation(DataObject $request): DataObject
    {
        return $request;
    }
}
