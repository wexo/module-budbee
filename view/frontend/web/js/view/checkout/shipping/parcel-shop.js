define([
    'Wexo_Shipping/js/view/parcel-shop',
    'ko',
    'mage/translate',
    'underscore',
    'Wexo_Budbee/js/model/parcel-shop-searcher',
    'Magento_Checkout/js/model/shipping-service'
], function (AbstractParcelShop, ko, $t, _, parcelShopSearcher, shippingService) {

    return AbstractParcelShop.extend({
        defaults: {
            parcelShopSearcher: parcelShopSearcher,
            label: $t('Find a Budbee Box'),
            modalItemTemplate: 'Wexo_Budbee/parcel-shop/parcel-shop-entry',
            chosenItemTemplate: 'Wexo_Budbee/parcel-shop/parcel-shop-entry',
            budbeeConfig: window.checkoutConfig.budbee_config
        },

        initialize: function () {
            this._super();

            this.shippingPostcode.subscribe(function (newVal) {
                if (!this.shippingMethod()) {
                    this.source.set('wexoShippingData.postcode', newVal);
                }
            }, this);

            return this;
        },

        _saveParcelShop: function () {
            this._super();
        },

        /**
         * @returns {*}
         */
        getPopupText: function () {
            return ko.pureComputed(function () {
                return $t('%1 boxes in postcode <u>%2</u>')
                    .replace('%1', this.parcelShops().length)
                    .replace('%2', this.wexoShippingData().postcode);
            }, this);
        },

        getCompanyName: function(company_name, time_label) {
            return this.budbeeConfig.box_interval_is_dynamic ? time_label : company_name;
        },

        /**
         * @param parcelShop
         * @returns {string}
         */
        formatOpeningHours: function (parcelShop) {
            try {
                // this is nessecary as Magento generates "translation maps" for JS translations. For some reason a
                // value has to be specificly declared to be included in said mapping. This means dynamic content
                // can not be translated if the expected translation has not been used elsewhere in a static reference.
                let staticReference = [$t("Monday"), $t("Tuesday"), $t("Wednesday"), $t("Thursday"), $t("Friday"), $t("Saturday"), $t("Sunday")];
                console.log(parcelShop);
                if (parcelShop.opening_hours.length && parcelShop.opening_hours) {
                    var openingHours = JSON.parse(parcelShop.opening_hours);
                    var formattedHours = [];
                    openingHours.forEach(function (openingHour) {
                        openingHour.day = $t(openingHour.day);
                        formattedHours.push(openingHour);
                    });
                    return '<table>' + _.map(formattedHours, function (day) {
                        return '<tr><th>%1</th><td>%2 - %3</td></tr>'.replace('%1', day.day)
                            .replace('%2', day.opens_at)
                            .replace('%3', day.closes_at);
                    }).join('') + '</table>';
                }
                return '';
            } catch (e) {
                return '';
            }
        },
    });
});
