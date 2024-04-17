<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2024 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Api\Data;

use Magento\Framework\Api\SearchCriteriaInterface;

/**
 * @api
 */
interface WaypointSearchResultInterface
{
    /**
     * @return WaypointInterface[]
     */
    public function getItems();

    /**
     * @param WaypointInterface[] $items
     * @return $this
     */
    public function setItems(array $items);

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return $this
     */
    public function setSearchCriteria(SearchCriteriaInterface $searchCriteria);

    /**
     * @param int $count
     * @return $this
     */
    public function setTotalCount($count);
}
