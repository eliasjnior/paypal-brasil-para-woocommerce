<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PayPal_Payments_API_Webhook_Handler extends PayPal_Payments_API_Handler {

	public function __construct() {
		add_filter( 'paypal_payments_handlers', array( $this, 'add_handlers' ) );
	}

	public function add_handlers( $handlers ) {
		$handlers['webhook'] = array(
			'callback' => array( $this, 'handle' ),
			'method'   => 'POST',
		);

		return $handlers;
	}

	public function handle() {
		$this->send_success_response( 'Handler for webhook.', array( 'test' => true ) );
	}

}

new PayPal_Payments_API_Webhook_Handler();