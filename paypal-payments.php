<?php

/**
 * Plugin Name: Portal de Pagamentos PayPal
 * Author: PayPal
 * Version: 1.0.0
 * Description: Portal com diversos produtos do PayPal para integração com WooCommerce.
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
	define( 'PAYPAL_PAYMENTS_VERSION', '0.0.3' );

	// Init plugin.
	PayPal_Payments::get_instance();
}

// Init plugin.
paypal_payments_init();
