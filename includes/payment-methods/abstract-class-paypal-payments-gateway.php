<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class PayPal_Payments_Gateway extends WC_Payment_Gateway {

	public $mode;
	public $debug;

	public $client_live;
	public $client_sandbox;
	public $secret_live;
	public $secret_sandbox;

	public $webhook_id;

	/**
	 * @var WC_Logger
	 */
	public $logger;

	public $partner_attribution_id;

	/**
	 * @var PayPal_Payments_API
	 */
	public $api;

	public function __construct() {
		$this->logger = new WC_Logger();
	}

	public function get_client_id() {
		return $this->mode === 'sandbox' ? $this->client_sandbox : $this->client_live;
	}

	public function get_secret() {
		return $this->mode === 'sandbox' ? $this->secret_sandbox : $this->secret_live;
	}

	public function log( $data ) {
		if ( $this->debug === 'yes' ) {
			$this->logger->add( $this->id, $data );
		}
	}

	/**
	 * Retrieve the raw request entity (body).
	 *
	 * @return string
	 */
	public function get_raw_data() {
		// $HTTP_RAW_POST_DATA is deprecated on PHP 5.6
		if ( function_exists( 'phpversion' ) && version_compare( phpversion(), '5.6', '>=' ) ) {
			return file_get_contents( 'php://input' );
		}
		global $HTTP_RAW_POST_DATA;
		// A bug in PHP < 5.2.2 makes $HTTP_RAW_POST_DATA not set by default,
		// but we can do it ourself.
		if ( ! isset( $HTTP_RAW_POST_DATA ) ) {
			$HTTP_RAW_POST_DATA = file_get_contents( 'php://input' );
		}

		return $HTTP_RAW_POST_DATA;
	}

	/**
	 * Handle webhooks from PayPal.
	 */
	public function webhook_handler() {
		include_once dirname( PAYPAL_PAYMENTS_MAIN_FILE ) . '/includes/handlers/class-paypal-payments-webhooks-handler.php';

		try {
			// Instance the handler.
			$handler = new PayPal_Payments_Webhooks_Handler( $this->id );

			// Get the data.
			$headers = array_change_key_case( getallheaders(), CASE_UPPER );
			$body    = $this->get_raw_data();

			$webhook_event = json_decode( $body, true );

			// Prepare the signature verification.
			$signature_verification = array(
				'auth_algo'         => $headers['PAYPAL-AUTH-ALGO'],
				'cert_url'          => $headers['PAYPAL-CERT-URL'],
				'transmission_id'   => $headers['PAYPAL-TRANSMISSION-ID'],
				'transmission_sig'  => $headers['PAYPAL-TRANSMISSION-SIG'],
				'transmission_time' => $headers['PAYPAL-TRANSMISSION-TIME'],
				'webhook_id'        => $this->webhook_id,
			);

			$payload = "{";
			foreach ( $signature_verification as $field => $value ) {
				$payload .= "\"$field\": \"$value\",";
			}
			$payload .= "\"webhook_event\": $body";
			$payload .= "}";

			$signature_response = $this->api->verify_signature( $payload );

			if ( $signature_response['verification_status'] === 'SUCCESS' ) {
				$handler->handle( $webhook_event );
			}
		} catch ( Exception $ex ) {
		}
	}

	/**
	 * Checks if the CNPJ is valid.
	 *
	 * @param string $cnpj CNPJ to validate.
	 *
	 * @return bool
	 */
	public function is_cnpj( $cnpj ) {
		$cnpj = sprintf( '%014s', preg_replace( '{\D}', '', $cnpj ) );
		if ( 14 !== strlen( $cnpj ) || 0 === intval( substr( $cnpj, - 4 ) ) ) {
			return false;
		}
		for ( $t = 11; $t < 13; ) {
			for ( $d = 0, $p = 2, $c = $t; $c >= 0; $c --, ( $p < 9 ) ? $p ++ : $p = 2 ) {
				$d += $cnpj[ $c ] * $p;
			}
			if ( intval( $cnpj[ ++ $t ] ) !== ( $d = ( ( 10 * $d ) % 11 ) % 10 ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Checks if the CPF is valid.
	 *
	 * @param string $cpf CPF to validate.
	 *
	 * @return bool
	 */
	public function is_cpf( $cpf ) {
		$cpf = preg_replace( '/[^0-9]/', '', $cpf );
		if ( 11 !== strlen( $cpf ) || preg_match( '/^([0-9])\1+$/', $cpf ) ) {
			return false;
		}
		$digit = substr( $cpf, 0, 9 );
		for ( $j = 10; $j <= 11; $j ++ ) {
			$sum = 0;
			for ( $i = 0; $i < $j - 1; $i ++ ) {
				$sum += ( $j - $i ) * intval( $digit[ $i ] );
			}
			$summod11        = $sum % 11;
			$digit[ $j - 1 ] = $summod11 < 2 ? 0 : 11 - $summod11;
		}

		return intval( $digit[9] ) === intval( $cpf[9] ) && intval( $digit[10] ) === intval( $cpf[10] );
	}

}