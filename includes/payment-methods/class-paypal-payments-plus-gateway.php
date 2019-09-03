<?php

// Ignore if access directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PayPal_Payments_Plus_Gateway.
 *
 * @property string client_live
 * @property string client_sandbox
 * @property string secret_live
 * @property string secret_sandbox
 * @property string format
 * @property string color
 * @property string shortcut_enabled
 * @property string reference_enabled
 * @property string debug
 * @property string invoice_id_prefix
 * @property string client_id
 * @property string client_secret
 * @property string wrong_credentials
 * @property string form_height
 */
class PayPal_Payments_Plus_Gateway extends PayPal_Payments_Gateway {

	/**
	 * PayPal_Payments_Plus_Gateway constructor.
	 */
	public function __construct() {
		parent::__construct();

		// Set default settings.
		$this->id                 = 'paypal-payments-plus-gateway';
		$this->has_fields         = true;
		$this->method_title       = __( 'PayPal Brasil', 'paypal-payments' );
		$this->method_description = __( 'Adicione as soluções da carteira digital do PayPal em sua loja WooCommerce.', 'paypal-payments' );
		$this->supports           = array(
			'products',
			'refunds',
		);

		// Load settings fields.
		$this->init_form_fields();
		$this->init_settings();

		// Get options in variable.
		$this->title             = $this->get_option( 'title' );
		$this->client_id         = $this->get_option( 'client_id' );
		$this->client_secret     = $this->get_option( 'client_secret' );
		$this->webhook_id        = $this->get_option( 'webhook_id' );
		$this->mode              = $this->get_option( 'mode' );
		$this->debug             = $this->get_option( 'debug' );
		$this->wrong_credentials = $this->get_option( 'wrong_credentials' );
		$this->form_height       = $this->get_option( 'form_height' );
		$this->invoice_id_prefix = $this->get_option( 'invoice_id_prefix', '' );

		// Instance the API.
		$this->api = new PayPal_Payments_API( $this->get_client_id(), $this->get_secret(), $this->mode, $this );

		// Handler for IPN.
		add_action( 'woocommerce_api_' . $this->id, array( $this, 'webhook_handler' ) );

		// Update web experience profile id before actually saving.
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array(
			$this,
			'before_process_admin_options'
		), 1 );

