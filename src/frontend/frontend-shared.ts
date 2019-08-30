declare const paypal_payments_settings: any;

export class PaypalPayments {

    /**
     * Scroll window to top.
     */
    static scrollTop() {
        jQuery('html, body').animate({scrollTop: 0}, 300);
    }

    /**
     * Set global notices.
     */
    static setNotices(message) {
        console.log('notice', message);
        jQuery('.woocommerce-notices-wrapper:first').html(message);
    }

    /**
     * Make a Ajax request
     * @param action
     * @param data
     */
    static makeRequest(action, data) {
        const settings = {
            async: true,
            crossDomain: true,
            url: PaypalPayments.replaceVars(paypal_payments_settings.paypal_payments_handler_url, {ACTION: action}),
            method: "POST",
            dataType: 'json',
            contentType: 'application/json; charset=utf-8',
            data: JSON.stringify(data),
        };

        return jQuery.ajax(settings);
    }

    /**
     * Show default submit button.
     */
    static showDefaultButton() {
        jQuery('#paypal-payments-button-container .default-submit-button').show();
        jQuery('#paypal-payments-button-container .paypal-submit-button').hide();
    }

    /**
     * Show PayPal submit button.
     */
    static showPaypalButton() {
        jQuery('#paypal-payments-button-container .default-submit-button').hide();
        jQuery('#paypal-payments-button-container .paypal-submit-button').show();
    }

    /**
     * Check if PayPal payments checkbox is selected.
     * @returns {boolean}
     */
    static isPaypalPaymentsSelected() {
        return !!jQuery('#payment_method_paypal-payments-spb-gateway:checked').length;
    }

    /**
     * Trigger update checkout.
     */
    static triggerUpdateCheckout() {
        jQuery(document.body).trigger('update_checkout');
    }

    /**
     * Trigger update cart.
     */
    static triggerUpdateCart() {
        jQuery(document.body).trigger('wc_update_cart');
    }

    /**
     * Submit form.
     */
    static submitForm() {
        jQuery('form.woocommerce-checkout').submit();
    }

    /**
     * Override any text with {VARIABLE}.
     * @param str
     * @param replaces
     */
    static replaceVars(str, replaces) {
        let replacedStr = str;
        for (let property in replaces) {
            if (replaces.hasOwnProperty(property)) {
                replacedStr = replacedStr.replace(new RegExp('{' + property + '}', 'g'), replaces[property]);
            }
        }

        return replacedStr;
    }

}