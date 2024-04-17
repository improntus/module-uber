/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2024 Improntus (http://www.improntus.com)
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
            this._super();
            // Update based on value
            switch (value) {
                case 'BILLING_TYPE_DECENTRALIZED':
                    this.updateValidation(true);
                    break;
                case 'BILLING_TYPE_CENTRALIZED':
                    this.updateValidation(false);
                    break;
            }
            return this;
        },
        /**
         * Update Input Validation
         * @param valRequire
         */
        updateValidation(valRequire){
            this._super();

            // Update Form Components
            let formElements = this.addressFields;
            formElements.forEach(function (field) {
                // Get Element
                var formInput = registry.get(`index = ${field}`);

                // Set Prop Required
                formInput.required(valRequire);

                // Set Visibility
                formInput.visible(!!valRequire);
            })

            // Show / Hide Address Collapse
            let addressCollapse = registry.get(`index = ${this.addressCollapse}`);
            addressCollapse.visible(valRequire);

            // Return
            return this;
        }

    });
});