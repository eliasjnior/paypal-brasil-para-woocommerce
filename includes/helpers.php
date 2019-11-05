<?php

// Exit if runs outside WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Force init gateways on load.
 */
function paypal_brasil_init_gateways_on_load() {
	new PayPal_Brasil_SPB_Gateway();
}

add_action( 'wp', 'paypal_brasil_init_gateways_on_load' );

/**
 * Check if is only digital items.
 *
 * @param $order WC_Order
 *
 * @return bool
 */
function paypal_brasil_is_order_only_digital( $order ) {
	// Consider as always digital.
	$only_digital = true;

	/** @var WC_Order_Item $item */
	foreach ( $order->get_items() as $id => $item ) {
		// Get the product.
		$product = $item->get_variation_id() ? wc_get_product( $item->get_variation_id() ) : wc_get_product( $item->get_product_id() );

		// Check if product is not digital.
		if ( ! ( $product->is_downloadable() || $product->is_virtual() ) ) {
			$only_digital = false;
			break;
		}
	}

	return $only_digital;
}

/**
 * Get cart items prepared for API.
 *
 * @param bool $only_items
 *
 * @return array
 */
function paypal_brasil_get_cart_items( $only_items = false ) {
	$items              = array();
	$only_digital_items = true;
	$diff               = '0.00';

	// Add all items.
	foreach ( WC()->cart->get_cart() as $key => $item ) {
		$product = $item['variation_id'] ? wc_get_product( $item['variation_id'] ) : wc_get_product( $item['product_id'] );

		// Force get product cents to avoid float problems.
		$product_price = paypal_brasil_math_div( $item['line_subtotal'], $item['quantity'] );

		$items[] = array(
			'name'     => $product->get_title(),
			'currency' => get_woocommerce_currency(),
			'quantity' => $item['quantity'],
			'price'    => $product_price,
			'sku'      => $product->get_sku() ? $product->get_sku() : $product->get_id(),
			'url'      => $product->get_permalink(),
		);

		// Check if product is not digital.
		if ( ! ( $product->is_downloadable() || $product->is_virtual() ) ) {
			$only_digital_items = false;
		}
	}

	// Add all discounts.
	$cart_totals = WC()->cart->get_totals();

	// Add discounts.
	if ( ! $only_items ) {
		if ( $cart_totals['discount_total'] ) {
			$items[] = array(
				'name'     => __( 'Desconto', 'paypal-brasil-para-woocommerce' ),
				'currency' => get_woocommerce_currency(),
				'quantity' => 1,
				'price'    => paypal_brasil_money_format( - $cart_totals['discount_total'] ),
				'sku'      => 'discount',
			);
		}
	}

	// Add fees.
	if ( ! $only_items ) {
		if ( $cart_totals['fee_total'] ) {
			foreach ( WC()->cart->get_fees() as $fee ) {
				$items[] = array(
					'name'     => $fee->name,
					'currency' => get_woocommerce_currency(),
					'quantity' => 1,
					'price'    => paypal_brasil_money_format( $fee->total ),
					'sku'      => $fee->id,
				);
			}
		}
	}

	// Add taxes.
	if ( ! $only_items ) {
		if ( $cart_totals['total_tax'] ) {
			$items[] = array(
				'name'     => __( 'Taxas', 'paypal-brasil-para-woocommerce' ),
				'currency' => get_woocommerce_currency(),
				'quantity' => 1,
				'price'    => paypal_brasil_money_format( $cart_totals['total_tax'] ),
				'sku'      => 'taxes',
			);
		}
	}

	if ( ! $only_items ) {
		if ( ( $diff = paypal_brasil_math_sub( $cart_totals['total'] - $cart_totals['shipping_total'], paypal_brasil_sum_items( $items ) ) ) !== '0.00' ) {
			$items[] = array(
				'name'     => __( 'Ajuste de preço', 'paypal-brasil-para-woocommerce' ),
				'currency' => get_woocommerce_currency(),
				'quantity' => 1,
				'price'    => $diff,
				'sku'      => 'price-adjustment',
			);
		}
	}

	// Calculate subtotal and shipping.
	$discount = $only_items ? '0.00' : - $cart_totals['discount_total'];
	$tax      = $only_items ? '0.00' : $cart_totals['total_tax'];
	$fee      = $only_items ? '0.00' : $cart_totals['fee_total'];
	$subtotal = paypal_brasil_math_add( $cart_totals['subtotal'], $discount, $tax );
	$shipping = paypal_brasil_money_format( $cart_totals['shipping_total'] );

	return array(
		'items'              => $items,
		'shipping'           => $shipping,
		'subtotal'           => paypal_brasil_math_add( $diff, $subtotal, $fee ),
		'has_rounding'       => $diff !== '0.00',
		'only_digital_items' => $only_digital_items,
		'total'              => $cart_totals['total'],
	);
}

