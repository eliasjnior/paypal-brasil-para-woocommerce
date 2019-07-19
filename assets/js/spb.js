jQuery(document).ready(function ($) {

    window.paypalPaymentsSpb = {

        init: function () {
            // Update checkout button when WooCommerce checkout is updated.
            $('body').on('updated_checkout', paypalPaymentsSpb.updateCheckoutButton);
            // Update checkout button when payment method is changed.
            $('form.woocommerce-checkout').on('change', '[name=payment_method]', paypalPaymentsSpb.updateCheckoutButton);
            // Render button when WooCommerce checkout is updated.
            $('body').on('updated_checkout', paypalPaymentsSpb.renderPayPalButton);
        },

        /**
         * Update the status of checkout button.
         */
        updateCheckoutButton: function () {
            console.log('updateCheckoutButton');
            // If the Paypal Payments is selected show the PayPal button.
            if (paypalPayments.isPaypalPaymentsSelected()) {
                paypalPayments.showPaypalButton();
            } else {
                paypalPayments.showDefaultButton();
            }
        },

        /**
         * Render PayPal Button.
         */
        renderPayPalButton() {
            paypal.Buttons({
                createOrder: paypalPaymentsSpb.payment.create,
                onApprove: paypalPaymentsSpb.payment.approve,
                onError: paypalPaymentsSpb.payment.error,
                onCancel: paypalPaymentsSpb.payment.cancel,
            }).render('#paypal-button');
        },

        /**
         * Payment Actions.
         */
        payment: {

            /**
             * Create a new payment.
             */
            create: function () {
                return new Promise((resolve, reject) => {
                    paypalPayments.makeRequest('checkout', {
                        nonce: paypal_payments_settings.nonce,
                        first_name: $('form.woocommerce-checkout [name=billing_first_name]').val(),
                        last_name: $('form.woocommerce-checkout [name=billing_last_name]').val(),
                        country: $('form.woocommerce-checkout [name=billing_country]').val(),
                        address_line_1: $('form.woocommerce-checkout [name=billing_address_1]').val(),
                        address_line_2: $('form.woocommerce-checkout [name=billing_address_2]').val(),
                        number: $('form.woocommerce-checkout [name=billing_number]').val(),
                        city: $('form.woocommerce-checkout [name=billing_city]').val(),
                        state: $('form.woocommerce-checkout [name=billing_state]').val(),
                        neighborhood: $('form.woocommerce-checkout [name=billing_neighborhood]').val(),
                        postcode: $('form.woocommerce-checkout [name=billing_postcode]').val(),
                        phone: $('form.woocommerce-checkout [name=billing_phone]').val(),
                    }).done(function (response) {
                        resolve(response.data.ec);
                    }).fail(function (jqXHR) {
                        reject(jqXHR.responseJSON);
                    });
                });
            },

            /**
             * Approve the payment.
             */
            approve: function (data) {
                $('#paypal-spb-fields [name=paypal-payments-spb-order-id]').val(data.orderID);
                $('#paypal-spb-fields [name=paypal-payments-spb-payer-id]').val(data.payerID);
                $('#paypal-spb-fields [name=paypal-payments-spb-pay-id]').val(data.paymentID);
                paypalPayments.submitForm();
            },

            /**
             * When have some error.
             */
            error: function (response) {
                // Update the checkout to render button again.
                paypalPayments.triggerUpdateCheckout();
                // Only do that if there's a JSON response.
                if (response) {
                    // Add the notices.
                    paypalPayments.setNotices(response.data.error_notice);
                    // Scroll screen to top.
                    paypalPayments.scrollTop();
                }
            },

            /**
             * When cancel approval.
             */
            cancel: function () {
                // Update the checkout to render button again.
                paypalPayments.triggerUpdateCheckout();
                // Add notices.
                paypalPayments.setNotices(paypal_payments_spb_settings.cancel_message);
                // Scroll screen to top.
                paypalPayments.scrollTop();
            }

        }

    };

    paypalPaymentsSpb.init();

});