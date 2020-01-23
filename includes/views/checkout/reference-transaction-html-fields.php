<?php

/** @var PayPal_Brasil_SPB_Gateway $this */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get user billing agreement details.
if ( is_user_logged_in() ) {
	$user_billing_agreement_id         = get_user_meta( get_current_user_id(), 'paypal_brasil_billing_agreement_id', true );
	$user_billing_agreement_payer_info = get_user_meta( get_current_user_id(), 'paypal_brasil_billing_agreement_payer_info', true );
} else {
	$user_billing_agreement_id         = WC()->session->get( 'paypal_brasil_billing_agreement_id' );
	$user_billing_agreement_payer_info = WC()->session->get( 'paypal_brasil_billing_agreement_payer_info' );
}
$has_billing_agreement   = false;
$billing_agreement_error = false;

$order = get_query_var( 'order-pay' ) ? wc_get_order( get_query_var( 'order-pay' ) ) : null;
$total = $order ? $order->get_total() : WC()->cart->get_totals()['total'];

?>
<ul class="paypal-brasil-billing-agreement-options">
    <!-- USER DEFAULT BILLING AGREEMENT -->
	<?php
	try {
		if ( $user_billing_agreement_id ):
			$calculated_financing = $this->api->get_calculate_financing( $user_billing_agreement_id, $total );
			?>
            <li>
                <label>
                    <input type="radio"
                           class="paypal-brasil-billing-agreement-option-radio"
                           name="paypal_brasil_billing_agreement"
                           value="<?php echo esc_attr( $user_billing_agreement_id ); ?>"
                           checked="checked">
					<?php echo sprintf( __( 'Conta PayPal vinculada: %s', 'paypal-brasil' ), '<strong>' . $user_billing_agreement_payer_info['email'] . '</strong>' ); ?>

                    <select class="paypal-brasil-billing-agreement-financing"
                            name="paypal_brasil_billing_agreement_installment">
						<?php foreach ( $calculated_financing['financing_options'][0]['qualifying_financing_options'] as $financing ): ?>
                            <option value="<?php echo esc_attr( json_encode( paypal_brasil_prepare_installment_option( $financing ), JSON_UNESCAPED_SLASHES ) ); ?>">
								<?php echo sprintf( '%dx de %s (Total: %s)', $financing['credit_financing']['term'], wc_price( $financing['monthly_payment']['value'] ), wc_price( $financing['total_cost']['value'] ) ); ?>
								<?php if ( isset( $financing['discount_amount'] ) && $discount = floatval( $financing['discount_amount']['value'] ) ): ?>
									<?php echo sprintf( __( ' - Desconto de %s %%(-%s)', 'paypal-brasil' ), floatval( $financing['discount_percentage'] ), wc_price( $discount ) ); ?>
								<?php endif; ?>
                            </option>
						<?php endforeach; ?>
                    </select>

                </label>
            </li>
			<?php
			$has_billing_agreement = true;
		endif;
	} catch ( PayPal_Brasil_API_Exception $ex ) {
		$billing_agreement_error = true;
		if ( $user_billing_agreement_id ) {
			wc_print_notice( __( 'Encontramos um erro no seu termo de aceite PayPal, por favor crie um novo.', 'paypal-brasil-para-woocommerce' ), 'notice' );
		}
	} catch ( PayPal_Brasil_Connection_Exception $ex ) {
		// Handle any connection error.
		wc_add_notice( $ex->getMessage() );
	}
	?>

	<?php if ( ! $user_billing_agreement_id || $billing_agreement_error ): ?>
        <img src="<?php echo esc_url( plugins_url( 'assets/images/saiba-mais.png', PAYPAL_PAYMENTS_MAIN_FILE ) ); ?>"
             style="max-width: 500px; margin: 0 auto; max-height: 100%; float: none;">
        <input type="radio"
               class="paypal-brasil-billing-agreement-option-radio"
               style="display: none;"
               name="paypal_brasil_billing_agreement"
               value="" <?php checked( true, ! $has_billing_agreement ); ?>>
        <input type="hidden"
               class="paypal_brasil_billing_agreement_token"
               name="paypal_brasil_billing_agreement_token">
	<?php else: ?>
        <!-- DEFAULT OPTION TO ADD NEW BILLING AGREEMENT -->
        <li>
            <label>
                <input type="radio"
                       class="paypal-brasil-billing-agreement-option-radio"
                       name="paypal_brasil_billing_agreement"
                       value="" <?php checked( true, ! $has_billing_agreement ); ?>>
				<?php if ( $has_billing_agreement ): ?>
					<?php _e( 'Alterar conta PayPal ou cartão de crédito', 'paypal-brasil' ); ?>
				<?php else: ?>
					<?php _e( 'Adicionar conta PayPal', 'paypal-brasil' ); ?>
				<?php endif; ?>
            </label>
            <input type="hidden"
                   class="paypal_brasil_billing_agreement_token"
                   name="paypal_brasil_billing_agreement_token">
        </li>
	<?php endif; ?>
</ul>

<input type="hidden" id="paypal-brasil-uuid" name="paypal-brasil-uuid">
<?php if ( ! $has_billing_agreement ): ?>
    <!--    <p>--><?php //_e( 'Prossiga com o  pagamento para criar uma nova autorização de pagamento . ', 'paypal-brasil' ); ?><!--</p>-->
<?php endif; ?>
<style>
    ul.paypal-brasil-billing-agreement-options {
        margin: 0;
        padding: 0;
    }

    ul.paypal-brasil-billing-agreement-options > li:not(:last-child) {
        margin-bottom: 10px;
    }

    ul.paypal-brasil-billing-agreement-options > li:only-child {
        display: none;
    }

    ul.paypal-brasil-billing-agreement-options select.paypal-brasil-billing-agreement-financing {
        width: 100%;
        display: block;
        margin-top: 5px;
        padding: 5px;
        border-radius: 3px;
    }

    ul.paypal-brasil-billing-agreement-options input.paypal-brasil-billing-agreement-option-radio:focus {
        outline: 0;
    }
</style>