<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2023 Improntus (http://www.improntus.com/)
 */

namespace Improntus\Uber\Model\ResourceModel\Organization;

use Improntus\Uber\Model\ResourceModel\AbstractCollection;
use Improntus\Uber\Model\Organization;

class Collection extends AbstractCollection
{

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Improntus\Uber\Model\Organization::class,
            \Improntus\Uber\Model\ResourceModel\Organization::class
        );
    }
}

