import {PaypalPayments} from "../frontend-shared";
import {paymentReferenceTransaction} from "./frontend-reference-transaction-api";

declare const paypal: any;
declare const paypal_payments_settings: any;
declare const paypal_payments_reference_transaction_settings: any;

class PaypalPaymentsReferenceTransaction extends PaypalPayments {

    constructor() {
        super();
        const $body = jQuery('body');
        const $form = jQuery('form.woocommerce-checkout');
        // Update checkout button when WooCommerce checkout is updated.
        $body.on('updated_checkout', this.updateCheckoutButton);
        // Update checkout button when payment method is changed.
        $form.on('change', '[name=payment_method]', this.updateCheckoutButton);
        // Update checkout button when billing agreement action is changed.
        $form.on('change', '.paypal-payments-billing-agreement-option-radio', this.updateCheckoutButton);
        // Render button when WooCommerce checkout is updated.
        $body.on('updated_checkout', this.renderPayPalButton);
        // Insert uuid
        this.insertUuid();
        $body.on('updated_checkout', this.insertUuid);
    }

    /**
     * Insert UUID when checkout is updated.
     */
    insertUuid() {
        const uuid = paypal_payments_reference_transaction_settings.uuid;
        const $container = jQuery('#paypal-payments-uuid');

        $container.val(uuid);
    }

    /**
     * Update the status of checkout button.
     */
    updateCheckoutButton() {
        // If the Paypal Payments is selected and is to create a new billing agreement, show the PayPal button.
        if (PaypalPayments.isPaypalPaymentsSelected() && PaypalPaymentsReferenceTransaction.isCreateBillingAgreementSelected()) {
            PaypalPayments.showPaypalButton();
        } else { // Show the default button if is to process billing agreement or isn't selected PayPal.
            PaypalPayments.showDefaultButton();
        }
    }

    /**
     * Get if create billing agreement radio is selected.
     */
    static isCreateBillingAgreementSelected() {
        return !jQuery('.paypal-payments-billing-agreement-option-radio:checked').val();
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
            createBillingAgreement: paymentReferenceTransaction.create,
            onApprove: paymentReferenceTransaction.approve,
            onError: paymentReferenceTransaction.error,
            onCancel: paymentReferenceTransaction.cancel,
        }).render('#paypal-button');
    }

}

new PaypalPaymentsReferenceTransaction();