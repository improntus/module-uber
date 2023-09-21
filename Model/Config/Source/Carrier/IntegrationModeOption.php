<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2023 Improntus (http://www.improntus.com/)
 */

namespace Improntus\Uber\Model\Config\Source\Carrier;

use Magento\Framework\Data\OptionSourceInterface;

class IntegrationModeOption implements OptionSourceInterface
{

    /**
     * @return array[]
     */
    public function toOptionArray(): array
    {
        return [
            ['label' => __('-- Select Mode --'), 'value' => ''],
            ['label' => __('Production'), 'value' => 1],
            ['label' => __('Sandbox'), 'value' => 0],
        ];
    }
}