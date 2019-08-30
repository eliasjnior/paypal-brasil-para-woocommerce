import {PaypalPayments} from "../frontend-shared";
import {paymentSpb} from "./frontend-spb-api";

declare const paypal: any;
declare const paypal_payments_settings: any;
declare const paypal_payments_spb_settings: any;

class PaypalPaymentsSpb extends PaypalPayments {

    constructor() {
        // Needs to call super.
        super();
        // Store jQuery variables.
        const $body = jQuery('body');
        // Update checkout button when WooCommerce checkout is updated.
        $body.on('updated_checkout', this.updateCheckoutButton);
        // Update checkout button when payment method is changed.
        jQuery('form.woocommerce-checkout').on('change', '[name=payment_method]', this.updateCheckoutButton);
        // Render button when WooCommerce checkout is updated.
        $body.on('updated_checkout', this.renderPayPalButton);
    }

    /**
     * Update the status of checkout button.
     */
    updateCheckoutButton() {
        // If the Paypal Payments is selected show the PayPal button.
        if (PaypalPayments.isPaypalPaymentsSelected()) {
            PaypalPayments.showPaypalButton();
        } else {
            PaypalPayments.showDefaultButton();
        }
    }

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
            createOrder: paymentSpb.create,
            onApprove: paymentSpb.approve,
            onError: paymentSpb.error,
            onCancel: paymentSpb.cancel,
        }).render('#paypal-button');
    }

}

new PaypalPaymentsSpb();