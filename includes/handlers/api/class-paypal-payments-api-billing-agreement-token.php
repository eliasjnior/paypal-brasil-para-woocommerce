<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PayPal_Payments_API_Billing_Agreement_Token_Handler extends PayPal_Payments_API_Handler {

	public function __construct() {
		add_filter( 'paypal_payments_handlers', array( $this, 'add_handlers' ) );
	}

	public function add_handlers( $handlers ) {
		$handlers['billing-agreement-token'] = array(
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
				'key'      => 'nonce',
				'sanitize' => 'sanitize_text_field',
				'validation' => array( $this, 'required_nonce' ),
			),
			array(
				'key'        => 'user_id',
				'sanitize'   => 'sanitize_text_field',
				'validation' => array( $this, 'required_current_user_id' ),
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

				$error_message = __( 'Alguns campos estão faltando para criar o token de autorização de pagamento.', 'paypay-payments' );

				$errors   = array();
				$errors[] = '<p>' . $error_message . '</p>';
				$errors[] = '<ul>';
				foreach ( $validation['errors'] as $key => $value ) {
					$errors[] = '<li>' . $value . '</li>';
				}
				$errors[] = '</ul>';

				ob_start();
				wc_print_notice( implode( '', $errors ), 'error' );
				$error_message_notice = ob_get_clean();

				$this->send_error_response(
					$error_message,
					array(
						'errors'       => $validation['errors'],
						'error_notice' => $error_message_notice,
					)
				);
			}

			// Get the wanted gateway.
			$gateway = $this->get_paypal_gateway( 'paypal-payments-spb-gateway' );

			// Create new token
			$response = $gateway->api->create_billing_agreement_token();

			// Store the requested data in session.
			WC()->session->set( 'paypal_payments_billing_agreement_token', $response['token_id'] );

			// Send success response with data.
			$this->send_success_response( __( 'Token criado com sucesso.', 'paypal-payments' ), array(
				'token_id' => $response['token_id'],
			) );
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

	public function required_current_user_id( $data, $key ) {
		if ( ! $data || get_current_user_id() != $data ) {
			return __( 'Você precisa estar logado para continuar com esse tipo de pagamento.', 'paypal-payments' );
		}

		return true;
	}

	// CUSTOM SANITIZER

	public function sanitize_boolean( $data, $key ) {
		return ! ! $data;
	}

}

new PayPal_Payments_API_Billing_Agreement_Token_Handler();