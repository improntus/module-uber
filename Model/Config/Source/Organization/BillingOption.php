<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2024 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Model\Config\Source\Organization;

use Magento\Framework\Data\OptionSourceInterface;

class BillingOption implements OptionSourceInterface
{

    /**
     * @return array[]
     */
    public function toOptionArray(): array
    {
        return [
            ['label' => __('-- Select Billing --'), 'value' => ''],
            ['label' => __('Centralized'), 'value' => 'BILLING_TYPE_CENTRALIZED'],
            ['label' => __('Decentralized'), 'value' => 'BILLING_TYPE_DECENTRALIZED'],
        ];
    }
}
