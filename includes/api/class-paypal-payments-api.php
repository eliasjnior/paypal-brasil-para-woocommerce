<?php

// Exit if runs outside WP.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PayPal_Payments_API.
 * @property string access_token_transient_key
 * @property string mode
 * @property string base_url
 * @property string client_id
 * @property string secret
 * @property string partner_attribution_id
 */
class PayPal_Payments_API {

	/**
	 * PayPal_Payments_API constructor.
	 *
	 * @param string $client_id
	 * @param string $secret
	 * @param string $mode The API mode sandbox|live.
	 * @param string $partner_attribution_id
	 */
	public function __construct( $client_id, $secret, $mode, $partner_attribution_id = null ) {
		// Set the access token transient key to a MD5 hash of client id and secret. So transient will change if
		// client id or secret changes also.
		$this->access_token_transient_key = 'paypal_payments_access_token_' . md5( $client_id . ':' . $secret );

		// Save the API data.
		$this->mode                   = $mode;
		$this->client_id              = $client_id;
		$this->secret                 = $secret;
		$this->partner_attribution_id = $partner_attribution_id;

		// Define the API base URL for live or sandbox.
		$this->base_url = ( $mode === 'live' ) ? 'https://api.paypal.com/v1' : 'https://api.sandbox.paypal.com/v1';
	}

	/**
	 * Get access token.
	 *
	 * @param bool $force
	 *
	 * @return array|WP_Error
	 * @throws Paypal_Payments_Api_Exception
	 * @throws Paypal_Payments_Connection_Exception
	 */
	public function get_access_token( $force = false ) {
		$url = $this->base_url . '/oauth2/token';

		// Try to get the transient for access token.
		$access_token = get_transient( $this->access_token_transient_key );

		// If there's any token in transients, return it.
		if ( ! $force && $access_token ) {
			return $access_token;
		}

		$headers = array(
			'Authorization' => 'Basic ' . base64_encode( $this->client_id . ':' . $this->secret ),
			'Content-Type'  => 'application/x-www-form-urlencoded',
		);

		$data = 'grant_type=client_credentials';

		$response      = $this->do_request( $url, 'POST', $data, $headers );
		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

		// Check if is WP_Error
		if ( is_wp_error( $response ) ) {
			throw new Paypal_Payments_Connection_Exception( $response->get_error_code(), $response->errors );
		}

		$code = wp_remote_retrieve_response_code( $response );

		// Check if response was ok.
		if ( $code === 200 ) {
			set_transient( $this->access_token_transient_key, $response_body['access_token'], $response_body['expires_in'] );

			return $response_body['access_token'];
		}

		throw new Paypal_Payments_Api_Exception( $code, __( 'Não foi possível obter o access token', 'paypal-payments' ), $response_body );
	}

	/**
	 * Create a payment.
	 *
	 * @param array $data
	 *
	 * @return mixed
	 * @throws Paypal_Payments_Api_Exception
	 * @throws Paypal_Payments_Connection_Exception
	 */
	public function create_payment( $data ) {
		$url = $this->base_url . '/payments/payment';

		// Get response.
		$response      = $this->do_request( $url, 'POST', $data );
		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

		// Check if is WP_Error
		if ( is_wp_error( $response ) ) {
			throw new Paypal_Payments_Connection_Exception( $response->get_error_code(), $response->errors );
		}

		$code = wp_remote_retrieve_response_code( $response );

		// Check if response was created.
		if ( $code === 201 ) {
			return $response_body;
		}

		throw new Paypal_Payments_Api_Exception( $code, __( 'Não foi possível criar o pagamento.', 'paypal-payments' ), $response_body );
	}

