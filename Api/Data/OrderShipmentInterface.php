<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2023 Improntus (http://www.improntus.com/)
 */

namespace Improntus\Uber\Api\Data;

interface OrderShipmentInterface
{
    const ENTITY_ID = 'entity_id';

    const ORDER_ID = 'order_id';

    const INCREMENT_ID = 'increment_id';

    const SOURCE_WAYPOINT = 'source_waypoint';

    const SOURCE_MSI = 'source_msi';

    const STATUS = 'status';

    /**
     * @return mixed
     */
    public function getId();

    /**
     * @param int $id
     * @return mixed
     */
    public function setId(int $id);

    /**
     * @return mixed
     */
    public function getOrderId();

    /**
     * @param int $orderId
     * @return mixed
     */
    public function setOrderId(int $orderId);

    /**
     * @return mixed
     */
    public function getSourceWaypoint();

    /**
     * @param int $sourceId
     * @return mixed
     */
    public function setSourceWaypoint(int $sourceId);

    /**
     * @return mixed
     */
    public function getSourceMsi();

    /**
     * @param string $sourceCode
     * @return mixed
     */
    public function setSourceMsi(string $sourceCode);

    /**
     * @return mixed
     */
    public function getStatus();

    /**
     * @param string $status
     * @return mixed
     */
    public function setStatus(string $status);

    /**
     * @return mixed
     */
    public function getIncrementId();

    /**
     * @param string $incrementId
     * @return mixed
     */
    public function setIncrementId(string $incrementId);
}