		// Now save with the save hook.
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array(
			$this,
			'process_admin_options'
		), 10 );

		// Filter the save data to add a custom experience profile id.
		add_filter( 'woocommerce_settings_api_sanitized_fields_' . $this->id, array( $this, 'filter_save_data' ) );

		// Enqueue scripts.
		add_action( 'wp_enqueue_scripts', array( $this, 'checkout_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
	}

	/**
	 * Replace method to get client id due different implementation.
	 * @return mixed
	 */
	public function get_client_id() {
		return $this->client_id;
	}

	/**
	 * Replace method to get secret due different implementation.
	 * @return mixed
	 */
	public function get_secret() {
		return $this->client_secret;
	}

	/**
	 * Check if the gateway is available for use.
	 *
	 * @return bool
	 */
	public function is_available() {
		$is_available = ( 'yes' === $this->enabled );

		if ( WC()->cart && 0 < $this->get_order_total() && 0 < $this->max_amount && $this->max_amount < $this->get_order_total() ) {
			$is_available = false;
		}

		if ( ! $this->client_id || ! $this->client_secret || ! $this->webhook_id || $this->wrong_credentials === 'yes' ) {
			$is_available = false;
		}

		return $is_available;
	}

	/**
	 * Set some settings before save the options.
	 */
	public function before_process_admin_options() {
		$client_id_key     = $this->get_field_key( 'client_id' );
		$client_secret_key = $this->get_field_key( 'client_secret' );
		$mode_key          = $this->get_field_key( 'mode' );
		// Update the client_id and client_secret with the posted data.
		$this->client_id     = isset( $_POST[ $client_id_key ] ) ? sanitize_text_field( trim( $_POST[ $client_id_key ] ) ) : '';
		$this->client_secret = isset( $_POST[ $client_secret_key ] ) ? sanitize_text_field( trim( $_POST[ $client_secret_key ] ) ) : '';
		$this->mode          = isset( $_POST[ $mode_key ] ) ? sanitize_text_field( $_POST[ $mode_key ] ) : '';
		// Validate credentials.
		$this->validate_credentials();
		// Update things.
		$this->update_webhooks();
	}

	/**
	 * Validate credentials when saving options page.s
	 */
	public function validate_credentials() {
		// Check first if is enabled
		$enabled = $this->get_field_value( 'enabled', $this->form_fields['enabled'] );
		if ( $enabled !== 'yes' ) {
			return;
		}

		try {
			$client = $this->get_field_value( 'client_id', $this->form_fields['client_id'] );
			$secret = $this->get_field_value( 'client_secret', $this->form_fields['client_secret'] );

			$this->api->get_access_token( true, $client, $secret );
			$this->add_notice( __( 'Suas credenciais estão corretas, um novo token foi gerado.', 'paypal-payments' ), 'updated' );
		} catch ( Exception $ex ) {
			$this->wrong_credentials = 'yes';
			$this->add_notice( __( 'Suas credenciais estão inválidas. Verifique os dados informados e salve as configurações novamente.', 'paypal-payments' ) );
		}
	}

	/**
	 * Update the webhooks.
	 */
	public function update_webhooks() {
		// Set by default as not found.
		$webhook = null;
		try {
			$webhook_url = $this->get_webhook_url();
			// Get a list of webhooks
			$registered_webhooks = $this->api->get_webhooks();

			// Search for registered webhook.
			foreach ( $registered_webhooks['webhooks'] as $registered_webhook ) {
				if ( $registered_webhook['url'] === $webhook_url ) {
					$webhook = $registered_webhook;
					break;
				}
			}

			// If no webhook matched, create a new one.
			if ( ! $webhook ) {
				$webhook_url  = $this->get_webhook_url();
				$events_types = array(
					'PAYMENT.SALE.COMPLETED',
					'PAYMENT.SALE.DENIED',
					'PAYMENT.SALE.PENDING',
					'PAYMENT.SALE.REFUNDED',
					'PAYMENT.SALE.REVERSED',
				);

				// Create webhook.
				$webhook_result = $this->api->create_webhook( $webhook_url, $events_types );

				// Set the webhook ID
				$this->webhook_id        = $webhook_result['id'];
				$this->wrong_credentials = 'no';

				return;
			}

			// Set the webhook ID
			$this->webhook_id        = $webhook['id'];
			$this->wrong_credentials = 'no';
		} catch ( Exception $ex ) {
			$this->add_notice( __( 'Houve um erro ao definir o webhook.', 'paypal-payments' ) );
		}

		// If we don't have a webhook, set as empty.ˆ
		if ( ! $webhook ) {
			$this->webhook_id = '';
		} else {
			$this->add_notice( __( 'O webhook foi definido com sucesso.', 'paypal-payments' ), 'updated' );
		}
	}

	/**
	 * Add the experience profile ID to save data.
	 *
	 * @param $settings
	 *
	 * @return mixed
	 */
	public function filter_save_data( $settings ) {
		if ( $this->wrong_credentials === 'yes' ) {
			$this->client_id           = '';
			$settings['client_id']     = $this->client_id;
			$this->client_secret       = '';
			$settings['client_secret'] = $this->client_secret;
			$this->webhook_id          = '';
			$settings['webhook_id']    = $this->webhook_id;
		}
		$settings['webhook_id']        = $this->webhook_id ? $this->webhook_id : '';
		$settings['wrong_credentials'] = $this->wrong_credentials ? $this->wrong_credentials : 'no';

		return $settings;
	}

	/**
	 * Get the store URL for gateway.
	 * @return string
	 */
	private function get_webhook_url() {
		$base_url = site_url();
		if ( defined( 'WC_PPP_BRASIL_WEBHOOK_URL' ) && WC_PPP_BRASIL_WEBHOOK_URL ) {
			$base_url = WC_PPP_BRASIL_WEBHOOK_URL;
		} else if ( $_SERVER['HTTP_HOST'] === 'localhost' ) {
			$base_url = 'https://example.com/';
		}

		return str_replace( 'http:', 'https:', add_query_arg( 'wc-api', $this->id, $base_url ) );
	}

	/**
	 * Init the admin form fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'           => array(
				'title'   => __( 'Habilitar/Desabilitar', 'paypal-payments' ),
				'type'    => 'checkbox',
				'label'   => __( 'Habilitar', 'paypal-payments' ),
				'default' => 'no',
			),
			'title'             => array(
				'title'       => __( 'Nome de exibição', 'paypal-payments' ),
				'type'        => 'text',
				'default'     => '',
				'placeholder' => __( 'Exemplo: (Parcelado em até 12x)', 'paypal-payments' ),
				'description' => __( 'Será exibido no checkout: Cartão de Crédito (Parcelado em até 12x)', 'paypal-payments' ),
				'desc_tip'    => __( 'Por padrão a solução do PayPal Plus é exibida como “Cartão de Crédito”, utilize esta opção para definir um texto adicional como parcelamento ou descontos.', 'paypal-payments' ),
			),
			'mode'              => array(
				'title'       => __( 'Modo', 'paypal-payments' ),
				'type'        => 'select',
				'options'     => array(
					'live'    => __( 'Produção', 'paypal-payments' ),
					'sandbox' => __( 'Sandbox', 'paypal-payments' ),
				),
				'description' => __( 'Utilize esta opção para alternar entre os modos Sandbox e Produção. Sandbox é utilizado para testes e Produção para compras reais.', 'paypal-payments' ),
			),
			'client_id'         => array(
				'title'       => __( 'Client ID', 'paypal-payments' ),
				'type'        => 'text',
				'default'     => '',
				'description' => sprintf( __( 'Para gerar o Client ID acesse <a href="%s" target="_blank">aqui</a> e procure pela seção “REST API apps”.', 'paypal-payments' ), 'https://developer.paypal.com/docs/classic/lifecycle/sb_credentials/' ),
			),
			'client_secret'     => array(
				'title'       => __( 'Secret ID', 'paypal-payments' ),
				'type'        => 'text',
				'default'     => '',
				'description' => sprintf( __( 'Para gerar o Secret ID acesse <a href="%s" target="_blank">aqui</a> e procure pela seção “REST API apps”.', 'paypal-payments' ), 'https://developer.paypal.com/docs/classic/lifecycle/sb_credentials/' ),
			),
			'debug'             => array(
				'title'       => __( 'Modo depuração', 'paypal-payments' ),
				'type'        => 'checkbox',
				'label'       => __( 'Habilitar', 'paypal-payments' ),
				'desc_tip'    => __( 'Habilite este modo para depurar a aplicação em caso de homologação ou erros.', 'paypal-payments' ),
				'description' => sprintf( __( 'Os logs serão salvos no caminho: %s.', 'paypal-payments' ), $this->get_log_view() ),
			),
			'advanced_settings' => array(
				'title'       => __( 'Configurações avançadas', 'paypal-payments' ),
				'type'        => 'title',
				'description' => __( 'Utilize estas opções para customizar a experiência da solução.', 'paypal-payments' ),
			),
			'form_height'       => array(
				'title'       => __( 'Altura do formulário', 'paypal-payments' ),
				'type'        => 'text',
				'default'     => '',
				'placeholder' => __( 'px', 'paypal-payments' ),
				'description' => __( 'Utilize esta opção para definir uma altura máxima do formulário de cartão de crédito (será considerado um valor em pixels). Será aceito um valor em pixels entre 400 - 550.', 'paypal-payments' ),
			),
			'invoice_id_prefix' => array(
				'title'       => __( 'Prefixo de Invoice ID', 'paypal-payments' ),
				'type'        => 'text',
				'default'     => '',
				'description' => __( 'Adicione um prefixo as transações feitas com PayPal Plus na sua loja. Isso pode auxiliar caso trabalhe com a mesma conta PayPal em mais de um site.', 'paypal-payments' ),
			),
		);
	}

	/**
	 * Get log.
	 *
	 * @return string
	 */
	protected function get_log_view() {
		return '<a target="_blank" href="' . esc_url( admin_url( 'admin.php?page=wc-status&tab=logs&log_file=' . esc_attr( $this->id ) . '-' . sanitize_file_name( wp_hash( $this->id ) ) . '.log' ) ) . '">' . __( 'Status do Sistema &gt; Logs', 'paypal-payments' ) . '</a>';
	}

	/**
	 * Process the payment.
	 *
	 * @param int $order_id
	 *
	 * @param bool $force
	 *
	 * @return null|array
	 */
	public function process_payment( $order_id, $force = false ) {
		$order      = wc_get_order( $order_id );
		$session    = WC()->session->get( 'wc-ppp-brasil-payment-id' );
		$payment_id = $session['payment_id'];
		// Check if is a iframe error
		if ( isset( $_POST['wc-ppp-brasil-error'] ) && ! empty( $_POST['wc-ppp-brasil-error'] ) ) {
			switch ( $_POST['wc-ppp-brasil-error'] ) {
				case 'CARD_ATTEMPT_INVALID':
					wc_add_notice( __( 'Número de tentativas excedidas, por favor tente novamente. Se o erro persistir entre em contato.', 'paypal-payments' ), 'error' );
					break;
				case 'INTERNAL_SERVICE_ERROR':
				case 'SOCKET_HANG_UP':
				case 'socket hang up':
				case 'connect ECONNREFUSED':
				case 'connect ETIMEDOUT':
				case 'UNKNOWN_INTERNAL_ERROR':
				case 'fiWalletLifecycle_unknown_error':
				case 'Failed to decrypt term info':
					wc_add_notice( __( 'Ocorreu um erro inesperado, por favor tente novamente. Se o erro persistir entre em contato.', 'paypal-payments' ), 'error' );
					break;
				case 'RISK_N_DECLINE':
				case 'NO_VALID_FUNDING_SOURCE_OR_RISK_REFUSED':
				case 'TRY_ANOTHER_CARD':
				case 'NO_VALID_FUNDING_INSTRUMENT':
					wc_add_notice( __( 'Não foi possível processar o seu pagamento, tente novamente ou entre em contato contato com o PayPal (0800-047-4482).', 'paypal-payments' ), 'error' );
					break;
				case 'INVALID_OR_EXPIRED_TOKEN':
					wc_add_notice( __( 'Ocorreu um erro temporário. Por favor, preencha os dados novamente. Se o erro persistir, entre em contato.', 'paypal-payments' ), 'error' );
					break;
				default:
					wc_add_notice( __( 'Por favor revise as informações inseridas do cartão de crédito.', 'paypal-payments' ), 'error' );
					break;
			}
			// Set refresh totals to trigger update_checkout on frontend.
			WC()->session->set( 'refresh_totals', true );
			do_action( 'wc_ppp_brasil_process_payment_error', 'IFRAME_ERROR', $order_id, $_POST['wc-ppp-brasil-error'] );

			return null;
		}
		// Prevent submit any dummy data.
		if ( WC()->session->get( 'wc-ppp-brasil-dummy-data' ) === true ) {
			wc_add_notice( __( 'You are not allowed to do that.', 'paypal-payments' ), 'error' );
			// Set refresh totals to trigger update_checkout on frontend.
			WC()->session->set( 'refresh_totals', true );

			return null;
		}
		// Check the payment id
		/**
		 * This error is caused by multiple requests that
		 */
		if ( ! $payment_id ) {
			wc_add_notice( __( 'Houve um erro interno ao processar o pagamento. Por favor, tente novamente. Se o erro persistir, entre em contato.', 'paypal-payments' ), 'error' );
			// Set refresh totals to trigger update_checkout on frontend.
			WC()->session->set( 'refresh_totals', true );
			do_action( 'wc_ppp_brasil_process_payment_error', 'SESSION_ERROR', $order_id, null );

			return null;
		}
		try {
			$iframe_data    = isset( $_POST['wc-ppp-brasil-data'] ) ? json_decode( wp_unslash( $_POST['wc-ppp-brasil-data'] ), true ) : null;
			$response_data  = isset( $_POST['wc-ppp-brasil-response'] ) ? json_decode( wp_unslash( $_POST['wc-ppp-brasil-response'] ), true ) : null;
			$payer_id       = $response_data['payer_id'];
			$remember_cards = $response_data['remembered_cards_token'];
			// Check if the payment id
			if ( empty( $payer_id ) ) {
				wc_add_notice( __( 'Ocorreu um erro inesperado, por favor tente novamente. Se o erro persistir, entre em contato.', 'paypal-payments' ), 'error' );
				// Set refresh totals to trigger update_checkout on frontend.
				WC()->session->set( 'refresh_totals', true );
				do_action( 'wc_ppp_brasil_process_payment_error', 'PAYER_ID', $order_id, null );

				return null;
			}
			// Check if the payment id equal to stored
			if ( $payment_id !== $iframe_data['payment_id'] ) {
				wc_add_notice( __( 'Houve um erro com a sessão do usuário. Por favor, tente novamente. Se o erro persistir, entre em contato.', 'paypal-payments' ), 'error' );
				// Set refresh totals to trigger update_checkout on frontend.
				WC()->session->set( 'refresh_totals', true );
				do_action( 'wc_ppp_brasil_process_payment_error', 'PAYMENT_ID', $order_id, array(
					'stored_payment_id' => $payment_id,
					'iframe_payment_id' => $iframe_data['payment_id']
				) );

				return null;
			}
			// execute the order here.
			$execution = $this->execute_payment( $order, $payment_id, $payer_id );
			$sale      = $execution["transactions"][0]["related_resources"][0]["sale"];
			// @todo: change to correct meta key
			update_post_meta( $order_id, 'wc_ppp_brasil_sale_id', $sale['id'] );
			update_post_meta( $order_id, 'wc_ppp_brasil_sale', $sale );
			$installments = 1;
			if ( $response_data && $response_data['term'] && $response_data['term']['term'] ) {
				$installments = $response_data['term']['term'];
			}
			update_post_meta( $order_id, 'wc_ppp_brasil_installments', $installments );
			update_post_meta( $order_id, 'wc_ppp_brasil_sandbox', $this->mode );
			$result_success = false;
			switch ( $sale['state'] ) {
				case 'completed';
					$order->payment_complete();
					$result_success = true;
					break;
				case 'pending':
					wc_reduce_stock_levels( $order_id );
					$order->update_status( 'on-hold', __( 'O pagamento está em revisão pelo PayPal.', 'paypal-payments' ) );
					$result_success = true;
					break;
			}
			if ( $result_success ) {
				// Remember user cards
				if ( is_user_logged_in() ) {
					update_user_meta( get_current_user_id(), 'wc_ppp_brasil_remembered_cards', $remember_cards );
				}
				do_action( 'wc_ppp_brasil_process_payment_success', $order_id );

				// Return the success URL.s
				return array(
					'result'   => 'success',
					'redirect' => $this->get_return_url( $order ),
				);
			}
		} catch ( Paypal_Payments_Api_Exception $ex ) {
			$data = $ex->getData();
			switch ( $data['name'] ) {
				// Repeat the execution
				case 'INTERNAL_SERVICE_ERROR':
					if ( $force ) {
						wc_add_notice( sprintf( __( 'Ocorreu um erro inesperado, por favor tente novamente. Se o erro persistir entre em contato.', 'paypal-payments' ) ), 'error' );
					} else {
						$this->process_payment( $order_id, true );
					}
					break;
				case 'VALIDATION_ERROR':
					wc_add_notice( sprintf( __( 'Ocorreu um erro inesperado, por favor tente novamente. Se o erro persistir entre em contato.', 'paypal-payments' ) ), 'error' );
					break;
				case 'PAYMENT_ALREADY_DONE':
					wc_add_notice( __( 'Já existe um pagamento para este pedido.', 'paypal-payments' ), 'error' );
					break;
				default:
					wc_add_notice( __( 'O seu pagamento não foi aprovado, por favor tente novamente.', 'paypal-payments' ), 'error' );
					break;
			}
			// Set refresh totals to trigger update_checkout on frontend.
			WC()->session->set( 'refresh_totals', true );
			do_action( 'wc_ppp_brasil_process_payment_error', 'API_EXCEPTION', $order_id, $data['name'] );

			return null;
		}

		return null;
	}

	/**
	 * Process the refund for an order.
	 *
	 * @param int $order_id
	 * @param null $amount
	 * @param string $reason
	 *
	 * @return WP_Error|bool
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$amount  = floatval( $amount );
		$sale_id = get_post_meta( $order_id, 'wc_ppp_brasil_sale_id', true );
		// Check if the amount is bigger than zero
		if ( $amount <= 0 ) {
			return new WP_Error( 'error', sprintf( __( 'O reembolso não pode ser menor que %s.', 'paypal-payments' ), wc_price( 0 ) ) );
		}
		// Check if we got the sale ID
		if ( $sale_id ) {
			try {
				$refund_sale = $this->api->refund_payment( $sale_id, $amount, get_woocommerce_currency() );
				// Check the result success.
				if ( $refund_sale['state'] === 'completed' ) {
					return true;
				} else {
					return new WP_Error( 'error', $refund_sale->getReason() );
				}
			} catch ( Paypal_Payments_Api_Exception $ex ) { // Catch any PayPal error.
				$data = $ex->getData();

				return new WP_Error( 'error', $data['message'] );
			}
		} else { // If we don't have the PayPal sale ID.
			return new WP_Error( 'error', sprintf( __( 'Parece que você não tem um pedido para realizar o reembolso.', 'paypal-payments' ) ) );
		}
	}

	/**
	 * Execute a payment.
	 * @throws WC_PPP_Brasil_API_Exception
	 */
	public function execute_payment( $order, $payment_id, $payer_id ) {
		$patch_data = array(
			array(
				'op'    => 'add',
				'path'  => '/transactions/0/invoice_number',
				'value' => $this->invoice_id_prefix . $order->get_id(),
			),
			array(
				'op'    => 'add',
				'path'  => '/transactions/0/description',
				'value' => sprintf( __( 'Pedido #%s realizado na loja %s', 'paypal-payments' ), $order->get_id(), get_bloginfo( 'name' ) ),
			),
			array(
				'op'    => 'add',
				'path'  => '/transactions/0/custom',
				'value' => sprintf( __( 'Pedido #%s realizado na loja %s', 'paypal-payments' ), $order->get_id(), get_bloginfo( 'name' ) ),
			),
		);
		$this->api->update_payment( $payment_id, $patch_data );
		$execution_response = $this->api->execute_payment( $payment_id, $payer_id );

		return $execution_response;
	}

	/**
	 * Render the payment fields in checkout.
	 */
	public function payment_fields() {
		include dirname( PAYPAL_PAYMENTS_MAIN_FILE ) . '/includes/views/checkout/plus-html-fields.php';
	}

	/**
	 * Render HTML in admin options.
	 */
	public function admin_options() {
		include dirname( PAYPAL_PAYMENTS_MAIN_FILE ) . '/includes/views/admin-options/admin-options-plus/admin-options-plus.php';
	}

	/**
	 * Get the posted data in the checkout.
	 *
	 * @return array
	 * @throws Exception
	 */
	public function get_posted_data() {
		$execution_time = microtime( true );
		$order_id       = get_query_var( 'order-pay' );
		$order          = $order_id ? new WC_Order( $order_id ) : null;
		$data           = array();
		$defaults       = array(
			'first_name'       => '',
			'last_name'        => '',
			'person_type'      => '',
			'cpf'              => '',
			'cnpj'             => '',
			'phone'            => '',
			'email'            => '',
			'postcode'         => '',
			'address'          => '',
			'number'           => '',
			'address_2'        => '',
			'neighborhood'     => '',
			'city'             => '',
			'state'            => '',
			'country'          => '',
			'approval_url'     => '',
			'payment_id'       => '',
			'dummy'            => false,
			'invalid'          => array(),
			'remembered_cards' => '',
		);
		if ( $order ) {
			$billing_cellphone    = get_post_meta( $order->get_id(), '_billing_cellphone', true );
			$data['postcode']     = $order->get_shipping_postcode();
			$data['address']      = $order->get_shipping_address_1();
			$data['address_2']    = $order->get_shipping_address_2();
			$data['city']         = $order->get_shipping_city();
			$data['state']        = $order->get_shipping_state();
			$data['country']      = $order->get_shipping_country();
			$data['neighborhood'] = get_post_meta( $order->get_id(), '_billing_neighborhood', true );
			$data['number']       = get_post_meta( $order->get_id(), '_billing_number', true );
			$data['first_name']   = $order->get_billing_first_name();
			$data['last_name']    = $order->get_billing_last_name();
			$data['person_type']  = get_post_meta( $order->get_id(), '_billing_persontype', true );
			$data['cpf']          = get_post_meta( $order->get_id(), '_billing_cpf', true );
			$data['cnpj']         = get_post_meta( $order->get_id(), '_billing_cnpj', true );
			$data['phone']        = $billing_cellphone ? $billing_cellphone : $order->get_billing_phone();
			$data['email']        = $order->get_billing_email();
		} else if ( $_POST ) {
			$data['postcode']  = isset( $_POST['s_postcode'] ) ? preg_replace( '/[^0-9]/', '', $_POST['s_postcode'] ) : '';
			$data['address']   = isset( $_POST['s_address'] ) ? sanitize_text_field( $_POST['s_address'] ) : '';
			$data['address_2'] = isset( $_POST['s_address_2'] ) ? sanitize_text_field( $_POST['s_address_2'] ) : '';
			$data['city']      = isset( $_POST['s_city'] ) ? sanitize_text_field( $_POST['s_city'] ) : '';
			$data['state']     = isset( $_POST['s_state'] ) ? sanitize_text_field( $_POST['s_state'] ) : '';
			$data['country']   = isset( $_POST['s_country'] ) ? sanitize_text_field( $_POST['s_country'] ) : '';
			// Now get other post data that other fields can send.
			$post_data = array();
			if ( isset( $_POST['post_data'] ) ) {
				parse_str( $_POST['post_data'], $post_data );
			}
			$billing_cellphone    = isset( $post_data['billing_cellphone'] ) ? sanitize_text_field( $post_data['billing_cellphone'] ) : '';
			$data['neighborhood'] = isset( $post_data['billing_neighborhood'] ) ? sanitize_text_field( $post_data['billing_neighborhood'] ) : '';
			$data['number']       = isset( $post_data['billing_number'] ) ? sanitize_text_field( $post_data['billing_number'] ) : '';
			$data['first_name']   = isset( $post_data['billing_first_name'] ) ? sanitize_text_field( $post_data['billing_first_name'] ) : '';
			$data['last_name']    = isset( $post_data['billing_last_name'] ) ? sanitize_text_field( $post_data['billing_last_name'] ) : '';
			$data['person_type']  = isset( $post_data['billing_persontype'] ) ? sanitize_text_field( $post_data['billing_persontype'] ) : '';
			$data['cpf']          = isset( $post_data['billing_cpf'] ) ? sanitize_text_field( $post_data['billing_cpf'] ) : '';
			$data['cnpj']         = isset( $post_data['billing_cnpj'] ) ? sanitize_text_field( $post_data['billing_cnpj'] ) : '';
			$data['phone']        = $billing_cellphone ? $billing_cellphone : ( isset( $post_data['billing_phone'] ) ? sanitize_text_field( $post_data['billing_phone'] ) : '' );
			$data['email']        = isset( $post_data['billing_email'] ) ? sanitize_text_field( $post_data['billing_email'] ) : '';
		}
		if ( paypal_payments_needs_cpf() ) {
			// Get wcbcf settings
			$wcbcf_settings = get_option( 'wcbcf_settings' );
			// Set the person type default if we don't have any person type defined
			if ( $wcbcf_settings && ! $data['person_type'] && ( $wcbcf_settings['person_type'] == '2' || $wcbcf_settings['person_type'] == '3' ) ) {
				// The value 2 from person_type in settings is CPF (1) and 3 is CNPJ (2), and 1 is both, that won't reach here.
				$data['person_type']         = $wcbcf_settings['person_type'] == '2' ? '1' : '2';
				$data['person_type_default'] = true;
			}
		}
		// Now set the invalid.
		$data    = wp_parse_args( $data, $defaults );
		$data    = apply_filters( 'wc_ppp_brasil_user_data', $data );
		$invalid = $this->validate_data( $data );
		// if its invalid, return demo data.
		if ( $invalid ) {
			$data = array(
				'first_name'   => 'PayPal',
				'last_name'    => 'Brasil',
				'person_type'  => '2',
				'cpf'          => '',
				'cnpj'         => '10.878.448/0001-66',
				'phone'        => '(21) 99999-99999',
				'email'        => 'contato@paypal.com.br',
				'postcode'     => '01310-100',
				'address'      => 'Av. Paulista',
				'number'       => '1048',
				'address_2'    => '',
				'neighborhood' => 'Bela Vista',
				'city'         => 'São Paulo',
				'state'        => 'SP',
				'country'      => 'BR',
				'dummy'        => true,
				'invalid'      => $invalid,
			);
		}
		// Add session if is dummy data to check it later.
		WC()->session->set( 'wc-ppp-brasil-dummy-data', $data['dummy'] );
		// Return the data if is dummy. We don't need to process this.
		if ( $invalid ) {
			return $data;
		}
		// Create the payment.
		$payment = $this->create_payment( $data, $data['dummy'] );
		// Get old session.
		$old_session = WC()->session->get( 'wc-ppp-brasil-payment-id' );
		// Check if old session exists and it's an array.
		if ( $old_session && is_array( $old_session ) ) {
			// If this execution time is later than old session time, we can ignore this request.
			if ( $execution_time < $old_session['execution_time'] ) {
				return $data;
			}
		}
		// Add session with payment ID to check it later.
		WC()->session->set( 'wc-ppp-brasil-payment-id', array(
			'payment_id'     => $payment['id'],
			'execution_time' => $execution_time,
		) );
		// Add the saved remember card, approval link and the payment URL.
		$data['remembered_cards'] = is_user_logged_in() ? get_user_meta( get_current_user_id(), 'wc_ppp_brasil_remembered_cards', true ) : '';
		$data['approval_url']     = $payment['links'][1]['href'];
		$data['payment_id']       = $payment['id'];

		return $data;
	}

	/**
	 * Create the PayPal payment.
	 *
	 * @param $data
	 * @param bool $dummy
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function create_payment( $data, $dummy = false ) {
		// Don' log if is dummy data.
		if ( $dummy ) {
			$this->debug = false;
		}

		$payment_data = array(
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

		// Add all items.
		$only_digital = true;
		foreach ( WC()->cart->get_cart() as $key => $item ) {
			$product = $item['variation_id'] ? wc_get_product( $item['variation_id'] ) : wc_get_product( $item['product_id'] );

			// Force get product cents to avoid float problems.
			$product_price_cents = intval( $item['line_subtotal'] * 100 ) / $item['quantity'];
			$product_price       = number_format( $product_price_cents / 100, 2, '.', '' );

			$items[] = array(
				'name'     => $product->get_title(),
				'currency' => get_woocommerce_currency(),
				'quantity' => $item['quantity'],
				'price'    => $product_price,
				'sku'      => $product->get_sku() ? $product->get_sku() : $product->get_id(),
				'url'      => $product->get_permalink(),
			);

			// Check if product is not digital.
			if ( ! ( $product->is_downloadable() || $product->is_virtual() ) ) {
				$only_digital = false;
			}
		}

		// Add all discounts.
		$cart_totals = WC()->cart->get_totals();

		// Add discounts.
		if ( $cart_totals['discount_total'] ) {
			$items[] = array(
				'name'     => __( 'Desconto', 'paypal-payments' ),
				'currency' => get_woocommerce_currency(),
				'quantity' => 1,
				'price'    => number_format( - $cart_totals['discount_total'], 2, '.', '' ),
				'sku'      => 'discount',
			);
		}

		// Add fees.
		if ( $cart_totals['total_tax'] ) {
			$items[] = array(
				'name'     => __( 'Taxas', 'paypal-payments' ),
				'currency' => get_woocommerce_currency(),
				'quantity' => 1,
				'price'    => number_format( $cart_totals['total_tax'], 2, '.', '' ),
				'sku'      => 'taxes',
			);
		}

		// Force get product cents to avoid float problems.
		$subtotal_cents = intval( $cart_totals['subtotal'] * 100 );
		$discount_cents = intval( $cart_totals['discount_total'] * 100 );
		$shipping_cents = intval( $cart_totals['shipping_total'] * 100 );
		$tax_cents      = intval( $cart_totals['total_tax'] * 100 );
		$subtotal       = number_format( ( $subtotal_cents - $discount_cents + $tax_cents ) / 100, 2, '.', '' );
		$shipping       = number_format( $shipping_cents / 100, 2, '.', '' );

		// Set details
		$payment_data['transactions'][0]['amount']['details'] = array(
			'shipping' => $shipping,
			'subtotal' => $subtotal,
		);

		// Set total Total
		$payment_data['transactions'][0]['amount']['total'] = $cart_totals['total'];

		// Add items to data.
		$payment_data['transactions'][0]['item_list']['items'] = $items;

		// Set the application context
		$payment_data['application_context'] = array(
			'brand_name'          => get_bloginfo( 'name' ),
			'shipping_preference' => $only_digital ? 'NO_SHIPPING' : 'SET_PROVIDED_ADDRESS',
		);

		// Check if is order pay
		$exception_data = array();

		// Create the address.
		if ( ! $dummy ) {
			// Set shipping only when isn't digital
			if ( ! $only_digital ) {
				// Prepare empty address_line_1
				$address_line_1 = array();
				// Add the address
				if ( $data['address'] ) {
					$address_line_1[] = $data['address'];
				}
				// Add the number
				if ( $data['number'] ) {
					$address_line_1[] = $data['number'];
				}
				// Prepare empty line 2.
				$address_line_2 = array();
				// Add neighborhood to line 2
				if ( $data['neighborhood'] ) {
					$address_line_2[] = $data['neighborhood'];
				}
				// Add shipping address line 2
				if ( $data['address_2'] ) {
					$address_line_2[] = $data['address_2'];
				}
				$shipping_address = array(
					'recipient_name' => $data['first_name'] . ' ' . $data['last_name'],
					'country_code'   => $data['country'],
					'postal_code'    => $data['postcode'],
					'line1'          => mb_substr( implode( ', ', $address_line_1 ), 0, 100 ),
					'city'           => $data['city'],
					'state'          => $data['state'],
					'phone'          => $data['phone'],
				);
				// If is anything on address line 2, add to shipping address.
				if ( $address_line_2 ) {
					$shipping_address['line2'] = mb_substr( implode( ', ', $address_line_2 ), 0, 100 );
				}
				$payment_data['transactions'][0]['item_list']['shipping_address'] = $shipping_address;
			}
		}

		try {
			// Create the payment.
			$result = $this->api->create_payment( $payment_data );

			return $result;
		} catch ( Paypal_Payments_Api_Exception $ex ) { // Catch any PayPal error.
			$error_data = $ex->getData();
			if ( $error_data['name'] === 'VALIDATION_ERROR' ) {
				$exception_data = $error_data['details'];
			}
		}

		$exception       = new Exception( __( 'Ocorreu um erro inesperado, por favor tente novamente. Se o erro persistir entre em contato.', 'paypal-payments' ) );
		$exception->data = $exception_data;

		throw $exception;
	}

	/**
	 * Validate data if contain any invalid field.
	 *
	 * @param $data
	 *
	 * @return array
	 */
	private function validate_data( $data ) {
		$errors = array();
		// Check first name.
		if ( empty( $data['first_name'] ) ) {
			$errors['first_name'] = __( 'Nome inválido', 'paypal-payments' );
		}
		// Check last name.
		if ( empty( $data['last_name'] ) ) {
			$errors['last_name'] = __( 'Sobrenome inválido', 'paypal-payments' );
		}
		// Check phone.
		if ( empty( $data['phone'] ) ) {
			$errors['phone'] = __( 'Telefone inválido', 'paypal-payments' );
		}
		if ( empty( $data['address'] ) ) {
			$errors['address'] = __( 'Endereço inválido', 'paypal-payments' );
		}
		if ( empty( $data['city'] ) ) {
			$errors['city'] = __( 'Cidade inválida', 'paypal-payments' );
		}
		if ( empty( $data['state'] ) ) {
			$errors['state'] = __( 'Estado inválido', 'paypal-payments' );
		}
		if ( empty( $data['country'] ) ) {
			$errors['country'] = __( 'País inválido', 'paypal-payments' );
		}
		if ( empty( $data['postcode'] ) ) {
			$errors['postcode'] = __( 'CEP inválido', 'paypal-payments' );
		}
		// Check email.
		if ( ! is_email( $data['email'] ) ) {
			$errors['email'] = __( 'Email inválido', 'paypal-payments' );
		}
		// Only if require CPF/CNPJ
		if ( paypal_payments_needs_cpf() ) {
			// Check address number (only with CPF/CPNJ)
			if ( empty( $data['number'] ) ) {
				$errors['number'] = __( 'Número inválido', 'paypal-payments' );
			}
			// Check person type.
			if ( $data['person_type'] !== '1' && $data['person_type'] !== '2' ) {
				$errors['person_type'] = __( 'Tipo de pessoa inválido', 'paypal-payments' );
			}
			// Check the CPF
			if ( $data['person_type'] == '1' && ! $this->is_cpf( $data['cpf'] ) ) {
				$errors['cpf'] = __( 'CPF inválido', 'paypal-payments' );
			}
			// Check the CNPJ
			if ( $data['person_type'] == '2' && ! $this->is_cnpj( $data['cnpj'] ) ) {
				$errors['cnpj'] = __( 'CNPJ inválido', 'paypal-payments' );
			}
		}

		return $errors;
	}

	/**
	 * Enqueue scripts in checkout.
	 */
	public function checkout_scripts() {
		// Just load this script in checkout and if isn't in order-receive.
		if ( is_checkout() && ! get_query_var( 'order-received' ) ) {
			if ( 'yes' === $this->debug ) {
				wp_enqueue_script( 'pretty-web-console', plugins_url( 'assets/js/libs/pretty-web-console.lib.js', PAYPAL_PAYMENTS_MAIN_FILE ), array(), '0.10.1', true );
			}
			wp_enqueue_script( 'ppp-script', '//www.paypalobjects.com/webstatic/ppplusdcc/ppplusdcc.min.js', array(), PAYPAL_PAYMENTS_VERSION, true );
			wp_localize_script( 'ppp-script', 'wc_ppp_brasil_data', array(
				'id'                => $this->id,
				'order_pay'         => ! ! get_query_var( 'order-pay' ),
				'mode'              => $this->mode === 'sandbox' ? 'sandbox' : 'live',
				'form_height'       => $this->get_form_height(),
				'show_payer_tax_id' => paypal_payments_needs_cpf(),
				'language'          => get_woocommerce_currency() === 'BRL' ? 'pt_BR' : 'en_US',
				'country'           => $this->get_woocommerce_country(),
				'messages'          => array(
					'check_entry' => __( 'Verifique os dados informados e tente novamente.', 'paypal-payments' ),
				),
				'debug_mode'        => 'yes' === $this->debug,
			) );
			wp_enqueue_script( 'wc-ppp-brasil-script', plugins_url( 'assets/dist/js/frontend-plus.js', PAYPAL_PAYMENTS_MAIN_FILE ), array( 'jquery' ), PAYPAL_PAYMENTS_VERSION, true );
			wp_enqueue_style( 'wc-ppp-brasil-style', plugins_url( 'assets/dist/css/frontend-plus.css', PAYPAL_PAYMENTS_MAIN_FILE ), array(), PAYPAL_PAYMENTS_VERSION, 'all' );
		}
	}

	/**
	 * Get the WooCommerce country.
	 *
	 * @return string
	 */
	private function get_woocommerce_country() {
		return get_woocommerce_currency() === 'BRL' ? 'BR' : 'US';
	}

	/**
	 * Get form height.
	 */
	private function get_form_height() {
		$height    = trim( $this->form_height );
		$min_value = 400;
		$max_value = 550;
		$test      = preg_match( '/[0-9]+/', $height, $matches );
		if ( $test && $matches[0] === $height && $height >= $min_value && $height <= $max_value ) {
			return $height;
		}

		return null;
	}

	/**
	 * Enqueue admin scripts.
	 */
	public function admin_scripts() {
		$screen         = get_current_screen();
		$screen_id      = $screen ? $screen->id : '';
		$wc_screen_id   = sanitize_title( __( 'WooCommerce', 'paypal-payments' ) );
		$wc_settings_id = $wc_screen_id . '_page_wc-settings';
		if ( $wc_settings_id === $screen_id && isset( $_GET['section'] ) && $_GET['section'] === $this->id ) {
			wp_enqueue_style( 'wc-ppp-brasil-admin-style', plugins_url( 'assets/dist/css/admin-options-plus.css', PAYPAL_PAYMENTS_MAIN_FILE ), array(), PAYPAL_PAYMENTS_VERSION, 'all' );
		}
	}

	/**
	 * Handle webhooks events.
	 */
	public function webhook_handler() {
		// Include the handler.
		include_once dirname( PAYPAL_PAYMENTS_MAIN_FILE ) . '/includes/handlers/class-paypal-payments-webhooks-handler.php';
		try {
			// Instance the handler.
			$handler = new PayPal_Payments_Webhooks_Handler( $this->id );
			// Get the data.
			$headers       = array_change_key_case( getallheaders(), CASE_UPPER );
			$body          = $this->get_raw_data();
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
			$payload                = "{";
			foreach ( $signature_verification as $field => $value ) {
				$payload .= "\"$field\": \"$value\",";
			}
			$payload            .= "\"webhook_event\": $body";
			$payload            .= "}";
			$signature_response = $this->api->verify_signature( $payload );
			if ( $signature_response['verification_status'] === 'SUCCESS' ) {
				$handler->handle( $webhook_event );
			}
		} catch ( Exception $ex ) {
		}
	}

	/**
	 * Return the gateway's title.
	 *
	 * @return string
	 */
	public function get_title() {
		// A description only for admin section.
		if ( is_admin() ) {
			global $pagenow;

			return $pagenow === 'post.php' ? __( 'PayPal - Checkout Transparente', 'paypal-payments' ) : __( 'Checkout Transparente', 'paypal-payments' );
		}

		$title = get_woocommerce_currency() === "BRL" ? __( 'Cartão de Crédito', 'paypal-payments' ) : __( 'Credit Card', 'paypal-payments' );
		if ( ! empty( $this->title ) ) {
			$title .= ' ' . $this->title;
		}

		return apply_filters( 'woocommerce_gateway_title', $title, $this->id );
	}

	public function add_notice( $text, $type = 'error' ) {
		$notices   = get_option( 'wc-ppp-brasil-notices', array() );
		$notices[] = array(
			'text' => $text,
			'type' => $type,
		);
		update_option( 'wc-ppp-brasil-notices', $notices );
	}

	public function get_notices( $clear = true ) {
		$notices = get_option( 'wc-ppp-brasil-notices', array() );
		if ( $clear ) {
			update_option( 'wc-ppp-brasil-notices', array() );
		}

		return $notices;
	}

}