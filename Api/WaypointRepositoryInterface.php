<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2024 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Api;

use Improntus\Uber\Api\Data\WaypointInterface;
use Improntus\Uber\Api\Data\WaypointSearchResultInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

interface WaypointRepositoryInterface
{
    /**
     * @param WaypointInterface $waypoint
     * @return mixed
     */
    public function save(WaypointInterface $waypoint);

    /**
     * @param $id
     * @return WaypointInterface
     * @throws NoSuchEntityException
     */
    public function get($id);

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return WaypointSearchResultInterface
     * @throws LocalizedException
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * @param WaypointInterface $waypoint
     * @return bool true on success
     * @throws LocalizedException
     */
    public function delete(WaypointInterface $waypoint);

    /**
     * @param int $waypointId
     * @return bool true on success
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function deleteById($waypointId);

    /**
     * @return void
     */
    public function clear();
}