/**
 * Get order items prepared to API.
 *
 * @param $order WC_Order
 *
 * @return array
 */
function paypal_brasil_get_order_items( $order ) {

	$items              = array();
	$fees               = '0.00';
	$only_digital_items = true;

	// Add all items.
	/** @var WC_Order_Item_Product $item */
	foreach ( $order->get_items() as $id => $item ) {
		$product = $item->get_variation_id() ? wc_get_product( $item->get_variation_id() ) : wc_get_product( $item->get_product_id() );
		// Force get product cents to avoid float problems.
		$product_price = paypal_brasil_math_div( $item->get_subtotal(), $item->get_quantity(), 2 );

		$items[] = array(
			'name'     => $product->get_title(),
			'currency' => get_woocommerce_currency(),
			'quantity' => $item->get_quantity(),
			'price'    => $product_price,
			'sku'      => $product->get_sku() ? $product->get_sku() : $product->get_id(),
			'url'      => $product->get_permalink(),
		);

		// Check if product is not digital.
		if ( ! ( $product->is_downloadable() || $product->is_virtual() ) ) {
			$only_digital_items = false;
		}
	}

	// Add discounts.
	if ( $order->get_discount_total() ) {
		$order_discount = paypal_brasil_money_format( - $order->get_discount_total() );
		$items[]        = array(
			'name'     => __( 'Desconto', 'paypal-brasil-para-woocommerce' ),
			'currency' => get_woocommerce_currency(),
			'quantity' => 1,
			'price'    => $order_discount,
			'sku'      => 'discount',
		);
	}

	// Add fees
	if ( $order->get_fees() ) {
		/** @var WC_Order_Item_Fee $fee */
		foreach ( $order->get_fees() as $fee ) {
			$items[] = array(
				'name'     => $fee->get_name(),
				'currency' => get_woocommerce_currency(),
				'quantity' => 1,
				'price'    => paypal_brasil_money_format( $fee->get_total() ),
				'sku'      => $fee->get_id(),
			);
		}

		// Add to subtotal.
		$fees = paypal_brasil_math_add( $fees, $fee->get_total() );
	}

	// Add fees.
	if ( $order->get_total_tax() ) {
		$items[] = array(
			'name'     => __( 'Taxas', 'paypal-brasil-para-woocommerce' ),
			'currency' => get_woocommerce_currency(),
			'quantity' => 1,
			'price'    => paypal_brasil_money_format( $order->get_total_tax() ),
			'sku'      => 'taxes',
		);
	}

	// Calculate de difference.
	$total_without_shipping = paypal_brasil_math_sub( $order->get_total(), $order->get_shipping_total() );

	// Calculate subtotal.
	$subtotal_without_diff = paypal_brasil_math_add( $order->get_subtotal(), $fees, $order->get_total_tax() );

	// Check the difference.
	if ( ( $diff = paypal_brasil_math_sub( $total_without_shipping, $subtotal_without_diff, - $order->get_discount_total() ) ) !== '0.00' ) {
		$items[] = array(
			'name'     => __( 'Arredondamento', 'paypal-brasil-para-woocommerce' ),
			'currency' => get_woocommerce_currency(),
			'quantity' => 1,
			'price'    => $diff,
			'sku'      => 'price-adjustment',
		);
	}

	return array(
		'items'              => $items,
		'shipping'           => $order->get_shipping_total(),
		'subtotal'           => paypal_brasil_math_add( $order->get_subtotal(), $diff, $fees, $order->get_total_tax(), - $order->get_discount_total() ),
		'has_rounding'       => $diff !== '0.00',
		'only_digital_items' => $only_digital_items,
	);
}

/**
 * Prepare the shipping address to send in API from an order.
 *
 * @param WC_Order $order
 *
 * @return array
 */
