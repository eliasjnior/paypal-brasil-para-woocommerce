<?php

// Ignore if access directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PayPal_Payments_Plus.
 *
 * @property string live_client_id
 * @property string live_secret
 * @property string webhook_id
 * @property string mode
 * @property string debug
 * @property string iframe_height
 * @property string invoice_id_prefix
 * @property string sandbox_client_id
 * @property string sandbox_secret
 * @property bool reference_transaction_enabled
 * @property bool shortcut_enabled
 */
class PayPal_Payments_SPB_Gateway extends PayPal_Payments_Gateway {

	private static $instance;

	/**
	 * PayPal_Payments_Plus constructor.
	 */
	public function __construct() {
		// Store some default gateway settings.
		$this->id                 = 'paypal-payments-spb-gateway';
		$this->has_fields         = true;
		$this->method_title       = __( 'PayPal SPB', 'paypal-payments' );
		$this->icon               = 'https://www.paypal-brasil.com.br/logocenter/util/img/botao-checkout_horizontal_pb.png';
		$this->method_description = '';
		$this->supports           = array(
			'products',
			'refunds',
		);

		// Load settings fields.
		$this->init_form_fields();
		$this->init_settings();

		// Get available options.
		$this->enabled           = $this->get_option( 'enabled' );
		$this->title             = 'PayPal SPB';
		$this->live_client_id    = $this->get_option( 'live_client_id' );
		$this->live_secret       = $this->get_option( 'live_secret' );
		$this->sandbox_client_id = 'ASpwwK3e6Xq319fcTEY4asiXBYzRZQK3kJLVZH5mQYf_7ZJw7cKzIScarLFGWwqcObuTKKYMPw6RLADw';
		$this->sandbox_secret    = 'EJWh8j2_IvgH-4CWwCnqrWOgvj_epwM0YCNrCRKfevUS9GIH04NEiK27H7hna3JofiRZ7hUj789aDX6j';
		$this->webhook_id        = $this->get_option( 'webhook_id' );
		$this->mode              = 'sandbox';
		$this->debug             = $this->get_option( 'debug' );
		$this->iframe_height     = $this->get_option( 'iframe_height' );
		$this->invoice_id_prefix = $this->get_option( 'invoice_id_prefix' );

		$this->reference_transaction_enabled = true;
		$this->shortcut_enabled              = true;

		// Instance the API.
		$this->api = new PayPal_Payments_API( $this->get_client_id(), $this->get_secret(), $this->mode );

		// Save settings.
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array(
			$this,
			'process_admin_options'
		), 10 );

		// Stop here if is not the first load.
		if ( ! $this->is_first_load() ) {
			return;
		}

