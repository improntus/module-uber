<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2024 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Model\Config\Source\Waypoint;

use Magento\Framework\Data\OptionSourceInterface;

class TimeOption implements OptionSourceInterface
{
    public const NO_AVAILABLE_VALUE = 99;

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => self::NO_AVAILABLE_VALUE, 'label' => __('No Available')],
            ['value' => '00', 'label' => '00:00'],
            ['value' => '01', 'label' => '01:00'],
            ['value' => '02', 'label' => '02:00'],
            ['value' => '03', 'label' => '03:00'],
            ['value' => '04', 'label' => '04:00'],
            ['value' => '05', 'label' => '05:00'],
            ['value' => '06', 'label' => '06:00'],
            ['value' => '07', 'label' => '07:00'],
            ['value' => '08', 'label' => '08:00'],
            ['value' => '09', 'label' => '09:00'],
            ['value' => '10', 'label' => '10:00'],
            ['value' => '11', 'label' => '11:00'],
            ['value' => '12', 'label' => '12:00'],
            ['value' => '13', 'label' => '13:00'],
            ['value' => '14', 'label' => '14:00'],
            ['value' => '15', 'label' => '15:00'],
            ['value' => '16', 'label' => '16:00'],
            ['value' => '17', 'label' => '17:00'],
            ['value' => '18', 'label' => '18:00'],
            ['value' => '19', 'label' => '19:00'],
            ['value' => '20', 'label' => '20:00'],
            ['value' => '21', 'label' => '21:00'],
            ['value' => '22', 'label' => '22:00'],
            ['value' => '23', 'label' => '23:00'],
        ];
    }
}
