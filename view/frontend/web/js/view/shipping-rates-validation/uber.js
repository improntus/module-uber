/*
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2023 Improntus (http://www.improntus.com/)
 */

define([
    'uiComponent',
    'Magento_Checkout/js/model/shipping-rates-validator',
    'Magento_Checkout/js/model/shipping-rates-validation-rules',
    '../../model/shipping-rates-validator/uber',
    '../../model/shipping-rates-validation-rules/uber'
], function (
    Component,
    defaultShippingRatesValidator,
    defaultShippingRatesValidationRules,
    uberShippingRatesValidator,
    uberShippingRatesValidationRules
) {
    'use strict';

    defaultShippingRatesValidator.registerValidator('uber', uberShippingRatesValidator);
    defaultShippingRatesValidationRules.registerRules('uber', uberShippingRatesValidationRules);

    return Component;
});
