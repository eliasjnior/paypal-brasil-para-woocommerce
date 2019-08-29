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
		// Load plugin text domain.
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Include the necessary files and init.
		$this->includes();
		$this->init();

		// Check if Extra Checkout Fields for Brazil is installed.
		if ( is_admin() ) {
			// Notices for ECFB and WC.
			add_action( 'admin_notices', array( $this, 'ecfb_missing_notice' ) );
			add_action( 'admin_notices', array( $this, 'woocommerce_wrong_version' ) );

			// Add custom links to plugins page.
			add_filter( 'plugin_action_links_' . plugin_basename( PAYPAL_PAYMENTS_MAIN_FILE ), array(
				$this,
				'plugin_action_links'
			) );
		}

		// Check if WC is compatible.
		if ( ! self::woocommerce_incompatible() ) {
			add_action( 'plugins_loaded', array( $this, 'include_gateways' ) );
			add_filter( 'woocommerce_payment_gateways', array( $this, 'add_payment_methods' ) );
			add_action( 'init', array( $this, 'filter_gateways_settings' ) );
		}

	}

	public function filter_gateways_settings() {
		if ( isset( $_GET['page'] ) && isset( $_GET['tab'] ) && $_GET['page'] === 'wc-settings' && $_GET['tab'] && $_GET['tab'] === 'checkout' && isset( $_REQUEST['paypal-payments'] ) ) {
			add_filter( 'woocommerce_payment_gateways', array( $this, 'filter_allowed_gateways' ) );
		}
	}

	public function filter_allowed_gateways( $load_gateways ) {
		$allowed_gateways = array(
			'PayPal_Payments_SPB_Gateway',
		);
		foreach ( $load_gateways as $key => $gateway ) {
			if ( ! in_array( $gateway, $allowed_gateways ) ) {
				unset( $load_gateways[ $key ] );
			}
		}

		return $load_gateways;
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
			if ( ! in_array( get_woocommerce_currency(), self::get_allowed_currencies() ) ) {
				add_action( 'admin_notices', array( $this, 'woocommerce_unavailable_currency' ) );
			}
		} else {
			add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
		}
	}

	/**
	 * Action links.
	 *
	 * @param array $links Action links.
	 *
	 * @return array
	 */
	public function plugin_action_links( $links ) {
		$plugin_links = array(
			'<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&paypal-payments' ) ) . '">' . __( 'Configurações', 'paypal-payments' ) . '</a>',
		);

		return array_merge( $plugin_links, $links );
	}

	/**
	 * Load the plugin text domain for translation.
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'paypal-payments', false, dirname( plugin_basename( PAYPAL_PAYMENTS_MAIN_FILE ) ) . '/languages/' );
	}

	/**
	 * Return if WooCommerce is compatible or not.
	 * @return mixed
	 */
	public static function woocommerce_incompatible() {
		$version = get_option( 'woocommerce_version' );

		return version_compare( $version, '3.0.0', "<" );
	}

	/**
	 * WooCommerce Extra Checkout Fields for Brazil notice.
	 */
	public function ecfb_missing_notice() {
		// Check if Extra Checkout Fields for Brazil is installed, but check if it's BRL.
		if ( ! class_exists( 'Extra_Checkout_Fields_For_Brazil' ) ) {
			include dirname( __FILE__ ) . '/includes/views/notices/html-notice-missing-ecfb.php';
		}
	}

	/**
	 * WooCommerce wrong version notice.
	 */
	public function woocommerce_wrong_version() {
		if ( self::woocommerce_incompatible() ) {
			include dirname( __FILE__ ) . '/includes/views/notices/html-notice-wrong-version-woocommerce.php';
		}
	}

	/**
	 * WooCommerce missing notice.
	 */
	public function woocommerce_missing_notice() {
		include dirname( __FILE__ ) . '/includes/views/notices/html-notice-missing-woocommerce.php';
	}

	/**
	 * WooCommerce unavailable currency notice.
	 */
	public function woocommerce_unavailable_currency() {
		include dirname( __FILE__ ) . '/includes/views/notices/html-notice-woocommerce-unavailable-currency.php';
	}

	/**
	 * Get allowed currencies for this gateway.
	 * @return array
	 */
	public static function get_allowed_currencies() {
		return array( 'BRL' );
	}

}