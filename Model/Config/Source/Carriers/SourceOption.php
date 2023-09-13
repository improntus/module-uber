<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2023 Improntus (http://www.improntus.com/)
 */

namespace Improntus\Uber\Model\Config\Source\Carriers;

use Magento\Framework\Data\OptionSourceInterface;

class SourceOption implements OptionSourceInterface
{

    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return [
            ['label' => __('-- Select Source --'), 'value' => ''],
            ['label' => __('MSI'), 'value' => 1],
            ['label' => __('Waypoints'), 'value' => 0],
        ];
    }
}