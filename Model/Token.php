<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2024 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Model;

use Improntus\Uber\Api\Data\TokenInterface;
use Improntus\Uber\Model\ResourceModel\Token as TokenResourceModel;
use Magento\Framework\Model\AbstractModel;

class Token extends AbstractModel implements TokenInterface
{
    /**
     * @var string
     */
    public const CACHE_TAG = 'improntus_uber_token';

    /**
     * @var string
     */
    protected $_cacheTag = self::CACHE_TAG;

    /**
     * @var string $_eventPrefix
     */
    protected $_eventPrefix = 'improntus_uber_token';

    /**
     * @var string $_eventObject
     */
    protected $_eventObject = 'token';

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(TokenResourceModel::class);
    }

    /**
     * @inheritDoc
     */
    public function getTokenId()
    {
        return $this->getData(TokenInterface::TOKEN_ID);
    }

    /**
     * @inheritDoc
     */
    public function setTokenId($tokenId)
    {
        return $this->setData(TokenInterface::TOKEN_ID, $tokenId);
    }

    /**
     * @inheritDoc
     */
    public function getStoreId()
    {
        return $this->getData(TokenInterface::STORE_ID);
    }

    /**
     * @inheritDoc
     */
    public function setStoreId(int $storeId)
    {
        return $this->setData(TokenInterface::STORE_ID, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function getExpirationDate()
    {
        return $this->getData(TokenInterface::EXPIRATION_DATE);
    }

    /**
     * @inheritDoc
     */
    public function setExpirationDate(string $expirationDate)
    {
        return $this->setData(TokenInterface::EXPIRATION_DATE, $expirationDate);
    }

    /**
     * @inheritDoc
     */
    public function getToken()
    {
        return $this->getData(TokenInterface::TOKEN);
    }

    /**
     * @inheritDoc
     */
    public function setToken(string $token)
    {
        return $this->setData(TokenInterface::TOKEN, $token);
    }

    /**
     * @inheritDoc
     */
    public function getScope()
    {
        return $this->getData(TokenInterface::SCOPE);
    }

    /**
     * @inheritDoc
     */
    public function setScope(string $scope)
    {
        return $this->setData(TokenInterface::SCOPE, $scope);
    }
}
