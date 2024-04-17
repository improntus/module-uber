<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2024 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Api\Data;

interface StoreInterface
{
    public const ENTITY_ID = 'entity_id';
    public const WAYPOINT_ID = 'waypoint_id';
    public const SOURCE_CODE = 'source_code';
    public const HASH = 'hash';

    /**
     * @return mixed
     */
    public function getId();

    /**
     * @param $id
     * @return mixed
     */
    public function setId($id);

    /**
     * @return mixed
     */
    public function getWaypointId();

    /**
     * @param int $waypointId
     * @return mixed
     */
    public function setWaypointId(int $waypointId);

    /**
     * @return mixed
     */
    public function getSourceCode();

    /**
     * @param string $sourceCode
     * @return mixed
     */
    public function setSourceCode(string $sourceCode);

    /**
     * @return mixed
     */
    public function getHash();

    /**
     * @param string $hash
     * @return mixed
     */
    public function setHash(string $hash);
}
