<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2024 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Model;

use Improntus\Uber\Api\Data\OrderShipmentInterface;
use Improntus\Uber\Model\ResourceModel\OrderShipment as OrderShipmentResourceModel;
use Magento\Framework\Model\AbstractModel;

class OrderShipment extends AbstractModel implements OrderShipmentInterface
{
    /**
     * @var string
     */
    public const CACHE_TAG = 'improntus_uber_order_shipment';

    /**
     * @var string
     */
    protected $_cacheTag = self::CACHE_TAG;

    /**
     * @var string $_eventPrefix
     */
    protected $_eventPrefix = 'improntus_uber_order_shipment';

    /**
     * @var string $_eventObject
     */
    protected $_eventObject = 'order_shipment';

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(OrderShipmentResourceModel::class);
    }

    /**
     * @return mixed
     */
    public function getOrderId()
    {
        return $this->getData(OrderShipmentInterface::ORDER_ID);
    }

    /**
     * @param int $orderId
     * @return mixed
     */
    public function setOrderId(int $orderId)
    {
        return $this->setData(OrderShipmentInterface::ORDER_ID, $orderId);
    }

    /**
     * @return mixed
     */
    public function getStoreId()
    {
        return $this->getData(OrderShipmentInterface::STORE_ID);
    }

    /**
     * @param int $storeId
     * @return mixed
     */
    public function setStoreId(int $storeId)
    {
        return $this->setData(OrderShipmentInterface::STORE_ID, $storeId);
    }


    /**
     * @return mixed
     */
    public function getSourceWaypoint()
    {
        return $this->getData(OrderShipmentInterface::SOURCE_WAYPOINT);
    }

    /**
     * @param int $sourceId
     * @return mixed
     */
    public function setSourceWaypoint(int $sourceId)
    {
        return $this->setData(OrderShipmentInterface::SOURCE_WAYPOINT, $sourceId);
    }

    /**
     * @return mixed
     */
    public function getSourceMsi()
    {
        return $this->getData(OrderShipmentInterface::SOURCE_MSI);
    }

    /**
     * @param string $sourceCode
     * @return mixed
     */
    public function setSourceMsi(string $sourceCode)
    {
        return $this->setData(OrderShipmentInterface::SOURCE_MSI, $sourceCode);
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->getData(OrderShipmentInterface::STATUS);
    }

    /**
     * @param string $status
     * @return mixed
     */
    public function setStatus(string $status)
    {
        return $this->setData(OrderShipmentInterface::STATUS, $status);
    }

    /**
     * @return mixed
     */
    public function getIncrementId()
    {
        return $this->getData(OrderShipmentInterface::INCREMENT_ID);
    }

    /**
     * @param string $incremenlId
     * @return mixed
     */
    public function setIncrementId(string $incrementId)
    {
        return $this->setData(OrderShipmentInterface::INCREMENT_ID, $incrementId);
    }

    /**
     * getUberShippingId
     * @return mixed
     */
    public function getUberShippingId()
    {
        return $this->getData(OrderShipmentInterface::UBER_SHIPPING_ID);
    }

    /**
     * setUberShippingId
     * @return mixed
     */
    public function setUberShippingId($uberShippingId)
    {
        return $this->setData(OrderShipmentInterface::UBER_SHIPPING_ID, $uberShippingId);
    }

    /**
     * getVerification
     * @return mixed
     */
    public function getVerification()
    {
        return $this->getData(OrderShipmentInterface::VERIFICATION);
    }

    /**
     * setVerification
     * @return mixed
     */
    public function setVerification($verification)
    {
        return $this->setData(OrderShipmentInterface::VERIFICATION, $verification);
    }
}