	/**
	 * Get a given payment id.
	 *
	 * @param $payment_id
	 *
	 * @return array|mixed|object
	 * @throws Paypal_Payments_Api_Exception
	 * @throws Paypal_Payments_Connection_Exception
	 */
	public function get_payment( $payment_id ) {
		$url = $this->base_url . '/payments/payment/' . $payment_id;

		// Get response.
		$response      = $this->do_request( $url, 'GET' );
		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

		// Check if is WP_Error
		if ( is_wp_error( $response ) ) {
			throw new Paypal_Payments_Connection_Exception( $response->get_error_code(), $response->errors );
		}

		$code = wp_remote_retrieve_response_code( $response );

		// Check if response was created.
		if ( $code === 200 ) {
			return $response_body;
		}

		throw new Paypal_Payments_Api_Exception( $code, __( 'Não foi possível obter o pagamento', 'paypal-payments' ), $response_body );
	}

	/**
	 * Execute a payment.
	 *
	 * @param $payment_id
	 * @param $payer_id
	 *
	 * @return array|mixed|object
	 * @throws Paypal_Payments_Api_Exception
	 * @throws Paypal_Payments_Connection_Exception
	 */
	public function execute_payment( $payment_id, $payer_id ) {
		$url = $this->base_url . '/payments/payment/' . $payment_id . '/execute';

		$data = array(
			'payer_id' => $payer_id,
		);

		// Get response.
		$response      = $this->do_request( $url, 'POST', $data );
		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

		// Check if is WP_Error
		if ( is_wp_error( $response ) ) {
			throw new Paypal_Payments_Connection_Exception( $response->get_error_code(), $response->errors );
		}

		$code = wp_remote_retrieve_response_code( $response );

		// Check if response was created.
		if ( $code === 200 ) {
			return $response_body;
		}

		throw new Paypal_Payments_Api_Exception( $code, __( 'Não foi possível executar o pagamento.', 'paypal-payments' ), $response_body );
	}

	/**
	 * @param $payment_id
	 * @param $data
	 *
	 * @return array|mixed|object
	 * @throws Paypal_Payments_Api_Exception
	 * @throws Paypal_Payments_Connection_Exception
	 */
	public function update_payment( $payment_id, $data ) {
		$url = $this->base_url . '/payments/payment/' . $payment_id;

		// Get response.
		$response      = $this->do_request( $url, 'PATCH', $data );
		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

		// Check if is WP_Error
		if ( is_wp_error( $response ) ) {
			throw new Paypal_Payments_Connection_Exception( $response->get_error_code(), $response->errors );
		}

		$code = wp_remote_retrieve_response_code( $response );

		// Check if response was created.
		if ( $code === 200 ) {
			return $response_body;
		}

		throw new Paypal_Payments_Api_Exception( $code, __( 'Não foi possível atualizar o pagamento.', 'paypal-payments' ), $response_body );
	}

	/**
	 * Create Billing Agreement Token
	 *
	 * @return array|mixed|object
	 * @throws Paypal_Payments_Api_Exception
	 * @throws Paypal_Payments_Connection_Exception
	 */
	public function create_billing_agreement_token() {
		$url  = $this->base_url . '/billing-agreements/agreement-tokens';
		$data = array(
			'description' => sprintf( 'Billing Agreement', get_bloginfo( 'name' ) ),
			'payer'       => array(
				'payment_method' => 'PAYPAL',
			),
			'plan'        => array(
				'type'                 => 'MERCHANT_INITIATED_BILLING_SINGLE_AGREEMENT',
				'merchant_preferences' => array(
					'return_url'                 => esc_html( home_url() ),
					'cancel_url'                 => esc_html( home_url() ),
					'notify_url'                 => esc_html( home_url() ),
					'accepted_pymt_type'         => 'INSTANT',
					'skip_shipping_address'      => true,
					'immutable_shipping_address' => true,
				),
			),
		);

		// Get response.
		$response      = $this->do_request( $url, 'POST', $data );
		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

		// Check if is WP_Error
		if ( is_wp_error( $response ) ) {
			throw new Paypal_Payments_Connection_Exception( $response->get_error_code(), $response->errors );
		}

		$code = wp_remote_retrieve_response_code( $response );

		// Check if response was created.
		if ( $code === 201 ) {
			return $response_body;
		}

		throw new Paypal_Payments_Api_Exception( $code, __( 'Não foi possível criar o token de autorização de cobrança.', 'paypal-payments' ), $response_body );
	}

