<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2023 Improntus (http://www.improntus.com/)
 */

namespace Improntus\Uber\Model\Carrier;

use Improntus\Uber\Helper\Data as UberHelper;
use Improntus\Uber\Model\WaypointRepository;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Directory\Helper\Data;
use Magento\Directory\Model\CountryFactory;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Xml\Security;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\Method;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Shipping\Model\Carrier\AbstractCarrierOnline;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\ResultFactory;
use Magento\Shipping\Model\Simplexml\ElementFactory;
use Magento\Shipping\Model\Tracking\Result\StatusFactory;
use Psr\Log\LoggerInterface;
use Magento\Store\Model\StoreManagerInterface;

class Uber extends AbstractCarrierOnline implements CarrierInterface
{

    /**
     * @var string
     */
    const CARRIER_CODE = 'uber';

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
     * @var WaypointRepository $waypointRepository
     */
    protected WaypointRepository $waypointRepository;

    /**
     * @var SearchCriteriaBuilder $searchCriteriaBuilder
     */
    protected SearchCriteriaBuilder $searchCriteriaBuilder;

    /**
     * @var StoreManagerInterface $storeManager
     */
    protected StoreManagerInterface $storeManager;

    /**
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
     * @param WaypointRepository $waypointRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param StoreManagerInterface $storeManager
     * @param array $data
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ErrorFactory $rateErrorFactory,
        LoggerInterface $logger,
        Security $xmlSecurity,
        ElementFactory $xmlElFactory,
        ResultFactory $rateFactory,
        MethodFactory $rateMethodFactory,
        \Magento\Shipping\Model\Tracking\ResultFactory $trackFactory,
        \Magento\Shipping\Model\Tracking\Result\ErrorFactory $trackErrorFactory,
        StatusFactory $trackStatusFactory,
        RegionFactory $regionFactory,
        CountryFactory $countryFactory,
        CurrencyFactory $currencyFactory,
        Data $directoryData,
        StockRegistryInterface $stockRegistry,
        UberHelper $uberHelper,
        WaypointRepository $waypointRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        StoreManagerInterface $storeManager,
        array $data = []
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

        $this->uberHelper = $uberHelper;
        $this->waypointRepository = $waypointRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Collect and get rates
     * @param RateRequest $request
     * @return bool|null
     */
    public function collectRates(RateRequest $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }

        // Return Rate
        $result = $this->_rateFactory->create();

        try {
            // Validate Street & Postcode / Zipcode
            if (empty($request->getDestStreet()) && empty($request->getDestPostcode())) {
                // todo: Change Exception msg (Street & Postcode)
                throw new \Exception(__('This shipping method is not available. Please specify the zip code.'));
            }

            // Validate Cart
            $isValidCart = $this->isValidCart($request);
            if (!$isValidCart) {
                // todo: Change Exception msg (Invalid items)
                throw new \Exception(__('The cart contains items that cannot be shipped with Uber'));
            }

            // Get Current StoreId
            $orderStoreId = $this->storeManager->getStore()->getStoreId();

            // Get Waypoint / Source MSI


            // Create Method Factory
            $uberMethod = $this->createMethod();

            // Free Ship?
            $isFreeShipping = $this->getConfigFlag('free_shipping') && $request->getFreeShipping();

            // Todo: Remove this split (Lol)
            $result->append($uberMethod);
        } catch (\Exception $e) {
            // Set error message
            $erroMsg = $e->getMessage() ?: __('No estimates available for the address entered');

            // Show ERROR Method?
            if ($this->getConfigFlag('showmethod')) {
                $error = $this->_rateErrorFactory->create();
                $error->setCarrier($this->_code);
                $error->setCarrierTitle($this->getConfigData('title'));
                $error->setErrorMessage($erroMsg);
                $result->append($error);
            }

            // Log error?
        }

        return $result;
    }

    /**
     * createMethod
     *
     * Create and Set basic data
     * @return Method
     */
    private function createMethod(): Method
    {
        // Instance
        $method = $this->_rateMethodFactory->create();
        // Set Carrier / Title
        $method->setCarrier($this->_code);
        $method->setCarrierTitle($this->getConfigData('title'));
        // Set Method / Title
        $method->setMethod($this->_code);
        $method->setMethodTitle($this->getConfigData('description'));
        // Set Method Position
        $method->setSortOrder($this->getConfigData('sort_order'));
        return $method;
    }

    /**
     * isValidCart
     *
     * We check if the cart has items that cannot be sent with Uber
     * @return bool
     */
    private function isValidCart(RateRequest $request): bool
    {
        $cartValid = true;
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
            if (!$_product->getIsUberCanShip()) {
                $cartValid = false;
                break;
            }
        }
        // Return Cart Validation
        return $cartValid;
    }

    /**
     * Method not Implemented
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
     * @param DataObject $request
     * @return DataObject
     */
    public function processAdditionalValidation(\Magento\Framework\DataObject $request)
    {
        return $request;
    }
}
