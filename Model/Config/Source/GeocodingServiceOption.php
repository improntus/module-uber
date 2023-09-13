<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2023 Improntus (http://www.improntus.com/)
 */

namespace Improntus\Uber\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class GeocodingServiceOption implements OptionSourceInterface
{

    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return [
            ['label' => __('-- Select --'), 'value' => ''],
            ['label' => __('Google Maps'), 'value' => 1],
            ['label' => __('Nominatim (OpenStreetMap)'), 'value' => 0],
        ];
    }
}