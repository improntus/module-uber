<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2024 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class OrganizationRoleOption implements OptionSourceInterface
{

    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return [
            ['label' => __('-- Select --'), 'value' => ''],
            ['label' => __('Admin'), 'value' => 'ROLE_ADMIN'],
            ['label' => __('Employee'), 'value' => 'ROLE_EMPLOYEE'],
        ];
    }
}
