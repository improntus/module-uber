<?php
/**
 *  @author Improntus Dev Team
 *  @copyright Copyright (c) 2023 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Model\Config\Source\Product;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

class SizeOption extends AbstractSource
{

    /**
     * @return array[]
     */
    public function getAllOptions(): array
    {
        return [
            ['label' => __('Small'), 'value' => 'small'],
            ['label' => __('Medium'), 'value' => 'medium'],
            ['label' => __('Large'), 'value' => 'large'],
            ['label' => __('Xlarge'), 'value' => 'xlarge']
        ];
    }
}