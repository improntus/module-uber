/*
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2023 Improntus (http://www.improntus.com/)
 */
define(
    [
        'jquery',
        'underscore'
    ],
    function (
        $,
        _
    ) {
        'use strict';
        let uberModal;
        var mixin = {
            uberValidateAddress: function () {
                // Abre la ventana emergente
                uberModal = window.open("https://www.ubereats.com/gb/delivery-details?ptr=improntus", "Valida tu Domicilio", "width=400,height=300");

                // Escucha los mensajes de la ventana emergente
                window.addEventListener("message", function (event) {
                    console.error("LLEGO");
                    console.error(event);
                    if (event.source === uberModal) {
                        // Maneja el mensaje recibido desde la ventana emergente
                        alert("Mensaje recibido de la ventana emergente: ");
                        console.error(event.data);
                    }
                });
            },
            isUber: function (method) {
                return method.carrier_code === 'uber';
            }
        };
        return function (target) {
            return target.extend(mixin);
        };
    });