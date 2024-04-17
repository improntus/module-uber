/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2024 Improntus (http://www.improntus.com)
 */

define([], function () {
    'use strict';

    return {
        /**
         * @return {Object}
         */
        getRules: function () {
            return {
                'postcode': {
                    'required': true
                }
            };
        }
    };
});
