<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2024 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Controller\Adminhtml\Organization;

use Improntus\Uber\Controller\Adminhtml\AbstractDelete;

class Delete extends AbstractDelete
{
    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Improntus_Uber::organization_delete');
    }
}
