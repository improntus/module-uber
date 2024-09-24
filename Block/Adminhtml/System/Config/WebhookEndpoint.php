<?php

/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2024 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\NoSuchEntityException;

class WebhookEndpoint extends Field
{
    public const UBER_WEBHOOK_API_PATH = 'rest/V1/uber/webhook';

    /**
     * @param AbstractElement $element
     *
     * @return string
     * @throws NoSuchEntityException
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_storeManager->getStore()->getBaseUrl() . self::UBER_WEBHOOK_API_PATH;
    }
}
