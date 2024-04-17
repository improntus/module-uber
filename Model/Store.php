<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2024 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Model;

use Improntus\Uber\Api\Data\StoreInterface;
use Improntus\Uber\Model\ResourceModel\Store as StoreResourceModel;
use Magento\Framework\Model\AbstractModel;

class Store extends AbstractModel implements StoreInterface
{
    /**
     * @var string
     */
    public const CACHE_TAG = 'improntus_uber_store';

    /**
     * @var string
     */
    protected $_cacheTag = self::CACHE_TAG;

    /**
     * @var string $_eventPrefix
     */
    protected $_eventPrefix = 'improntus_uber_store';

    /**
     * @var string $_eventObject
     */
    protected $_eventObject = 'store';

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(StoreResourceModel::class);
    }

    /**
     * @inheritDoc
     */
    public function getId()
    {
        return $this->getData(StoreInterface::ENTITY_ID);
    }

    /**
     * @inheritDoc
     */
    public function setId($entity_id)
    {
        return $this->setData(StoreInterface::ENTITY_ID, $entity_id);
    }

    /**
     * @inheritDoc
     */
    public function getSourceCode()
    {
        return $this->getData(StoreInterface::SOURCE_CODE);
    }

    /**
     * @inheritDoc
     */
    public function setSourcecode(string $sourceCode)
    {
        return $this->setData(StoreInterface::SOURCE_CODE, $sourceCode);
    }

    /**
     * @inheritDoc
     */
    public function getWaypointId()
    {
        return $this->getData(StoreInterface::WAYPOINT_ID);
    }

    /**
     * @inheritDoc
     */
    public function setWaypointId(int $waypointId)
    {
        return $this->setData(StoreInterface::WAYPOINT_ID, $waypointId);
    }

    /**
     * @inheritDoc
     */
    public function getHash()
    {
        return $this->getData(StoreInterface::HASH);
    }

    /**
     * @inheritDoc
     */
    public function setHash(string $hash)
    {
        return $this->setData(StoreInterface::HASH, $hash);
    }
}
