<?php

// Exit if not in WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check if class already exists before create.
if ( ! class_exists( 'PayPal_Brasil_Webhooks_Handler' ) ) {

	/**
	 * Class WC_PPP_Brasil_Webhooks_Handler.
	 */
	class PayPal_Brasil_Webhooks_Handler {

		private $gateway_id;

		/**
		 * WC_PPP_Brasil_Webhooks_Handler constructor.
		 */
		public function __construct( $gateway_id ) {
			$this->gateway_id = $gateway_id;
		}

		/**
		 * Handle the event.
		 *
		 * @param $event
		 *
		 * @throws Exception
		 */
		public function handle( $event ) {
			global $wpdb;
			$method_name = 'handle_process_' . str_replace( '.', '_', strtolower( $event['event_type'] ) );
			if ( method_exists( $this, $method_name ) ) {
				$resource_id = isset( $event['resource']['sale_id'] ) ? $event['resource']['sale_id'] : $event['resource']['id'];
				$order_id    = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'paypal_brasil_sale_id' AND meta_value = %s", $resource_id ) );
				// If found the order ID with this sale ID.
				if ( $order_id ) {
					// Get the payment method to check if was processed by this gateway.
					$payment_method = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = '_payment_method' AND post_id = %s", $order_id ) );
					// If is this gateway, process the order.
					if ( $payment_method === $this->gateway_id ) {
						$order = new WC_Order( $order_id );
						$this->{$method_name}( $order, $event );
					}
				}
			} else {
				throw new Exception( 'Invalid method to handle.' );
			}
		}

		/**
		 * When payment is marked as completed.
		 *
		 * @param $order WC_Order
		 */
		public function handle_process_payment_sale_completed( $order, $event ) {
			// Check if order exists.
			if ( ! $order ) {
				return;
			}
			// Check if the current status isn't processing or completed.
			if ( ! in_array( $order->get_status(), array(
				'processing',
				'completed',
				'refunded',
				'cancelled'
			), true )
			) {
				$order->add_order_note( __( 'PayPal: Transação paga.', 'paypal-paymnets' ) );
				$order->payment_complete();
			}
		}

		/**
		 * When payment is denied.
		 *
		 * @param $order WC_Order
		 */
		public function handle_process_payment_sale_denied( $order, $event ) {
			// Check if order exists.
			if ( ! $order ) {
				return;
			}
			// Check if the current status isn't failed.
			if ( ! in_array( $order->get_status(), array( 'failed', 'completed', 'processing' ), true ) ) {
				$order->update_status( 'failed', __( 'PayPal: A transação foi rejeitada pela empresa de cartão ou por fraude.', 'paypal-paymnets' ) );
			}
		}

		/**
		 * When payment is refunded.
		 *
		 * @param $order WC_Order
		 *
		 * @throws Exception
		 */
		public function handle_process_payment_sale_refunded( $order, $event ) {
			// Check if order exists.
			if ( ! $order ) {
				return;
			}

			// Check if is partial refund.
			$partial_refund = wc_format_decimal( $order->get_total() - $order->get_total_refunded() ) !== paypal_brasil_money_format( $event['resource']['amount']['total'] );

			// Check if is total refund
			if ( ! $partial_refund ) {
				$order->update_status( 'refunded', __( 'PayPal: A transação foi reembolsada por completo.', 'paypal-brasil-para-woocommerce' ) );
			}

			// Check if the current status isn't refunded.
			if ( ! in_array( $order->get_status(), array( 'refunded' ), true ) ) {
				$refund = wc_create_refund( array(
					'amount'         => wc_format_decimal( $event['resource']['amount']['total'] ),
					'reason'         => $partial_refund ? __( 'PayPal: transação parcialmente reembolsada.', 'paypal-brasil-para-woocommerce' ) : __( 'PayPal: transação reembolsada por completo.', 'paypal-brasil-para-woocommerce' ),
					'order_id'       => $order->get_id(),
					'refund_payment' => true,
				) );

				if ( is_wp_error( $refund ) ) {
					throw new Exception( sprintf( __( 'Houve um erro ao tentar realizar o reembolso: %s', 'paypal-brasil-para-woocommerce' ), $refund->get_error_message() ) );
				}
			}
		}

		/**
		 * When payment is reversed.
		 *
		 * @param $order WC_Order
		 */
		public function handle_process_payment_sale_reversed( $order, $event ) {
			// Check if order exists.
			if ( ! $order ) {
				return;
			}
			$order->update_status( 'refunded', __( 'PayPal: A transação foi revertida.', 'paypal-paymnets' ) );
		}

	}

}