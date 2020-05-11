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
		if ( ! $product->is_virtual() ) {
			$only_digital = false;
			break;
		}
	}

	return $only_digital;
}

/**
 * Check if cart is only digital items.
 *
 * @return bool
 */
function paypal_brasil_is_cart_only_digital() {
	// Consider as always digital.
	$only_digital_items = true;

	/** @var WC_Order_Item $item */
	foreach ( WC()->cart->get_cart() as $id => $item ) {
		$product = $item['variation_id'] ? wc_get_product( $item['variation_id'] ) : wc_get_product( $item['product_id'] );

		// Check if product is not digital.
		if ( ! $product->is_virtual() ) {
			$only_digital_items = false;
		}
	}

	return $only_digital_items;
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
 * Generate a unique id.
 * @return int
 */
function paypal_brasil_unique_id() {
	return rand( 1, 10000 );
}

/**
 * Generate partners URL
 *
 * @param $partner_id
 * @param $partner_client_id
 * @param $gateway
 * @param bool $sandbox
 *
 * @return string
 * @throws Exception
 */
function paypal_brasil_partners_url( $partner_id, $partner_client_id, $gateway, $sandbox = false ) {
	$url_regex = "/((https?:\/\/localhost)|(https?:\/\/.+\.localhost[^.]))/";

	// Mai
	$settings_url = admin_url( 'admin.php' );
	$settings_url = 'https://paypal.eliasjr.dev/wp-admin/admin.php';

	// Override with a fake URL if is on localhost.
	if ( preg_match( $url_regex, $settings_url ) ) {
		$settings_url = 'https://paypal.com.br';
	}

	$nonce = bin2hex( random_bytes( 50 ) );

	$return_url = urlencode( add_query_arg( array(
		'page'            => 'wc-settings',
		'tab'             => 'checkout',
		'section'         => $gateway->id,
		'mode'            => $sandbox ? 'sandbox' : 'live',
		'paypal-partners' => $gateway,
		'paypal-nonce'    => $nonce,
	), $settings_url ) );

	$showPermissions = false;

	return "https://" . ( $sandbox ? 'sandbox' : 'www' ) . ".paypal.com/BR/merchantsignup/partner/onboardingentry?showPermissions={$showPermissions}&channelId=partner&partnerId={$partner_id}&productIntentId=addipmt&integrationType=FO&features=PAYMENT,REFUND&partnerClientId={$partner_client_id}&displayMode=minibrowser&sellerNonce={$nonce}";
}

/**
 * Process PayPal Connect
 */
function paypal_brasil_process_connect() {
	// Check if is on admin.
	if ( ! is_admin() ) {
		return;
	}

	// Check if is requesting PayPal Partners
	if ( isset( $_GET['paypal-partners'] ) && $gateway_name = sanitize_text_field( $_GET['paypal-partners'] ) ) {

		// Check the cookie
		$cookies = array(
			'paypal-partners-shared-id',
			'paypal-partners-auth-code',
		);

		$all_cookies = true;

		foreach ( $cookies as $cookie ) {
			if ( ! isset( $_COOKIE[ $cookie ] ) || ! $_COOKIE[ $cookie ] ) {
				$all_cookies = false;
				break;
			}
		}

		// Return if is missing cookie.
		if ( ! $all_cookies ) {
			return;
		}

		$shared_id = $_COOKIE[ $cookies[0] ];
		$auth_code = $_COOKIE[ $cookies[1] ];
		$mode      = $_GET['mode'] ? sanitize_text_field( $_GET['mode'] ) : '';
		$nonce     = $_GET['paypal-nonce'] ? sanitize_text_field( $_GET['paypal-nonce'] ) : '';
		$gateway   = $gateway_name === 'paypal-brasil-spb-gateway' ? new PayPal_Brasil_SPB_Gateway() : ( $gateway_name === 'paypal-brasil-plus-gateway' ? new PayPal_Brasil_Plus_Gateway() : null );

		if ( ! $gateway ) {
			return;
		}

		if ( ! in_array( $mode, array( 'live', 'sandbox' ) ) ) {
			return;
		}

		$api = new PayPal_Brasil_API( null, null, $mode, $gateway );

		$access_token = $api->oauth_partner( $shared_id, $auth_code, $nonce );
		$credentials  = $api->get_credentials( $access_token );

		// Redirect to the URL without the partners.
		$redirect_url = remove_query_arg( array(
			'paypal-partners',
			'merchantId',
			'merchantIdInPayPal',
			'permissionsGranted',
			'consentStatus',
			'productIntentId',
			'productIntentID',
			'isEmailConfirmed',
			'accountStatus',
		) );

		$redirect_url = add_query_arg( 'partner-updated', true, $redirect_url, '/' );

		update_option( $gateway->get_option_key() . '_partner_client_id', $credentials['client_id'] );
		update_option( $gateway->get_option_key() . '_partner_client_secret', $credentials['client_secret'] );

		foreach ( $cookies as $cookie ) {
			setcookie( $cookie, '', time() - 3600 );
		}

		wp_redirect( $redirect_url );
		exit;
	}
}

add_action( 'admin_init', 'paypal_brasil_process_connect' );
