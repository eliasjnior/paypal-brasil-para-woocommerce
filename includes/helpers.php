<?php

// Exit if runs outside WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Force init gateways on load.
 */
function paypal_payments_init_gateways_on_load() {
	new PayPal_Payments_SPB_Gateway();
}

add_action( 'wp', 'paypal_payments_init_gateways_on_load' );

/**
 * Get order items prepared to API.
 *
 * @param $order WC_Order
 *
 * @return array
 */
function paypal_payments_get_order_items( $order ) {

	$items = array();

	// Add all items.
	/** @var WC_Order_Item_Product $item */
	foreach ( $order->get_items() as $id => $item ) {
		$product = $item->get_variation_id() ? wc_get_product( $item->get_variation_id() ) : wc_get_product( $item->get_product_id() );
		// Force get product cents to avoid float problems.
		$product_price_cents = intval( $item->get_subtotal() * 100 ) / $item->get_quantity();
		$product_price       = number_format( $product_price_cents / 100, 2, '.', '' );

		$items[] = array(
			'name'     => $product->get_title(),
			'currency' => get_woocommerce_currency(),
			'quantity' => $item->get_quantity(),
			'price'    => $product_price,
			'sku'      => $product->get_sku() ? $product->get_sku() : $product->get_id(),
			'url'      => $product->get_permalink(),
		);
	}

	// Add discounts.
	if ( $order->get_discount_total() ) {
		$discount_cents = intval( $order->get_discount_total() * 100 );
		$items[]        = array(
			'name'     => __( 'Desconto', 'paypal-payments' ),
			'currency' => get_woocommerce_currency(),
			'quantity' => 1,
			'price'    => number_format( ( - $discount_cents ) / 100, 2, '.', '' ),
			'sku'      => 'discount',
		);
	}

	// Add fees.
	if ( $order->get_total_tax() ) {
		$tax_cents = intval( $order->get_total_tax() * 100 );
		$items[]   = array(
			'name'     => __( 'Taxas', 'paypal-payments' ),
			'currency' => get_woocommerce_currency(),
			'quantity' => 1,
			'price'    => number_format( $tax_cents / 100, 2, '.', '' ),
			'sku'      => 'taxes',
		);
	}

	return $items;
}

/**
 * Prepare the shipping address to send in API from an order.
 *
 * @param WC_Order $order
 *
 * @return array
 */
function paypal_payments_get_shipping_address( $order ) {
	$line1 = array();
	$line2 = array();

	if ( $shipping_address_1 = $order->get_shipping_address_1() ) {
		$line1[] = $shipping_address_1;
	}

	if ( $shipping_number = get_post_meta( $order->get_id(), '_shipping_number', true ) ) {
		$line1[] = $shipping_number;
	}

	if ( $shipping_neighborhood = get_post_meta( $order->get_id(), '_shipping_neighborhood', true ) ) {
		$line2[] = $shipping_neighborhood;
		if ( $shipping_address_2 = $order->get_shipping_address_2() ) {
			$line1[] = $shipping_address_2;
		}
	} else if ( $shipping_address_2 = $order->get_shipping_address_2() ) {
		$line2[] = $shipping_address_2;
	}

	$shipping_address = array(
		'line1'          => implode( ', ', $line1 ),
		'line2'          => implode( ', ', $line2 ),
		'city'           => $order->get_shipping_city(),
		'state'          => $order->get_shipping_state(),
		'postal_code'    => $order->get_shipping_postcode(),
		'country_code'   => $order->get_shipping_country(),
		'recipient_name' => trim( sprintf( '%s %s', $order->get_shipping_first_name(), $order->get_shipping_last_name() ) ),
	);

	return $shipping_address;
}

/**
 * Prepare the installment option with API input data.
 *
 * @param $data
 *
 * @return array
 */
function paypal_payments_prepare_installment_option( $data ) {
	$value = array(
		'term'            => $data['credit_financing']['term'],
		'monthly_payment' => array(
			'value'    => $data['monthly_payment']['value'],
			'currency' => $data['monthly_payment']['currency_code'],
		),
	);

	if ( isset( $data['discount_percentage'] ) ) {
		$value['discount_percentage'] = $data['discount_percentage'];
		$value['discount_amount']     = array(
			'value'    => $data['discount_amount']['value'],
			'currency' => $data['discount_amount']['currency_code'],
		);
	}

	return $value;
}

/**
 * Explode a full name into first name and last name.
 *
 * @param $full_name
 *
 * @return array
 */
function paypal_payments_explode_name( $full_name ) {
	$full_name  = explode( ' ', $full_name );
	$first_name = $full_name ? $full_name[0] : '';
	unset( $full_name[0] );
	$last_name = implode( ' ', $full_name );

	return array(
		'first_name' => $first_name,
		'last_name'  => $last_name,
	);
}

/**
 * Update WooCommerce settings.
 */
function paypal_payments_wc_settings_ajax() {
	header( 'Content-type: application/json' );

	$choice = isset( $_REQUEST['enable'] ) && $_REQUEST['enable'] === 'yes' ? 'yes' : 'no';

	if ( $choice === 'yes' ) {
		update_option( 'woocommerce_enable_checkout_login_reminder', 'yes' );
		update_option( 'woocommerce_enable_signup_and_login_from_checkout', 'yes' );
		update_option( 'woocommerce_enable_guest_checkout', 'no' );
	}

	echo json_encode( array(
		'success' => true,
		'choice'  => $choice,
		'message' => $choice === 'yes' ? __( 'As configurações do WooCommerce foram alteradas com sucesso.', 'paypal-payments' ) : __( 'As configurações do WooCommerce não foram alteradas.', 'paypal-payments' ),
	) );

	wp_die();
}

add_action( 'wp_ajax_paypal_payments_wc_settings', 'paypal_payments_wc_settings_ajax' );

/**
 * Check if WooCommerce settings is activated.
 */
function paypal_payments_wc_settings_valid() {
	return get_option( 'woocommerce_enable_checkout_login_reminder' ) === 'yes' &&
	       get_option( 'woocommerce_enable_signup_and_login_from_checkout' ) === 'yes' &&
	       get_option( 'woocommerce_enable_guest_checkout' ) === 'no';
}

/**
 * Return if needs CPF.
 * @return bool
 */
function paypal_payments_needs_cpf() {
	return function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() === 'BRL' : false;
}