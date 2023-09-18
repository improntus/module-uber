<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2023 Improntus (http://www.improntus.com/)
 */

namespace Improntus\Uber\Model\Carrier;

use Improntus\Uber\Helper\Data as UberHelper;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Directory\Helper\Data;
use Magento\Directory\Model\CountryFactory;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Xml\Security;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Shipping\Model\Carrier\AbstractCarrierOnline;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\ResultFactory;
use Magento\Shipping\Model\Simplexml\ElementFactory;
use Magento\Shipping\Model\Tracking\Result\StatusFactory;
use Psr\Log\LoggerInterface;

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

        // Order Free Shipping?
        $isFreeShipping = $this->getConfigFlag('free_shipping') && $request->getFreeShipping();

        // Create Method Factory
        $uberMethod = $this->_rateMethodFactory->create();

        // Set Carrier / Title
        $uberMethod->setCarrier($this->_code);
        $uberMethod->setCarrierTitle($this->getConfigData('title'));

        // Set Method / Title
        $uberMethod->setMethod($this->_code);
        $uberMethod->setMethodTitle($this->getConfigData('description'));

        // Set Method Position
        $uberMethod->setSortOrder($this->getConfigData('sort_order'));

        // isValidCart?
        $isValidCart = $this->isValidCart($request);

        // Return Rate
        $result = $this->_rateFactory->create();
        $result->append($uberMethod);
        return $result;
    }

    /**
     * isValidCart
     *
     * We check if the cart has items that cannot be sent with Uber
     * @return void
     */
    private function isValidCart(RateRequest $request)
    {
        foreach ($request->getAllItems() as $_item) {
            if ($_item->getProductType() == 'configurable') {
                continue;
            }

            $_product = $_item->getProduct();

            if ($_item->getParentItem()) {
                $_item = $_item->getParentItem();
            }

            $attr = $_product->getAttributes();
            //$val = $_item->getProduct()->getResource()->getAttribute('is_uber_can_ship')->getValue($_item->getProduct());

            $a = 1;
        }
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
}
