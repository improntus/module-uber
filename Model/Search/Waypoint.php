<?php
/**
 *  @author Improntus Dev Team
 *  @copyright Copyright (c) 2023 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Model\Search;

use Magento\Framework\DataObject;

/**
 * @method Waypoint setQuery(string $query)
 * @method string|null getQuery()
 * @method bool hasQuery()
 * @method Waypoint setStart(int $startPosition)
 * @method int|null getStart()
 * @method bool hasStart()
 * @method Waypoint setLimit(int $limit)
 * @method int|null getLimit()
 * @method bool hasLimit()
 * @method Waypoint setResults(array $results)
 * @method array getResults()
 */
class Waypoint extends DataObject
{
    /**
     * @return $this
     */
    public function load()
    {
        $result = [];
        $this->setResults($result);
        return $this;
    }
}
