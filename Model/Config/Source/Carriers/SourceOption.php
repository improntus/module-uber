<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2024 Improntus (http://www.improntus.com)
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
     * @var array
     */
    protected array $defaultOptions;

    /**
     * @param Data $helper
     * @param array $defaultOptions
     */
    public function __construct(
        Data  $helper,
        array $defaultOptions = []
    ) {
        $this->helper = $helper;
        $this->defaultOptions = $defaultOptions;
    }

    /**
     * @return array[]
     */
    public function toOptionArray(): array
    {
        $sourceOptions = [];

        foreach ($this->defaultOptions as $key => $value) {
            $sourceOptions[] = [
                'value' => $key,
                'label' => $value,
            ];
        }

        /**
         * MSI has Enabled?
         *
         * if ($this->helper->hasMsiInstalled()) {
         * $sourceOptions[] = ['label' => __('MSI'), 'value' => 1];
         * } else {
         * $sourceOptions[] = ['label' => __('Waypoints'), 'value' => 0];
         * }*/

        return $sourceOptions;
    }
}