function paypal_brasil_get_shipping_address( $order ) {
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
function paypal_brasil_prepare_installment_option( $data ) {
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
function paypal_brasil_explode_name( $full_name ) {
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
function paypal_brasil_wc_settings_ajax() {
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
		'message' => $choice === 'yes' ? __( 'As configurações do WooCommerce foram alteradas com sucesso.', 'paypal-brasil-para-woocommerce' ) : __( 'As configurações do WooCommerce não foram alteradas.', 'paypal-brasil-para-woocommerce' ),
	) );

	wp_die();
}

add_action( 'wp_ajax_paypal_brasil_wc_settings', 'paypal_brasil_wc_settings_ajax' );

/**
 * Check if WooCommerce settings is activated.
 */
function paypal_brasil_wc_settings_valid() {
	return get_option( 'woocommerce_enable_checkout_login_reminder' ) === 'yes' &&
	       get_option( 'woocommerce_enable_signup_and_login_from_checkout' ) === 'yes' &&
	       get_option( 'woocommerce_enable_guest_checkout' ) === 'no';
}

/**
 * Return if needs CPF.
 * @return bool
 */
function paypal_brasil_needs_cpf() {
	return function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() === 'BRL' : false;
}

/**
 * Protect some metadata.
 */
function paypal_brasil_protect_metadata( $protected, $meta_key ) {
	$keys = array(
		'paypal_brasil_id',
		'paypal_brasil_sale_id',
		'wc_ppp_brasil_installments',
		'wc_ppp_brasil_sale',
		'wc_ppp_brasil_sale_id',
		'wc_ppp_brasil_sandbox',
	);

	if ( 'shop_order' == get_post_type() ) {
		if ( in_array( $meta_key, $keys ) ) {
			return true;
		}
	}

	return $protected;
}

add_filter( 'is_protected_meta', 'paypal_brasil_protect_metadata', 10, 2 );

/**
 * Get the latest log for a gateway.
 *
 * @param $id
 *
 * @return string
 */
function paypal_brasil_get_log_file( $id ) {
	$logs         = WC_Admin_Status::scan_log_files();
	$matched_logs = array();

	foreach ( $logs as $key => $value ) {
		if ( preg_match( '/(' . $id . '-)/', $value ) ) {
			$matched_logs[] = $value;
		}
	}

	return $matched_logs ? end( $matched_logs ) : '';
}

/**
 * Method to div two values.
 *
 * @param $value1
 * @param $value2
 * @param int $precision
 *
 * @return string
 */
function paypal_brasil_math_div( $value1, $value2, $precision = 2 ) {
	return paypal_brasil_money_format( $value1 / $value2, $precision );
}

/**
 * Method to multiply two values.
 *
 * @param $value1
 * @param $value2
 * @param int $precision
 *
 * @return string
 */
function paypal_brasil_math_mul( $value1, $value2, $precision = 2 ) {
	return paypal_brasil_money_format( $value1 * $value2, $precision );
}

/**
 * Method to add two numbers.
 *
 * @param array $values
 *
 * @return string
 */
function paypal_brasil_math_add( ...$values ) {
	$sum = '0.00';

	foreach ( $values as $value ) {
		$sum = paypal_brasil_money_format( $sum + $value );
	}

	return $sum;
}

/**
 * Method to sub two numbers.
 *
 * @param array $values
 *
 * @return string
 */
function paypal_brasil_math_sub( ...$values ) {
	$sub = $values[0];

	foreach ( $values as $key => $value ) {
		// Skip the first.
		if ( ! $key ) {
			continue;
		}
		$sub = paypal_brasil_money_format( $sub - $value );
	}

	return $sub;
}

/**
 * Format the money for PayPal API.
 *
 * @param $value
 * @param int $precision
 *
 * @return string
 */
function paypal_brasil_money_format( $value, $precision = 2 ) {
	return number_format( $value, $precision, '.', '' );
}

/**
 * Sum PayPal API items.
 *
 * @param $items
 *
 * @return string
 */
function paypal_brasil_sum_items( $items ) {
	$sum = '0.00';

	foreach ( $items as $item ) {
		$sum = paypal_brasil_math_add( $sum, $item['price'] );
	}

	return $sum;
}

/**
 * Generate a unique id.
 * @return int
 */
function paypal_brasil_unique_id() {
	return rand( 1, 10000 );
}