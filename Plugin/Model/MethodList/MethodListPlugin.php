<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2024 Improntus (http://www.improntus.com)
 */

namespace Improntus\Uber\Plugin\Model\MethodList;

use Improntus\Uber\Helper\Data;
use Improntus\Uber\Model\Carrier\Uber;
use Magento\OfflinePayments\Model\Cashondelivery;
use Magento\Payment\Model\MethodInterface;
use Magento\Payment\Model\MethodList;
use Magento\Quote\Api\Data\CartInterface;

class MethodListPlugin
{
    protected const CARRIER_CODE = Uber::CARRIER_CODE . '_' . Uber::CARRIER_CODE;

    protected const PAYMENT_COD_CODE = Cashondelivery::PAYMENT_METHOD_CASHONDELIVERY_CODE;

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
     * afterGetAvailableMethods
     * @param MethodList $subject
     * @param MethodInterface[] $result
     * @param CartInterface|null $quote
     * @return MethodInterface[]
     */
    public function afterGetAvailableMethods(
        MethodList $subject,
        array $availableMethods,
        ?CartInterface $quote = null
    ): array {
        // Validate Shipping Method
        if ($this->getShippingMethodFromQuote($quote) !== self::CARRIER_CODE) {
            return $availableMethods;
        }

        $isCashOnDeliveryEnabled = $this->helper->isCashOnDeliveryEnabled();
        $isPaymentCashOnDeliveryEnabled = $this->helper->isPaymentCashOnDeliveryEnabled();
        if (!$isCashOnDeliveryEnabled || !$isPaymentCashOnDeliveryEnabled) {
            foreach ($availableMethods as $key => $method) {
                if ($method->getCode() === self::PAYMENT_COD_CODE) {
                    // Unset Cash On Delivery
                    unset($availableMethods[$key]);
                }
            }
        }

        return $availableMethods;
    }

    /**
     * getShippingMethodFromQuote
     * @param CartInterface $quote
     * @return string
     */
    private function getShippingMethodFromQuote($quote): string
    {
        return $quote->getShippingAddress()->getShippingMethod() ?? '';
    }
}
