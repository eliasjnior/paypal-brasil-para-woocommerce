<?php /** @var PayPal_Payments_Plus_Gateway $this */ ?>
<?php if ( paypal_payments_needs_cpf() && ! class_exists( 'Extra_Checkout_Fields_For_Brazil' ) ): ?>
    <div id="message-wecfb" class="error inline">
        <p>
            <strong><?php echo sprintf( __( 'O PayPal Plus não está ativo. Não foi possível encontrar nenhum plugin com o suporte de CPF/CNPJ, por favor visite a <a href="%s" target="_blank">página oficial</a> do plugin para mais informações.', 'paypal-payments' ), 'https://br.wordpress.org/plugins/paypal-payments/' ); ?></strong>
        </p>
    </div>
<?php endif; ?>
<?php if ( ! paypal_payments_needs_cpf() ): ?>
    <div id="message-alert-usd" class="error inline">
        <p>
            <strong><?php _e( 'Você está utilizando USD em sua loja. Desta forma você só poderá receber pagamento de contas não-brasileiras.', 'paypal-payments' ); ?></strong>
        </p>
    </div>
<?php endif; ?>

<?php if ( $notices = $this->get_notices() ): ?>
	<?php foreach ( $notices as $notice ): ?>
        <div class="<?php echo $notice['type']; ?> inline">
            <p><strong><?php echo $notice['text']; ?></strong></p>
        </div>
	<?php endforeach; ?>
<?php endif; ?>

<img class="ppp-brasil-banner"
     srcset="<?php echo esc_attr( plugins_url( 'assets/images/banner-plus-2x.png', PAYPAL_PAYMENTS_MAIN_FILE ) ); ?> 2x"
     src="<?php echo plugins_url( 'assets/images/banner-plus.png', PAYPAL_PAYMENTS_MAIN_FILE ); ?>"
     title="<?php _e( 'PayPal Brasil', 'paypal-payments' ); ?>"
     alt="<?php _e( 'PayPal Brasil', 'paypal-payments' ); ?>">
<?php echo wp_kses_post( wpautop( $this->get_method_description() ) ); ?>

<table class="form-table">
	<?php echo $this->generate_settings_html( $this->get_form_fields(), false ); ?>
</table>