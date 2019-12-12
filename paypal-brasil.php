<?php

/**
 * Plugin Name: PayPal Brasil para WooCommerce
 * Description: Adicione facilmente opções de pagamento do PayPal à sua loja do WooCommerce.
 * Version: 1.0.5
 * Author: PayPal
 * Author URI: https://paypal.com.br
 * Requires at least: 4.4
 * Tested up to: 5.3
 * Text Domain: paypal-brasil-para-woocommerce
 * Domain Path: /languages/
 * WC requires at least: 3.0
 * WC tested up to: 3.8
 * Requires PHP: 5.6
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Init PayPal Payments.
 */
function paypal_brasil_init() {
	include dirname( __FILE__ ) . '/class-paypal-brasil.php';

	// Define files.
	define( 'PAYPAL_PAYMENTS_MAIN_FILE', __FILE__ );
	define( 'PAYPAL_PAYMENTS_VERSION', '1.0.5' );

	// Init plugin.
	PayPal_Brasil::get_instance();
}

// Init plugin.
paypal_brasil_init();
