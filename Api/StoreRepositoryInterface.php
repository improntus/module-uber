<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2024 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Api;

use Improntus\Uber\Api\Data\StoreInterface;
use Improntus\Uber\Api\Data\StoreSearchResultInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

interface StoreRepositoryInterface
{
    /**
     * @param StoreInterface $store
     * @return mixed
     */
    public function save(StoreInterface $store);

    /**
     * @param $id
     * @return StoreInterface
     * @throws NoSuchEntityException
     */
    public function get($id);

    /**
     * @param $waypointId
     * @return StoreInterface
     * @throws NoSuchEntityException
     */
    public function getByWaypoint($waypointId);

    /**
     * @param $sourceCode
     * @return StoreInterface
     * @throws NoSuchEntityException
     */
    public function getBySourceCode($sourceCode);

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return StoreSearchResultInterface
     * @throws LocalizedException
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * @param StoreInterface $store
     * @return bool true on success
     * @throws LocalizedException
     */
    public function delete(StoreInterface $store);
}
