<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2023 Improntus (http://www.improntus.com/)
 */

namespace Improntus\Uber\Helper;

use Improntus\Uber\Logger\Logger;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Module\ModuleListInterface;

class Data extends AbstractHelper
{
    const UBER_PRODUCTION_ENDPOINT = 'https://api.uber.com/v1/%s';

    const UBER_SANDBOX_ENDPOINT = 'https://sandbox-api.uber.com/v1/%s';

    const UBER_CARRIER_CONFIG_PATH = 'carriers/uber/%s';

    const UBER_SHIPPING_CONFIG_PATH = 'shipping/uber/%s';

    const PAYMENT_COD_CONFIG_PATH = 'payment/cashondelivery/%s';

    const CLIENT_SECRET = 'client_secret';

    const CLIENT_ID = 'client_id';

    /**
     * @var Logger $logger
     */
    protected Logger $logger;

    /**
     * @var EncryptorInterface $encryptor
     */
    protected EncryptorInterface $encryptor;

    /**
     * @var StoreManagerInterface $storeManager
     */
    protected StoreManagerInterface $storeManager;

    /**
     * @var ModuleListInterface $moduleList
     */
    protected ModuleListInterface $moduleList;

    /**
     * @param EncryptorInterface $encryptor
     * @param ScopeConfigInterface $scopeConfig
     * @param Logger $logger
     * @param StoreManagerInterface $storeManager
     * @param Context $context
     * @param ModuleListInterface $moduleList
     */
    public function __construct(
        EncryptorInterface $encryptor,
        ScopeConfigInterface $scopeConfig,
        Logger $logger,
        StoreManagerInterface $storeManager,
        Context $context,
        ModuleListInterface $moduleList
    ) {
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->encryptor = $encryptor;
        $this->scopeConfig = $scopeConfig;
        $this->moduleList = $moduleList;
        parent::__construct($context);
    }

    /**
     * isDebugEnabled
     * @param $storeId
     * @return bool
     */
    public function isDebugEnabled($storeId = null): bool
    {
        return (bool)$this->getConfigCarrierData('debug', $storeId);
    }

    /**
     * isCashOnDeliveryEnabled
     * @param $storeId
     * @return bool
     */
    public function isCashOnDeliveryEnabled($storeId = null): bool
    {
        return (bool)$this->getConfigCarrierData('cod', $storeId);
    }

    /**
     * isModuleEnabled
     * @param $storeId
     * @return bool
     */
    public function isModuleEnabled($storeId = null): bool
    {
        return (bool)$this->getConfigCarrierData('active', $storeId);
    }

    /**
     * getSourceOrigin
     *
     * Returns True if MSI should be used or False if Uber Waypoints should be used
     * @param $storeId
     * @return bool
     */
    public function getSourceOrigin($storeId = null): bool
    {
        return (bool)$this->getConfigCarrierData('source', $storeId);
    }

    /**
     * isAutomaticShipmentGenerationEnabled
     * @param $storeId
     * @return bool
     */
    public function isAutomaticShipmentGenerationEnabled($storeId = null): bool
    {
        return (bool)$this->getConfigCarrierData('automatic_shipment', $storeId);
    }

    /**
     * getAutomaticShipmentGenerationStatus
     * @param $storeId
     * @return array
     */
    public function getAutomaticShipmentGenerationStatus($storeId = null): array
    {
        $automaticShipmentGenerationStatus = $this->getConfigCarrierData('automatic_shipment', $storeId) ?: [];
        return explode(",", $automaticShipmentGenerationStatus);
    }

    /**
     * isPaymentCashOnDeliveryEnabled
     * @param $storeId
     * @return bool
     */
    public function isPaymentCashOnDeliveryEnabled($storeId = null): bool
    {
        $path = vsprintf(self::PAYMENT_COD_CONFIG_PATH, ["active"]);
        return $this->scopeConfig->isSetFlag($path, ScopeInterface::SCOPE_STORE, $storeId) ?? false;
    }

    /**
     * hasMsiInstalled
     * @return bool
     */
    public function hasMsiInstalled(): bool
    {
        return $this->moduleList->has('Magento_Inventory');
    }

    /**
     * getCustomerId / organizationId
     * @param $storeId
     * @return mixed|string
     */
    public function getCustomerId($storeId = null): mixed
    {
        return $this->getConfigShippingData('customer_id', $storeId);
    }

    /**
     * getClientId
     * @param $storeId
     * @return mixed
     */
    public function getClientId($storeId = null): mixed
    {
        return $this->getConfigShippingData('client_id', $storeId);
    }

    /**
     * getClientSecret
     * @param $storeId
     * @return mixed
     */
    public function getClientSecret($storeId = null): mixed
    {
        return $this->getConfigShippingData('client_secret', $storeId);
    }

    /**
     * getConfigShippingData
     * @param $configPath
     * @param $storeId
     * @return mixed|string
     */
    public function getConfigShippingData($configPath, $storeId = null): mixed
    {
        $path = vsprintf(self::UBER_SHIPPING_CONFIG_PATH, [$configPath]);
        if ($configPath === self::CLIENT_SECRET || $configPath === self::CLIENT_ID) {
            return $this->encryptor->decrypt($this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId) ?? '');
        }
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId) ?? '';
    }

    /**
     * getConfigCarrierData
     * @param $configPath
     * @param $storeId
     * @return mixed|string
     */
    public function getConfigCarrierData($configPath, $storeId = null): mixed
    {
        $path = vsprintf(self::UBER_CARRIER_CONFIG_PATH, [$configPath]);
        if ($configPath === self::CLIENT_SECRET || $configPath === self::CLIENT_ID) {
            return $this->encryptor->decrypt($this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId) ?? '');
        }
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId) ?? '';
    }

    /**
     * log
     * @param $message
     * @param string $type
     * @return void
     */
    public function log($message, string $type = 'debug'): void
    {
        if ($this->isDebugEnabled()) {
            $this->logger->setName('Uber');
            if ($type !== 'debug') {
                $this->logger->info($message);
            } else {
                $this->logger->debug($message);
            }
        }
    }

    /**
     * buildRequestURL
     * @param string $endpoint
     * @param int|null $storeId
     * @return string
     */
    public function buildRequestURL(string $endpoint, int|null $storeId = null): string
    {
        // Get Integration Mode
        $productionMode = $this->isDebugEnabled($storeId);

        // Get Base URL
        $basePath = $productionMode ? self::UBER_PRODUCTION_ENDPOINT : self::UBER_SANDBOX_ENDPOINT;
        return vsprintf($basePath, [$endpoint]);
    }
}
