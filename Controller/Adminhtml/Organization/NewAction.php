<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2023 Improntus (http://www.improntus.com/)
 */

namespace Improntus\Uber\Controller\Adminhtml\Organization;

use Improntus\Uber\Controller\Adminhtml\AbstractNewAction;

class NewAction extends AbstractNewAction
{

    const ADMIN_RESOURCE = 'Improntus_Uber::organization_create';

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(self::ADMIN_RESOURCE);
    }

}