	public function create_billing_agreement( $token ) {
		$url  = $this->base_url . '/billing-agreements/agreements';
		$data = array(
			'token_id' => $token,
		);

		// Get response.
		$response      = $this->do_request( $url, 'POST', $data );
		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

		// Check if is WP_Error
		if ( is_wp_error( $response ) ) {
			throw new Paypal_Payments_Connection_Exception( $response->get_error_code(), $response->errors );
		}

		$code = wp_remote_retrieve_response_code( $response );

		// Check if response was created.
		if ( $code === 201 ) {
			return $response_body;
		}

		throw new Paypal_Payments_Api_Exception( $code, __( 'Não foi possível criar a autorização de cobrança.', 'paypal-payments' ), $response_body );
	}

	public function get_calculate_financing( $billing_agreement, $value ) {
		$url  = $this->base_url . '/credit/calculated-financing-options';
		$data = array(
			'financing_country_code' => 'BR',
			'transaction_amount'     => array(
				'value'         => $value,
				'currency_code' => 'BRL',
			),
			'funding_instrument'     => array(
				'type'              => 'BILLING_AGREEMENT',
				'billing_agreement' => array(
					'billing_agreement_id' => $billing_agreement,
				),
			),
		);

		// Get response.
		$response      = $this->do_request( $url, 'POST', $data );
		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

		// Check if is WP_Error
		if ( is_wp_error( $response ) ) {
			throw new Paypal_Payments_Connection_Exception( $response->get_error_code(), $response->errors );
		}

		$code = wp_remote_retrieve_response_code( $response );

		// Check if response was created.
		if ( $code === 200 ) {
			return $response_body;
		}

		throw new Paypal_Payments_Api_Exception( $code, __( 'Não foi possível obter as opções de parcelamento.', 'paypal-payments' ), $response_body );
	}

	/**
	 * Do requests in the API.
	 *
	 * @param string $url URL.
	 * @param string $method Request method.
	 * @param array $data Request data.
	 * @param array $headers Request headers.
	 *
	 * @return array            Request response.
	 * @throws Paypal_Payments_Api_Exception
	 * @throws Paypal_Payments_Connection_Exception
	 */
	protected function do_request( $url, $method = 'POST', $data = array(), $headers = array() ) {

		// Default headers.
		$headers = wp_parse_args( array(
			'Accept-Language' => get_locale(), // use default WP locale.
			'Content-Type'    => 'application/json;charset=UTF-8', // send as json for default.
		), $headers );

		// Set the partner attribution ID if exists.
		if ( $this->partner_attribution_id ) {
			$headers['PayPal-Partner-Attribution-Id'] = $this->partner_attribution_id;
		}

		// Add access token if needed.
		// In case is access token request, the authorization already exists, so no way
		// will reach the Paypal_Payments_Api_Exception and Paypal_Payments_Connection_Exception.
		if ( ! isset( $headers['Authorization'] ) ) {
			$headers['Authorization'] = 'Bearer ' . $this->get_access_token();
		}

		$params = array(
			'method'  => $method,
			'timeout' => 60,
			'headers' => $headers,
		);

		// Add the body for post requests.
		if ( in_array( $method, array( 'POST', 'PATCH' ) ) && ! empty( $data ) ) {
			if ( preg_match( '/(application\/json)/', $headers['Content-Type'] ) && is_array( $data ) ) {
				$data = json_encode( $data, JSON_UNESCAPED_UNICODE );
			}

			$params['body'] = $data;
		}

		return wp_safe_remote_request( $url, $params );
	}

	/**
	 * Parse the links from PayPal response.
	 *
	 * @param $links
	 *
	 * @return array
	 */
	public function parse_links( $links ) {
		$data = array();

		foreach ( $links as $link ) {
			$data[ $link['rel'] ] = $link['href'];
		}

		return $data;
	}
}