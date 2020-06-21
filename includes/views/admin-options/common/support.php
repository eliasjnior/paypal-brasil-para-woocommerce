<?php defined( 'ABSPATH' ) || exit; ?>
<?php
$report      = WC()->api->get_endpoint_data( '/wc/v3/system_status' );
$environment = $report['environment'];
?>

<h2>Suporte</h2>


<?php
$data = array(
	array(
		'title' => 'Endereço do site (URL):',
		'tip'   => '',
		'value' => $environment['site_url'],
	),
	array(
		'title' => 'Versão do WordPress:',
		'tip'   => '',
		'value' => $environment['wp_version'],
	),
	array(
		'title' => 'Versão do WooCommerce:',
		'tip'   => '',
		'value' => $environment['version'],
	),
	array(
		'title' => 'Versão do PHP:',
		'tip'   => '',
		'value' => $environment['php_version'],
	),
	array(
		'title' => 'Versão do cURL:',
		'tip'   => '',
		'value' => $environment['curl_version'],
	),
	array(
		'title'      => 'fsockopen/cURL:',
		'tip'        => '',
		'value'      => $environment['fsockopen_or_curl_enabled'] ? '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>' : '<mark class="error"><span class="dashicons dashicons-warning"></span></mark>',
		'text_value' => $environment['fsockopen_or_curl_enabled'] ? '✔' : '✕',
	),
	array(
		'title'      => 'Requisição remota do tipo POST:',
		'tip'        => '',
		'value'      => $environment['remote_post_successful'] ? '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>' : '<mark class="error"><span class="dashicons dashicons-warning"></span></mark>',
		'text_value' => $environment['remote_post_successful'] ? '✔' : '✕',
	),
	array(
		'title'      => 'Requisição remota do tipo GET:',
		'tip'        => '',
		'value'      => $environment['remote_get_successful'] ? '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>' : '<mark class="error"><span class="dashicons dashicons-warning"></span></mark>',
		'text_value' => $environment['remote_get_successful'] ? '✔' : '✕',
	)
);

$raw_data = array_reduce( $data, 'paypal_brasil_reduce_support_data', '' );

?>

<tr valign="top">
    <th scope="row" class="titledesc">
        <label>Informações de suporte</label>
    </th>
    <td class="forminp">
        <table class="wc_status_table widefat" cellspacing="0">
            <thead>
            <tr>
                <th colspan="3">
                    <h2>Ambiente</h2>
                </th>
            </tr>
            </thead>
            <tbody>
			<?php foreach ( $data as $item ): ?>
                <tr>
                    <td><?php echo esc_html( $item['title'] ); ?></td>
                    <td class="help">
						<?php if ( isset( $item['tip'] ) && $item['tip'] ): ?>
							<?php echo wc_help_tip( $item['tip'], true ); ?>
						<?php endif; ?>
                    </td>
                    <td><?php echo esc_html( $item['value'] ); ?></td>
                </tr>
			<?php endforeach; ?>
            </tbody>
        </table>
        <textarea id="support-resume" readonly
                  onclick="this.focus(); this.select(); document.execCommand('copy'); alert('Informações copiadas!')"
                  style="width: 100%; height: 100px;"><?php echo esc_html( $raw_data ); ?></textarea>
    </td>
</tr>
