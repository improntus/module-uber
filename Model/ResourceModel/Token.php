<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2024 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Model\ResourceModel;

class Token extends AbstractModel
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('improntus_uber_token', 'entity_id');
    }
}
