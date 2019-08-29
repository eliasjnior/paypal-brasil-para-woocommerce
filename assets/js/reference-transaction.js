jQuery(document).ready(function ($) {

    window.paypalPaymentsReferenceCheckout = {

        init: function () {
            // Update checkout button when WooCommerce checkout is updated.
            $('body').on('updated_checkout', paypalPaymentsReferenceCheckout.updateCheckoutButton);
            // Update checkout button when payment method is changed.
            $('form.woocommerce-checkout').on('change', '[name=payment_method]', paypalPaymentsReferenceCheckout.updateCheckoutButton);
            // Update checkout button when billing agreement action is changed.
            $('form.woocommerce-checkout').on('change', '.paypal-payments-billing-agreement-option-radio', paypalPaymentsReferenceCheckout.updateCheckoutButton);
            // Render button when WooCommerce checkout is updated.
            $('body').on('updated_checkout', paypalPaymentsReferenceCheckout.renderPayPalButton);
            // Insert uuid
            paypalPaymentsReferenceCheckout.insertUuid();
            $('body').on('updated_checkout', paypalPaymentsReferenceCheckout.insertUuid);
        },

        /**
         * Insert UUID when checkout is updated.
         */
        insertUuid: function () {
            const uuid = paypal_payments_reference_transaction_settings.uuid;
            const $container = $('#paypal-payments-uuid');

            $container.val(uuid);
        },

        /**
         * Update the status of checkout button.
         */
        updateCheckoutButton: function () {
            // If the Paypal Payments is selected and is to create a new billing agreement, show the PayPal button.
            if (paypalPayments.isPaypalPaymentsSelected() && paypalPaymentsReferenceCheckout.isCreateBillingAgreementSelected()) {
                paypalPayments.showPaypalButton();
            } else { // Show the default button if is to process billing agreement or isn't selected PayPal.
                paypalPayments.showDefaultButton();
            }
        },

        /**
         * Get if create billing agreement radio is selected.
         */
        isCreateBillingAgreementSelected() {
            return !$('.paypal-payments-billing-agreement-option-radio:checked').val();
        },

        /**
         * Render PayPal Button.
         */
        renderPayPalButton() {
            paypal.Buttons({
                locale: 'pt_BR',
                style: {
                    size: 'responsive',
                    color: paypal_payments_settings.style.color,
                    shape: paypal_payments_settings.style.format,
                    label: 'pay',
                },
                createBillingAgreement: paypalPaymentsReferenceCheckout.billingAgreement.create,
                onApprove: paypalPaymentsReferenceCheckout.billingAgreement.approve,
                onError: paypalPaymentsReferenceCheckout.billingAgreement.error,
                onCancel: paypalPaymentsReferenceCheckout.billingAgreement.cancel,
            }).render('#paypal-button');
        },

        /**
         * Billing agreement actions.
         */
        billingAgreement: {

            /**
             * Create a new token.
             */
            create: function () {
                return new Promise((resolve, reject) => {
                    paypalPayments.makeRequest('billing-agreement-token', {
                        nonce: paypal_payments_settings.nonce,
                        user_id: paypal_payments_settings.current_user_id,
                    }).done(function (response) {
                        resolve(response.data.token_id);
                    }).fail(function (jqXHR) {
                        reject(jqXHR.responseJSON);
                    });
                });
            },

            /**
             * When user approves the token.
             */
            approve: function (data) {
                // Fill the input data with the billing agreement token.
                $('[name=paypal_payments_billing_agreement_token]').val(data.billingToken);
                // Forte update checkout to create billing agreement.
                paypalPayments.triggerUpdateCheckout();
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
                paypalPayments.setNotices(paypal_payments_reference_transaction_settings.cancel_message);
                // Scroll screen to top.
                paypalPayments.scrollTop();
            }

        }

    };

    // Init reference checkout.
    if (paypalPayments.checkSdkLoaded()) {
        paypalPaymentsReferenceCheckout.init();
    }

});