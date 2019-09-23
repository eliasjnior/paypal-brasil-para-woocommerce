<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="paypal-spb-fields">
    <img src="<?php echo esc_url( plugins_url( 'assets/images/saiba-mais.png', PAYPAL_PAYMENTS_MAIN_FILE ) ); ?>"
         style="max-width: 100%; max-height: 100%; float: none;">
    <input type="hidden" name="paypal-brasil-spb-order-id">
    <input type="hidden" name="paypal-brasil-spb-payer-id">
    <input type="hidden" name="paypal-brasil-spb-pay-id">
</div>
