<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2023 Improntus (http://www.improntus.com/)
 */

namespace Improntus\Uber\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class IntegrationModeOption implements OptionSourceInterface
{

    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return [
            ['label' => __('Production'), 'value' => 1],
            ['label' => __('Sandbox'), 'value' => 0],
        ];
    }
}