<?php
/**
 *  @author Improntus Dev Team
 *  @copyright Copyright (c) 2023 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Model\ResourceModel\OrderShipment;

use Improntus\Uber\Model\ResourceModel\AbstractCollection;
use Improntus\Uber\Model\OrderShipment;

class Collection extends AbstractCollection
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            OrderShipment::class,
            \Improntus\Uber\Model\ResourceModel\OrderShipment::class
        );
    }
}
