<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PayPal_Payments_API_Shortcut_Mini_Cart_Handler extends PayPal_Payments_API_Handler {

	public function __construct() {
		add_filter( 'paypal_payments_handlers', array( $this, 'add_handlers' ) );
	}

	public function add_handlers( $handlers ) {
		$handlers['shortcut'] = array(
			'callback' => array( $this, 'handle' ),
			'method'   => 'POST',
		);

		return $handlers;
	}

	/**
	 * Add validators and input fields.
	 *
	 * @return array
	 */
	public function get_fields() {
		return array(
			array(
				'key'        => 'nonce',
				'sanitize'   => 'sanitize_text_field',
				'validation' => array( $this, 'required_nonce' ),
			),
		);
	}

	/**
	 * Handle the request.
	 */
	public function handle() {
		try {

			$validation = $this->validate_input_data();

			if ( ! $validation['success'] ) {
				$this->send_error_response(
					__( 'Alguns campos estão faltando para iniciar o pagamento.', 'paypay-payments' ),
					array(
						'errors' => $validation['errors']
					)
				);
			}

			$posted_data = $validation['data'];

			// Get the wanted gateway.
			$gateway = $this->get_paypal_gateway( 'paypal-payments-spb-gateway' );

			// Store cart.
			$cart = WC()->cart;

			// Disable shipping while handle shortcode and force recalculate totals.
			add_filter( 'woocommerce_cart_needs_shipping', '__return_false' );
			$cart->calculate_totals();

			// Check if there is anything on cart.
			if ( ! $cart->get_totals()['total'] ) {
				$this->send_error_response( __( 'Você não pode fazer o pagamento de um pedido vazio.', 'paypal-payments' ) );
			}

			$data = array(
				'intent'        => 'sale',
				'payer'         => array(
					'payment_method' => 'paypal',
				),
				'transactions'  => array(
					array(
						'payment_options' => array(
							'allowed_payment_method' => 'IMMEDIATE_PAY',
						),
						'item_list'       => array(
							'items' => array(),
						),
						'amount'          => array(
							'currency' => get_woocommerce_currency(),
						),
					),
				),
				'redirect_urls' => array(
					'return_url' => home_url(),
					'cancel_url' => home_url(),
				),
			);

			$items = array();

			// Add cart items.
			foreach ( $cart->get_cart() as $cart_item ) {
				/** @var WC_Product $product */
				$id      = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
				$product = wc_get_product( $id );

				$items[] = array(
					'name'     => $product->get_title(),
					'currency' => get_woocommerce_currency(),
					'quantity' => $cart_item['quantity'],
					'price'    => $product->get_price(),
					'sku'      => $product->get_sku() ? $product->get_sku() : $product->get_id(),
					'url'      => $product->get_permalink(),
				);
			}

			// Add taxes.
			foreach ( $cart->get_tax_totals() as $tax ) {
				$items[] = array(
					'name'     => $tax->label,
					'currency' => get_woocommerce_currency(),
					'quantity' => 1,
					'sku'      => sanitize_title( $tax->label ),
					'price'    => $tax->amount,
				);
			}

			// Add discounts.
			if ( $discount = $cart->get_cart_discount_total() ) {
				$items[] = array(
					'name'     => __( 'Desconto', 'paypal-payments' ),
					'currency' => get_woocommerce_currency(),
					'quantity' => 1,
					'sku'      => 'discount',
					'price'    => $discount,
				);
			}

			// Add fees.
			foreach ( $cart->get_fees() as $fee ) {
				$items[] = array(
					'name'     => $fee->name,
					'currency' => get_woocommerce_currency(),
					'quantity' => 1,
					'sku'      => $fee->id,
					'price'    => $fee->total,
				);
			}

			// Set details
			$data['transactions'][0]['amount']['details'] = array(
				'shipping' => 0,
				'subtotal' => $cart->get_subtotal(),
			);

			// Set total Total
			$data['transactions'][0]['amount']['total'] = $cart->get_totals()['total'];

			// Add items to data.
			$data['transactions'][0]['item_list']['items'] = $items;

			// Set the application context
			$data['application_context'] = array(
				'brand_name'          => get_bloginfo( 'name' ),
				'shipping_preference' => 'GET_FROM_FILE',
				'user_action'         => 'continue',
			);

			// Create the payment in API.
			$create_payment = $gateway->api->create_payment( $data );

			// Get the response links.
			$links = $gateway->api->parse_links( $create_payment['links'] );

			// Extract EC token from response.
			preg_match( '/(EC-\w+)/', $links['approval_url'], $ec_token );

			// Separate data.
			$data = array(
				'pay_id'   => $create_payment['id'],
				'ec'       => $ec_token[0],
				'postcode' => preg_replace( '/[^0-9]/', '', WC()->customer->get_shipping_postcode() ),
			);

			// Store the requested data in session.
			WC()->session->set( 'paypal_payments_spb_shortcut_data', $data );

			// Send success response with data.
			$this->send_success_response( __( 'Pagamento criado com sucesso.', 'paypal-payments' ), $data );
		} catch ( Exception $ex ) {
			$this->send_error_response( $ex->getMessage() );
		}
	}

	// CUSTOM VALIDATORS

	public function required_nonce( $data, $key ) {
		if ( wp_verify_nonce( $data, 'paypal-payments-checkout' ) ) {
			return true;
		}

		return __( 'Nonce inválido', 'paypal-payments' );
	}

	// CUSTOM SANITIZER

	public function sanitize_boolean( $data, $key ) {
		return ! ! $data;
	}

}

new PayPal_Payments_API_Shortcut_Mini_Cart_Handler();