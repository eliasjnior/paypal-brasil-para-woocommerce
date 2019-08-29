<?php

/** @var PayPal_Payments_SPB_Gateway $this */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get user billing agreement details.
if ( is_user_logged_in() ) {
	$user_billing_agreement_id         = get_user_meta( get_current_user_id(), 'paypal_payments_billing_agreement_id', true );
	$user_billing_agreement_payer_info = get_user_meta( get_current_user_id(), 'paypal_payments_billing_agreement_payer_info', true );
} else {
	$user_billing_agreement_id         = WC()->session->get( 'paypal_payments_billing_agreement_id' );
	$user_billing_agreement_payer_info = WC()->session->get( 'paypal_payments_billing_agreement_payer_info' );
}
$has_billing_agreement = false;
?>
<ul class="paypal-payments-billing-agreement-options">
    <!-- USER DEFAULT BILLING AGREEMENT -->
	<?php
	try {
		$calculated_financing = $this->api->get_calculate_financing( $user_billing_agreement_id, WC()->cart->get_totals()['total'] );
		?>
        <li>
            <label>
                <input type="radio"
                       class="paypal-payments-billing-agreement-option-radio"
                       name="paypal_payments_billing_agreement"
                       value="<?php echo esc_attr( $user_billing_agreement_id ); ?>"
                       checked="checked">
				<?php echo sprintf( __( 'Conta PayPal vinculada: %s', 'paypal-payments' ), '<strong>' . $user_billing_agreement_payer_info['email'] . '</strong>' ); ?>

                <select class="paypal-payments-billing-agreement-financing"
                        name="paypal_payments_billing_agreement_installment">
					<?php foreach ( $calculated_financing['financing_options'][0]['qualifying_financing_options'] as $financing ): ?>
                        <option value="<?php echo esc_attr( json_encode( paypal_payments_prepare_installment_option( $financing ), JSON_UNESCAPED_SLASHES ) ); ?>">
							<?php echo sprintf( '%dx de %s (Total: %s)', $financing['credit_financing']['term'], wc_price( $financing['monthly_payment']['value'] ), wc_price( $financing['total_cost']['value'] ) ); ?>
							<?php if ( isset( $financing['discount_amount'] ) && $discount = floatval( $financing['discount_amount']['value'] ) ): ?>
								<?php echo sprintf( __( '- Desconto de %s%% (-%s)', 'paypal-payments' ), floatval( $financing['discount_percentage'] ), wc_price( $discount ) ); ?>
							<?php endif; ?>
                        </option>
					<?php endforeach; ?>
                </select>

            </label>
        </li>
		<?php
		$has_billing_agreement = true;
	} catch ( Paypal_Payments_Api_Exception $ex ) {
		// Don't do nothing, some error happened or user don't have the billing agreement anymore.
	} catch ( Paypal_Payments_Connection_Exception $ex ) {
		// Handle any connection error.
		wc_add_notice( $ex->getMessage() );
	}
	?>
    <!-- DEFAULT OPTION TO ADD NEW BILLING AGREEMENT -->
    <li>
        <label>
            <input type="radio"
                   class="paypal-payments-billing-agreement-option-radio"
                   name="paypal_payments_billing_agreement"
                   value="" <?php checked( true, ! $has_billing_agreement ); ?>>
			<?php if ( $has_billing_agreement ): ?>
				<?php _e( 'Alterar conta PayPal ou cartão de crédito', 'paypal-payments' ); ?>
			<?php else: ?>
				<?php _e( 'Adicionar conta PayPal', 'paypal-payments' ); ?>
			<?php endif; ?>
        </label>
        <input type="hidden"
               class="paypal_payments_billing_agreement_token"
               name="paypal_payments_billing_agreement_token">
    </li>
</ul>
<input type="hidden" id="paypal-payments-uuid" name="paypal-payments-uuid">
<?php if ( ! $has_billing_agreement ): ?>
    <p><?php _e( 'Prossiga com o  pagamento para criar uma nova autorização de pagamento.', 'paypal-payments' ); ?></p>
<?php endif; ?>
<style>
    ul.paypal-payments-billing-agreement-options {
        margin: 0;
        padding: 0;
    }

    ul.paypal-payments-billing-agreement-options > li:not(:last-child) {
        margin-bottom: 10px;
    }

    ul.paypal-payments-billing-agreement-options > li:only-child {
        display: none;
    }

    ul.paypal-payments-billing-agreement-options select.paypal-payments-billing-agreement-financing {
        width: 100%;
        display: block;
        margin-top: 5px;
        padding: 5px;
        border-radius: 3px;
    }

    ul.paypal-payments-billing-agreement-options input.paypal-payments-billing-agreement-option-radio:focus {
        outline: 0;
    }
</style>