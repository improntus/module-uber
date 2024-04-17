<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2024 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Controller\Adminhtml\Organization;

use Improntus\Uber\Controller\Adminhtml\AbstractIndex;

class Index extends AbstractIndex
{
    public const ADMIN_RESOURCE = 'Improntus_Uber::organizations';

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(self::ADMIN_RESOURCE);
    }
}
