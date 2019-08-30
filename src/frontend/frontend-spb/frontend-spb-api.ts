import {PaypalPayments} from "../frontend-shared";

declare const paypal_payments_settings: any;
declare const paypal_payments_spb_settings: any;

export const paymentSpb = {

    create: () => {
        return new Promise((resolve, reject) => {
            PaypalPayments.makeRequest('checkout', {
                nonce: paypal_payments_settings.nonce,
                first_name: jQuery('form.woocommerce-checkout [name=billing_first_name]').val(),
                last_name: jQuery('form.woocommerce-checkout [name=billing_last_name]').val(),
                country: jQuery('form.woocommerce-checkout [name=billing_country]').val(),
                address_line_1: jQuery('form.woocommerce-checkout [name=billing_address_1]').val(),
                address_line_2: jQuery('form.woocommerce-checkout [name=billing_address_2]').val(),
                number: jQuery('form.woocommerce-checkout [name=billing_number]').val(),
                city: jQuery('form.woocommerce-checkout [name=billing_city]').val(),
                state: jQuery('form.woocommerce-checkout [name=billing_state]').val(),
                neighborhood: jQuery('form.woocommerce-checkout [name=billing_neighborhood]').val(),
                postcode: jQuery('form.woocommerce-checkout [name=billing_postcode]').val(),
                phone: jQuery('form.woocommerce-checkout [name=billing_phone]').val(),
            }).done(function (response) {
                resolve(response.data.ec);
            }).fail(function (jqXHR) {
                reject(jqXHR.responseJSON);
            });
        });
    },

    approve: (data) => {
        jQuery('#paypal-spb-fields [name=paypal-payments-spb-order-id]').val(data.orderID);
        jQuery('#paypal-spb-fields [name=paypal-payments-spb-payer-id]').val(data.payerID);
        jQuery('#paypal-spb-fields [name=paypal-payments-spb-pay-id]').val(data.paymentID);
        PaypalPayments.submitForm();
    },

    error: (response) => {
        // Update the checkout to render button again.
        PaypalPayments.triggerUpdateCheckout();
        // Only do that if there's a JSON response.
        if (response) {
            // Add the notices.
            PaypalPayments.setNotices(response.data.error_notice);
            // Scroll screen to top.
            PaypalPayments.scrollTop();
        }
    },

    cancel: () => {
        // Update the checkout to render button again.
        PaypalPayments.triggerUpdateCheckout();
        // Add notices.
        PaypalPayments.setNotices(paypal_payments_spb_settings.cancel_message);
        // Scroll screen to top.
        PaypalPayments.scrollTop();
    }

};