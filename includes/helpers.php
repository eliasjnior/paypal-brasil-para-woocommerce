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

	/** @var WC_Order_Item_Product $item */
	foreach ( $order->get_items() as $item ) {
		$product = $item->get_product();
		$items[] = array(
			'currency' => get_woocommerce_currency(),
			'name'     => $item->get_name(),
			'quantity' => $item->get_quantity(),
			'sku'      => $product->get_sku() ? $product->get_sku() : $product->get_id(),
			'price'    => $product->get_price(),
		);
	}

	// Add any discount.
	if ( $discount_total = floatval( $order->get_discount_total() ) ) {
		$items[] = array(
			'currency' => get_woocommerce_currency(),
			'name'     => __( 'Desconto', 'paypal-payments' ),
			'quantity' => 1,
			'sku'      => 'discount',
			'price'    => $discount_total,
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
	return array(
		'term'                => $data['credit_financing']['term'],
		'monthly_payment'     => array(
			'value'    => $data['monthly_payment']['value'],
			'currency' => $data['monthly_payment']['currency_code'],
		),
		'discount_percentage' => $data['discount_percentage'],
		'discount_amount'     => array(
			'value'    => $data['discount_amount']['value'],
			'currency' => $data['discount_amount']['currency_code'],
		),
	);
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