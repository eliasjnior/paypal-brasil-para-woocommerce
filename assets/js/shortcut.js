jQuery(document).ready(function ($) {

    window.paypalPaymentsShortcut = {

        init: function () {
            // Render cart button.
            $('body')
                .on('updated_wc_div', paypalPaymentsShortcut.renderCartButton)
                .on('updated_mini_cart', paypalPaymentsShortcut.renderMiniCartButton);
            // Render cart for the first time.
            paypalPaymentsShortcut.renderCartButton();
            paypalPaymentsShortcut.renderMiniCartButton();
        },

        renderMiniCartButton: function () {
            const $elements = $('.shortcut-button-mini-cart');
            $elements.each(function () {
                paypal.Buttons({
                    locale: 'pt_BR',
                    style: {
                        size: 'responsive',
                        color: paypal_payments_settings.style.color,
                        shape: paypal_payments_settings.style.format,
                        label: 'buynow',
                    },
                    createOrder: paypalPaymentsShortcut.paymentMiniCart.create,
                    onApprove: paypalPaymentsShortcut.paymentMiniCart.approve,
                    onError: paypalPaymentsShortcut.paymentMiniCart.error,
                    onCancel: paypalPaymentsShortcut.paymentMiniCart.cancel,
                }).render(this);
            });
        },

        renderCartButton: function () {
            const $elements = $('.wc-proceed-to-checkout .shortcut-button');
            $elements.each(function () {
                console.log('criou aqui', paypal_payments_settings.style.color);
                paypal.Buttons({
                    locale: 'pt_BR',
                    style: {
                        size: 'responsive',
                        color: paypal_payments_settings.style.color,
                        shape: paypal_payments_settings.style.format,
                        label: 'buynow',
                    },
                    createOrder: paypalPaymentsShortcut.paymentCart.create,
                    onApprove: paypalPaymentsShortcut.paymentCart.approve,
                    onError: paypalPaymentsShortcut.paymentCart.error,
                    onCancel: paypalPaymentsShortcut.paymentCart.cancel,
                }).render(this);
            });
        },

        paymentMiniCart: {
            create: function () {
                return new Promise((resolve, reject) => {
                    paypalPayments.makeRequest('shortcut', {
                        nonce: paypal_payments_settings.nonce,
                    }).done(function (response) {
                        resolve(response.data.ec);
                    }).fail(function (jqXHR) {
                        reject(jqXHR.responseJSON);
                    });
                });
            },
            approve: function (data) {
                // Redirect to page review.
                window.location = paypalPayments.replaceVars(paypal_payments_settings.checkout_review_page_url, {
                    PAY_ID: data.paymentID,
                    PAYER_ID: data.payerID,
                });
            },
            error: function (response) {
                // Only do that if there's a JSON response.
                if (response) {
                    // Add the notices.
                    paypalPayments.setNotices(response.data.error_notice);
                    // Scroll screen to top.
                    paypalPayments.scrollTop();
                }
            },
            cancel: function () {
                // Add notices.
                paypalPayments.setNotices(paypal_payments_spb_settings.cancel_message);
                // Scroll screen to top.
                paypalPayments.scrollTop();
            },
        },

        paymentCart: {
            create: function () {
                return new Promise((resolve, reject) => {
                    paypalPayments.makeRequest('shortcut-cart', {
                        nonce: paypal_payments_settings.nonce,
                    }).done(function (response) {
                        resolve(response.data.ec);
                    }).fail(function (jqXHR) {
                        reject(jqXHR.responseJSON);
                    });
                });
            },
            approve: function (data) {
                // Redirect to page review.
                window.location = paypalPayments.replaceVars(paypal_payments_settings.checkout_review_page_url, {
                    PAY_ID: data.paymentID,
                    PAYER_ID: data.payerID,
                });
            },
            error: function (response) {
                // Only do that if there's a JSON response.
                if (response) {
                    // Add the notices.
                    paypalPayments.setNotices(response.data.error_notice);
                    // Scroll screen to top.
                    paypalPayments.scrollTop();
                }
            },
            cancel: function () {
                // Update the cart to render button again.
                paypalPayments.triggerUpdateCart();
                // Add notices.
                paypalPayments.setNotices(paypal_payments_spb_settings.cancel_message);
                // Scroll screen to top.
                paypalPayments.scrollTop();
            },
        },
    };

    if (paypalPayments.checkSdkLoaded()) {
        paypalPaymentsShortcut.init();
    }

});