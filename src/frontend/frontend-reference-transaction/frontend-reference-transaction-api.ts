import {PaypalPayments} from "../frontend-shared";

declare const paypal_payments_settings: any;
declare const paypal_payments_reference_transaction_settings: any;

export const paymentReferenceTransaction = {

    create: () => {
        return new Promise((resolve, reject) => {
            PaypalPayments.makeRequest('billing-agreement-token', {
                nonce: paypal_payments_settings.nonce,
                user_id: paypal_payments_settings.current_user_id,
            }).done(function (response) {
                resolve(response.data.token_id);
            }).fail(function (jqXHR) {
                reject(jqXHR.responseJSON);
            });
        });
    },

    approve: (data) => {
        // Fill the input data with the billing agreement token.
        jQuery('[name=paypal_payments_billing_agreement_token]').val(data.billingToken);
        // Forte update checkout to create billing agreement.
        PaypalPayments.triggerUpdateCheckout();
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
        PaypalPayments.setNotices(paypal_payments_reference_transaction_settings.cancel_message);
        // Scroll screen to top.
        PaypalPayments.scrollTop();
    }

};