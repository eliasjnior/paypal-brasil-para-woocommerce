<?php

/**
 * Plugin Name: PayPal Brasil para WooCommerce
 * Author: PayPal
 * Version: 1.0.0
 * Description: Adicione facilmente opções de pagamento do PayPal ao seu site do WordPress/WooCommerce.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Init PayPal Payments.
 */
function paypal_payments_init() {
	include dirname( __FILE__ ) . '/class-paypal-payments.php';

	// Define files.
	define( 'PAYPAL_PAYMENTS_MAIN_FILE', __FILE__ );
	define( 'PAYPAL_PAYMENTS_VERSION', '0.0.4' );

	// Init plugin.
	PayPal_Payments::get_instance();
}

// Init plugin.
paypal_payments_init();
