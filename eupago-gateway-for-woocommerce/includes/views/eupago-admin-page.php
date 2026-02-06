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
           $channel   = isset($_POST['channel']) 
            ? sanitize_text_field(wp_unslash($_POST['channel'])) 
            : '';

            $api_key   = isset($_POST['api_key']) 
                ? sanitize_text_field(wp_unslash($_POST['api_key'])) 
                : '';

            $endpoint  = isset($_POST['endpoint']) 
                ? sanitize_text_field(wp_unslash($_POST['endpoint'])) 
                : '';

            $reminder  = isset($_POST['reminder']) 
                ? sanitize_text_field(wp_unslash($_POST['reminder'])) 
                : '0';

            $debug     = isset($_POST['debug']) 
                ? sanitize_text_field(wp_unslash($_POST['debug'])) 
                : 'no';

            $client_id = isset($_POST['client_id']) 
                ? sanitize_text_field(wp_unslash($_POST['client_id'])) 
                : '';

            $client_secret = isset($_POST['client_secret']) 
                ? sanitize_text_field(wp_unslash($_POST['client_secret'])) 
                : '';

            $user_eupago = isset($_POST['user_eupago']) 
                ? sanitize_text_field(wp_unslash($_POST['user_eupago'])) 
                : '';

            $password_eupago = isset($_POST['password_eupago']) 
                ? sanitize_text_field(wp_unslash($_POST['password_eupago'])) 
                : '';

            $sms_enable = isset($_POST['sms_enable']) 
                ? sanitize_text_field(wp_unslash($_POST['sms_enable'])) 
                : 'no';

            $sms_payment_hold = isset($_POST['sms_payment_hold']) 
                ? sanitize_text_field(wp_unslash($_POST['sms_payment_hold'])) 
                : 'no';

            $sms_payment_confirmation = isset($_POST['sms_payment_confirmation']) 
                ? sanitize_text_field(wp_unslash($_POST['sms_payment_confirmation'])) 
                : 'no';

            $sms_order_confirmation = isset($_POST['sms_order_confirmation']) 
                ? sanitize_text_field(wp_unslash($_POST['sms_order_confirmation'])) 
                : 'no';

            $sms_intelidus_id = isset($_POST['sms_intelidus_id']) 
                ? sanitize_text_field(wp_unslash($_POST['sms_intelidus_id'])) 
                : '';

            $sms_intelidus_api = isset($_POST['sms_intelidus_api']) 
                ? sanitize_text_field(wp_unslash($_POST['sms_intelidus_api'])) 
                : '';

            $intelidus_sender = isset($_POST['intelidus_sender']) 
                ? sanitize_text_field(wp_unslash($_POST['intelidus_sender'])) 
                : '';

            $biziq_environment = isset($_POST['biziq_environment']) 
                ? sanitize_text_field(wp_unslash($_POST['biziq_environment'])) 
                : 'live';


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

	        // Prepare POST data
	        $request_body = [
		        'grant_type'       => 'client_credentials',
		        'client_id'        => $client_id,
		        'client_secret'    => $client_secret,
		        'callback_api_key' => $api_key,
	        ];

	        // Make the request to info-script.php
	        $response = wp_remote_post(site_url('/wp-content/plugins/eupago-gateway-for-woocommerce/includes/views/info-script.php'), [
		        'body'    => $request_body,
		        'timeout' => 15,
	        ]);

	        // Handle errors
	        if (is_wp_error($response)) {
		        $error_message = $response->get_error_message();
		        echo "Something went wrong: $error_message";
	        } else {
		        // Retrieve and decode JSON body
		        $body = wp_remote_retrieve_body($response);
		        $data = json_decode($body, true);

		        if (!empty($data['channelInfo'])) {
			        $channel_data['eupago_webhook_version'] = $data['channelInfo']['webhookVersion'];
			        $channel_data['eupago_webhook_encrypt_key'] = $data['channelInfo']['webhookEncryptKey'];
			        $channel_data['eupago_webhook_url'] = $data['channelInfo']['webhookUrl'];
			        saveWebhookInfo($channel_data);
		        } else {
			        echo "Unexpected response format or missing channelInfo.";
		        }
	        }

        }
    }

	if (
		$_SERVER['REQUEST_METHOD'] === 'POST' &&
		isset($_POST['eupago_webhook_nonce']) &&
		wp_verify_nonce($_POST['eupago_webhook_nonce'], 'eupago_save_webhook')
	) {
		 saveWebhookInfo($_POST);
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
            <?php
            $locale = get_locale();
            if ($locale == 'pt_PT' || $locale == 'pt_BR') {
                echo '<p>' . esc_html__('Integração de serviços Eupago.', 'eupago-gateway-for-woocommerce') . '</p>';
            } elseif ($locale == 'es_ES') {
                echo '<p>' . esc_html__('Integración de servicios Eupago.', 'eupago-gateway-for-woocommerce') . '</p>';
            } else {
                echo '<p>' . esc_html__('Eupago services integration.', 'eupago-gateway-for-woocommerce') . '</p>';
            }
            ?>
        </div>
    </div>
    <?php if (isset($_POST['eupago_save'])) { ?>
    <!-- Show success message if settings are saved -->

    <div class="eupago-notice notice notice-success">
        <p><strong><?php
                $locale = get_locale();
                if ($locale == 'pt_PT' || $locale == 'pt_BR') {
                    echo esc_html__('Configurações salvas.', 'eupago-gateway-for-woocommerce');
                } elseif ($locale == 'es_ES') {
                    echo esc_html__('Configuraciones guardadas.', 'eupago-gateway-for-woocommerce');
                } else {
                    echo esc_html__('Settings saved.', 'eupago-gateway-for-woocommerce');
                }
                ?></strong></p>
    </div>
<?php } ?>


    <div class="eupago-settings">
        <div>
            <form name="eupago-settings" method="POST" action="">
                <!-- Add the nonce field -->
                <?php wp_nonce_field('eupago_settings_nonce', '_wpnonce'); ?>


                <table class="form-table" role="presentation">
                    <?php
                    $texto_traduzido = esc_html__('Each account has at least a channel. Each channel has an API Key that identifies your Eupago\'s account.', 'eupago-gateway-for-woocommerce');
                    $texto_callback = esc_html__('Please activate callback to this url on Eupago dashboard: ', 'eupago-gateway-for-woocommerce');
                    $texto_enable = esc_html__('Enable', 'eupago-gateway-for-woocommerce');
                    $texto_antepenultimo = esc_html__('Please make sure that the fields with the Client ID, Client Secret and Api Key are properly filled above to use the buttons bellow.','eupago-gateway-for-woocommerce');
                    $texto_ultimo = esc_html__('The generated URL is the default webhook for a Woocommece store. If your store has a different file path,', 'eupago-gateway-for-woocommerce');
                    $callback_button = esc_html__('Update Webhook','eupago-gateway-for-woocommerce');
                    $callback = esc_html__('Webhook Tools (Callback)','eupago-gateway-for-woocommerce');
                    $order = esc_html__('In order to use Eupago plugin for WooCommerce you must have an Eupago account.', 'eupago-gateway-for-woocommerce');
                    //Primeira linha do ficheiro para traduzir
                    $channel_name = esc_html__('Channel Name:', 'eupago-gateway-for-woocommerce');
                    $channel = esc_html__('What is a channel?', 'eupago-gateway-for-woocommerce');
                    $order_api = esc_html__('In order to find your API Key and channel name please follow this guide on our', 'eupago-gateway-for-woocommerce');
                    $help_center = esc_html__('Help Center','eupago-gateway-for-woocommerce');
                    $api_key_text = esc_html__('Api Key: ', 'eupago-gateway-for-woocommerce');
                    $end_point_text = esc_html__('Endpoint: ','eupago-gateway-for-woocommerce');
                    $live_text = esc_html__('Live','eupago-gateway-for-woocommerce');
                    $sandbox_text = esc_html__('Sandbox', 'eupago-gateway-for-woocommerce');
                    $reminder_faiolver = esc_html__('Reminder(FailOver):', 'eupago-gateway-for-woocommerce');
                    $see_reminder = esc_html__('Do you want to send a reminder to your customer to inform them that they have a pending payment order? Activate this option. Read more about this reminder', 'eupago-gateway-for-woocommerce');
                    $here = esc_html__('here.','eupago-gateway-for-woocommerce');
                    $debug_log = esc_html__('Debug Log: ' ,'eupago-gateway-for-woocommerce');
                    $log_plugins = esc_html__('Log plugin events and request responses inside', 'eupago-gateway-for-woocommerce');
                    $refund_text = esc_html('Refund:','eupago-gateway-for-woocommerce');
                    $user_text = esc_html__('User: ','eupago-gateway-for-woocommerce');
                    $password_text = esc_html__('Password','eupagp-gateway-for-woocommerce');
                    $notificacoes_sms = esc_html__('Nofitications Biziq: ','eupago-gateway-for-woocommerce');
                    $payment_on_hold = esc_html__('Send SMS with payment details:', 'eupago-gateway-for-woocommerce');
                    $sms_order_confirmation = esc_html('SMS Order Confirmation:', 'eupago-gateway-for-woocommerce');
                    $payment_confirmation = esc_html__('SMS Payment Confirmation:', 'eupago-gateway-for-woocommerce');
                    $callback_url_text = esc_html__('Callback url: ','eupago-gateway-for-woocommerce');
                    $doyounedd_account = esc_html__('Do you need an account? You may sign up at', 'eupago-gateway-for-woocommerce');
                    $demo_account = esc_html__('Do you already have a demo account and need to finish your real account? Please reach out by email:', 'eupago-gateway-for-woocommerce');
                    $save_changes_text = esc_html__('Save Changes', 'eupago-gateway-for-woocommerce');
                    $endpoint_description = esc_html__('Choose "Live" for real payments or "Sandbox" for testing without making payments.', 'eupago-gateway-for-woocommerce');
                    $callback_help = esc_html__('use the Eupago Backoffice.', 'eupago-gateway-for-woocommerce');
                    $biziq_help = esc_html__('Click here for more details about Biziq environment.', 'eupago-gateway-for-woocommerce');
                    $environment = esc_html__('Environment', 'eupago-gateway-for-woocommerce');
                    $sync_channel = esc_html__('Synchronize Channel Info', 'eupago-gateway-for-woocommerce');
                    $refund_note = esc_html__('Please make sure that the fields Client ID and Client Secret are properly filled to configure the refund functionality', 'eupago-gateway-for-woocommerce');
                    if (get_locale() == 'pt_BR' || get_locale() == 'pt_PT') {
                        $texto_traduzido = 'Cada conta tem pelo menos um canal. Cada canal possui uma chave API que identifica a sua conta da Eupago.';
                        $texto_callback = 'Por favor, ative o callback para este URL no painel da Eupago:';
                        $texto_enable = 'Ativar';
                        $texto_antepenultimo = 'Por favor, certifique-se de que os campos com o Client ID, Client Secret e Api Key estão devidamente preenchidos acima para utilizar os botões abaixo.';
                        $texto_ultimo = 'A URL gerada é o webhook padrão para uma loja WooCommerce. Se a sua loja tiver um caminho de ficheiro diferente, por favor, ajuste-o conforme necessário,';
                        $callback = 'Ferramentas de Webhook (Callback)';
	                    $callback_button = esc_html__('Atualizar Webhook','eupago-gateway-for-woocommerce');
                        $order = 'Para usar o plugin da Eupago para WooCommerce, você deve ter uma conta na Eupago.';
                        //Primeira linha do ficheiro para traduzir
                        $channel_name = esc_html__('Nome do Canal:', 'eupago-gateway-for-woocommerce');
                        $channel = esc_html__('O que é um canal?', 'eupago-gateway-for-woocommerce');
                        $order_api = esc_html__('Para encontrar a sua chave API e nome do canal, siga este guia no nosso', 'eupago-gateway-for-woocommerce');
                        $help_center = esc_html__('Centro de Ajuda','eupago-gateway-for-woocommerce');
                        $api_key_text = esc_html__('Chave API: ', 'eupago-gateway-for-woocommerce');
                        $end_point_text = esc_html__('Endpoint: ','eupago-gateway-for-woocommerce');
                        $live_text = esc_html__('Produção','eupago-gateway-for-woocommerce');
                        $sandbox_text = esc_html__('Sandbox', 'eupago-gateway-for-woocommerce');
                        $reminder_faiolver = esc_html__('Lembrete (FailOver):', 'eupago-gateway-for-woocommerce');
                        $see_reminder = esc_html__('Quer enviar um lembrete para informar o seu cliente que tem um pedido de pagamento pendente? Ative esta opção. Leia mais sobre este lembrete', 'eupago-gateway-for-woocommerce');
                        $here = esc_html__('aqui.','eupago-gateway-for-woocommerce');
                        $debug_log = esc_html__('Log de Depuração: ' ,'eupago-gateway-for-woocommerce');
                        $log_plugins = esc_html__('Registe eventos do plugin e as respostas a pedidos, dentro do diretório', 'eupago-gateway-for-woocommerce');
                        $refund_text = esc_html__('Reembolso:','eupago-gateway-for-woocommerce');
                        $user_text = esc_html__('Utilizador: ','eupago-gateway-for-woocommerce');
                        $password_text = esc_html__('Palavra-Passe','eupagp-gateway-for-woocommerce');
                        $notificacoes_sms = esc_html__('Notificações Biziq: ','eupago-gateway-for-woocommerce');
                        $payment_on_hold = esc_html__('Envio de SMS dos detalhes de pagamento:', 'eupago-gateway-for-woocommerce');
                        $sms_order_confirmation = esc_html__('Confirmação de Pedido por SMS:', 'eupago-gateway-for-woocommerce');
                        $payment_confirmation = esc_html__('Confirmação de Pagamento por SMS:', 'eupago-gateway-for-woocommerce');
                        $callback_url_text = esc_html__('URL de Retorno: ','eupago-gateway-for-woocommerce');
                        $doyounedd_account = esc_html__('Precisa de uma conta? Você pode se inscrever em', 'eupago-gateway-for-woocommerce');
                        $demo_account = esc_html__('Já tem uma conta de demonstração e precisa concluir sua conta real? Entre em contato por e-mail:', 'eupago-gateway-for-woocommerce');
                        $save_changes_text = esc_html__('Guardar Alterações', 'eupago-gateway-for-woocommerce');
                        $endpoint_description = esc_html('Escolha "Produção" para pagamentos reais ou "Sandbox" para testes sem efetuar pagamentos.', 'eupago-gateway-for-woocommerce');
                        $callback_help = esc_html__('utilize o Backoffice da Eupago.', 'eupago-gateway-for-woocommerce');
                        $biziq_help = esc_html__('Clique aqui para mais detalhes sobre o ambiente Biziq.', 'eupago-gateway-for-woocommerce');
                        $environment = esc_html__('Ambiente', 'eupago-gateway-for-woocommerce');
                        $sync_channel = esc_html__('Sincronizar Informações do Canal', 'eupago-gateway-for-woocommerce');
                        $refund_note = esc_html__('Certifique-se de que os campos Client ID e Client Secret estão devidamente preenchidos para configurar a funcionalidade de reembolso.', 'eupago-gateway-for-woocommerce');

                    }else if(get_locale()== 'es_ES'){
                        $texto_traduzido = 'Cada cuenta tiene al menos un canal. Cada canal tiene una Clave API que identifica su cuenta de Eupago.';
                        $texto_callback = 'Por favor, active la devolución de llamada para esta URL en el panel de Eupago:';
                        $texto_enable = 'Habilitar';
                        $texto_antepenultimo = 'Por favor, asegúrese de que los campos con el Client ID, Client Secret y Api Key estén correctamente rellenados arriba para poder usar los botones de abajo.';
                        $texto_ultimo = 'La URL generada es el webhook predeterminado para una tienda WooCommerce. Si su tienda tiene una ruta de archivo diferente, por favor, ajústela según sea necesario.,';
                        $callback_button = 'Actualizar devolución de llamada';
                        $callback = 'Herramientas de Webhook (Llamada de retorno)';
                        $order = 'Para utilizar el plugin de Eupago para WooCommerce, debe tener una cuenta en Eupago.';
                        //Primeira linha do ficheiro para traduzir
                        $channel_name = esc_html__('Nombre del canal:', 'eupago-gateway-for-woocommerce');
                        $channel = esc_html__('¿Qué es un canal?', 'eupago-gateway-for-woocommerce');
                        $order_api = esc_html__('Para encontrar su Clave API y nombre de canal, siga esta guía en nuestro', 'eupago-gateway-for-woocommerce');
                        $help_center = esc_html__('Centro de ayuda','eupago-gateway-for-woocommerce');
                        $api_key_text = esc_html__('Clave API: ', 'eupago-gateway-for-woocommerce');
                        $end_point_text = esc_html__('Endpoint: ','eupago-gateway-for-woocommerce');
                        $live_text = esc_html__('En vivo','eupago-gateway-for-woocommerce');
                        $sandbox_text = esc_html__('Sandbox', 'eupago-gateway-for-woocommerce');
                        $reminder_faiolver = esc_html__('Recordatorio (FailOver):', 'eupago-gateway-for-woocommerce');
                        $see_reminder = esc_html__('¿Desea enviar un recordatorio a su cliente para informarle que tiene un pedido de pago pendiente? Active esta opción. Lea más sobre este recordatorio', 'eupago-gateway-for-woocommerce');
                        $here = esc_html__('aquí.','eupago-gateway-for-woocommerce');
                        $debug_log = esc_html__('Registro de depuración: ' ,'eupago-gateway-for-woocommerce');
                        $log_plugins = esc_html__('Registrar eventos del plugin y las respuestas a solicitudes dentro', 'eupago-gateway-for-woocommerce');
                        $refund_text = esc_html__('Reembolso:','eupago-gateway-for-woocommerce');
                        $user_text = esc_html__('Usuario: ','eupago-gateway-for-woocommerce');
                        $password_text = esc_html__('Contraseña','eupagp-gateway-for-woocommerce');
                        $notificacoes_sms = esc_html__('Notificaciones Biziq: ','eupago-gateway-for-woocommerce');
                        $payment_on_hold = esc_html__('Envío de SMS con los detalles de pago:', 'eupago-gateway-for-woocommerce');
                        $sms_order_confirmation = esc_html__('Confirmación de pedido SMS:', 'eupago-gateway-for-woocommerce');
                        $payment_confirmation = esc_html__('Confirmación de pago SMS:', 'eupago-gateway-for-woocommerce');
                        $callback_url_text = esc_html__('URL de devolución de llamada: ','eupago-gateway-for-woocommerce');
                        $doyounedd_account = esc_html__('¿Necesita una cuenta? Puede registrarse en', 'eupago-gateway-for-woocommerce');
                        $demo_account = esc_html__('¿Ya tiene una cuenta de demostración y necesita terminar su cuenta real? Por favor, contáctenos por correo electrónico:', 'eupago-gateway-for-woocommerce');
                        $save_changes_text = esc_html__('Guardar Cambios','eupago-gateway-for-woocomerce');
                        $endpoint_description = esc_html__('Elija "Producción" para pagos reales o "Sandbox" para pruebas sin realizar pagos.', 'eupago-gateway-for-woocommerce');
                        $callback_help = esc_html__('utilice el Backoffice de Eupago.', 'eupago-gateway-for-woocommerce');
                        $biziq_help = esc_html__('Haga clic aquí para más detalles sobre el entorno de Biziq.', 'eupago-gateway-for-woocommerce');
                        $environment = esc_html__('Entorno', 'eupago-gateway-for-woocommerce');
                        $sync_channel = esc_html__('Sincronizar Información del Canal', 'eupago-gateway-for-woocommerce');
                        $refund_note = esc_html__('Asegúrese de que los campos Client ID y Client Secret estén correctamente completados para configurar la funcionalidad de reembolso.', 'eupago-gateway-for-woocommerce');
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
                                <a href="https://eupago.atlassian.net/servicedesk/customer/portal/2/article/224297034?src=1875300770" target="_BLANK"><?php echo esc_html($help_center); ?></a>.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="api_key"><?php esc_html_e($api_key_text); ?></label></th>
                        <td>
                            <input class="regular-text" type="text" name="api_key" id="api_key" value="<?php echo esc_attr(get_option('eupago_api_key')); ?>">
                            <p>
                                <?php esc_html_e($texto_callback); ?> <?php echo get_option('permalink_structure') == '' ? home_url('/') . '?wc-api=WC_euPago' : home_url('/') . 'wc-api/WC_euPago/'; ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="endpoint"><?php esc_html_e($end_point_text); ?></label></th>
                        <td>
                            <select name="endpoint">
                                <option value="clientes" <?php selected(get_option('eupago_endpoint'), 'clientes'); ?>>
                                    <?php esc_html_e($live_text); ?>
                                </option>
                                <option value="sandbox" <?php selected(get_option('eupago_endpoint'), 'sandbox'); ?>>
                                    <?php esc_html_e($sandbox_text); ?>
                                </option>
                            </select>
                            <p>
                                <?php esc_html_e($endpoint_description); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="reminder"><?php esc_html_e($reminder_faiolver); ?></label></th>

                        <td>
                            <input type="checkbox" name="reminder" value="1" <?php echo $reminder_checked; ?>><?php esc_html_e($texto_enable); ?>
                            <p>
                                <?php esc_html_e($see_reminder); ?>
                                <a href="https://eupago.atlassian.net/servicedesk/customer/portal/2/article/652967937" target="_BLANK"><?php esc_html_e($here) . ' .'?></a>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="debug"><?php esc_html_e($debug_log); ?></label></th>
                        <td>
                            <input type="checkbox" name="debug" value="yes" <?php echo $debug_checked; ?>><?php esc_html_e($texto_enable); ?>
                            <p>
                                <?php esc_html_e($log_plugins); ?>
                                <?php $uploads = wp_upload_dir(); ?>
                                <code><?php echo wp_basename($uploads['baseurl']) . '/wc-logs/'; ?></code>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="client_id"><?php esc_html_e('Client ID:', 'eupago-gateway-for-woocommerce'); ?></label></th>
                        <td><input class="regular-text" type="text" id='client_id' name="client_id" value="<?php echo esc_attr(get_option('eupago_client_id')); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="client_secret"><?php esc_html_e('Client Secret:', 'eupago-gateway-for-woocommerce'); ?></label></th>
                        <td><input class="regular-text" type="text" id='client_secret' name="client_secret" value="<?php echo esc_attr(get_option('eupago_client_secret')); ?>"></td>
                    </tr>
					<tr>
						<th scope="row">
							<a href="https://customer.support.eupago.com/servicedesk/customer/portal/2/topic/1adeadd6-a39f-4c79-8f61-930a9302fb1b/article/226983964" target="_blank"><?php echo esc_html($help_center).' Client ID/Secret'; ?>
							</a>
						</th>
					</tr>
					<?php
					if(get_option('eupago_webhook_version') && !empty(get_option('eupago_webhook_version'))){
					?>
						<tr>
							<th scope="row"><label for="eupago_webhook_version"><?php esc_html_e('Webhook Version:', 'eupago-gateway-for-woocommerce'); ?></label></th>
							<td><input class="regular-text" type="text" readonly id='webhook_version' name="webhook_version" value="<?php echo esc_attr(get_option('eupago_webhook_version')); ?>"></td>
						</tr>
					<?php } ?>
					<?php
					if(get_option('eupago_webhook_url') && !empty(get_option('eupago_webhook_url'))){
					?>
					<tr>
						<th scope="row"><label for="eupago_webhook_url"><?php esc_html_e('Webhook Url:', 'eupago-gateway-for-woocommerce'); ?></label></th>
						<td><input class="regular-text" type="text" readonly id='webhook_url' name="webhook_url" value="<?php echo esc_attr(get_option('eupago_webhook_url')); ?>"></td>
					</tr>
                    <?php } ?>
                    </tbody>
                </table>

                <h3><?php esc_html_e($refund_text); ?></h3>
                <table class="form-table" role="presentation">
                    <tbody>
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
                <p><?php esc_html_e($refund_note); ?></p>

                <h3><?php esc_html_e('SMS Biziq', 'eupago-gateway-for-woocommerce'); ?></h3>
                <table class="form-table" role="presentation">
                    <tbody>
                    <tr >
                        <th scope="row"><label for="sms_enable"><?php esc_html_e($notificacoes_sms); ?></label></th>
                        <td><input type="checkbox" name="sms_enable" value="yes" <?php echo $sms_enable_checked; ?>><?php esc_html_e($texto_enable); ?></td>
                    </tr>
                    <tr>

                            <p style="font-weight: bold;">
                                <?php
                                // Captura a linguagem atual do WordPress
                                $locale = get_locale();

                                // Exibe a mensagem baseada na linguagem
                                if ($locale == 'pt_PT') {
                                    // Português
                                    esc_html_e('Os SMS devem ser configurados em cada método de pagamento específico.', 'eupago-gateway-for-woocommerce');
                                } elseif ($locale == 'en_US') {
                                    // Inglês
                                    esc_html_e('SMS must be configured for each specific payment method.', 'eupago-gateway-for-woocommerce');
                                } elseif ($locale == 'es_ES') {
                                    // Espanhol
                                    esc_html_e('Los SMS deben configurarse en cada método de pago específico.', 'eupago-gateway-for-woocommerce');
                                } else {
                                    // Caso o idioma não seja um dos especificados, exibe o texto em inglês por padrão
                                    esc_html_e('SMS must be configured for each specific payment method.', 'eupago-gateway-for-woocommerce');
                                }
                                ?>
                            </p>

                    </tr>

                    <?php
                    if (!empty(get_option('eupago_sms_enable')) && get_option('eupago_sms_enable') == 'yes') {
                        $sms_enabled = 'eupago-sms-notifications active';
                    } else {
                        $sms_enabled = 'eupago-sms-notifications';
                    }
                    ?>
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
                        <th scope="row"><label for="biziq_environment"><?php esc_html_e($environment); ?></label></th>
                        <td>
                            <select name="biziq_environment">
                                <option value="live" <?php selected(get_option('biziq_environment'), 'live'); ?>>Live</option>
                                <option value="sandbox" <?php selected(get_option('biziq_environment'), 'sandbox'); ?>>Sandbox</option>
                            </select>
                        </td>
                    </tr>

                    </tbody>
                </table>

                <p><a href="https://customer.support.eupago.com/servicedesk/customer/portal/2/topic/50cd7c32-266c-4dfc-a60c-7587875c2de4/article/224362527" target="_blank"><?php esc_html_e($biziq_help); ?></a></p>
                <div class="button-container">
                    <input class="button button-primary save-button" type="submit" name="eupago_save" value="<?php esc_html_e($save_changes_text); ?>">
                </div>
            </form>

            <form id="update-callback-form" method="POST" action="">
                <div class="eupago-callback">
                    <h3><?php esc_html_e($callback); ?></h3>
                    <p>
                        <?php esc_html_e($texto_antepenultimo); ?>
                    <br/>
                    <?php esc_html_e($texto_ultimo); ?>
                    <a href="https://customer.support.eupago.com/servicedesk/customer/portal/5/article/224297068" target="_blank">
                        <?php echo $callback_help; ?>
                    </a>
                    </p>

                </div>
                <p>
                    <input class="button button-primary" type="submit" name="eupago_update" id='updateCallback' value="<?php esc_html_e($callback_button); ?>">
                    <input class="button button-primary" type="submit" name="sync_channel" id='syncEupagoChannel' value="<?php esc_html_e($sync_channel); ?>">
                </p>
            </form>
			<form name="eupago-sync-webook" method="POST" action="">
				<?php wp_nonce_field('eupago_save_webhook', 'eupago_webhook_nonce'); ?>
				<input type="hidden" name="eupago_webhook_version" id="eupago_webhook_version" value="<?php echo esc_attr(get_option('eupago_webhook_version')); ?>">
				<input type="hidden" name="eupago_webhook_encrypt_key" id="eupago_webhook_encrypt_key" value="<?php echo esc_attr(get_option('eupago_webhook_encrypt_key')); ?>">
				<input type="hidden" name="eupago_webhook_url" id="eupago_webhook_url" value="<?php echo esc_attr(get_option('eupago_webhook_url')); ?>">
			</form>
        </div>
        <div class="eupago-sidebar">
            <img src="<?php echo esc_attr(plugins_url('images/eupago_logo.png', __FILE__)); ?>" alt="Eupago Logo">
            <p><?php esc_html_e($order); ?></p>
            <p><?php esc_html_e($doyounedd_account); ?> <a href="https://www.eupago.pt/registo" target="_BLANK">https://www.eupago.pt/registo</a>.</p>
            <p><?php esc_html_e($demo_account); ?>  <a href="mailto:comercial@eupago.pt">comercial@eupago.pt</a>.</p>
        </div>
    </div>


    <script>
        document.getElementById("updateCallback").addEventListener("click", function(event) {
            event.preventDefault(); // Prevent form submission

            const clientSecret = document.getElementById("client_secret").value;
            const clientId = document.getElementById("client_id").value;
            const apiKey = document.getElementById("api_key").value;
            const callbackUrl = "<?php echo esc_js($callback_url); ?>"; // Use the predefined Callback URL
            const eupago_webhook_version = document.getElementById("eupago_webhook_version").value;;

            // Make an AJAX request to your server-side script
            $.ajax({
                url: '/wp-content/plugins/eupago-gateway-for-woocommerce/includes/views/callback-script.php',
                method: 'POST',
                data: {
                    grant_type: 'client_credentials',
                    client_id: clientId,
                    client_secret: clientSecret,
                    callback_api_key: apiKey,
                    callback_url: callbackUrl,
                    webhookVersion: eupago_webhook_version
                },
                dataType: 'json',
                success: function(response) {
                    if (response.transactionStatus === "Success") {
                        $('#syncEupagoChannel').click();
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
        document.getElementById("syncEupagoChannel").addEventListener("click", function(event) {
            event.preventDefault(); // Prevent form submission

            const clientSecret = document.getElementById("client_secret").value;
            const clientId = document.getElementById("client_id").value;
            const apiKey = document.getElementById("api_key").value;

            // Make an AJAX request to your server-side script
            $.ajax({
                url: '/wp-content/plugins/eupago-gateway-for-woocommerce/includes/views/info-script.php',
                method: 'POST',
                data: {
                    grant_type: 'client_credentials',
                    client_id: clientId,
                    client_secret: clientSecret,
                    callback_api_key: apiKey
                },
                dataType: 'json',
                success: function(response) {
                    if (response.transactionStatus === "Success") {
                        $('#eupago_webhook_version').val(response.channelInfo.webhookVersion);
                        $('#eupago_webhook_encrypt_key').val(response.channelInfo.webhookEncryptKey);
                        $('#eupago_webhook_url').val(response.channelInfo.webhookUrl);

                        // Submit the form
                        $('form[name="eupago-sync-webook"]').submit();
                        // Display success message
                        alert('Channel Synchronized successfully.');
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
function saveWebhookInfo($data)
{
	$version = sanitize_text_field($data['eupago_webhook_version']);
	$encrypt_key = sanitize_text_field($data['eupago_webhook_encrypt_key']);
	$webhook_url = sanitize_text_field($data['eupago_webhook_url']);

	if (empty(get_option('eupago_webhook_version'))) {
		delete_option('eupago_webhook_version');
		add_option('eupago_webhook_version', $version, '', 'yes');
	}else{
		update_option('eupago_webhook_version', $version);
	}

	if (empty(get_option('eupago_webhook_encrypt_key'))) {
		delete_option('eupago_webhook_encrypt_key');
		add_option('eupago_webhook_encrypt_key', $encrypt_key, '', 'yes');
	}
	else{
		update_option('eupago_webhook_encrypt_key', $encrypt_key);
	}

	if (empty(get_option('eupago_webhook_url'))) {
		delete_option('eupago_webhook_url');
		add_option('eupago_webhook_url', $webhook_url, '', 'yes');
	}
	else{
		update_option('eupago_webhook_url', $webhook_url);
	}
}
?>