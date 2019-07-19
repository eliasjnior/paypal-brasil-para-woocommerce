<?php

// Ignore if access directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PayPal_Payments.
 * @property PayPal_Payments_Handler handler
 */
class PayPal_Payments {

	/**
	 * @var PayPal_Payments
	 */
	private static $instance;

	/**
	 * PayPal_Payments constructor.
	 */
	private function __construct() {
		$this->includes();
		$this->init();
		add_action( 'plugins_loaded', array( $this, 'include_gateways' ) );
		add_filter( 'woocommerce_payment_gateways', array( $this, 'add_payment_methods' ) );
	}

	/**
	 * Get plugin instance.
	 *
	 * @return PayPal_Payments
	 */
	public static function get_instance() {
		// Init a instance if not created.
		if ( ! self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Include files.
	 */
	private function includes() {
		include dirname( __FILE__ ) . '/includes/helpers.php';
		include dirname( __FILE__ ) . '/includes/api/class-paypal-payments-api.php';
		include dirname( __FILE__ ) . '/includes/api/class-paypal-payments-api-exception.php';
		include dirname( __FILE__ ) . '/includes/api/class-paypal-payments-connection-exception.php';
		include dirname( __FILE__ ) . '/includes/handlers/class-paypal-payments-handler.php';
	}

	/**
	 * Init necessary classes.
	 */
	private function init() {
		$this->handler = new PayPal_Payments_Handler();
	}

	/**
	 * Add plugin payment methods to WooCommerce methods.
	 *
	 * @param $methods
	 *
	 * @return array
	 */
	public function add_payment_methods( $methods ) {
		$methods[] = 'PayPal_Payments_SPB_Gateway';

		return $methods;
	}

	/**
	 * Include payment gateways.
	 */
	public function include_gateways() {
		if ( class_exists( 'WC_Payment_Gateway' ) ) {
			include dirname( __FILE__ ) . '/includes/payment-methods/abstract-class-paypal-payments-gateway.php';
			include_once dirname( __FILE__ ) . '/includes/payment-methods/class-paypal-payments-spb-gateway.php';
		}
	}

}