<?php
/**
 *  @author Improntus Dev Team
 *  @copyright Copyright (c) 2023 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Model\ResourceModel\Waypoint;

use Improntus\Uber\Model\ResourceModel\AbstractCollection;
use Improntus\Uber\Model\Waypoint;

class Collection extends AbstractCollection
{

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            Waypoint::class,
            \Improntus\Uber\Model\ResourceModel\Waypoint::class
        );
    }

    /**
     * @return Collection
     */
    public function getActiveList()
    {
        return $this->addFieldToFilter('active', ['eq' => true]);
    }
}

