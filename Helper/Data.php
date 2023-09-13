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

class Data extends AbstractHelper
{
    const UBER_AUTH_ENDPOINT = 'https://login.uber.com/oauth/v2/token';

    const UBER_PRODUCTION_ENDPOINT = 'https://api.uber.com/v1/%s';

    const UBER_SANDBOX_ENDPOINT = 'https://sandbox-api.uber.com/v1/%s';

    const UBER_CARRIER_CONFIG_PATH = 'carriers/uber/%s';

    const UBER_SHIPPING_CONFIG_PATH = 'shipping/uber/%s';

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
     * @param EncryptorInterface $encryptor
     * @param ScopeConfigInterface $scopeConfig
     * @param Logger $logger
     * @param StoreManagerInterface $storeManager
     * @param Context $context
     */
    public function __construct(
        EncryptorInterface $encryptor,
        ScopeConfigInterface $scopeConfig,
        Logger $logger,
        StoreManagerInterface $storeManager,
        Context $context
    ) {
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->encryptor = $encryptor;
        $this->scopeConfig = $scopeConfig;
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
    public function getConfigCarrierData($configPath, $storeId = null)
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
