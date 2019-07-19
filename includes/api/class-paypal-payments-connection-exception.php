<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class Paypal_Payments_Connection_Exception.
 */
class Paypal_Payments_Connection_Exception extends Exception {
	protected $error_code;
	protected $data;

	/**
	 * Paypal_Payments_Connection_Exception constructor.
	 *
	 * @param mixed $error_code
	 * @param mixed $data
	 */
	public function __construct( $error_code = '', $data = null ) {
		$this->error_code = $error_code;
		$this->data       = $data;
		parent::__construct( __( 'Houve um erro de conexão com o PayPal.', 'paypal-payments' ), $error_code );
	}

	/**
	 * Get the error code.
	 *
	 * @return mixed
	 */
	public function getErrorCode() {
		return $this->error_code;
	}

	/**
	 * Get error data.
	 * @return mixed
	 */
	public function getData() {
		return $this->data;
	}
}