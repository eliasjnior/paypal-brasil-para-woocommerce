jQuery(document).ready(function ($) {

    function scrollToTop() {
        $('html, body').animate({scrollTop: 0}, 300);
    }

    $('body').on('updated_checkout', renderButton);
    $('form.woocommerce-checkout').on('change', '[name=payment_method]', changedPaymentMethod);

    if (paypal_payments_settings.is_reference_transaction && paypal_payments_settings.current_user_id) {
        $('body').on('updated_checkout', updateSubmitButton);
        $('form.woocommerce-checkout').on('change', 'input[name=paypal_payments_billing_agreement]', updateSubmitButton);
    }

    function updateSubmitButton() {
        if ($('input[name=paypal_payments_billing_agreement]:checked').val()) {
            hidePayPalButton();
        } else {
            showPayPalButton();
        }
    }

    function changedPaymentMethod() {
        console.log('changedPaymentMethod');
        if (isPayPalSelected()) {
            showPayPalButton();
        } else {
            hidePayPalButton();
        }
    }

    function isPayPalSelected() {
        return !!$('#payment_method_paypal-payments-spb-gateway:checked').length;
    }

    function renderButton() {
        // First check selected option.
        if (isPayPalSelected()) {
            showPayPalButton();
        } else {
            hidePayPalButton();
        }

        // Check if container exists, so we are on spb, not in shortcut.
        const container = document.getElementById('paypal-spb-container');
        if (container) {

            if (paypal_payments_settings.is_reference_transaction) {
                paypal.Buttons({
                    createBillingAgreement: createBillingAgreement,
                    onApprove: onApproveBillingAgreement,
                    onError: function (response) {
                        console.log('onError', response);
                        $('body').trigger('update_checkout');
                        $('.woocommerce-notices-wrapper:first').html(response.data.error_notice);
                        scrollToTop();
                    },
                    onCancel: function (a, b) {
                        alert('Você cancelou o pagamento');
                        $('body').trigger('update_checkout');
                    }
                }).render('#paypal-button');
            } else {
                // Render the button
                paypal.Buttons({
                    createOrder: getPaymentId,
                    onApprove: onApprove,
                    onError: function (a, b) {
                        alert('Houve um erro');
                        console.log('onError', a, b);
                        $('body').trigger('update_checkout');
                    },
                    onCancel: function (a, b) {
                        alert('Você cancelou o pagamento');
                        $('body').trigger('update_checkout');
                    }
                }).render('#paypal-button');
            }
        } else {
            console.log('PayPal Button not found on checkout');
        }
    }

    function createBillingAgreement() {
        return new Promise((resolve, reject) => {
            const settings = {
                async: true,
                crossDomain: true,
                url: "/?wc-api=paypal_payments_handler&action=billing-agreement-token",
                method: "POST",
                dataType: 'json',
                contentType: 'application/json; charset=utf-8',
                data: JSON.stringify({
                    nonce: paypal_payments_settings.nonce,
                    user_id: paypal_payments_settings.current_user_id,
                }),
            };

            $.ajax(settings).done(function (response) {
                resolve(response.data.token_id);
            }).fail(function (jqXHR) {
                reject(jqXHR.responseJSON);
            });
        });
    }

    function onApproveBillingAgreement(data) {
        console.log('onApproveBillingAgreement', data);
        // Fill the input data with the billing agreement token.
        $('[name=paypal_payments_billing_agreement_token]').val(data.billingToken);
        // Forte update checkout to create billing agreement.
        $(document.body).trigger('update_checkout');
    }

    function getPaymentId(data) {
        return new Promise((resolve, reject) => {
            const settings = {
                async: true,
                crossDomain: true,
                url: "/?wc-api=paypal_payments_handler&action=checkout",
                method: "POST",
                dataType: 'json',
                contentType: 'application/json; charset=utf-8',
                data: JSON.stringify({
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
                }),
            };

            $.ajax(settings).done(function (response) {
                resolve(response.data.ec);
            }).fail(function (jqXHR, textStatus) {
                reject(textStatus);
            });
        });
    }

    function onApprove(data) {
        console.log('onApprove', data);
        $('#paypal-spb-fields [name=paypal-payments-spb-order-id]').val(data.orderID);
        $('#paypal-spb-fields [name=paypal-payments-spb-payer-id]').val(data.payerID);
        $('#paypal-spb-fields [name=paypal-payments-spb-pay-id]').val(data.paymentID);
        $('form.woocommerce-checkout').submit();
    }

    function hidePayPalButton() {
        console.log('hidePayPalButton');
        $('#paypal-spb-container .default-submit-button').show();
        $('#paypal-spb-container .paypal-submit-button').hide();
    }

    function showPayPalButton() {
        console.log('showPayPalButton');
        $('#paypal-spb-container .default-submit-button').hide();
        $('#paypal-spb-container .paypal-submit-button').show();
    }

});

jQuery(document).ready(function ($) {

    const $body = $(document.body);
    $body.on('updated_mini_cart', updateMiniCartButton);
    $body.on('updated_shipping_method', updateCartButton);
    initialRender();

    // When page loads.
    function initialRender() {
        $('.shortcut-button').each(function () {
            renderCartButton(this);
        });

        $('.shortcut-button-mini-cart').each(function () {
            renderMiniCartButton(this);
        });
    }

    function updateMiniCartButton() {
        $('.shortcut-button-mini-cart').each(function () {
            renderMiniCartButton(this);
        });
    }

    // When update shippign method.
    function updateCartButton() {
        $('div.cart_totals .shortcut-button').each(function () {
            renderCartButton(this);
        });
    }

    function renderMiniCartButton(element) {
        // Render the button
        paypal.Buttons({
            createOrder: getPaymentIdMiniCart,
            onApprove: onApprove,
        }).render(element);
    }

    function renderCartButton(element) {
        console.log('render cart button');
        let response;
        // Render the button
        paypal.Buttons({
            createOrder: function () {
                if (response) {
                    return response;
                }
                response = getPaymentId();
                return response;
            },
            onApprove: onApprove
        }).render(element);
    }

    function getPaymentIdMiniCart(data) {
        return new Promise((resolve, reject) => {
            const settings = {
                async: true,
                crossDomain: true,
                url: "/?wc-api=paypal_payments_handler&action=shortcut",
                method: "POST",
                dataType: 'json',
                contentType: 'application/json; charset=utf-8',
                data: JSON.stringify({
                    nonce: paypal_payments_settings.nonce,
                }),
            };

            $.ajax(settings).done(function (response) {
                resolve(response.data.ec);
            }).fail(function (jqXHR, textStatus) {
                reject(textStatus);
            });
        });
    }

    function getPaymentId(data) {
        console.log('called get payment id');
        return new Promise((resolve, reject) => {
            const settings = {
                async: true,
                crossDomain: true,
                url: "/?wc-api=paypal_payments_handler&action=shortcut",
                method: "POST",
                dataType: 'json',
                contentType: 'application/json; charset=utf-8',
                data: JSON.stringify({
                    nonce: '',
                }),
            };

            $.ajax(settings).done(function (response) {
                console.log('got payment', response);
                resolve(response.data.ec);
            }).fail(function (jqXHR, textStatus) {
                reject(textStatus);
            });
        });
    }

    function onApprove(data) {
        $('body').block({
            message: null,
            overlayCSS: {
                background: '#fff',
                opacity: 0.6
            }
        });

        window.location = '/finalizar-compra?review-payment=1&pay-id=' + data.paymentID + '&payer-id=' + data.payerID;
    }

});