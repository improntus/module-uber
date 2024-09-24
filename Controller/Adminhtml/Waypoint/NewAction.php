<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2024 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Controller\Adminhtml\Waypoint;

use Improntus\Uber\Controller\Adminhtml\AbstractNewAction;

class NewAction extends AbstractNewAction
{
    public const ADMIN_RESOURCE = 'Improntus_Uber::waypoint_create';

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(self::ADMIN_RESOURCE);
    }
}
