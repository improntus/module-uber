<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2023 Improntus (http://www.improntus.com/)
 */

namespace Improntus\Uber\Model\Config\Source\Carriers;

use Improntus\Uber\Helper\Data;
use Magento\Framework\Data\OptionSourceInterface;

class SourceOption implements OptionSourceInterface
{
    /**
     * @var Data $helper
     */
    protected Data $helper;

    /**
     * @param Data $helper
     */
    public function __construct(
        Data $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * @return array[]
     */
    public function toOptionArray(): array
    {
        $sourceOptions = [
            ['label' => __('-- Select Source --'), 'value' => ''],
            ['label' => __('Waypoints'), 'value' => 0]
        ];

        /**
         * MSI has Enabled?
         */
        if ($this->helper->hasMsiInstalled()) {
            $sourceOptions[] = ['label' => __('MSI'), 'value' => 1];
        }

        return $sourceOptions;
    }
}
