<?php
/**
 * Eupago Admin Page.
 */
function getStoreUrl()
{
    // Check if WooCommerce is active
    if (class_exists('WooCommerce')) {
        // Get the URL components
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $domain = $_SERVER['HTTP_HOST'];

        // Get the shop base slug
        $shop_base_slug = get_option('woocommerce_shop_page_id');
        // $base_uri = ($shop_base_slug) ? '/' . get_page_uri($shop_base_slug) . '/' : '/';

        // Add the custom part to the store URL
        $custom_part = '/?wc-api=WC_euPago';

        // Combine the components to form the store URL
        $store_url = $protocol . $domain . $custom_part;

        return $store_url;
    } else {
        // WooCommerce is not active, handle the error or return a default URL
        return 'https://example.com/'; // Replace with your default URL
    }
}

function eupago_admin_menu()
{
    add_submenu_page(
        'woocommerce',
        __('Eupago', 'eupago-gateway-for-woocommerce'),
        __('Eupago', 'eupago-gateway-for-woocommerce'),
        'manage_options',
        'eupago',
        'eupago_page_content',
        3
    );
}

add_action('admin_menu', 'eupago_admin_menu');

function eupago_page_content()
{
    $callback_url = getStoreUrl(); // Set the callback URL to the value returned by getStoreUrl()

    if (isset($_POST['eupago_save']) && wp_verify_nonce($_POST['_wpnonce'], 'eupago_settings_nonce')) {
        if (sanitize_text_field(isset($_POST['eupago_save']))) {
            $channel = sanitize_text_field($_POST['channel']);
            $api_key = sanitize_text_field($_POST['api_key']);
            $endpoint = sanitize_text_field($_POST['endpoint']);
            $reminder = sanitize_text_field(isset($_POST['reminder'])) ? sanitize_text_field($_POST['reminder']) : '0';
            $debug = sanitize_text_field(isset($_POST['debug'])) ? sanitize_text_field($_POST['debug']) : 'no';
            $client_id = sanitize_text_field($_POST['client_id']);
            $client_secret = sanitize_text_field($_POST['client_secret']);
            $user_eupago = sanitize_text_field($_POST['user_eupago']);
            $password_eupago = sanitize_text_field($_POST['password_eupago']);
            $sms_enable = sanitize_text_field(isset($_POST['sms_enable'])) ? sanitize_text_field($_POST['sms_enable']) : 'no';
            $sms_payment_hold = sanitize_text_field(isset($_POST['sms_payment_hold'])) ? sanitize_text_field($_POST['sms_payment_hold']) : 'no';
            $sms_payment_confirmation = sanitize_text_field(isset($_POST['sms_payment_confirmation'])) ? sanitize_text_field($_POST['sms_payment_confirmation']) : 'no';
            $sms_order_confirmation = sanitize_text_field(isset($_POST['sms_order_confirmation'])) ? sanitize_text_field($_POST['sms_order_confirmation']) : 'no';
            $sms_intelidus_id = sanitize_text_field($_POST['sms_intelidus_id']);
            $sms_intelidus_api = sanitize_text_field($_POST['sms_intelidus_api']);
            $intelidus_sender = sanitize_text_field($_POST['intelidus_sender']);
            $biziq_environment = sanitize_text_field($_POST['biziq_environment']);

            if (empty(get_option('eupago_channel'))) {
                delete_option('eupago_channel');
                add_option('eupago_channel', $channel, '', 'no');
            } else {
                update_option('eupago_channel', $channel);
            }

            if (empty(get_option('eupago_api_key'))) {
                delete_option('eupago_api_key');
                add_option('eupago_api_key', $api_key, '', 'yes');
            } else {
                update_option('eupago_api_key', $api_key);
            }

            if (empty(get_option('eupago_endpoint'))) {
                delete_option('eupago_endpoint');
                add_option('eupago_endpoint', $endpoint, '', 'yes');
            } else {
                update_option('eupago_endpoint', $endpoint);
            }

            if (empty(get_option('eupago_reminder'))) {
                delete_option('eupago_reminder');
                add_option('eupago_reminder', $reminder, '', 'yes');
            } else {
                update_option('eupago_reminder', $reminder);
            }

            if (empty(get_option('eupago_debug'))) {
                delete_option('eupago_debug');
                add_option('eupago_debug', $debug, '', 'yes');
            } else {
                update_option('eupago_debug', $debug);
            }

            if (empty(get_option('eupago_client_id'))) {
                delete_option('eupago_client_id');
                add_option('eupago_client_id', $client_id, '', 'yes');
            } else {
                update_option('eupago_client_id', $client_id);
            }

            if (empty(get_option('eupago_client_secret'))) {
                delete_option('eupago_client_secret');
                add_option('eupago_client_secret', $client_secret, '', 'yes');
            } else {
                update_option('eupago_client_secret', $client_secret);
            }

            if (empty(get_option('eupago_user'))) {
                delete_option('eupago_user');
                add_option('eupago_user', $user_eupago, '', 'yes');
            } else {
                update_option('eupago_user', $user_eupago);
            }

            if (empty(get_option('eupago_password'))) {
                delete_option('eupago_password');
                add_option('eupago_password', $password_eupago, '', 'yes');
            } else {
                update_option('eupago_password', $password_eupago);
            }

            if (empty(get_option('eupago_sms_enable'))) {
                delete_option('eupago_sms_enable');
                add_option('eupago_sms_enable', $sms_enable, '', 'yes');
            } else {
                update_option('eupago_sms_enable', $sms_enable);
            }

            if (empty(get_option('eupago_sms_payment_hold'))) {
                delete_option('eupago_sms_payment_hold');
                add_option('eupago_sms_payment_hold', $sms_payment_hold, '', 'yes');
            } else {
                update_option('eupago_sms_payment_hold', $sms_payment_hold);
            }

            if (empty(get_option('eupago_sms_payment_confirmation'))) {
                delete_option('eupago_sms_payment_confirmation');
                add_option('eupago_sms_payment_confirmation', $sms_payment_confirmation, '', 'yes');
            } else {
                update_option('eupago_sms_payment_confirmation', $sms_payment_confirmation);
            }

            if (empty(get_option('eupago_sms_order_confirmation'))) {
                delete_option('eupago_sms_order_confirmation');
                add_option('eupago_sms_order_confirmation', $sms_order_confirmation, '', 'yes');
            } else {
                update_option('eupago_sms_order_confirmation', $sms_order_confirmation);
            }

            if (empty(get_option('eupago_sms_intelidus_id'))) {
                delete_option('eupago_sms_intelidus_id');
                add_option('eupago_sms_intelidus_id', $sms_intelidus_id, '', 'yes');
            } else {
                update_option('eupago_sms_intelidus_id', $sms_intelidus_id);
            }

            if (empty(get_option('eupago_sms_intelidus_api'))) {
                delete_option('eupago_sms_intelidus_api');
                add_option('eupago_sms_intelidus_api', $sms_intelidus_api, '', 'yes');
            } else {
                update_option('eupago_sms_intelidus_api', $sms_intelidus_api);
            }

            if (empty(get_option('eupago_intelidus_sender'))) {
                delete_option('eupago_intelidus_sender');
                add_option('eupago_intelidus_sender', $intelidus_sender, '', 'yes');
            } else {
                update_option('eupago_intelidus_sender', $intelidus_sender);
            }
            if (empty(get_option('biziq_environment'))) {
                delete_option('biziq_environment');
                add_option('biziq_environment', $biziq_environment, '', 'yes');
            } else {
                update_option('biziq_environment', $biziq_environment);
            }
        }
    }
        $reminder_checked = '';
        $debug_checked = '';
        $sms_enable_checked = '';
        $sms_payment_hold_checked = '';
        $sms_payment_confirmation_checked = '';
        $sms_order_confirmation_checked = '';

        if (get_option('eupago_reminder') == '1') {
            $reminder_checked = 'checked';
        }

        if (get_option('eupago_debug') == 'yes') {
            $debug_checked = 'checked';
        }

        if (get_option('eupago_sms_enable') == 'yes') {
            $sms_enable_checked = 'checked';
        }

        if (get_option('eupago_sms_payment_hold') == 'yes') {
            $sms_payment_hold_checked = 'checked';
        }

        if (get_option('eupago_sms_payment_confirmation') == 'yes') {
            $sms_payment_confirmation_checked = 'checked';
        }

        if (get_option('eupago_sms_order_confirmation') == 'yes') {
            $sms_order_confirmation_checked = 'checked';
        }

    // Generate the nonce
    $eupago_settings_nonce = wp_create_nonce('eupago_settings_nonce');
    ?>
	<!-- Include jQuery library -->
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

	<div class="eupago_header">
		<div>
            <img src="<?php echo esc_attr(plugins_url('images/eupago_nobg.png', __FILE__)); ?>" alt="avatar" style="width: 25%;">
		</div>
		<div>
			<h1><?php esc_html_e('Eupago', 'eupago-gateway-for-woocommerce'); ?></h1>
			<p><?php esc_html_e('Eupago services integration.', 'eupago-gateway-for-woocommerce'); ?></p>
		</div>
	</div>
    <?php if (isset($_POST['eupago_save'])) { ?>
        <!-- Show success message if settings are saved -->
        <div class="eupago-notice notice notice-success"> 
            <p><strong><?php esc_html_e('Settings saved.', 'eupago-gateway-for-woocommerce'); ?></strong></p>
        </div>
    <?php } ?>

	
	<div class="eupago-settings">
		<form name="eupago-settings" method="POST" action="">
              <!-- Add the nonce field -->
              <?php wp_nonce_field('eupago_settings_nonce', '_wpnonce'); ?>
              

			<table class="form-table" role="presentation">
            <?php
                $texto_traduzido = esc_html__('Each account has at least a channel. Each channel has an API Key that identifies your Eupago\'s account.', 'eupago-gateway-for-woocommerce');
                $texto_callback = esc_html__('Please activate callback to this url on Eupago dashboard: <code>%s</code>', 'eupago-gateway-for-woocommerce');
                $texto_enable = esc_html__('Enable', 'eupago-gateway-for-woocommerce');
                $texto_enable_login = esc_html__('Enable loggin', 'eupago-gateway-for-woocommerce');
                $texto_antepenultimo = esc_html__('Please make sure that the field with Client ID and Client Secret is properly filled above to update the callback successfully.','eupago-gateway-for-woocommerce');
                $texto_ultimo = esc_html__('The generated URL is the default callback for a Woocommece store. If your store has a different file path please use the Eupago Backoffice.', 'eupago-gateway-for-woocommerce'); 
                $callback = esc_html__('Update Callback','eupago-gateway-for-woocommerce');
                $order = esc_html__('In order to use Eupago plugin for WooCommerce you must have an Eupago account.', 'eupago-gateway-for-woocommerce');
                //Primeira linha do ficheiro para traduzir
                $channel_name = esc_html__('Channel Name:', 'eupago-gateway-for-woocommerce');
                $channel = esc_html__('What is a channel?', 'eupago-gateway-for-woocommerce');
                $order_api = esc_html__('In order to find your API Key and channel name please follow this guide on our', 'eupago-gateway-for-woocommerce');
                $help_center = esc_html__('Help Center','eupago-gateway-for-woocommerce');
                $api_key_text = esc_html__('Api Key: ', 'eupago-gateway-for-woocommerce');
                $end_point_text = esc_html__('Enpoint: ','eupago-gateway-for-woocommerce');
                $live_text = esc_html__('Live','eupago-gateway-for-woocommerce');
                $sandbox_text = esc_html__('Sandbox', 'eupago-gateway-for-woocommerce');
                $reminder_faiolver = esc_html__('Reminder(FailOver):', 'eupago-gateway-for-woocommerce');
                $see_reminder = esc_html__('Do you want to send a reminder to your customer to inform him he has a pending order? Activate this option. Read more about this reminder', 'eupago-gateway-for-woocommerce');
                $here = esc_html__('here','eupago-gateway-for-woocommerce');
                $debug_log = esc_html__('Debug Log: ' ,'eupago-gateway-for-woocommerce');
                $log_plugins = esc_html('Log plugin events, such as callback requests, inside', 'eupago-gateway-for-woocommerce');
                $refund_text = esc_html('Refund:','eupago-gateway-for-woocommerce');
                $user_text = esc_html__('User: ','eupago-gateway-for-woocommerce');
                $password_text = esc_html__('Password','eupagp-gateway-for-woocommerce');
                $notificacoes_sms = esc_html__('Nofitications Biziq: ','eupago-gateway-for-woocommerce');
                $payment_on_hold = esc_html__('SMS Payment On Hold:', 'eupago-gateway-for-woocommerce');
                $sms_order_confirmation = esc_html('SMS Order Confirmation:', 'eupago-gateway-for-woocommerce');
                $payment_confirmation = esc_html__('SMS Payment Confirmation:', 'eupago-gateway-for-woocommerce');
                $callback_url_text = esc_html__('Callback url: ','eupago-gateway-for-woocommerce');
                $doyounedd_account = esc_html__('Do you need an account? You may sign up at', 'eupago-gateway-for-woocommerce');
                $demo_account = esc_html__('Do you already have a demo account and need to finish your real account? Please reach out by email:', 'eupago-gateway-for-woocommerce');
                $save_changes_text = esc_html__('Save Changes', 'eupago-gateway-for-woocommerce');
                if (get_locale() == 'pt_BR' || get_locale() == 'pt_PT') {
                    $texto_traduzido = 'Cada conta tem pelo menos um canal. Cada canal possui uma Chave de API que identifica a sua conta do Eupago.';
                    $texto_callback = 'Por favor, ative o callback para este URL no painel da Eupago:';
                    $texto_enable = 'Ativar';
                    $texto_enable_login = 'Ativar login';
                    $texto_antepenultimo = 'Por favor, certifique-se de que o campo com o ID do Cliente e o Segredo do Cliente esteja corretamente preenchido acima para atualizar o callback com sucesso.';
                    $texto_ultimo = 'O URL gerado é o callback padrão para uma loja Woocommerce. Se a sua loja tiver um caminho de arquivo diferente, por favor, utilize o Backoffice da Eupago.';
                    $callback = 'Atualizar callback';
                    $order = 'Para usar o plugin da Eupago para WooCommerce, você deve ter uma conta na Eupago.';
                    //Primeira linha do ficheiro para traduzir
                    $channel_name = esc_html__('Nome do Canal:', 'eupago-gateway-for-woocommerce');
                    $channel = esc_html__('O que é um canal?', 'eupago-gateway-for-woocommerce');
                    $order_api = esc_html__('Para encontrar sua Chave de API e nome do canal, siga este guia em nosso', 'eupago-gateway-for-woocommerce');
                    $help_center = esc_html__('Centro de Ajuda','eupago-gateway-for-woocommerce');
                    $api_key_text = esc_html__('Chave de API: ', 'eupago-gateway-for-woocommerce');
                    $end_point_text = esc_html__('Ponto de extremidade: ','eupago-gateway-for-woocommerce');
                    $live_text = esc_html__('Produção','eupago-gateway-for-woocommerce');
                    $sandbox_text = esc_html__('Sandbox', 'eupago-gateway-for-woocommerce');
                    $reminder_faiolver = esc_html__('Lembrete (FailOver):', 'eupago-gateway-for-woocommerce');
                    $see_reminder = esc_html__('Quer enviar um lembrete para seu cliente informando que ele tem um pedido pendente? Ative esta opção. Leia mais sobre este lembrete', 'eupago-gateway-for-woocommerce');
                    $here = esc_html__('aqui','eupago-gateway-for-woocommerce');
                    $debug_log = esc_html__('Log de Depuração: ' ,'eupago-gateway-for-woocommerce');
                    $log_plugins = esc_html__('Registre eventos do plugin, como solicitações de retorno, dentro', 'eupago-gateway-for-woocommerce');
                    $refund_text = esc_html__('Reembolso:','eupago-gateway-for-woocommerce');
                    $user_text = esc_html__('Usuário: ','eupago-gateway-for-woocommerce');
                    $password_text = esc_html__('Senha','eupagp-gateway-for-woocommerce');
                    $notificacoes_sms = esc_html__('Notificações Biziq: ','eupago-gateway-for-woocommerce');
                    $payment_on_hold = esc_html__('Pagamento SMS em Espera:', 'eupago-gateway-for-woocommerce');
                    $sms_order_confirmation = esc_html__('Confirmação de Pedido por SMS:', 'eupago-gateway-for-woocommerce');
                    $payment_confirmation = esc_html__('Confirmação de Pagamento por SMS:', 'eupago-gateway-for-woocommerce');
                    $callback_url_text = esc_html__('URL de Retorno: ','eupago-gateway-for-woocommerce');
                    $doyounedd_account = esc_html__('Precisa de uma conta? Você pode se inscrever em', 'eupago-gateway-for-woocommerce');
                    $demo_account = esc_html__('Já tem uma conta de demonstração e precisa concluir sua conta real? Entre em contato por e-mail:', 'eupago-gateway-for-woocommerce');
                    $save_changes_text = esc_html__('Guardar Alterações', 'eupago-gateway-for-woocommerce');
               }else if(get_locale()== 'es_ES'){
                    $texto_traduzido = 'Cada cuenta tiene al menos un canal. Cada canal tiene una Clave de API que identifica su cuenta de Eupago.';
                    $texto_callback = 'Por favor, active la devolución de llamada para esta URL en el panel de Eupago:';
                    $texto_enable = 'Habilitar';
                    $texto_enable_login = 'Habilitar inicio de sesión';
                    $texto_antepenultimo = 'Por favor, asegúrese de que el campo con el ID de Cliente y el Secreto de Cliente estén completados correctamente arriba para actualizar la devolución de llamada correctamente.';
                    $texto_ultimo = 'La URL generada es la devolución de llamada predeterminada para una tienda Woocommerce. Si su tienda tiene una ruta de archivo diferente, por favor, utilice el Backoffice de Eupago.';
                    $callback = 'Actualizar devolución de llamada';
                    $order = 'Para utilizar el plugin de Eupago para WooCommerce, debe tener una cuenta en Eupago.';
                    //Primeira linha do ficheiro para traduzir
                    $channel_name = esc_html__('Nombre del canal:', 'eupago-gateway-for-woocommerce');
                    $channel = esc_html__('¿Qué es un canal?', 'eupago-gateway-for-woocommerce');
                    $order_api = esc_html__('Para encontrar su Clave de API y nombre de canal, siga esta guía en nuestro', 'eupago-gateway-for-woocommerce');
                    $help_center = esc_html__('Centro de ayuda','eupago-gateway-for-woocommerce');
                    $api_key_text = esc_html__('Clave de API: ', 'eupago-gateway-for-woocommerce');
                    $end_point_text = esc_html__('Punto final: ','eupago-gateway-for-woocommerce');
                    $live_text = esc_html__('En vivo','eupago-gateway-for-woocommerce');
                    $sandbox_text = esc_html__('Sandbox', 'eupago-gateway-for-woocommerce');
                    $reminder_faiolver = esc_html__('Recordatorio (FailOver):', 'eupago-gateway-for-woocommerce');
                    $see_reminder = esc_html__('¿Desea enviar un recordatorio a su cliente para informarle que tiene un pedido pendiente? Active esta opción. Lea más sobre este recordatorio', 'eupago-gateway-for-woocommerce');
                    $here = esc_html__('aquí','eupago-gateway-for-woocommerce');
                    $debug_log = esc_html__('Registro de depuración: ' ,'eupago-gateway-for-woocommerce');
                    $log_plugins = esc_html__('Registrar eventos del plugin, como solicitudes de devolución de llamada, dentro', 'eupago-gateway-for-woocommerce');
                    $refund_text = esc_html__('Reembolso:','eupago-gateway-for-woocommerce');
                    $user_text = esc_html__('Usuario: ','eupago-gateway-for-woocommerce');
                    $password_text = esc_html__('Contraseña','eupagp-gateway-for-woocommerce');
                    $notificacoes_sms = esc_html__('Notificaciones Biziq: ','eupago-gateway-for-woocommerce');
                    $payment_on_hold = esc_html__('Pago SMS en espera:', 'eupago-gateway-for-woocommerce');
                    $sms_order_confirmation = esc_html__('Confirmación de pedido SMS:', 'eupago-gateway-for-woocommerce');
                    $payment_confirmation = esc_html__('Confirmación de pago SMS:', 'eupago-gateway-for-woocommerce');
                    $callback_url_text = esc_html__('URL de devolución de llamada: ','eupago-gateway-for-woocommerce');
                    $doyounedd_account = esc_html__('¿Necesita una cuenta? Puede registrarse en', 'eupago-gateway-for-woocommerce');
                    $demo_account = esc_html__('¿Ya tiene una cuenta de demostración y necesita terminar su cuenta real? Por favor, contáctenos por correo electrónico:', 'eupago-gateway-for-woocommerce');
                    $save_changes_text = esc_html__('Guardar Cambios','eupago-gateway-for-woocomerce');
               }
            ?><tbody>
					<tr>
						<th scope="row"><label for="channel"><?php esc_html_e($channel_name); ?></label></th>
						<td>
							<input class="regular-text" type="text" name="channel" value="<?php echo esc_attr(get_option('eupago_channel')); ?>">
							<h4><?php esc_html_e($channel); ?></h4>
							<p><?php esc_html_e($texto_traduzido); ?></p>
							<p>
								<?php esc_html_e($order_api); ?> 
								<a href="https://eupago.atlassian.net/servicedesk/customer/portal/2/article/224297034?src=1875300770" target="_BLANK"><?php esc_html_e($help_center); ?></a>.
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="api_key"><?php esc_html_e($api_key_text); ?></label></th>
						<td>
							<input class="regular-text" type="text" name="api_key" value="<?php echo esc_attr(get_option('eupago_api_key')); ?>">
							<p>
                               <?php esc_html_e($texto_callback); ?> <?php echo get_option('permalink_structure') == '' ? home_url('/') . '?wc-api=WC_euPago' : home_url('/') . 'wc-api/WC_euPago/'; ?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="endpoint"><?php esc_html_e($endpoint_text); ?></label></th>
						<td>
							<select name="endpoint">
								<?php if (get_option('eupago_endpoint') == 'clientes') { ?>
									<option value="clientes" selected><?php esc_html_e($live_text); ?></option>
								<?php } else { ?>
									<option value="clientes"><?php esc_html_e($live_text); ?></option>
								<?php } ?>
								<?php if (get_option('eupago_endpoint') == 'sandbox') { ?>
									<option value="sandbox" selected><?php esc_html_e($sandbox_text); ?></option>
								<?php } else { ?>
									<option value="sandbox"><?php esc_html_e($sandbox_text); ?></option>
								<?php } ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="reminder"><?php esc_html_e($reminder_faiolver); ?></label></th>
						<td>
							<input type="checkbox" name="reminder" value="1" <?php echo $reminder_checked; ?>><?php esc_html_e($texto_enable); ?>
							<p>
								<?php esc_html_e($see_reminder); ?> 
								<a href="https://eupago.atlassian.net/servicedesk/customer/portal/2/article/652967937" target="_BLANK"><?php esc_html_e($here); ?></a>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="debug"><?php esc_html_e($debug_log); ?></label></th>
						<td>
							<input type="checkbox" name="debug" value="yes" <?php echo $debug_checked; ?>><?php esc_html_e($texto_enable_login); ?>
							<p>
								<?php esc_html_e($log_plugins); ?>
								<?php $uploads = wp_upload_dir(); ?>
								<code><?php echo wp_basename($uploads['baseurl']) . '/wc-logs/'; ?></code>
							</p>
						</td>
					</tr>
				</tbody>
			</table>

			<h3><?php esc_html_e($refund_text); ?></h3>
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row"><label for="client_id"><?php esc_html_e('Client ID:', 'eupago-gateway-for-woocommerce'); ?></label></th>
						<td><input class="regular-text" type="text" id='client_id' name="client_id" value="<?php echo esc_attr(get_option('eupago_client_id')); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="client_secret"><?php esc_html_e('Client Secret:', 'eupago-gateway-for-woocommerce'); ?></label></th>
						<td><input class="regular-text" type="text" id='client_secret' name="client_secret" value="<?php echo esc_attr(get_option('eupago_client_secret')); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="user_eupago"><?php esc_html_e($user_text); ?></label></th>
						<td><input class="regular-text" type="text" name="user_eupago" value="<?php echo esc_attr(get_option('eupago_user')); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="password_eupago"><?php esc_html_e($password_text); ?></label></th>
						<td><input class="regular-text" type="password" name="password_eupago" value="<?php echo esc_attr(get_option('eupago_password')); ?>"></td>
					</tr>
				</tbody>
			</table>
			<h3><?php esc_html_e('SMS Biziq', 'eupago-gateway-for-woocommerce'); ?></h3>
			<table class="form-table" role="presentation">
				<tbody>
					<tr >
						<th scope="row"><label for="sms_enable"><?php esc_html_e($notificacoes_sms); ?></label></th>
						<td><input type="checkbox" name="sms_enable" value="yes" <?php echo $sms_enable_checked; ?>><?php esc_html_e($texto_enable); ?></td>
					</tr>
					<?php
                    if (!empty(get_option('eupago_sms_enable')) && get_option('eupago_sms_enable') == 'yes') {
                        $sms_enabled = 'eupago-sms-notifications active';
                    } else {
                        $sms_enabled = 'eupago-sms-notifications';
                    }
    ?>
					<tr class="<?php echo esc_html($sms_enabled); ?>">
						<th scope="row"><label for="sms_payment_hold"><?php esc_html_e($payment_on_hold); ?></label></th>
						<td><input type="checkbox" name="sms_payment_hold" value="yes" <?php echo $sms_payment_hold_checked; ?>><?php esc_html_e($texto_enable); ?></td>
					</tr>
					<tr class="<?php echo esc_html($sms_enabled); ?>">
						<th scope="row"><label for="sms_payment_confirmation"><?php esc_html_e($payment_confirmation); ?></label></th>
						<td><input type="checkbox" name="sms_payment_confirmation" value="yes" <?php echo $sms_payment_confirmation_checked; ?>><?php esc_html_e($texto_enable); ?></td>
					</tr>
					<tr class="<?php echo esc_html($sms_enabled); ?>">
						<th scope="row"><label for="sms_order_confirmation"><?php esc_html_e($sms_order_confirmation); ?></label></th>
						<td><input type="checkbox" name="sms_order_confirmation" value="yes" <?php echo $sms_order_confirmation_checked; ?>><?php esc_html_e($texto_enable); ?></td>
					</tr>
					<tr class="<?php echo esc_html($sms_enabled); ?>">
						<th scope="row"><label for="sms_intelidus_id"><?php esc_html_e('SMS Biziq ID:', 'eupago-gateway-for-woocommerce'); ?></label></th>
						<td><input class="regular-text" type="text" name="sms_intelidus_id" value="<?php echo esc_attr(get_option('eupago_sms_intelidus_id')); ?>"></td>
					</tr>
					<tr class="<?php echo esc_html($sms_enabled); ?>">
						<th scope="row"><label for="sms_intelidus_api"><?php esc_html_e('SMS Biziq API:', 'eupago-gateway-for-woocommerce'); ?></label></th>
						<td><input class="regular-text" type="text" name="sms_intelidus_api" value="<?php echo esc_attr(get_option('eupago_sms_intelidus_api')); ?>"></td>
					</tr>
					<tr class="<?php echo esc_html($sms_enabled); ?>">
						<th scope="row"><label for="intelidus_sender"><?php esc_html_e('Biziq Sender:', 'eupago-gateway-for-woocommerce'); ?></label></th>
						<td><input class="regular-text" type="text" name="intelidus_sender" value="<?php echo esc_attr(get_option('eupago_intelidus_sender')); ?>"></td>
					</tr>
                    <tr class="<?php echo esc_html($sms_enabled); ?>">
                        <th scope="row"><label for="biziq_environment"><?php esc_html_e('Ambiente:', 'eupago-gateway-for-woocommerce'); ?></label></th>
                        <td>
                            <select name="biziq_environment">
                                <option value="live" <?php selected(get_option('biziq_environment'), 'live'); ?>>Live</option>
                                <option value="sandbox" <?php selected(get_option('biziq_environment'), 'sandbox'); ?>>Sandbox</option>
                            </select>
                        </td>
                    </tr>
				</tbody>
			</table>
			<p>
				<input class="button button-primary" type="submit" name="eupago_save" value="<?php esc_html_e($save_changes_text); ?>">
			</p>
		</form>

		<form id="update-callback-form" method="POST" action="">
    <div class="eupago-callback">
        <h3><?php esc_html_e($callback); ?></h3>
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row"><label for="callback_api_key"><?php esc_html_e($api_key_text); ?></label></th>
                    <td><input class="regular-text" type="text" id='callback_api_key' name="callback_api_key"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="callback_url"><?php esc_html_e($callback_url_text); ?></label></th>
                    <td><input class="regular-text" type="text" id='callback_url' name="callback_url" value="<?php echo esc_attr($callback_url); ?>" readonly></td>
                </tr>
            </tbody>
        </table>
        <p>
            <?php esc_html_e($texto_antepenultimo); ?>
            <p></p>
            <?php esc_html_e($texto_ultimo); ?>

        </p>
        <p>
            <input class="button button-primary" type="submit" name="eupago_update" id='updateCallback' value="<?php esc_html_e($callback); ?>">
        </p>
    </div>
</form>

		<div class="eupago-sidebar">
			<img src="<?php echo esc_attr(plugins_url('images/eupago_logo.png', __FILE__)); ?>" alt="Eupago Logo">
			<p><?php esc_html_e($order); ?></p>
			<p><?php esc_html_e($doyounedd_account); ?> <a href="https://www.eupago.pt/registo" target="_BLANK">https://www.eupago.pt/registo</a>.</p>
			<p><?php esc_html_e($demo_account); ?>  <a href="mailto:comercial@eupago.pt">comercial@eupago.pt</a>.</p>
		</div>
	</div>

	
    <script>
            document.addEventListener("DOMContentLoaded", function() {
                // Get the callback URL from the server-side PHP function
                const callbackUrl = "<?php echo esc_js($callback_url); ?>";

                // Set the value of the input field
                document.getElementById("callback_url").value = callbackUrl;
            });

            document.getElementById("updateCallback").addEventListener("click", function(event) {
                event.preventDefault(); // Prevent form submission

                const clientSecret = document.getElementById("client_secret").value;
                const clientId = document.getElementById("client_id").value;
                const apiKey = document.getElementById("callback_api_key").value;
                const callbackUrl = "<?php echo esc_js($callback_url); ?>"; // Use the predefined Callback URL

                // Make an AJAX request to your server-side script
                $.ajax({
                    url: '/wp-content/plugins/eupago-gateway-for-woocommerce/includes/views/callback-script.php',
                    method: 'POST',
                    data: {
                        grant_type: 'client_credentials',
                        client_id: clientId,
                        client_secret: clientSecret,
                        callback_api_key: apiKey,
                        callback_url: callbackUrl
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.transactionStatus === "Success") {
                            // Display success message
                            alert(response.message);
                        } else if (response.transactionStatus === "Rejected") {
                            // Display error message
                            alert(response.text);
                        } else {
                            // Handle other response cases if needed
                            alert("Unexpected response from the server.");
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error(errorThrown);
                        // Display error message
                        alert("An error occurred during the request.");
                    }
                });
            });
        </script>

<?php
}
?>