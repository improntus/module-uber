<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2024 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Model\Config\Source\Organization;

use Magento\Framework\Data\OptionSourceInterface;

class OnboardingInviteOption implements OptionSourceInterface
{

    /**
     * @return array[]
     */
    public function toOptionArray(): array
    {
        return [
            ['label' => __('-- Select Onboarding --'), 'value' => ''],
            ['label' => __('Do not send instructions'), 'value' => 'ONBOARDING_INVITE_TYPE_INVALID'],
            ['label' => __('Send instructions'), 'value' => 'ONBOARDING_INVITE_TYPE_EMAIL'],
        ];
    }
}