		// Enqueue scripts.
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'checkout_scripts' ) );

		// Clear SPB session data when refresh fragments.
		add_action( 'woocommerce_checkout_update_order_review', array( $this, 'clear_spb_session_data' ) );

		// Process billing agreement
		add_action( 'woocommerce_checkout_update_order_review', array( $this, 'create_billing_agreement' ) );

		// Add shortcut button in cart.
		add_action( 'woocommerce_proceed_to_checkout', array( $this, 'shortcut_button_cart' ) );

		// Add shortcut button in mini cart.
		add_action( 'woocommerce_after_mini_cart', array( $this, 'shortcut_button_mini_cart' ) );

		// Add custom trigger for mini cart.
		add_action( 'woocommerce_after_mini_cart', array( $this, 'trigger_mini_cart_update' ) );

		// Render different things if is shortcut process.
		if ( $this->is_processing_shortcut() ) {

			// Add shortcut custom fields
			add_action( 'woocommerce_before_checkout_billing_form', array(
				$this,
				'shortcut_before_checkout_fields'
			) );

			// Add some fields to store data
			add_action( 'woocommerce_before_checkout_billing_form', array( $this, 'shortcut_checkout_fields' ) );

			// Remove all other gateways
			add_action( 'woocommerce_available_payment_gateways', array( $this, 'filter_gateways' ) );

			// Filter the page title.
			add_filter( 'the_title', array( $this, 'filter_review_title' ), 10, 2 );

			// If is NOT override address, we should remove unnecessary fields.
			if ( ! $this->is_shortcut_override_address() ) {
				// Filter form fields validation when is shortcut.
				add_filter( 'woocommerce_checkout_posted_data', array( $this, 'shortcut_filter_posted_data' ) );
				add_filter( 'wcbcf_disable_checkout_validation', '__return_true' );

				// Remove unnecessary fields.
				add_filter( 'woocommerce_billing_fields', array( $this, 'remove_billing_fields' ) );
				add_filter( 'woocommerce_shipping_fields', array( $this, 'remove_shipping_fields' ) );
			} else {
				// Pre populate with correct information.
				add_filter( 'woocommerce_checkout_get_value', array( $this, 'pre_populate_shortcut_fields' ), 10, 2 );
			}

		}

		// Content only for SPB.
		if ( $this->is_processing_spb() ) {

			// Add custom submit button.
			add_action( 'woocommerce_review_order_before_submit', array( $this, 'html_before_submit_button' ) );
			add_action( 'woocommerce_review_order_after_submit', array( $this, 'html_after_submit_button' ) );
		}

		// If it's first load, add a instance of this.
		self::$instance = $this;
	}

	/**
	 * Define gateway form fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'Habilitar/Desabilitar', 'paypal-plus-brasil' ),
				'type'    => 'checkbox',
				'label'   => __( 'Habilitar', 'paypal-plus-brasil' ),
				'default' => 'no',
			),
		);
	}

	/**
	 * Check if is first load of this class.
	 * This should prevent add double hooks.
	 *
	 * @return bool
	 */
	private function is_first_load() {
		return ! self::$instance;
	}

	/**
	 * Check if the try of make a shortcut request is invalid.
	 * @return bool|string
	 */
	private function is_invalid_shortcut_session() {
		// Check if is a ajax request first.
		if ( is_ajax() ) {
			$post_data      = $this->get_posted_data();
			$review_payment = isset( $post_data['paypal-payments-shortcut-review-payment'] ) ? sanitize_text_field( $post_data['paypal-payments-shortcut-review-payment'] ) : '';
			$pay_id         = isset( $post_data['paypal-payments-shortcut-pay-id'] ) ? sanitize_text_field( $post_data['paypal-payments-shortcut-pay-id'] ) : '';
			$payer_id       = isset( $post_data['paypal-payments-shortcut-payer-id'] ) ? sanitize_text_field( $post_data['paypal-payments-shortcut-payer-id'] ) : '';
		} else {
			$review_payment = isset( $_GET['paypal-payments-shortcut-review-payment'] ) ? sanitize_text_field( $_GET['paypal-payments-shortcut-review-payment'] ) : '';
			$pay_id         = isset( $_GET['paypal-payments-shortcut-pay-id'] ) ? sanitize_text_field( $_GET['paypal-payments-shortcut-pay-id'] ) : '';
			$payer_id       = isset( $_GET['paypal-payments-shortcut-payer-id'] ) ? sanitize_text_field( $_GET['paypal-payments-shortcut-payer-id'] ) : '';
		}

		if ( ! $review_payment ) {
			return 'missing_review_payment';
		} else if ( ! $payer_id ) {
			return 'missing_payer_id';
		} else if ( ! $pay_id ) {
			return 'missing_pay_id';
		}

		$session = WC()->session->get( 'paypal-payments-spb-shortcut-data' );
		if ( ! $session || $session['pay_id'] !== $pay_id ) {
			return 'invalid_pay_id';
		}

		return false;
	}

	/**
	 * Check if is shortcut and also overriding address.
	 * @return bool
	 */
	private function is_shortcut_override_address() {
		// Check if is $_GET.
		if ( $this->is_processing_shortcut() && isset( $_GET['override-address'] ) && $_GET['override-address'] ) {
			return true;
		}

		// Check if is $_POST (fragments)
		if ( $post_data = $this->get_posted_data() ) {
			if ( isset( $post_data['paypal-payments-shortcut-override-address'] ) && $post_data['paypal-payments-shortcut-override-address'] ) {
				return true;
			}
		}

		// Check if is $_POST (checkout)
		if ( isset( $_POST['paypal-payments-shortcut-override-address'] ) && $_POST['paypal-payments-shortcut-override-address'] ) {
			return true;
		}

		// It isn't, so we can return false.
		return false;
	}

	/**
	 * Get the posted data in $_POST['post_data'].
	 * @return array
	 */
	private function get_posted_data() {
		if ( isset( $_POST['post_data'] ) ) {
			parse_str( $_POST['post_data'], $post_data );

			return $post_data;
		}

		return array();
	}

	/**
	 * Check if we are processing spb checkout.
	 * Will check if is not processing reference transaction or shortcut, so it's spb.
	 * @return bool
	 */
	private function is_processing_spb() {
		return ! $this->is_processing_reference_transaction() && ! $this->is_processing_shortcut();
	}

	/**
	 * Populate checkout fields if is running shortcut override address.
	 *
	 * @param $input
	 * @param $key
	 *
	 * @return string
	 */
	public function pre_populate_shortcut_fields( $input, $key ) {

		$session = WC()->session->get( 'paypal_payments_shortcut_payer_info' );

		if ( $session ) {
			switch ( $key ) {
				case 'billing_first_name':
					return paypal_payments_explode_name( $session['shipping_name'] )['first_name'];
				case 'billing_last_name':
					return paypal_payments_explode_name( $session['shipping_name'] )['last_name'];
				case 'billing_persontype':
					return $session['persontype'];
				case 'billing_cpf':
					return $session['cpf'];
				case 'billing_cnpj':
					return $session['cnpj'];
				case 'billing_company':
					return $session['company'];
				case 'billing_country':
					return $session['country'];
				case 'billing_state':
					return $session['state'];
				case 'billing_city':
					return $session['city'];
				case 'billing_postcode':
					return $session['postcode'];
				case 'billing_email':
					return $session['email'];
				case 'billing_number':
				case 'billing_address_1':
				case 'billing_address_2';
				case 'billing_neighborhood':
					return '';
			}
		}

		return $input;
	}

	/**
	 * Check if is processing shortcut and if is a valid process.
	 *
	 * @return bool
	 */
	private function is_processing_shortcut() {
		// If shortcut is not enabled, we can say it's not.
		if ( ! $this->shortcut_enabled ) {
			return false;
		}

		// Check if is $_GET
		if ( isset( $_GET['review-payment'] ) && $_GET['review-payment']
		     && isset( $_GET['payer-id'] ) && $_GET['payer-id']
		     && isset( $_GET['pay-id'] ) && $_GET['pay-id'] ) {
			return true;
		}

		// Check if is $_POST (fragments)
		if ( isset( $_POST['post_data'] ) ) {
			parse_str( $_POST['post_data'], $post_data );

			if ( isset( $post_data['paypal-payments-shortcut-review-payment'] ) && $post_data['paypal-payments-shortcut-review-payment']
			     && isset( $post_data['paypal-payments-shortcut-payer-id'] ) && $post_data['paypal-payments-shortcut-payer-id']
			     && isset( $post_data['paypal-payments-shortcut-pay-id'] ) && $post_data['paypal-payments-shortcut-pay-id'] ) {
				return true;
			}
		}

		// Check if is $_POST (checkout)
		if ( isset( $_POST['paypal-payments-shortcut-review-payment'] ) && $_POST['paypal-payments-shortcut-review-payment']
		     && isset( $_POST['paypal-payments-shortcut-payer-id'] ) && $_POST['paypal-payments-shortcut-payer-id']
		     && isset( $_POST['paypal-payments-shortcut-pay-id'] ) && $_POST['paypal-payments-shortcut-pay-id'] ) {
			return true;
		}

		return false;
	}

	private function is_reference_transaction() {
		if ( ! $this->reference_transaction_enabled || ! is_user_logged_in() || $this->is_processing_shortcut() ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if is processing reference transaction.
	 * @return bool
	 */
	private function is_processing_reference_transaction() {
		// We can't process if is not enabled or user is not logged in.
		if ( ! $this->is_reference_transaction() ) {
			return false;
		}

		// If posted a billing agreement, we are processing.
		if ( isset( $_POST['paypal_payments_billing_agreement'] ) && $_POST['paypal_payments_billing_agreement'] ) {
			return true;
		}

		return false;
	}

	/**
	 * Filter shortcut data to add fields information sent by PayPal.
	 *
	 * @param $data
	 *
	 * @return mixed
	 */
	public function shortcut_filter_posted_data( $data ) {
		if ( $this->is_processing_shortcut() ) {
			$payer_info = WC()->session->get( 'paypal_payments_shortcut_payer_info' );

			if ( isset( $payer_info ) ) {
				$data['billing_first_name']   = $payer_info['first_name'];
				$data['billing_last_name']    = $payer_info['last_name'];
				$data['billing_persontype']   = $payer_info['persontype'] === '1' ? '1' : '2';
				$data['billing_cpf']          = $payer_info['cpf'];
				$data['billing_company']      = $payer_info['company'];
				$data['billing_cnpj']         = $payer_info['cnpj'];
				$data['billing_country']      = $payer_info['country'];
				$data['billing_postcode']     = $payer_info['postcode'];
				$data['billing_address_1']    = $payer_info['address_line_1'];
				$data['billing_number']       = '';
				$data['billing_neighborhood'] = '';
				$data['billing_city']         = $payer_info['city'];
				$data['billing_state']        = $payer_info['state'];
				$data['billing_email']        = $payer_info['email'];

				$data['shipping_first_name']   = $payer_info['shipping_name'];
				$data['shipping_company']      = $payer_info['company'];
				$data['shipping_country']      = $payer_info['country'];
				$data['shipping_postcode']     = $payer_info['postcode'];
				$data['shipping_address_1']    = $payer_info['address_line_1'];
				$data['shipping_number']       = '';
				$data['shipping_neighborhood'] = '';
				$data['shipping_city']         = $payer_info['city'];
				$data['shipping_state']        = $payer_info['state'];
			}
		}

		return $data;
	}

	public function create_billing_agreement( $input_data ) {
		parse_str( $input_data, $post_data );

		// If we sent a billing agreement token, we should create this token to the user.
		// For safety, check the user session.
		$session_billing_agreement_token = WC()->session->get( 'paypal_payments_billing_agreement_token' );
		// The checkbox should have empty value, otherwise the billing agreement is selected.
		if ( empty( $post_data['paypal_payments_billing_agreement'] ) ) {
			// If we have the billing agreement token, we should create the billing agreement.
			if ( ! empty( $post_data['paypal_payments_billing_agreement_token'] ) ) {
				if ( ! $session_billing_agreement_token || $session_billing_agreement_token !== $post_data['paypal_payments_billing_agreement_token'] ) {
					// This means something happened with user session and billing agreement token doesn't match.
					wc_add_notice( __( 'Houve um problema na verificação da sessão do token de acordo de pagamento.', 'paypal-payments' ), 'error' );
				} else {
					try {
						// Create the billing agreement.
						$billing_agreement = $this->api->create_billing_agreement( $post_data['paypal_payments_billing_agreement_token'] );
						// Save the billing agreement to the user.
						update_user_meta( get_current_user_id(), 'paypal_payments_billing_agreement_id', $billing_agreement['id'] );
						update_user_meta( get_current_user_id(), 'paypal_payments_billing_agreement_payer_info', $billing_agreement['payer']['payer_info'] );
					} catch ( Paypal_Payments_Api_Exception $ex ) {
						// Some problem happened creating billing agreement.
						wc_add_notice( __( 'Houve um erro na criação da autorização de pagamento.', 'paypal-payments' ), 'error' );
					}
				}
			} else {
				// We don't have the billing agreement and also don't have the token, something wrong
				// Probably the user updated the cart without opening lightbox, so do nothing.
			}

			// At the end, clean the token session.
			unset( WC()->session->paypal_payments_billing_agreement_token );
		}
	}

	/**
	 * Change the page title for shortcut.
	 *
	 * @param $title
	 * @param $id
	 *
	 * @return string
	 */
	public function filter_review_title( $title, $id ) {
		if ( $id === wc_get_page_id( 'checkout' ) ) {
			return __( 'Revisão de Pagamento', 'paypal-payments' );
		}

		return $title;
	}

	public function shortcut_before_checkout_fields() {
		include dirname( PAYPAL_PAYMENTS_MAIN_FILE ) . '/includes/views/checkout/shortcut-before-checkout-fields.php';
	}

	/**
	 * Add hidden fields to store params data to get when fragments is refreshed.
	 */
	public function shortcut_checkout_fields() {
		include dirname( PAYPAL_PAYMENTS_MAIN_FILE ) . '/includes/views/checkout/shortcut-checkout-fields.php';
	}

	/**
	 * Allow only the current gateway in checkout.
	 *
	 * @param $gateways
	 *
	 * @return mixed
	 */
	public function filter_gateways( $gateways ) {
		foreach ( $gateways as $key => $gateway ) {
			if ( $key !== $this->id ) {
				unset( $gateways[ $key ] );
			}
		}

		return $gateways;
	}

	/**
	 * On shortcut remove billing fields.
	 *
	 * @param $fields
	 *
	 * @return mixed
	 */
	public function remove_billing_fields( $fields ) {
		unset( $fields['billing_first_name'] );
		unset( $fields['billing_last_name'] );
		unset( $fields['billing_persontype'] );
		unset( $fields['billing_cpf'] );
		unset( $fields['billing_cnpj'] );
		unset( $fields['billing_company'] );
		unset( $fields['billing_country'] );
		unset( $fields['billing_address_1'] );
		unset( $fields['billing_number'] );
		unset( $fields['billing_address_2'] );
		unset( $fields['billing_neighborhood'] );
		unset( $fields['billing_city'] );
		unset( $fields['billing_state'] );
		unset( $fields['billing_postcode'] );
		unset( $fields['billing_email'] );

		return $fields;
	}

	/**
	 * On shortcut remove shipping fields.
	 *
	 * @param $fields
	 *
	 * @return mixed
	 */
	public function remove_shipping_fields( $fields ) {
		unset( $fields['shipping_first_name'] );
		unset( $fields['shipping_last_name'] );
		unset( $fields['shipping_company'] );
		unset( $fields['shipping_country'] );
		unset( $fields['shipping_address_1'] );
		unset( $fields['shipping_number'] );
		unset( $fields['shipping_address_2'] );
		unset( $fields['shipping_neighborhood'] );
		unset( $fields['shipping_city'] );
		unset( $fields['shipping_state'] );
		unset( $fields['shipping_postcode'] );

		return $fields;
	}

	/**
	 * Trigger a script in mini cart to alert our JS when mini cart is updated.
	 */
	public function trigger_mini_cart_update() {
		echo '<script>jQuery("body").trigger("updated_mini_cart");</script>';
	}

	/**
	 * Render shortcut button in cart.
	 */
	public function shortcut_button_cart() {
		echo '<div class="shortcut-button"></div>';
	}

	/**
	 * Render shortcut button in mini cart.
	 */
	public function shortcut_button_mini_cart() {
		if ( ! WC()->cart->is_empty() ) {
			echo '<div class="shortcut-button-mini-cart"></div>';
		}
	}

	/**
	 * Clear SPB session data every time checkout fragments is updated.
	 * As checkout fragment is updated when some field is updated, we should
	 * render the button again to create a new payment with correct data.
	 * As we compare the session data when process the payment, we should check
	 * if is sending the last payment id.
	 *
	 * @param $fragments
	 *
	 * @return mixed
	 */
	public function clear_spb_session_data() {
		// Set payment_token to null. It's a security reason.
		WC()->session->set( 'paypal_payments_spb_data', null );
	}

	/**
	 * Add a code before submit button to show and hide ours.
	 */
	public function html_before_submit_button() {
		echo '<div id="paypal-payments-button-container"><div class="default-submit-button">';
	}

	/**
	 * Add a code after submit button to show and hide ours.
	 */
	public function html_after_submit_button() {
		echo '</div><!-- .default-submit-button -->';
		echo '<div class="paypal-submit-button"><div id="paypal-button"></div></div>';
		echo '</div><!-- #paypal-spb-container -->';
	}

	/**
	 * Get if gateway is available.
	 *
	 * @return bool
	 */
	public function is_available() {
		return true;
	}

	/**
	 * Clear all user session. This should be used after process payment.
	 * Will clean every session for all integrations, as we don't need that anymore.
	 */
	private function clear_all_sessions() {
		$sessions = array(
			'paypal-payments-spb-data',
			'paypal-payments-spb-shortcut-data',
			'paypal_payments_billing_agreement_token',
			'paypal-payments-spb-data',
			'paypal_payments_shortcut_payer_info',
		);

		// Each session will be destroyed.
		foreach ( $sessions as $session ) {
			unset( WC()->session->{$session} );
		}
	}

	/**
	 * @param WC_order $order
	 *
	 * @return array
	 * @throws Paypal_Payments_Api_Exception
	 * @throws Paypal_Payments_Connection_Exception
	 */
	private function process_payment_shortcut( $order ) {
		$data = array(
			array(
				'op'    => 'replace',
				'path'  => '/transactions/0/amount',
				'value' => array(
					'total'    => $order->get_total(),
					'currency' => $order->get_currency(),
					'details'  => array(
						'subtotal' => $order->get_subtotal(),
						'shipping' => $order->get_shipping_total(),
					),
				),
			),
			array(
				'op'    => 'replace',
				'path'  => '/transactions/0/item_list/items',
				'value' => paypal_payments_get_order_items( $order ),
			),
		);

		// Path address if needed.
		if ( $this->is_shortcut_override_address() ) {
			$data[] = array(
				'op'    => 'replace',
				'path'  => '/transactions/0/item_list/shipping_address',
				'value' => paypal_payments_get_shipping_address( $order ),
			);
		}

		$session  = WC()->session->get( 'paypal_payments_spb_shortcut_data' );
		$payer_id = isset( $_POST['paypal-payments-shortcut-payer-id'] ) ? sanitize_text_field( $_POST['paypal-payments-shortcut-payer-id'] ) : '';

		// Execute API requests.
		$this->api->update_payment( $session['pay_id'], $data );
		$this->api->execute_payment( $session['pay_id'], $payer_id );

		// Process the order.
		$order->payment_complete();

		// Clear all sessions for this order.
		$this->clear_all_sessions();

		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		);
	}

	/**
	 * @param WC_Order $order
	 *
	 * @return array
	 * @throws Paypal_Payments_Api_Exception
	 * @throws Paypal_Payments_Connection_Exception
	 */
	private function process_payment_reference_transaction( $order ) {
		$installment = isset( $_POST['paypal_payments_billing_agreement_installment'] ) ? json_decode( stripslashes( $_POST['paypal_payments_billing_agreement_installment'] ), true ) : array();

		$data = array(
			'intent'        => 'sale',
			'payer'         => array(
				'payment_method'      => 'paypal',
				'funding_instruments' => array(
					array(
						'billing' => array(
							'billing_agreement_id'        => get_user_meta( get_current_user_id(), 'paypal_payments_billing_agreement_id', true ),
							'selected_installment_option' => $installment,
						),
					),
				),
			),
			'transactions'  => array(
				array(
					'amount'         => array(
						'currency' => $order->get_currency(),
						'total'    => $order->get_total(),
						'details'  => array(
							'shipping' => $order->get_shipping_total(),
							'subtotal' => $order->get_subtotal(),
						),
					),
					'description'    => sprintf( __( 'Pagamento do pedido #%s na loja %s', 'paypal-payments' ), $order->get_id(), get_bloginfo( 'name' ) ),
					'invoice_number' => sprintf( '%s-%s', $order->get_id(), uniqid() ),
					'item_list'      => array(
						'shipping_address' => paypal_payments_get_shipping_address( $order ),
						'items'            => paypal_payments_get_order_items( $order ),
					),

				),
			),
			'redirect_urls' => array(
				'return_url' => home_url(),
				'cancel_url' => home_url(),
			),
		);

		// Make API request.
		$this->api->create_payment( $data );

		// If has discount, add to order information.
		if ( $discount_value = floatval( $installment['discount_amount']['value'] ) ) {
			$discount = new WC_Order_Item_Fee();
			$discount->set_amount( - $discount_value );
			$discount->set_total( - $discount_value );
			$discount->set_name( sprintf( __( 'Desconto PayPal (%d%%)', 'paypal-payments' ), floatval( $installment['discount_percentage'] ) ) );
			$discount->save();

			$order->add_item( $discount );
			$order->calculate_totals();
			$order->save();
		}

		// Process the order.
		$order->payment_complete();

		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		);
	}

	/**
	 * @param WC_Order $order
	 *
	 * @return array
	 * @throws Paypal_Payments_Api_Exception
	 * @throws Paypal_Payments_Connection_Exception
	 */
	private function process_payment_spb( $order ) {
		$spb_order_id = sanitize_text_field( $_POST['paypal-payments-spb-order-id'] );
		$spb_payer_id = sanitize_text_field( $_POST['paypal-payments-spb-payer-id'] );
		$spb_pay_id   = sanitize_text_field( $_POST['paypal-payments-spb-pay-id'] );

		$this->api->execute_payment( $spb_pay_id, $spb_payer_id );

		$order->payment_complete();

		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		);
	}

	/**
	 * Process gateway payment for a given order ID.
	 *
	 * @param $order_id
	 *
	 * @return array
	 * @throws Paypal_Payments_Api_Exception
	 * @throws Paypal_Payments_Connection_Exception
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( $this->is_processing_shortcut() ) {
			return $this->process_payment_shortcut( $order );
		} else if ( $this->is_processing_reference_transaction() ) {
			return $this->process_payment_reference_transaction( $order );
		} else if ( $this->is_processing_spb() ) {
			return $this->process_payment_spb( $order );
		} else {
			wc_add_notice( __( 'O método de pagamento não foi detectado corretamente. Por favor, tente novamente.', 'paypal-payments' ), 'error' );
		}
	}

	/**
	 * Process gateway refund for a given order ID.
	 *
	 * @param $order_id
	 * @param null $amount
	 * @param string $reason
	 *
	 * @return bool
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		return parent::process_refund( $order_id, $amount, $reason );
	}

	/**
	 * Frontend Payment Fields.
	 */
	public function payment_fields() {
		if ( $this->is_processing_shortcut() ) {
			echo 'Seu pagamento já está aprovado, revise seu pagamento e finalize a compra.';
		} else if ( $this->is_reference_transaction() ) {
			include dirname( PAYPAL_PAYMENTS_MAIN_FILE ) . '/includes/views/checkout/reference-transaction-html-fields.php';
		} else {
			include dirname( PAYPAL_PAYMENTS_MAIN_FILE ) . '/includes/views/checkout/spb-checkout-fields.php';
		}
	}

	/**
	 * Backend view for admin options.
	 */
	public function admin_options() {
		include dirname( PAYPAL_PAYMENTS_MAIN_FILE ) . '/includes/views/admin-options/admin-options-plus.php';
	}

	/**
	 * Enqueue admin scripts for gateway settings page.
	 */
	public function admin_scripts() {
		$screen         = get_current_screen();
		$screen_id      = $screen ? $screen->id : '';
		$wc_screen_id   = sanitize_title( __( 'WooCommerce', 'paypal-plus-brasil' ) );
		$wc_settings_id = $wc_screen_id . '_page_wc-settings';

		// Check if we are on the gateway settings page.
		if ( $wc_settings_id === $screen_id && isset( $_GET['section'] ) && $_GET['section'] === $this->id ) {

			// Add shared file if exists.
			if ( file_exists( dirname( PAYPAL_PAYMENTS_MAIN_FILE ) . '/assets/dist/shared.js' ) ) {
				wp_enqueue_script( 'paypal_payments_admin_options_shared', plugins_url( 'assets/dist/shared.js', PAYPAL_PAYMENTS_MAIN_FILE ), array(), '1.0.0', true );
			}

			// Enqueue admin options and localize settings.
			wp_enqueue_script( $this->id . '_script', plugins_url( 'assets/dist/main.js', PAYPAL_PAYMENTS_MAIN_FILE ), array(), '1.0.0', true );
			wp_localize_script( $this->id . '_script', 'paypal_payments_admin_options_plus', array(
				'template'          => $this->get_admin_options_template(),
				'enabled'           => $this->enabled,
				'title'             => $this->title,
				'mode'              => $this->mode,
				'live_client_id'    => $this->live_client_id,
				'live_secret'       => $this->live_secret,
				'sandbox_client_id' => $this->sandbox_client_id,
				'sandbox_secret'    => $this->sandbox_secret,
				'debug'             => $this->debug,
			) );

			wp_enqueue_style( $this->id . '_script', plugins_url( 'assets/dist/main.js', PAYPAL_PAYMENTS_MAIN_FILE ), array(), '1.0.0', true );

		}
	}

	/**
	 * Get the admin options template to render by Vue.
	 */
	private function get_admin_options_template() {
		ob_start();
		include dirname( PAYPAL_PAYMENTS_MAIN_FILE ) . '/includes/views/admin-options/admin-options-plus-template.php';

		return ob_get_clean();
	}

	/**
	 * Check if shortcut is enabled.
	 * @return bool
	 */
	private function is_shortcut_enabled() {
		return $this->shortcut_enabled;
	}

	/**
	 * Enqueue scripts in checkout.
	 */
	public function checkout_scripts() {
		// PayPal SDK arguments.
		$paypal_args = array(
			'currency'  => 'BRL',
			'client-id' => $this->get_client_id(),
		);

		// Enqueue shared.
		wp_enqueue_script( 'paypal-payments-shared', plugins_url( 'assets/js/shared.js', PAYPAL_PAYMENTS_MAIN_FILE ), array(), null, true );
		wp_localize_script( 'paypal-payments-shared', 'paypal_payments_settings', array(
			'nonce'                       => wp_create_nonce( 'paypal-payments-checkout' ),
			'is_reference_transaction'    => $this->reference_transaction_enabled,
			'current_user_id'             => get_current_user_id(),
			'paypal_payments_handler_url' => add_query_arg( array(
				'wc-api' => 'paypal_payments_handler',
				'action' => '{ACTION}'
			), home_url() ),
			'checkout_page_url'           => wc_get_checkout_url(),
			'checkout_review_page_url'    => add_query_arg( array(
				'review-payment' => '1',
				'pay-id'         => '{PAY_ID}',
				'payer-id'       => '{PAYER_ID}',
			), wc_get_checkout_url() ),
		) );

		if ( $this->is_reference_transaction() ) { // reference transaction checkout
			$paypal_args['vault'] = 'true';
			wp_enqueue_script( 'paypal-payments-reference-transaction', plugins_url( 'assets/js/reference-transaction.js', PAYPAL_PAYMENTS_MAIN_FILE ), array(), null, true );
			ob_start();
			wc_print_notice( __( 'Você cancelou a criação do token. Reinicie o processo de checkout.', 'paypal-payments' ), 'error' );
			$cancel_message = ob_get_clean();

			wp_localize_script( 'paypal-payments-reference-transaction', 'paypal_payments_reference_transaction_settings', array(
				'cancel_message' => $cancel_message,
			) );

		} else if ( ! $this->is_processing_shortcut() ) { // spb checkout
			wp_enqueue_script( 'paypal-payments-spb', plugins_url( 'assets/js/spb.js', PAYPAL_PAYMENTS_MAIN_FILE ), array(), null, true );

			ob_start();
			wc_print_notice( __( 'Você cancelou o pagamento.', 'paypal-payments' ), 'error' );
			$cancel_message = ob_get_clean();

			wp_localize_script( 'paypal-payments-spb', 'paypal_payments_spb_settings', array(
				'cancel_message' => $cancel_message,
			) );
		}

		// Shortcut
		if ( $this->is_shortcut_enabled() ) {
			wp_enqueue_script( 'paypal-payments-shortcut', plugins_url( 'assets/js/shortcut.js', PAYPAL_PAYMENTS_MAIN_FILE ), array(), null, true );
		}

		wp_enqueue_script( 'paypal-payments-scripts', add_query_arg( $paypal_args, 'https://www.paypal.com/sdk/js' ), array(), null, true );

	}

}