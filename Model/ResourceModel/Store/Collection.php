<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2024 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Model\ResourceModel\Store;

use Improntus\Uber\Model\ResourceModel\AbstractCollection;
use Improntus\Uber\Model\Store;

class Collection extends AbstractCollection
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            Store::class,
            \Improntus\Uber\Model\ResourceModel\Store::class
        );
    }
}
