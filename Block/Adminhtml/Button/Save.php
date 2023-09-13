<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2023 Improntus (http://www.improntus.com/)
 */

namespace Improntus\Uber\Block\Adminhtml\Button;

use Magento\Framework\Phrase;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class Save implements ButtonProviderInterface
{
    /**
     * @var string
     */
    private $label;

    /**
     * @param string $label
     */
    public function __construct(
        string $label = ''
    ) {
        $this->label = $label;
    }

    /**
     * @return array
     */
    public function getButtonData()
    {
        return [
            'label' => $this->getLabel(),
            'class' => 'uber-button',
            'data_attribute' => [
                'mage-init' => [
                    'button' => ['event' => 'save']
                ],
                'form-role' => 'save',
            ],
            'sort_order' => 40,
        ];
    }

    /**
     * @return Phrase|null|string
     */
    private function getLabel()
    {
        if (empty($this->label)) {
            return __('Save');
        }
        return $this->label;
    }
}
