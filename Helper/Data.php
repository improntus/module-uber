<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2024 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Helper;

use Improntus\Uber\Logger\Logger;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Data extends AbstractHelper
{
    public const UBER_PRODUCTION_ENDPOINT = 'https://api.uber.com/v1/%s';

    public const UBER_SANDBOX_ENDPOINT = 'https://sandbox-api.uber.com/v1/%s';

    public const UBER_CARRIER_CONFIG_PATH = 'carriers/uber/%s';

    public const UBER_SHIPPING_CONFIG_PATH = 'shipping/uber/%s';

    public const PAYMENT_COD_CONFIG_PATH = 'payment/cashondelivery/%s';

    public const STORE_WEIGHT_UNIT_CONFIG_PATH = 'general/locale/weight_unit';

    public const STORE_NAME_CONFIG_PATH = 'general/store_information/name';

    public const UBER_DELIVERED_STATUS = 'uber_delivered';

    /**
     * @var Logger $logger
     */
    protected Logger $logger;

    /**
     * @var StoreManagerInterface $storeManager
     */
    protected StoreManagerInterface $storeManager;

    /**
     * @var ModuleListInterface $moduleList
     */
    protected ModuleListInterface $moduleList;

    /**
     * @var EncryptorInterface $encryptor
     */
    protected EncryptorInterface $encryptor;

    /**
     * @var TimezoneInterface $timezone
     */
    protected TimezoneInterface $timezoneInterface;

    /**
     * @param EncryptorInterface $encryptor
     * @param ScopeConfigInterface $scopeConfig
     * @param Logger $logger
     * @param StoreManagerInterface $storeManager
     * @param Context $context
     * @param ModuleListInterface $moduleList
     * @param TimezoneInterface $timezoneInterface
     */
    public function __construct(
        EncryptorInterface $encryptor,
        ScopeConfigInterface $scopeConfig,
        Logger $logger,
        StoreManagerInterface $storeManager,
        Context $context,
        ModuleListInterface $moduleList,
        TimezoneInterface $timezoneInterface
    ) {
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->encryptor = $encryptor;
        $this->scopeConfig = $scopeConfig;
        $this->moduleList = $moduleList;
        $this->timezoneInterface = $timezoneInterface;
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
     * isWebhooksEnabled
     * @param $storeId
     * @return bool
     */
    public function isWebhooksEnabled($storeId = null): bool
    {
        return (bool)$this->getConfigCarrierData('webhooks_integration', $storeId);
    }

    /**
     * getWebhookSignature
     * @param $storeId
     * @return string
     */
    public function getWebhookSignature($storeId = null): string
    {
        return $this->getConfigCarrierData('webhook_signing', $storeId) ?: '';
    }

    /**
     * getIntegrationMode
     * @param $storeId
     * @return bool
     */
    public function getIntegrationMode($storeId = null): bool
    {
        return (bool)$this->getConfigCarrierData('mode', $storeId);
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
     * @return string
     */
    public function getSourceOrigin(): string
    {
        return $this->getConfigCarrierData('source');
    }

    /**
     * getPreparationTime
     *
     * Returns Windows Delivery (PreparationTime)
     * @param $storeId
     * @return int
     */
    public function getPreparationTime($storeId = null): int
    {
        return (int)$this->getConfigCarrierData('preparation_time', $storeId);
    }

    /**
     * getPromiseTime
     *
     * Returns Promise Time Delivery
     * @default 20 Minutes
     * @param $storeId
     * @return int
     */
    public function getPromiseTime($storeId = null): int
    {
        return (int)$this->getConfigCarrierData('promise_time', $storeId) ?: 20;
    }

    /**
     * getVerificationType
     *
     * Returns Verification Type selected
     * @param null $storeId
     * @param string $area
     * @return string
     */
    public function getVerificationType($storeId = null, string $area = 'dropoff'): string
    {
        return $this->getConfigCarrierData("verification_type_{$area}", $storeId);
    }

    /**
     * getProductWidthAttribute
     *
     * Return attribute name
     * @param $storeId
     * @return string
     */
    public function getProductWidthAttribute($storeId = null): string
    {
        return $this->getConfigCarrierData('product_width_attribute', $storeId);
    }

    /**
     * getProductHeightAttribute
     *
     * Return attribute name
     * @param $storeId
     * @return string
     */
    public function getProductHeightAttribute($storeId = null): string
    {
        return $this->getConfigCarrierData('product_height_attribute', $storeId);
    }

    /**
     * getProductDepthAttribute
     *
     * Return attribute name
     * @param $storeId
     * @return string
     */
    public function getProductDepthAttribute($storeId = null): string
    {
        return $this->getConfigCarrierData('product_depth_attribute', $storeId);
    }

    /**
     * getIdentificationAge
     *
     * Returns Identification Min Age for Verification
     * @param null $storeId
     * @param string $area
     * @return int
     */
    public function getIdentificationAge($storeId = null, string $area = 'dropoff'): int
    {
        return (int)$this->getConfigCarrierData("verification_age_{$area}", $storeId);
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
        $automaticShipmentGenerationStatus = $this->getConfigCarrierData('status_allowed', $storeId) ?: [];
        return explode(",", $automaticShipmentGenerationStatus);
    }

    /**
     * getShippingTitle
     *
     * Return Carrier Title
     * @param $storeId
     * @return string
     */
    public function getShippingTitle($storeId = null): string
    {
        return $this->getConfigCarrierData('title', $storeId);
    }

    /**
     * getShippingDescriptionOBH
     *
     * Return Carrier Description Outside Business Hours
     * @param $storeId
     * @return string
     */
    public function getShippingDescriptionOBH($storeId = null): string
    {
        return $this->getConfigCarrierData('description_obh', $storeId);
    }

    /**
     * showUberShippingOBH
     * @param $storeId
     * @return bool
     */
    public function showUberShippingOBH($storeId = null): bool
    {
        return (bool)$this->getConfigCarrierData('show_carrier_obh', $storeId);
    }

    /**
     * getStoreWeightUnit
     * @param $storeId
     * @return mixed
     */
    public function getStoreWeightUnit($storeId = null): mixed
    {
        return $this->scopeConfig->getValue(self::STORE_WEIGHT_UNIT_CONFIG_PATH, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * getStoreName
     *
     * Returns store name. If not set, generate one from the URL
     * @param $storeId
     * @return string
     */
    public function getStoreName($storeId = null): string
    {
        $storeName = $this->scopeConfig->getValue(self::STORE_NAME_CONFIG_PATH, ScopeInterface::SCOPE_STORE, $storeId);
        if ($storeName === null) {
            $storeUrl = $this->scopeConfig->getValue(
                'web/unsecure/base_url',
                ScopeInterface::SCOPE_STORE,
                $storeId
            ) ?? "M2";
            $storeName = "$storeUrl - Uber Direct";
        }
        return $storeName;
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
     *
     * Returns true if the requirements to implement MSI are met
     * @return bool
     */
    public function hasMsiInstalled(): bool
    {
        return $this->moduleList->has('Magento_Inventory') && $this->moduleList->has('Improntus_UberInventory');
    }

    /**
     * Get Source Default
     *
     * Return Default Stock from MSI
     * @param null $storeId
     * @return string
     */
    public function getSourceDefault($storeId = null): string
    {
        return $this->getConfigCarrierData('default_source_stock', $storeId) ?? 'default';
    }

    /**
     * getCustomerId / organizationId
     * @param null $storeId
     * @param string $scope
     * @return mixed|string
     */
    public function getCustomerId($storeId = null, $scope = ScopeInterface::SCOPE_STORE): mixed
    {
        return $this->getConfigShippingData('customer_id', $storeId, $scope);
    }

    /**
     * getClientId
     * @param $storeId
     * @return mixed
     */
    public function getClientId($storeId = null): mixed
    {
        $clienteId = $this->getConfigShippingData('client_id', $storeId);
        return $clienteId !== null ? $this->encryptor->decrypt($clienteId) : '';
    }

    /**
     * getClientSecret
     * @param $storeId
     * @return mixed
     */
    public function getClientSecret($storeId = null): mixed
    {
        $clientSecret = $this->getConfigShippingData('client_secret', $storeId);
        return $clientSecret !== null ? $this->encryptor->decrypt($clientSecret) : '';
    }

    /**
     * getConfigShippingData
     * @param $configPath
     * @param null $storeId
     * @param string $scope
     * @return mixed|string
     */
    public function getConfigShippingData($configPath, $storeId = null, $scope = ScopeInterface::SCOPE_STORE): mixed
    {
        $path = vsprintf(self::UBER_SHIPPING_CONFIG_PATH, [$configPath]);
        return $this->scopeConfig->getValue($path, $scope, $storeId) ?? '';
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
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId) ?? '';
    }

    /**
     * log
     *
     * This method will log CRITICAL errors even if Debug Mode is not active
     * @param $message
     * @param string $type
     * @return void
     */
    public function log($message, string $type = 'debug'): void
    {
        $this->logger->critical($message);
    }

    /**
     * logDebug
     *
     * This method will log errors ONLY when Debug Mode is active
     * @param $message
     * @param string $type
     * @return void
     */
    public function logDebug($message, string $type = 'debug')
    {
        if ($this->isDebugEnabled()) {
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
        $integrationMode = $this->getIntegrationMode($storeId);

        // Get Base URL
        $basePath = $integrationMode ? self::UBER_PRODUCTION_ENDPOINT : self::UBER_SANDBOX_ENDPOINT;
        return vsprintf($basePath, [$endpoint]);
    }

    /**
     * getDeliveryTime
     *
     * Return Estimated shipping time based on store time zone
     *
     * @param $storeId
     * @return \DateTime
     */
    public function getDeliveryTime($storeId = null): \DateTime
    {
        // Get Preparation Time (Window Delivery)
        $preparationTime = $this->getPreparationTime($storeId ?? $this->storeManager->getStore()->getId());
        $currentTime = $this->timezoneInterface->date();
        $interval = new \DateInterval("PT{$preparationTime}M");
        return $currentTime->add($interval);
    }
}
