<?php
/**
 *  @author Improntus Dev Team
 *  @copyright Copyright (c) 2023 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Model\ResourceModel;

class Organization extends AbstractModel
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('improntus_uber_organization', 'entity_id');
    }
}
