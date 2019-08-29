<div class="admin-options-container">

	<?php if ( ( ! isset( $_POST ) && $this->enabled === 'yes' ) || ( isset( $_POST ) && $this->get_updated_values()['enabled'] === 'yes' ) ): ?>

        <!-- CREDENTIALS ERROR -->
		<?php if ( get_option( $this->get_option_key() . '_validator' ) === 'no' ): ?>
            <div id="message" class="error inline">
                <p>
                    <strong><?php _e( 'Suas credenciais não são válidas. Por favor, verifique os dados informados.', 'paypal-payments' ); ?></strong>
                </p>
            </div>
		<?php elseif ( ( isset( $_POST ) && $this->get_updated_values()['reference_enabled'] === 'yes' && get_option( $this->get_option_key() . '_reference_transaction_validator' ) === 'no' )
		               || ( empty( $_POST ) && $this->reference_enabled === 'yes' && get_option( $this->get_option_key() . '_reference_transaction_validator' ) === 'no' ) ): ?>
            <div id="message" class="error inline">
                <p>
                    <strong><?php _e( 'Você ativou a opção de Salvar Carteira Digital, porém sua conta não está autorizada a utilizar essa funcionalidade. Entre em contato com nosso suporte para a ativação.', 'paypal-payments' ); ?></strong>
                </p>
            </div>
		<?php endif; ?>

        <!-- REFERENCE TRANSACTION SETTINGS -->
		<?php if ( ! paypal_payments_wc_settings_valid() ): ?>
            <div id="message-reference-transaction-settings" class="error inline">
                <p>
                    <strong><?php _e( 'Você ativou a opção de Salvar Carteira Digital, porém as configurações necessárias para a funcionalidade não estão ativadas.', 'paypal-payments' ); ?></strong>
                </p>
            </div>
		<?php endif; ?>

	<?php endif; ?>

    <img class="banner"
         src="<?php echo esc_attr( plugins_url( 'assets/images/banner.png', PAYPAL_PAYMENTS_MAIN_FILE ) ); ?>">

	<?php echo wp_kses_post( wpautop( $this->get_method_description() ) ); ?>

    <table class="form-table">

        <tbody>

        <!-- HABILITAR -->

        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $this->get_field_key( 'enabled' ) ); ?>">Ativar/Desativar</label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span>Ativar</span></legend>
                    <label for="<?php echo esc_attr( $this->get_field_key( 'enabled' ) ); ?>">
                        <input type="checkbox"
                               class="test"
                               name="<?php echo esc_attr( $this->get_field_key( 'enabled' ) ); ?>"
                               id="<?php echo esc_attr( $this->get_field_key( 'enabled' ) ); ?>"
                               value="<?php echo esc_attr( $this->enabled ); ?>"
                               v-model="enabled"
                               true-value="yes"
                               false-value="">
                        Habilitar</label><br>
                </fieldset>
            </td>
        </tr>

        <!-- NOME DE EXIBIÇÃO -->

        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $this->get_field_key( 'title' ) ); ?>">Nome de exibição
                    (complemento)</label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span>Nome de exibição</span></legend>
                    <input class="input-text regular-input"
                           type="text"
                           name="<?php echo esc_attr( $this->get_field_key( 'title' ) ); ?>"
                           id="<?php echo esc_attr( $this->get_field_key( 'title' ) ); ?>"
                           v-model="title"
                           placeholder="Exemplo: (Parcelado em até 12x)">
                    <p class="description">Será exibido no checkout: PayPal {{title ? '(' + title + ')':
                        ''}}</p>
                </fieldset>
            </td>
        </tr>

        <!-- MODO -->

        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $this->get_field_key( 'mode' ) ); ?>">Modo</label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span>Modo</span></legend>
                    <select class="select"
                            id="<?php echo esc_attr( $this->get_field_key( 'mode' ) ); ?>"
                            name="<?php echo esc_attr( $this->get_field_key( 'mode' ) ); ?>"

                            v-model="mode">
                        <option value="live">Produção</option>
                        <option value="sandbox" selected="selected">Sandbox</option>
                    </select>
                    <p class="description">Utilize esta opção para alternar entre os modos Sandbox e Produção. Sandbox é
                        utilizado para testes e Produção para compras reais.</p>
                </fieldset>
            </td>
        </tr>

        <!-- CLIENT ID LIVE -->

        <tr valign="top" :class="{hidden: !isLive()}">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $this->get_field_key( 'client_live' ) ); ?>">Client ID (live)</label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span>Client ID</span></legend>
                    <input class="input-text regular-input"
                           type="text"
                           id="<?php echo esc_attr( $this->get_field_key( 'client_live' ) ); ?>"
                           name="<?php echo esc_attr( $this->get_field_key( 'client_live' ) ); ?>"
                           v-model="client.live">
                    <p class="description">Para gerar o Client ID acesse <a
                                href="https://developer.paypal.com/docs/classic/lifecycle/sb_credentials/"
                                target="_blank">aqui</a>
                        e procure pela seção “REST API apps”.</p>
                </fieldset>
            </td>
        </tr>

        <!-- CLIENT ID SANDBOX -->

        <tr valign="top" :class="{hidden: isLive()}">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $this->get_field_key( 'client_sandbox' ) ); ?>">Client ID
                    (sandbox) </label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span>Client ID</span></legend>
                    <input class="input-text regular-input"
                           type="text"

                           id="<?php echo esc_attr( $this->get_field_key( 'client_sandbox' ) ); ?>"
                           name="<?php echo esc_attr( $this->get_field_key( 'client_sandbox' ) ); ?>"
                           v-model="client.sandbox">
                    <p class="description">Para gerar o Client ID acesse <a
                                href="https://developer.paypal.com/docs/classic/lifecycle/sb_credentials/"
                                target="_blank">aqui</a>
                        e procure pela seção “REST API apps”.</p>
                </fieldset>
            </td>
        </tr>

        <!-- SECRET LIVE -->

        <tr valign="top" :class="{hidden: !isLive()}">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $this->get_field_key( 'secret_live' ) ); ?>">Secret (live)</label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span>Secret ID</span></legend>
                    <input class="input-text regular-input"
                           type="text"
                           id="<?php echo esc_attr( $this->get_field_key( 'secret_live' ) ); ?>"
                           name="<?php echo esc_attr( $this->get_field_key( 'secret_live' ) ); ?>"
                           v-model="secret.live">
                    <p class="description">Para gerar o Secret ID acesse <a
                                href="https://developer.paypal.com/docs/classic/lifecycle/sb_credentials/"
                                target="_blank">aqui</a>
                        e procure pela seção “REST API apps”.</p>
                </fieldset>
            </td>
        </tr>

        <!-- SECRET SANDBOX -->

        <tr valign="top" :class="{hidden: isLive()}">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $this->get_field_key( 'secret_sandbox' ) ); ?>">Secret ID
                    (sandbox)</label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span>Secret (sandbox)</span></legend>
                    <input class="input-text regular-input"
                           type="text"
                           id="<?php echo esc_attr( $this->get_field_key( 'secret_sandbox' ) ); ?>"
                           name="<?php echo esc_attr( $this->get_field_key( 'secret_sandbox' ) ); ?>"

                           v-model="secret.sandbox">
                    <p class="description">Para gerar o Secret ID acesse <a
                                href="https://developer.paypal.com/docs/classic/lifecycle/sb_credentials/"
                                target="_blank">aqui</a>
                        e procure pela seção “REST API apps”.</p>
                </fieldset>
            </td>
        </tr>

        <!-- HEADER -->
        <h2>Configurações do Botão</h2>

        <!-- FOMARTO -->

        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $this->get_field_key( 'format' ) ); ?>">Formato</label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span>Formato</span></legend>
                    <select class="select"
                            id="<?php echo esc_attr( $this->get_field_key( 'format' ) ); ?>"
                            name="<?php echo esc_attr( $this->get_field_key( 'format' ) ); ?>"

                            v-model="button.format">
                        <option value="rect">Retangular</option>
                        <option value="pill">Arredondado</option>
                    </select>
                </fieldset>
            </td>
        </tr>

        <!-- COR -->

        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $this->get_field_key( 'color' ) ); ?>">Cor</label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span>Cor</span></legend>
                    <select class="select"
                            id="<?php echo esc_attr( $this->get_field_key( 'color' ) ); ?>"
                            name="<?php echo esc_attr( $this->get_field_key( 'color' ) ); ?>"

                            v-model="button.color">
                        <option value="blue">Azul</option>
                        <option value="gold">Dourado</option>
                        <option value="silver">Prateado</option>
                    </select>
                </fieldset>
            </td>
        </tr>

        <!-- PRÉ-VIUALIZAÇÃO -->

        <tr valign="top">
            <th scope="row" class="titledesc">
                <label>Pré visualização do botão</label>
            </th>
            <td class="forminp">
                <div class="preview-container">
                    <img class="preview" :src="imagesPath + '/' + button.format + '-' + button.color + '.png'">
                </div>
            </td>
        </tr>

        <!-- PAYPAL NO CARRINHO -->

        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $this->get_field_key( 'shortcut_enabled' ) ); ?>">PayPal no
                    Carrinho</label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span>Habilitar</span></legend>
                    <label for="<?php echo esc_attr( $this->get_field_key( 'shortcut_enabled' ) ); ?>">
                        <input type="checkbox"
                               id="<?php echo esc_attr( $this->get_field_key( 'shortcut_enabled' ) ); ?>"
                               name="<?php echo esc_attr( $this->get_field_key( 'shortcut_enabled' ) ); ?>"
                               v-model="shortcutEnabled"
                               true-value="yes"
                               false-value="">
                        Habilitar</label><br>
                    <p class="description">A carteira digital do PayPal será oferecida também no carrinho de
                        compras.</p>
                </fieldset>
            </td>
        </tr>

        <!-- SALVAR CARTEIRA DIGITAL -->

        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $this->get_field_key( 'reference_enabled' ) ); ?>">Salvar Carteira
                    Digital</label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span>Habilitar/Desabilitar</span></legend>
                    <label for="<?php echo esc_attr( $this->get_field_key( 'reference_enabled' ) ); ?>">
                        <input type="checkbox"
                               id="<?php echo esc_attr( $this->get_field_key( 'reference_enabled' ) ); ?>"
                               name="<?php echo esc_attr( $this->get_field_key( 'reference_enabled' ) ); ?>"
                               v-model="referenceEnabled"
                               true-value="yes"
                               false-value="">
                        Habilitar</label><br>
                    <p class="description">A conveniência de salvar a carteira digital PayPal de seu cliente em sua
                        loja. Assim ele não precisa mais se autenticar em sua conta PayPal, garantindo uma compra mais
                        rápida e segura. <b>Esta funcionalidade requer aprovação PayPal. Entre em contato pelo
                            0800-047-4482
                            e solicite a sua liberação.</b></p>
                </fieldset>
                <div class="reference-active-description" v-bind:class="{hidden: referenceEnabled != 'yes'}">
                    <p class="description">Por padrão o WooCommerce mantém desativado algumas configurações que são
                        necessárias para garantir a integridade da carteira digital do seu cliente. Por questões de
                        segurança, é necessário que as seguintes opções sejam ativadas em <a target="_blank"
                                                                                             href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=account' ) ); ?>">WooCommerce
                            > Configurações > Contas e privacidade</a>.</p>
                    <br>
                    <label>
                        <input type="checkbox" checked disabled>
                        Permitir que seus clientes façam login em uma conta existente durante a finalização da
                        compra
                    </label>
                    <br> <br>
                    <label>
                        <input type="checkbox" checked disabled>
                        Permitir que seus clientes criem uma conta durante a finalização da compra
                    </label>
                    <br>
                    <br>
                    <button type="button"
                            :disabled="updateSettingsState.executed && updateSettingsState.loading"
                            v-on:click="updateSettings"
                            class="button-primary">
						<?php _e( 'Ative as configurações para mim', 'paypal-payments' ); ?></button>
					<?php echo wc_help_tip( 'Para facilitar, você poderá clicar neste botão que ativaremos as configurações necessárias para você' ); ?>
                    <span class="state-loading" v-if="updateSettingsState.executed && updateSettingsState.loading">
                        <span class="dashicons dashicons-update"></span>
                    </span>
                    <span class="state-success"
                          v-if="updateSettingsState.executed && !updateSettingsState.loading && updateSettingsState.success">
                        <span class="dashicons dashicons-yes"></span>
                    </span>
                    <span class="state-error"
                          v-if="updateSettingsState.executed && !updateSettingsState.loading && !updateSettingsState.success">
                        <span class="dashicons dashicons-no-alt"></span>
                    </span>
                    <br>
                    <br>
                    <p class="description"><b>Só habilite esta funcionalidade se você possui aprovação do PayPal e se as
                            configurações acima foram aplicadas.</b></p>
                </div>
            </td>
        </tr>

        <h2>Configurações Avançadas</h2>

        <!-- PREFIXO -->

        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $this->get_field_key( 'invoice_id_prefix' ) ); ?>">Prefixo de Invoice
                    ID</label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span>Prefixo de Invoice ID</span></legend>
                    <input class="input-text regular-input"
                           type="text"
                           id="<?php echo esc_attr( $this->get_field_key( 'invoice_id_prefix' ) ); ?>"
                           name="<?php echo esc_attr( $this->get_field_key( 'invoice_id_prefix' ) ); ?>"
                           v-model="invoiceIdPrefix">
                    <p class="description">Adicione um prefixo as transações feitas com PayPal Plus na sua loja. Isso
                        pode auxiliar caso trabalhe com a mesma conta PayPal em mais de um site.</p>
                </fieldset>
            </td>
        </tr>

        <!-- MODO DEPURAÇÃO -->

        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $this->get_field_key( 'debug' ) ); ?>">Modo depuração <span
                            class="woocommerce-help-tip"></span></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span>Modo depuração</span></legend>
                    <label for="<?php echo esc_attr( $this->get_field_key( 'debug' ) ); ?>">
                        <input type="checkbox"
                               id="<?php echo esc_attr( $this->get_field_key( 'debug' ) ); ?>"
                               name="<?php echo esc_attr( $this->get_field_key( 'debug' ) ); ?>"
                               v-model="debugMode"
                               true-value="yes"
                               false-value="">
                        Habilitar</label><br>
                    <p class="description">Os logs serão salvos no caminho: <a target="_blank"
                                                                               href="http://paypal.localhost/wp-admin/admin.php?page=wc-status&amp;tab=logs&amp;log_file=wc-ppp-brasil-gateway-83f06720c330899c8b474a48c137d3a6.log">Status
                            do Sistema &gt; Logs</a>.</p>
                </fieldset>
            </td>
        </tr>

        </tbody>

    </table>

</div>