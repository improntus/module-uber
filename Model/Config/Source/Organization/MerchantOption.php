<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2024 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Model\Config\Source\Organization;

use Magento\Framework\Data\OptionSourceInterface;

class MerchantOption implements OptionSourceInterface
{

    /**
     * @return array[]
     */
    public function toOptionArray(): array
    {
        return [
            ['label' => __('-- Select Merchant --'), 'value' => ''],
            ['label' => __('Restaurant'), 'value' => 'MERCHANT_TYPE_RESTAURANT'],
            ['label' => __('Grocery'), 'value' => 'MERCHANT_TYPE_GROCERY'],
            ['label' => __('Liquor'), 'value' => 'MERCHANT_TYPE_LIQUOR'],
            ['label' => __('Retail'), 'value' => 'MERCHANT_TYPE_RETAIL'],
            ['label' => __('Essentials'), 'value' => 'MERCHANT_TYPE_ESSENTIALS'],
            ['label' => __('Pharmacy'), 'value' => 'MERCHANT_TYPE_PHARMACY'],
            ['label' => __('Specialty Food'), 'value' => 'MERCHANT_TYPE_SPECIALTY_FOOD'],
            ['label' => __('Flower shop'), 'value' => 'MERCHANT_TYPE_FLOWER'],
            ['label' => __('Pet shop'), 'value' => 'MERCHANT_TYPE_PET_SUPPLY'],
        ];
    }
}
