define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/shipping-rates-validation-rules',
        '../model/shipping-rates-validation-rules'
    ],
    function(
        Component,
        defaultShippingRatesValidationRules,
        shippingRatesValidationRules
    ) {
        'use strict';
        defaultShippingRatesValidationRules.registerRules('budbee', shippingRatesValidationRules);
        return Component;
    }
);
