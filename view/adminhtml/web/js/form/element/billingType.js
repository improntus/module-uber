/*
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2023 Improntus (http://www.improntus.com/)
 */

define([
    'Magento_Ui/js/form/element/select',
    'uiRegistry'
], function (Select, registry) {
    'use strict';
    return Select.extend({
        defaults: {
            addressFields: ['street', 'postcode', 'city', 'state', 'country'],
            addressCollapse: 'address_data',
        },
        /**
         * Component initialization
         */
        initialize: function () {
            this._super();
            this.onUpdate(this.value());
            return this;
        },
        /**
         * On value change handler.
         * @param {String} value
         */
        onUpdate: function (value) {
            // Get Address Collapse
            let addressCollapse = registry.get(`index = ${this.addressCollapse}`);
            // Update based on value
            if (value == "BILLING_TYPE_DECENTRALIZED") {
                addressCollapse.visible(true);
                this.updateValidation(true);
            } else {
                addressCollapse.visible(false);
                this.updateValidation(false);
            }
            return this;
        },
        /**
         * Update Input Validation
         * @param valRequire
         */
        updateValidation(valRequire){
            let formElements = this.addressFields;
            formElements.forEach(function (field) {
                registry.get(`index = ${field}`).required(valRequire).value(valRequire ? '' : '-');
            })
            return this;
        }

    });
});