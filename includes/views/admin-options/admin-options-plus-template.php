<div class="admin-options-container">

    <table class="form-table">

        <tbody>

        <!-- HABILITAR -->

        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="woocommerce_wc-ppp-brasil-gateway_enabled">Habilitar/Desabilitar </label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span>Habilitar/Desabilitar</span></legend>
                    <label for="woocommerce_wc-ppp-brasil-gateway_enabled">
                        <input class=""
                               type="checkbox"
                               name="woocommerce_wc-ppp-brasil-gateway_enabled"
                               id="woocommerce_wc-ppp-brasil-gateway_enabled"
                               style=""
                               v-model="enabled"
                               true-value="1"
                               false-value="">
                        Habilitar</label><br>
                </fieldset>
            </td>
        </tr>

        <!-- NOME DE EXIBIÇÃO -->

        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="woocommerce_wc-ppp-brasil-gateway_title">Nome de exibição <span
                            class="woocommerce-help-tip"></span></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span>Nome de exibição</span></legend>
                    <input class="input-text regular-input " type="text" name="woocommerce_wc-ppp-brasil-gateway_title"
                           id="woocommerce_wc-ppp-brasil-gateway_title"
                           v-model="title"
                           placeholder="Exemplo: (Parcelado em até 12x)">
                    <p class="description">Será exibido no checkout: Cartão de Crédito {{title ? '(' + title + ')':
                        ''}}</p>
                </fieldset>
            </td>
        </tr>

        <!-- MODO -->

        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="woocommerce_wc-ppp-brasil-gateway_mode">Modo</label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span>Modo</span></legend>
                    <select class="select "
                            name="woocommerce_wc-ppp-brasil-gateway_mode"
                            id="woocommerce_wc-ppp-brasil-gateway_mode"
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

        <tr valign="top" v-if="isLive()">
            <th scope="row" class="titledesc">
                <label for="woocommerce_wc-ppp-brasil-gateway_client_id">Client ID (live) </label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span>Client ID</span></legend>
                    <input class="input-text regular-input " type="text"
                           name="woocommerce_wc-ppp-brasil-gateway_client_id"
                           id="woocommerce_wc-ppp-brasil-gateway_client_id" style=""
                           value="AcFGzGwFquuzjk5dlpbxMNmxSvkGQHirp9_VHPFUb1Lo1Y7RwJ4qcmmO1txUADp5Ypo1DlOyEd65KCbm"
                           placeholder="">
                    <p class="description">Para gerar o Client ID acesse <a
                                href="https://developer.paypal.com/docs/classic/lifecycle/sb_credentials/"
                                target="_blank">aqui</a>
                        e procure pela seção “REST API apps”.</p>
                </fieldset>
            </td>
        </tr>

        <!-- CLIENT ID SANDBOX -->

        <tr valign="top" v-if="!isLive()">
            <th scope="row" class="titledesc">
                <label for="woocommerce_wc-ppp-brasil-gateway_client_id">Client ID (sandbox) </label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span>Client ID</span></legend>
                    <input class="input-text regular-input " type="text"
                           name="woocommerce_wc-ppp-brasil-gateway_client_id"
                           id="woocommerce_wc-ppp-brasil-gateway_client_id" style=""
                           value="AcFGzGwFquuzjk5dlpbxMNmxSvkGQHirp9_VHPFUb1Lo1Y7RwJ4qcmmO1txUADp5Ypo1DlOyEd65KCbm"
                           placeholder="">
                    <p class="description">Para gerar o Client ID acesse <a
                                href="https://developer.paypal.com/docs/classic/lifecycle/sb_credentials/"
                                target="_blank">aqui</a>
                        e procure pela seção “REST API apps”.</p>
                </fieldset>
            </td>
        </tr>

        <!-- SECRET LIVE -->

        <tr valign="top" v-if="isLive()">
            <th scope="row" class="titledesc">
                <label for="woocommerce_wc-ppp-brasil-gateway_client_secret">Secret (live)</label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span>Secret ID</span></legend>
                    <input class="input-text regular-input " type="text"
                           name="woocommerce_wc-ppp-brasil-gateway_client_secret"
                           id="woocommerce_wc-ppp-brasil-gateway_client_secret" style=""
                           value="EC3Buw2OfuZuiwaHUrUypL52CxU6tMJmvX5mXJBoT9SlmCTI8PcZssfzV7sxnUcxPezSdG_YNNOrGoSy"
                           placeholder="">
                    <p class="description">Para gerar o Secret ID acesse <a
                                href="https://developer.paypal.com/docs/classic/lifecycle/sb_credentials/"
                                target="_blank">aqui</a>
                        e procure pela seção “REST API apps”.</p>
                </fieldset>
            </td>
        </tr>

        <!-- SECRET SANDBOX -->

        <tr valign="top" v-if="!isLive()">
            <th scope="row" class="titledesc">
                <label for="woocommerce_wc-ppp-brasil-gateway_client_secret">Secret ID (sandbox)</label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span>Secret (sandbox)</span></legend>
                    <input class="input-text regular-input " type="text"
                           name="woocommerce_wc-ppp-brasil-gateway_client_secret"
                           id="woocommerce_wc-ppp-brasil-gateway_client_secret" style=""
                           value="EC3Buw2OfuZuiwaHUrUypL52CxU6tMJmvX5mXJBoT9SlmCTI8PcZssfzV7sxnUcxPezSdG_YNNOrGoSy"
                           placeholder="">
                    <p class="description">Para gerar o Secret ID acesse <a
                                href="https://developer.paypal.com/docs/classic/lifecycle/sb_credentials/"
                                target="_blank">aqui</a>
                        e procure pela seção “REST API apps”.</p>
                </fieldset>
            </td>
        </tr>

        <!-- MODO DEPURAÇÃO -->

        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="woocommerce_wc-ppp-brasil-gateway_debug">Modo depuração <span
                            class="woocommerce-help-tip"></span></label>
            </th>
            <td class="forminp">
                <fieldset>
                    <legend class="screen-reader-text"><span>Modo depuração</span></legend>
                    <label for="woocommerce_wc-ppp-brasil-gateway_debug">
                        <input class="" type="checkbox" name="woocommerce_wc-ppp-brasil-gateway_debug"
                               id="woocommerce_wc-ppp-brasil-gateway_debug" style="" value="1" checked="checked">
                        Habilitar</label><br>
                    <p class="description">Os logs serão salvos no caminho: <a target="_blank"
                                                                               href="http://paypal.localhost/wp-admin/admin.php?page=wc-status&amp;tab=logs&amp;log_file=wc-ppp-brasil-gateway-83f06720c330899c8b474a48c137d3a6.log">Status
                            do Sistema &gt; Logs</a>.</p>
                </fieldset>
            </td>
        </tr>

        </tbody>

    </table>

    <div class="accordion">

        <div class="accordion-header">

            <h3>Group Title</h3>

        </div>

        <div class="accordion-body">
            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus nec commodo lorem, in hendrerit leo.
                Maecenas convallis in magna vel aliquam. Aenean sagittis lorem id ornare laoreet. Praesent quis augue
                nec elit ornare ultrices. Nulla consectetur enim non orci fringilla consequat. Integer ac porttitor
                mauris. Morbi vulputate sollicitudin augue, at tincidunt turpis faucibus sit amet. Orci varius natoque
                penatibus et magnis dis parturient montes, nascetur ridiculus mus. Interdum et malesuada fames ac ante
                ipsum primis in faucibus. Aliquam sollicitudin vitae orci et lobortis. Quisque bibendum arcu non mollis
                faucibus. Morbi eget consectetur libero. Vivamus tincidunt auctor vehicula. In fermentum, arcu ac
                aliquet consequat, urna augue ultrices ligula, et tristique nunc turpis nec orci. Integer vel porttitor
                nibh, scelerisque semper dolor. Donec auctor, est eget consequat hendrerit, neque ligula blandit purus,
                nec dapibus justo eros sed diam.</p>
        </div>

    </div>

</div>