<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2024 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Model\Config\Source\Carriers;

use Magento\Framework\Data\OptionSourceInterface;

class SteetAddressLinesOption implements OptionSourceInterface
{
    /**
     * @return array[]
     */
    public function toOptionArray(): array
    {
        return [
            ['label' => __('First Line'), 'value' => 0],
            ['label' => __('Second Line'), 'value' => 1],
            ['label' => __('Third Line'), 'value' => 2],
            ['label' => __('Fourth Line'), 'value' => 3],
        ];
    }
}
