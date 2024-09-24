<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2024 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Model\Config\Source\Carriers;

use Magento\Framework\Data\OptionSourceInterface;

class VerificationPickupOption implements OptionSourceInterface
{

    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return [
            ['label' => __('-- Select --'), 'value' => ''],
            ['label' => __('Signature'), 'value' => 'signature_requirement'],
            ['label' => __('Barcodes'), 'value' => 'barcodes'],
            ['label' => __('Identification'), 'value' => 'identification'],
            ['label' => __('Picture'), 'value' => 'picture'],
        ];
    }
}
