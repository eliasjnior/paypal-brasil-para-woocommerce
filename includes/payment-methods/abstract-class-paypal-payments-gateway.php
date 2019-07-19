<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class PayPal_Payments_Gateway extends WC_Payment_Gateway {

	public $mode;

	public $live_client_id;
	public $live_secret;
	public $sandbox_client_id;
	public $sandbox_secret;

	public $webook_id;

	public $invoice_id_prefix;

	public $partner_attribution_id;

	/**
	 * @var PayPal_Payments_API
	 */
	public $api;

	public function get_client_id() {
		return $this->mode === 'sandbox' ? $this->sandbox_client_id : $this->live_client_id;
	}

	public function get_secret() {
		return $this->mode === 'sandbox' ? $this->sandbox_secret : $this->live_secret;
	}

}