/**
 *  @author Improntus Dev Team
 *  @copyright Copyright (c) 2023 Improntus (http://www.improntus.com)
 */

define([
    'jquery',
    'jquery/ui',
    'Magento_Ui/js/modal/modal',
    'mage/translate'
], function ($) {
    'use strict';
    $.widget('mage.orderUberPod', {
        options: {
            order_id: null,
            modal:  null,
            url: null
        },

        /**
         * @protected
         */
        _create: function () {
            this._prepareDialog();
        },

        /**
         * Open Proof of Delivery Panel
         */
        showPOD: function () {
            var self = this;
            $.ajax({
                showLoader: true,
                url: this.options.url,
                data: {
                    order_id: this.options.order_id
                },
                type: 'GET',
                dataType: 'json'
            }).done(function (response) {
                var podContent = '';
                podContent = response.error ? response.msg : self.prepareContent(response);
                self.options.dialog.html(podContent + '<br>').modal('openModal');
            });
        },

        /**
         * prepareContent
         *
         * Return Verification Data
         * @param verificationData
         * @returns {string}
         */
        prepareContent: function (verificationData) {
            var verificationContent = "<hr><br>";
            let verificationType = Object.keys(verificationData).toString();

            // Set Method Title
            verificationContent += '<h2>' + $.mage.__('Verification Method: ') + `<b>${verificationType}</b>` + '</h2>';

            // Prepare content
            switch (verificationType) {
                case 'signature':
                    verificationContent += '<p>' + $.mage.__('Signer Name: ') + `<b>${verificationData.signature.signer_name}</b>` + '</p>';
                    verificationContent += '<p>' + $.mage.__('Signer Relationship: ') + `<b>${verificationData.signature.signer_relationship}</b>` + '</p>';
                    verificationContent += '<p>' + $.mage.__('Signature:') + '</p>';
                    verificationContent += `<img src='${verificationData.signature.image_url}' width='350' draggable='false'/><br>`;
                    verificationContent += `<a href='${verificationData.signature.image_url}' target="_blank">Open in New Tab</a>`;
                    break;
                case 'pincode':
                    verificationContent += '<p>' + $.mage.__('Pin Code: ') + `<b>${verificationData.pin_code.entered}</b>` + '</p>';
                    break;
                case 'barcode':
                    verificationContent += '<p>' + $.mage.__('Barcode: ') + `<b>${verificationData.barcodes.value}</b>` + '</p>';
                    verificationContent += '<p>' + $.mage.__('Result: ') + `<b>${verificationData.barcodes.scan_result.outcome}</b>` + '</p>';
                    break;
                case 'picture':
                    verificationContent += `<img src='${verificationData.picture.image_url}' width='350' draggable='false'/><br>`;
                    verificationContent += `<a href='${verificationData.picture.image_url}' target="_blank">Open in New Tab</a>`;
                    break;
                default:
                    verificationContent += 'Unknown';
                    break;
            }
            return verificationContent;
        },

        /**
         * Prepare modal
         * @protected
         */
        _prepareDialog: function () {
            var self = this;
            this.options.dialog = $('<div class="ui-dialog-content ui-widget-content"></div>').modal({
                type: 'popup',
                title: $.mage.__('Uber Proof of Delivery'),
                responsive: true,
                buttons: []
            });
        }
    });
    return $.mage.orderUberPod;
});
