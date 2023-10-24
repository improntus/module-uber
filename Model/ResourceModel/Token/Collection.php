<?php
/**
 *  @author Improntus Dev Team
 *  @copyright Copyright (c) 2023 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Model\ResourceModel\Token;

use Improntus\Uber\Model\ResourceModel\AbstractCollection;
use Improntus\Uber\Model\Token;

class Collection extends AbstractCollection
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            Token::class,
            \Improntus\Uber\Model\ResourceModel\Token::class
        );
    }
}
