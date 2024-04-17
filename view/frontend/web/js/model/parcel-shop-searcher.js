define([
    'ko',
    'mage/storage',
    'Magento_Checkout/js/model/quote',
    'jquery'
], function(ko, storage, quote, $) {

    var currentRequest = null;

    return function(wexoShippingData, shippingCountryId) {
        if (currentRequest && currentRequest.abort) {
            currentRequest.abort();
        }
        $('body').trigger('processStart');
        let shippingAddress = quote.shippingAddress();
        let postCode = wexoShippingData.postcode.replace(/\s/g, '');

        return storage.get('/rest/V1/wexo-budbee/get-parcel-shops?' + $.param({
            zip: postCode,
            country_code: shippingCountryId,
        })).always(function() {
            currentRequest = null;
            $('body').trigger('processStop');
        });
    };
});
