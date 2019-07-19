jQuery(document).ready(function ($) {

    window.paypalPayments = {

        /**
         * Scroll window to top.
         */
        scrollTop: function () {
            $('html, body').animate({scrollTop: 0}, 300);
        },

        /**
         * Set global notices.
         */
        setNotices: function (message) {
            console.log('notice', message);
            $('.woocommerce-notices-wrapper:first').html(message);
        },

        /**
         * Make a Ajax request
         * @param action
         * @param data
         */
        makeRequest: function (action, data) {
            const settings = {
                async: true,
                crossDomain: true,
                url: paypalPayments.replaceVars(paypal_payments_settings.paypal_payments_handler_url, {ACTION: action}),
                method: "POST",
                dataType: 'json',
                contentType: 'application/json; charset=utf-8',
                data: JSON.stringify(data),
            };

            return $.ajax(settings);
        },

        /**
         * Show default submit button.
         */
        showDefaultButton: function () {
            $('#paypal-payments-button-container .default-submit-button').show();
            $('#paypal-payments-button-container .paypal-submit-button').hide();
        },

        /**
         * Show PayPal submit button.
         */
        showPaypalButton: function () {
            $('#paypal-payments-button-container .default-submit-button').hide();
            $('#paypal-payments-button-container .paypal-submit-button').show();
        },

        /**
         * Check if PayPal payments checkbox is selected.
         * @returns {boolean}
         */
        isPaypalPaymentsSelected: function () {
            return !!$('#payment_method_paypal-payments-spb-gateway:checked').length;
        },

        /**
         * Trigger update checkout.
         */
        triggerUpdateCheckout: function () {
            $(document.body).trigger('update_checkout');
        },

        /**
         * Trigger update cart.
         */
        triggerUpdateCart: function () {
            $(document.body).trigger('wc_update_cart');
        },

        /**
         * Submit form.
         */
        submitForm: function () {
            $('form.woocommerce-checkout').submit();
        },

        /**
         * Override any text with {VARIABLE}.
         * @param str
         * @param replaces
         */
        replaceVars: function (str, replaces) {
            let replacedStr = str;
            for (let property in replaces) {
                if (replaces.hasOwnProperty(property)) {
                    replacedStr = replacedStr.replace(new RegExp('{' + property + '}', 'g'), replaces[property]);
                }
            }

            return replacedStr;
        }

    };

});