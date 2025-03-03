<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WC_Eupago_Pix')) {

    class WC_Eupago_Pix extends WC_Payment_Gateway {

        protected $instructions;
        //protected $only_portugal; Brazil?
        protected $only_above;
        protected $only_below;
        protected $stock_when;
        protected $sms_payment_hold_pix;
        protected $sms_payment_confirmation_pix;
        protected $sms_order_confirmation_pix;
        protected $client;

        public function __construct() {

            global $woocommerce;
            $this->id = 'eupago_pix';
            $this->icon = plugins_url('assets/images/pix_icon.png', dirname(__FILE__));
            $this->has_fields = false;
            $this->method_title = __('EuroPix (Eupago)', 'eupago-gateway-for-woocommerce');

            // Plugin options and settings
            $this->init_form_fields();
            $this->init_settings();

            // User settings
            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');
            $this->instructions = $this->get_option('instructions');
            //$this->only_portugal = $this->get_option('only_portugal');
            $this->only_above = $this->get_option('only_above');
            $this->only_below = $this->get_option('only_below');
            $this->stock_when = $this->get_option('stock_when');
            $this->sms_payment_hold_pix = $this->get_option('sms_payment_hold_pix');
            $this->sms_payment_confirmation_pix = $this->get_option('sms_payment_confirmation_pix');
            $this->sms_order_confirmation_pix = $this->get_option('sms_order_confirmation_pix');

            // Set the API
            $this->client = new WC_Eupago_API($this);

            // Actions and filters
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
            //add_action('woocommerce_order_status_pending', array($this, 'send_sms_pending_pix'));
            //add_action('woocommerce_order_status_on-hold', array($this, 'send_sms_pending_pix'));
            //add_action('woocommerce_order_status_processing' , array($this, 'send_sms_processing_pix'));
            //add_action('woocommerce_order_status_completed', array($this, 'send_sms_completed_pix'));

            if (function_exists('icl_object_id') && function_exists('icl_register_string')) {
                add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'register_wpml_strings']);
            }
            add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));
            add_action('woocommerce_order_details_after_order_table', [$this, 'order_details_after_order_table'], 20);

            //add_filter('woocommerce_available_payment_gateways', [$this, 'disable_unless_portugal']);
            add_filter('woocommerce_available_payment_gateways', [$this, 'disable_only_above_or_below']);

            // Customer Emails
            add_action('woocommerce_email_before_order_table', [$this, 'email_instructions'], 10, 2);

            // Filter to decide if payment_complete reduces stock, or not
            add_filter('woocommerce_payment_complete_reduce_order_stock', [$this, 'woocommerce_payment_complete_reduce_order_stock'], 10, 2);
        }

        /**
         * WPML compatibility
         */
        public function register_wpml_strings() {
            $to_register = [];

            foreach ($to_register as $string) {
                icl_register_string($this->id, $this->id . '_' . $string, $this->settings[$string]);
            }
        }

        public function init_form_fields() {

            // Get the current language of the WooCommerce admin page
            $admin_language = get_locale();

            // Default language options
            $language_options = [
                'default' => __('Default', 'eupago-gateway-for-woocommerce'),
                'pt' => __('Portuguese', 'eupago-gateway-for-woocommerce'),
                'en' => __('English', 'eupago-gateway-for-woocommerce'),
                'es' => __('Spanish', 'eupago-gateway-for-woocommerce'),
            ];

            // Translate language options if the admin language is not English
            if ($admin_language === 'pt_PT' || $admin_language === 'pt_BR') {
                $language_options['default'] = __('Por padrão', 'eupago-gateway-for-woocommerce');
                $language_options['pt'] = __('Português', 'eupago-gateway-for-woocommerce');
                $language_options['en'] = __('Inglês', 'eupago-gateway-for-woocommerce');
                $language_options['es'] = __('Espanhol', 'eupago-gateway-for-woocommerce');
            } elseif ($admin_language === 'es_ES') {
                $language_options['default'] = __('Default', 'eupago-gateway-for-woocommerce');
                $language_options['pt'] = __('Portuguese', 'eupago-gateway-for-woocommerce');
                $language_options['en'] = __('English', 'eupago-gateway-for-woocommerce');
                $language_options['es'] = __('Spanish', 'eupago-gateway-for-woocommerce');
            }

            // Translate title based on the selected language
            $language_title = esc_html(__('Language', 'eupago-gateway-for-woocommerce'));
            $language_description = esc_html(__('Select the language for the payment process.', 'eupago-gateway-for-woocommerce'));

            if ($admin_language === 'pt_PT' || $admin_language === 'pt_BR') {
                $language_title = esc_html(__('Idioma', 'eupago-gateway-for-woocommerce'));
                $language_description = esc_html(__('Selecione o idioma para o processo de pagamento.', 'eupago-gateway-for-woocommerce'));
            } elseif ($admin_language === 'es_ES') {
                $language_title = esc_html(__('Idioma', 'eupago-gateway-for-woocommerce'));
                $language_description = esc_html(__('Seleccione el idioma para el proceso de pago.', 'eupago-gateway-for-woocommerce'));
            }

            $payment_on_hold = __('Send SMS with payment details:', 'eupago-gateway-for-woocommerce');
            $enable_text = esc_html__('Enable', 'eupago-gateway-for-woocommerce');
            $sms_order_confirmation = esc_html('SMS Order Confirmation:', 'eupago-gateway-for-woocommerce');
            $enable_disable_title = __('Enable/Disable', 'eupago-gateway-for-woocommerce');
            $title_pix = __('Title', 'eupago-gateway-for-woocommerce');
            $pix = __('EuroPix', 'eupago-gateway-for-woocommerce');
            $instructions_text = __('Instructions', 'eupago-gateway-for-woocommerce');
            $description_instructions_text = __('Instructions that will be added to the thank you page and email sent to customer.', 'eupago-gateway-for-woocommerce');
            $duplicate_payments_text = __('Duplicate payments', 'eupago-gateway-for-woocommerce');
            $controls_checkout = __('This controls the title the user sees during the checkout process', 'eupago-gateway-for-woocommerce');
            $allow_duplicate_text = __('Allow duplicate payments.', 'eupago-gateway-for-woocommerce');
            $no_text = __('No', 'eupago-gateway-for-woocommerce');
            $yes_text = __('Yes', 'eupago-gateway-for-woocommerce');
            $description = __('Description', 'eupago-gateway-for-woocommerce');
            $description_checkout = __('This controls the description the user sees during checkout.', 'eupago-gateway-for-woocommerce');
            $expire_date_text = __('Expire Date', 'eupago-gateway-for-woocommerce');
            $number_days_expire = __('Number of days to payment expire.', 'eupago-gateway-for-woocommerce');
            /* $only_portuguese_customers = __('Only for Portuguese customers?', 'eupago-gateway-for-woocommerce'); */
            /* $enable_only_portugal = __('Enable only for customers whose address is in Portugal', 'eupago-gateway-for-woocommerce'); */
            $orders_above = __('Only for orders above', 'eupago-gateway-for-woocommerce');
            $orders_description = __('Enable only for orders above x &euro; (exclusive). Leave blank (or zero) to allow for any order value.', 'eupago-gateway-for-woocommerce') . '  ' . __('Eupago allows Pix payments starting from 0.50 € (exclusive). You can use this option to set a minimum value.', 'eupago-gateway-for-woocommerce');
            $orders_below = __('Only for orders below', 'eupago-gateway-for-woocommerce');
            $orders_below_description = __('Enable only for orders above x &euro; (exclusive). Leave blank (or zero) to allow for any order value.', 'eupago-gateway-for-woocommerce') . '  ' . __('Typically, Pix payments do not have a fixed universal limit. You can use this option to set a maximum value.', 'eupago-gateway-for-woocommerce');
            $reduce_stock = __('Reduce stock', 'eupago-gateway-for-woocommerce');
            $choose_reduce_stock = __('Choose when to reduce stock.', 'eupago-gateway-for-woocommerce');
            $when_order_paid = __('when order is paid (requires active callback)', 'eupago-gateway-for-woocommerce');
            $when_order_placed = __('when order is placed (before payment)', 'eupago-gateway-for-woocommerce');
            $enable_pix = __('Enable EuroPix (using Eupago)', 'eupago-gateway-for-woocommerce');
            $payment_confirmation = esc_html__('SMS Payment Confirmation:', 'eupago-gateway-for-woocommerce');

            // Translate title based on the selected language
            if ($admin_language === 'pt_PT' || $admin_language === 'pt_BR') {
                $enable_disable_title = __('Ativar/Desativar', 'eupago-gateway-for-woocommerce');
                $enable_pix = __('Ativar EuroPix (usando Eupago)', 'eupago-gateway-for-woocommerce');
                $pix = __('EuroPix', 'eupago-gateway-for-woocommerce');
                $description = __('Descrição', 'eupago-gateway-for-woocommerce');
                $controls_checkout = __('Isto controla o título que o utilizador vê durante o processo de pagamento', 'eupago-gateway-for-woocommerce');
                $title_pix = __('Título', 'eupago-gateway-for-woocommerce');
                $description_checkout = __('Isto controla a descrição que o utilizador vê durante o pagamento.', 'eupago-gateway-for-woocommerce');
                $instructions_text = __('Instruções', 'eupago-gateway-for-woocommerce');
                $description_instructions_text = __('Instruções que serão adicionadas à página de agradecimento e ao e-mail enviado ao cliente.', 'eupago-gateway-for-woocommerce');
                $duplicate_payments_text = __('Pagamentos duplicados', 'eupago-gateway-for-woocommerce');
                $allow_duplicate_text = __('Permitir pagamentos duplicados.', 'eupago-gateway-for-woocommerce');
                $no_text = __('Não', 'eupago-gateway-for-woocommerce');
                $yes_text = __('Sim', 'eupago-gateway-for-woocommerce');
                $expire_date_text = __('Data de expiração', 'eupago-gateway-for-woocommerce');
                $number_days_expire = __('Número de dias para expiração do pagamento.', 'eupago-gateway-for-woocommerce');
                /* $only_portuguese_customers = __('Apenas para clientes portugueses?', 'eupago-gateway-for-woocommerce'); */
                /* $enable_only_portugal = __('Ativar apenas para clientes cujo endereço está em Portugal', 'eupago-gateway-for-woocommerce'); */
                $orders_above = __('Apenas para pedidos acima de', 'eupago-gateway-for-woocommerce');
                $orders_description = __('Ativar apenas para pedidos acima de x &euro; (exclusivo). Deixe em branco (ou zero) para permitir qualquer valor de pedido.', 'eupago-gateway-for-woocommerce') . '  ' . __('A Eupago permite pagamentos por EuroPix a partir de 0,50 € (exclusivo). Pode usar esta opção para definir um valor mínimo.', 'eupago-gateway-for-woocommerce');
                $orders_below = __('Apenas para pedidos abaixo de', 'eupago-gateway-for-woocommerce');
                $orders_below_description = __('Ativar apenas para pedidos acima de x &euro; (exclusivo). Deixe em branco (ou zero) para permitir qualquer valor de pedido.', 'eupago-gateway-for-woocommerce') . '  ' . __('Normalmente, os pagamentos por EuroPix não têm limite fixo universal. Pode usar esta opção para definir um valor máximo.', 'eupago-gateway-for-woocommerce');

                $reduce_stock = __('Reduzir stock', 'eupago-gateway-for-woocommerce');
                $choose_reduce_stock = __('Escolha quando reduzir o stock.', 'eupago-gateway-for-woocommerce');
                $when_order_paid = __('quando o pedido é pago (requer callback ativo)', 'eupago-gateway-for-woocommerce');
                $when_order_placed = __('quando o pedido é feito (antes do pagamento)', 'eupago-gateway-for-woocommerce');
                $payment_on_hold = esc_html__('Envio de SMS dos detalhes de pagamento:', 'eupago-gateway-for-woocommerce');
                $enable_text = 'Ativar';
                $payment_confirmation = esc_html__('Confirmação do pagamento por SMS:', 'eupago-gateway-for-woocommerce');
                $sms_order_confirmation = esc_html__('Confirmação de pedido por SMS:', 'eupago-gateway-for-woocommerce');
            } elseif ($admin_language === 'es_ES') {
                $enable_disable_title = __('Activar/Desactivar', 'eupago-gateway-for-woocommerce');
                $title_pix = __('Título', 'eupago-gateway-for-woocommerce');
                $enable_pix = __('Activar EuroPix (usando Eupago)', 'eupago-gateway-for-woocommerce');
                $pix = __('EuroPix', 'eupago-gateway-for-woocommerce');
                $description = __('Descripción', 'eupago-gateway-for-woocommerce');
                $controls_checkout = __('Esto controla el título que el usuario ve durante el proceso de pago', 'eupago-gateway-for-woocommerce');
                $instructions_text = __('Instrucciones', 'eupago-gateway-for-woocommerce');
                $description_instructions_text = __('Instrucciones que se añadirán a la página de agradecimiento y al correo electrónico enviado al cliente.', 'eupago-gateway-for-woocommerce');
                $duplicate_payments_text = __('Pagos duplicados', 'eupago-gateway-for-woocommerce');
                $description_checkout = __('Esto controla la descripción que ve el usuario durante el pago.', 'eupago-gateway-for-woocommerce');
                $allow_duplicate_text = __('Permitir pagos duplicados.', 'eupago-gateway-for-woocommerce');
                $no_text = __('No', 'eupago-gateway-for-woocommerce');
                $yes_text = __('Sí', 'eupago-gateway-for-woocommerce');
                $expire_date_text = __('Fecha de vencimiento', 'eupago-gateway-for-woocommerce');
                $number_days_expire = __('Número de días para que caduque el pago.', 'eupago-gateway-for-woocommerce');
                /* $only_portuguese_customers = __('¿Solo para clientes portugueses?', 'eupago-gateway-for-woocommerce'); */
                /* $enable_only_portugal = __('Habilitar solo para clientes cuya dirección esté en Portugal', 'eupago-gateway-for-woocommerce'); */
                $orders_above = __('Solo para pedidos superiores a', 'eupago-gateway-for-woocommerce');
                $orders_description = __('Activar solo para pedidos superiores a x &euro; (exclusivo). Deje en blanco (o cero) para permitir cualquier valor de pedido.', 'eupago-gateway-for-woocommerce') . '  ' . __('Eupago permite pagos por EuroPix a partir de 0,50 € (exclusivo). Puede usar esta opción para definir un valor mínimo.', 'eupago-gateway-for-woocommerce');
                $orders_below = __('Solo para pedidos inferiores a', 'eupago-gateway-for-woocommerce');
                $orders_below_description = __('Activar solo para pedidos superiores a x &euro; (exclusivo). Deje en blanco (o cero) para permitir cualquier valor de pedido.', 'eupago-gateway-for-woocommerce') . '  ' . __('Normalmente, los pagos por EuroPix no tienen un límite fijo universal. Puede usar esta opción para definir un valor máximo.', 'eupago-gateway-for-woocommerce');
                $reduce_stock = __('Reducir el stock', 'eupago-gateway-for-woocommerce');
                $choose_reduce_stock = __('Elegir cuándo reducir el stock.', 'eupago-gateway-for-woocommerce');
                $when_order_paid = __('cuando el pedido se paga (requiere callback activo)', 'eupago-gateway-for-woocommerce');
                $when_order_placed = __('cuando el pedido se realiza (antes del pago)', 'eupago-gateway-for-woocommerce');
                $payment_on_hold = esc_html__('Envío de SMS con los detalles de pago:', 'eupago-gateway-for-woocommerce');
                $enable_text = 'Habilitar';
                $payment_confirmation = esc_html__('Confirmación de pago SMS:', 'eupago-gateway-for-woocommerce');
                $sms_order_confirmation = esc_html__('Confirmación de pedido SMS:', 'eupago-gateway-for-woocommerce');
            }
        
            $this->form_fields = [
                'enabled' => [
                    'title' => esc_html($enable_disable_title),
                    'type' => 'checkbox',
                    'default' => 'no',
                    'label' => esc_html($enable_pix),
                ],
                'title' => [
                    'title' => esc_html($title_pix),
                    'type' => 'text',
                    'description' => esc_html($controls_checkout),
                    'default' => esc_html__($pix),
                ],
                'description' => [
                    'title' => esc_html($description),
                    'type' => 'textarea',
                    'description' => esc_html($description_checkout),
                ],
                'instructions' => [
                    'title' => esc_html($instructions_text),
                    'type' => 'textarea',
                    'description' => esc_html($description_instructions_text),
                ],
                /* 'only_portugal' => [
                    'title' => esc_html($only_portuguese_customers),
                    'type' => 'checkbox',
                    'label' => esc_html($enable_only_portugal),
                    'default' => 'no',
                ], */
                'only_above' => [
                    'title' => esc_html($orders_above),
                    'type' => 'number',
                    'description' => esc_html($orders_description),
                    'default' => '',
                ],
                'only_below' => [
                    'title' => esc_html($orders_below),
                    'type' => 'number',
                    'description' => esc_html($orders_below_description),
                    'default' => '',
                ],
                'stock_when' => [
                    'title' => esc_html($reduce_stock),
                    'type' => 'select',
                    'description' => esc_html($choose_reduce_stock),
                    'default' => '',
                    'options' => [
                        '' => esc_html($when_order_paid),
                        'order' => esc_html($when_order_placed),
                    ],
                ],
                'sms_payment_hold_pix' => [
                    'title' => esc_html($payment_on_hold),
                    'type' => 'checkbox',
                    'label' => esc_html($enable_text),
                    'default' => 'no',
                ],
                'sms_payment_confirmation_pix' => [
                    'title' => esc_html($payment_confirmation),
                    'type' => 'checkbox',
                    'label' => esc_html($enable_text),
                    'default' => 'no',
                ],
            ];
        }

        public function admin_options() {
            include 'views/html-admin-page.php';
        }

        /**
         * Icon HTML
         */
        public function get_icon() {
            $alt = (function_exists('icl_object_id') ? icl_t($this->id, $this->id . '_title', $this->title) : $this->title);
            $icon_html = '<img src="' . esc_attr($this->icon) . '" alt="' . esc_attr($alt) . '" />';

            return apply_filters('woocommerce_gateway_icon', $icon_html, $this->id);
        }

        public function check_order_errors($order_id)
        {
          $order = wc_get_order($order_id);
          $order_total = version_compare(WC_VERSION, '3.0', '>=') ? $order->get_total() : $order->order_total;

            // Store is not in Euros
            if (trim(get_woocommerce_currency()) != 'EUR') {
                return __('Configuration error. This store currency is not Euros (&euro;).', 'eupago-gateway-for-woocommerce');
            }

            // Invalid value for pix
            if (($order_total <= 0.5 || $order_total > 99999)) {
                return __('It\'s not possible to use EuroPix for this order value.', 'eupago-gateway-for-woocommerce');
            }

            return false;
        }

        /**
         * Thank you page message
         */
        public function thankyou_page($order_id)
        {
            $order = wc_get_order($order_id);
            $order_total = version_compare(WC_VERSION, '3.0', '>=') ? $order->get_total() : $order->order_total;
            $payment_method = version_compare(WC_VERSION, '3.0', '>=') ? $order->get_payment_method() : $order->payment_method;

            if ($payment_method == $this->id) {
                wc_get_template('payment-instructions.php', [
                    'method' => $payment_method,
                    'payment_name' => (function_exists('icl_object_id') ? icl_t($this->id, $this->id . '_title', $this->title) : $this->title),
                    'instructions' => isset($this->instructions) && !empty($this->instructions) ? $this->instructions : '',
                    'referencia' => $order->get_meta('_eupago_pix_referencia', true),
                    'order_total' => $order_total,
                    'pixImage' => $order->get_meta('_eupago_pix_pixImage', true),
                    'pixCode' => $order->get_meta('_eupago_pix_pixCode', true),
                ], 'woocommerce/eupago/', (new WC_Eupago())->get_templates_path());
            }
        }

        /**
         * View order detail payment reference
         */
        public function order_details_after_order_table($order)
        {
            if (is_wc_endpoint_url('view-order')) {
                $this->thankyou_page($order->get_id());
            }
        }

        /**
         * Email instructions
         */
        public function email_instructions($order, $sent_to_admin, $plain_text = false)
        {
            $order_id = version_compare(WC_VERSION, '3.0', '>=') ? $order->get_id() : $order->id;
            $order_total = version_compare(WC_VERSION, '3.0', '>=') ? $order->get_total() : $order->order_total;
            $payment_method = version_compare(WC_VERSION, '3.0', '>=') ? $order->get_payment_method() : $order->payment_method;

            if ($sent_to_admin || !$order->has_status('on-hold') || $this->id !== $payment_method) {
                return;
            }

            if ($plain_text) {
                wc_get_template('emails/plain-instructions.php', [
                    'method' => $payment_method,
                    'payment_name' => (function_exists('icl_object_id') ? icl_t($this->id, $this->id . '_title', $this->title) : $this->title),
                    'instructions' => isset($this->instructions) && !empty($this->instructions) ? $this->instructions : '',
                    'referencia' => $order->get_meta('_eupago_pix_referencia', true),
                    'pixImage' => $order->get_meta('_eupago_pix_pixImage', true),
                    'pixCode' => $order->get_meta('_eupago_pix_pixCode', true),
                    'order_total' => $order_total,
                ], 'woocommerce/eupago/', (new WC_Eupago())->get_templates_path());
            } else {
                wc_get_template('emails/html-instructions.php', [
                    'method' => $payment_method,
                    'payment_name' => (function_exists('icl_object_id') ? icl_t($this->id, $this->id . '_title', $this->title) : $this->title),
                    'instructions' => isset($this->instructions) && !empty($this->instructions) ? $this->instructions : '',
                    'referencia' => $order->get_meta('_eupago_pix_referencia', true),
                    'pixImage' => $order->get_meta('_eupago_pix_pixImage', true),
                    'pixCode' => $order->get_meta('_eupago_pix_pixCode', true),
                    'order_total' => $order_total,
                ], 'woocommerce/eupago/', (new WC_Eupago())->get_templates_path());
            }
        }

        public function process_payment($order_id) {
            global $woocommerce;
        
            $order = wc_get_order($order_id);
            $order_total = version_compare(WC_VERSION, '3.0', '>=') ? $order->get_total() : $order->order_total;
            $payment_method = version_compare(WC_VERSION, '3.0', '>=') ? $order->get_payment_method() : $order->payment_method;
        
            if ($error_message = $this->check_order_errors($order_id)) {
                wc_add_notice(__('Payment error:', 'eupago-gateway-for-woocommerce') . ' ' . $error_message, 'error');
                return [
                    'result' => 'failure',
                    'redirect' => '',
                ];
            }
        
            // Determine language
            $lang = $this->determine_language($order);
        
            // Make Eupago pix request
            $eupagopix = $this->client->getReferencePix($order_id, $order_total);
            $data = json_decode($eupagopix, true);
        
            if ($data['transactionStatus'] !== 'Success') {
                $error_message = __('Payment error:', 'eupago-gateway-for-woocommerce') . ' ' . $data['transactionStatus'];
                wc_add_notice($error_message, 'error');
                return [
                    'result' => 'failure',
                    'redirect' => '',
                ];
            }
        
            // Update order meta data
            $order->update_meta_data('_eupago_pix_referencia', $data['reference']);
            $order->update_meta_data('_eupago_pix_transactionID', $data['transactionID']);
            $order->update_meta_data('_eupago_pix_pixCode', $data['pixCode']);
            $order->update_meta_data('_eupago_pix_pixImage', $data['pixImage']);
            $order->save();
        
            // Mark as on-hold
            $order->update_status('on-hold', __('Awaiting EuroPix payment.', 'eupago-gateway-for-woocommerce'));
        
            // Reduce stock levels
            $this->reduce_stock_levels($order);
        
            // Empty cart and session
            $this->clear_cart_and_session();
        
            if (file_exists(plugin_dir_path(__FILE__) . 'hooks/hooks-sms.php') && $this->get_option('sms_payment_hold_pix') === 'yes') {
                include_once(plugin_dir_path(__FILE__) . 'hooks/hooks-sms.php');
                if (function_exists('send_sms')) {
                    send_sms($order_id);
                } else {
                    $this->callback_log('Função send_sms_prossessing não encontrada.');
                }
            }
        
            // Return thankyou redirect
            return [
                'result' => 'success',
                'redirect' => $this->get_return_url($order),
            ];
        }

        private function clear_cart_and_session() {
            global $woocommerce;
        
            // Remove cart
            $woocommerce->cart->empty_cart();
        
            // Empty awaiting payment session
            if (isset($_SESSION['order_awaiting_payment'])) {
                unset($_SESSION['order_awaiting_payment']);
            }
        }

        private function reduce_stock_levels($order) {
            if ($this->stock_when == 'order') {
                $order->reduce_order_stock();
            }
        }

        private function determine_language($order) {
            $lang = 'pt'; // Default to PT
        
            if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
                $browser_language = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
                $lang = substr($browser_language, 0, 2); 
            }
        
            return $lang;
        }

        /**
         * Just above/below certain amounts
         */
        public function disable_only_above_or_below($available_gateways)
        {
            global $woocommerce;

            if (isset($available_gateways[$this->id])) {
                if (@floatval($available_gateways[$this->id]->only_above) > 0) {
                    if ($woocommerce->cart->total < floatval($available_gateways[$this->id]->only_above)) {
                        unset($available_gateways[$this->id]);
                    }
                }

                if (@floatval($available_gateways[$this->id]->only_below) > 0) {
                    if ($woocommerce->cart->total > floatval($available_gateways[$this->id]->only_below)) {
                        unset($available_gateways[$this->id]);
                    }
                }
            }

            return $available_gateways;
        }

        public function payment_complete($order, $txn_id = '', $note = '')
        {
            $order->add_order_note($note);
            $order->payment_complete($txn_id);
        }

        /* Reduce stock on 'wc_maybe_reduce_stock_levels'? */
        public function woocommerce_payment_complete_reduce_order_stock($bool, $order_id)
        {
            $order = wc_get_order($order_id);

            if ($order->get_payment_method() == $this->id) {
                return (new WC_Eupago())->woocommerce_payment_complete_reduce_order_stock($bool, $order, $this->id, $this->stock_when);
            } else {
                return $bool;
            }
        }

        /* public function disable_unless_portugal($available_gateways)
        {
            if (!is_admin()) {
                if (isset(WC()->customer)) {
                    $country = version_compare(WC_VERSION, '3.0', '>=') ? WC()->customer->get_billing_country() : WC()->customer->get_country();

                    if (isset($available_gateways[$this->id])) {
                        if ($available_gateways[$this->id]->only_portugal == 'yes' && trim($country) != 'PT') {
                            unset($available_gateways[$this->id]);
                        }
                    }
                }
            }

            return $available_gateways;
        } */

    } // WC_Eupago_Pix
} // class_exists()