<?php

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

/*
 * Eupago - Credit Card
 *
 * @since 1.1
 */
if (!class_exists('WC_Eupago_CC')) {
    class WC_Eupago_CC extends WC_Payment_Gateway
    {
        /**
         * Constructor for your payment class
         *
         * @access public
         *
         * @return void
         */
        protected $instructions;
        protected $only_portugal;
        protected $only_above;
        protected $only_below;
        protected $stock_when;
        protected $sms_payment_hold_cc;
        protected $sms_payment_confirmation_cc;
        protected $sms_order_confirmation_cc;
        protected $client;
        public function __construct()
        {
            global $woocommerce;
            $this->id = 'eupago_cc';

            $this->icon = plugins_url('assets/images/cc_icon.jpg', dirname(__FILE__));
            $this->has_fields = false;
            $this->method_title = __('Credit Card (Eupago)', 'eupago-gateway-for-woocommerce');

            // Plugin options and settings
            $this->init_form_fields();
            $this->init_settings();

            // User settings
            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');
            $this->instructions = $this->get_option('instructions');
            $this->only_portugal = $this->get_option('only_portugal');
            $this->only_above = $this->get_option('only_above');
            $this->only_below = $this->get_option('only_below');
            $this->stock_when = $this->get_option('stock_when');
            $this->sms_payment_hold_cc = $this->get_option('sms_payment_hold_cc');
            $this->sms_payment_confirmation_cc = $this->get_option('sms_payment_confirmation_cc');
            $this->sms_order_confirmation_cc = $this->get_option('sms_order_confirmation_cc');

            // Set the API.
            $this->client = new WC_Eupago_API($this);

            // Actions and filters
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
            //add_action('woocommerce_order_status_pending', array($this, 'send_sms_pending_cc'));
            //add_action('woocommerce_order_status_on-hold', array($this, 'send_sms_pending_cc'));
            //add_action('woocommerce_order_status_processing' , array($this, 'send_sms_processing_cc'));
            //add_action('woocommerce_order_status_completed', array($this, 'send_sms_completed_cc'));

            if (function_exists('icl_object_id') && function_exists('icl_register_string')) {
                add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'register_wpml_strings']);
            }
            add_action('woocommerce_thankyou_' . $this->id, [$this, 'thankyou_page']);
            add_action('woocommerce_order_details_after_order_table', [$this, 'order_details_after_order_table'], 20);

            add_filter('woocommerce_available_payment_gateways', [$this, 'disable_unless_portugal']);
            add_filter('woocommerce_available_payment_gateways', [$this, 'disable_only_above_or_below']);

            // Customer Emails
            add_action('woocommerce_email_before_order_table', [$this, 'email_instructions'], 10, 2);

            // Filter to decide if payment_complete reduces stock, or not
            add_filter('woocommerce_payment_complete_reduce_order_stock', [$this, 'woocommerce_payment_complete_reduce_order_stock'], 10, 2);
        }

        /**
         * WPML compatibility
         */
        public function register_wpml_strings()
        {
            // These are already registered by WooCommerce Multilingual
            /* $to_register=array('title','description',); */
            $to_register = [];

            foreach ($to_register as $string) {
                icl_register_string($this->id, $this->id . '_' . $string, $this->settings[$string]);
            }
        }


        /**
         * Initialize form fields for the Eupago Gateway - Credit Card settings in WooCommerce.
         *
         * This method sets up the form fields that will be displayed in the WooCommerce admin settings of Credit Card page
         * for the Eupago Gateway plugin.
         *
         */
        public function init_form_fields()
        {
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


            $texto_enable = esc_html__('Enable', 'eupago-gateway-for-woocommerce');
            $payment_on_hold = __('SMS Payment On Hold:', 'eupago-gateway-for-woocommerce');
            $enable_disable_title = __('Enable/Disable', 'eupago-gateway-for-woocommerce');
            $title_credit_card = __('Title','eupago-gateway-for-woocommerce');
            $enable_credit_card = __('Enable Credit Card (using Eupago)','eupago-gateway-for-woocommerce');
            $controls_checkout = __('This controls the title the user sees during the checkout process','eupago-gateway-for-woocommerce');
            $credit_card = __('Credit Card','eupago-gateway-for-woocommerce');
            $description = __('Description','eupago-gateway-for-woocommerce');
            $description_checkout = __('This controls the description the user sees during checkout.','eupago-gateway-for-woocommerce');
            $pay_with_card = __('Pay with Card','eupago-gateway-for-woocommerce');
            $logo = __('Logo','eupago-gateway-for-woocommerce');
            $shop_logo = __('Store Logo for the checkout page','eupago-gateway-for-woocommerce');
            $payment_confirmation = esc_html__('SMS Payment Confirmation:', 'eupago-gateway-for-woocommerce');
            $sms_order_confirmation = esc_html('SMS Order Confirmation:', 'eupago-gateway-for-woocommerce');
            //Fim de Adição
            $instructions_text = __('Instructions', 'eupago-gateway-for-woocommerce');
            $description_instructions_text = __('Instructions to be added to the thank you page and the email sent to the customer.', 'eupago-gateway-for-woocommerce');
            $duplicated_payments_text = __('Duplicated Payments', 'eupago-gateway-for-woocommerce');
            $allow_duplicated_text = __('Allow duplicated payments.', 'eupago-gateway-for-woocommerce');
            $no_text = __('No', 'eupago-gateway-for-woocommerce');
            $yes_text = __('Yes', 'eupago-gateway-for-woocommerce');
            $data_expirada_text = __('Expiration Date', 'eupago-gateway-for-woocommerce');
            $numero_dias_expirado = __('Number of days for the payment to expire.', 'eupago-gateway-for-woocommerce');
            $apenas_portugueses = __('Only for Portuguese customers?', 'eupago-gateway-for-woocommerce');
            $endereço_português = __('Enable only for customers whose address is in Portugal', 'eupago-gateway-for-woocommerce');
            $orders_acima = __('Only for orders above', 'eupago-gateway-for-woocommerce');
            $orders_description = __('Enable only for orders above x &euro; (exclusive). Leave blank (or zero) to allow any order value.', 'eupago-gateway-for-woocommerce') . ' ' . __('By design, Multibanco only allows payments from 1 to 999999 &euro; (inclusive). You can use this option to further limit this range.', 'eupago-gateway-for-woocommerce');
            $orders_abaixo = __('Only for orders below', 'eupago-gateway-for-woocommerce');
            $orders_abaixo_description = __('Enable only for orders below x &euro; (exclusive). Leave blank (or zero) to allow any order value.', 'eupago-gateway-for-woocommerce') . '' . __('By design, Multibanco only allows payments from 1 to 999999 &euro; (inclusive). You can use this option to further limit this range.', 'eupago-gateway-for-woocommerce');
            $reduzir_stock = __('Reduce Stock', 'eupago-gateway-for-woocommerce');
            $escolher_reduzir_stock = __('Choose when to reduce stock.', 'eupago-gateway-for-woocommerce');
            $quando_order_paga = __('when the order is paid (requires active callback)', 'eupago-gateway-for-woocommerce');
            $quando_order_colocada = __('when the order is placed (before payment)', 'eupago-gateway-for-woocommerce');

            // Translate title based on the selected language
            if ($admin_language === 'pt_PT' || $admin_language === 'pt_BR') {
                $enable_disable_title = __('Ativar/Desativar', 'eupago-gateway-for-woocommerce');
                //Início de Adição
                $title_credit_card = __('Título','eupago-gateway-for-woocommerce');
                $enable_credit_card = __('Habilitar Cartão de Crédito (usando Eupago)','eupago-gateway-for-woocommerce');
                $controls_checkout = __('Isto controla o título que o utilizador vê durante o processo de pagamento','eupago-gateway-for-woocommerce');
                $credit_card = __('Cartão de Crédito','eupago-gateway-for-woocommerce');
                $description = __('Descrição','eupago-gateway-for-woocommerce');
                $description_checkout = __('Isto controla a descrição que o utilizador vê durante o pagamento.','eupago-gateway-for-woocommerce');
                $pay_with_card = __('Pagar com Cartão','eupago-gateway-for-woocommerce');
                $logo = __('Logo','eupago-gateway-for-woocommerce');
                $shop_logo = __('Logotipo da loja para a página de pagamento','eupago-gateway-for-woocommerce');
                $texto_enable = 'Ativar';
                $payment_confirmation = esc_html__('Confirmação do pagamento por SMS:', 'eupago-gateway-for-woocommerce');
                //Fim de adição
                $instructions_text = __('Instruções', 'eupago-gateway-for-woocommerce');
                $description_instructions_text = __('Instruções que serão adicionadas à página de agradecimento e ao e-mail enviado ao cliente.', 'eupago-gateway-for-woocommerce');
                $duplicated_payments_text = __('Pagamentos duplicados', 'eupago-gateway-for-woocommerce');
                $allow_duplicated_text = __('Permitir pagamentos duplicados.', 'eupago-gateway-for-woocommerce');
                $no_text = __('Não', 'eupago-gateway-for-woocommerce');
                $yes_text = __('Sim', 'eupago-gateway-for-woocommerce');
                $data_expirada_text = __('Data de validade', 'eupago-gateway-for-woocommerce');
                $numero_dias_expirado = __('Número de dias para que o pagamento expire.', 'eupago-gateway-for-woocommerce');
                $apenas_portugueses = __('Apenas para clientes portugueses?', 'eupago-gateway-for-woocommerce');
                $endereço_português = __('Ativar apenas para clientes cuja morada esteja em Portugal', 'eupago-gateway-for-woocommerce');
                $orders_acima = __('Apenas para encomendas superiores a', 'eupago-gateway-for-woocommerce');
                $orders_description = __('Ativar apenas para encomendas superiores a x &euro; (exclusivo). Deixe em branco (ou zero) para permitir qualquer valor de encomenda.', 'eupago-gateway-for-woocommerce') . ' ' . __('Por design, Multibanco só permite pagamentos de 1 a 999999 &euro; (inclusive). Pode usar esta opção para limitar ainda mais este intervalo.', 'eupago-gateway-for-woocommerce');
                $orders_abaixo = __('Apenas para encomendas inferiores a', 'eupago-gateway-for-woocommerce');
                $orders_abaixo_description = __('Ativar apenas para encomendas inferiores a x &euro; (exclusivo). Deixe em branco (ou zero) para permitir qualquer valor de encomenda.', 'eupago-gateway-for-woocommerce') . '  ' . __('Por design, Multibanco só permite pagamentos de 1 a 999999 &euro; (inclusive). Pode usar esta opção para limitar ainda mais este intervalo.', 'eupago-gateway-for-woocommerce');
                $reduzir_stock = __('Reduzir o stock', 'eupago-gateway-for-woocommerce');
                $escolher_reduzir_stock = __('Escolher quando reduzir o stock.', 'eupago-gateway-for-woocommerce');
                $quando_order_paga = __('quando a encomenda é paga (requer callback ativo)', 'eupago-gateway-for-woocommerce');
                $quando_order_colocada = __('quando a encomenda é colocada (antes do pagamento)', 'eupago-gateway-for-woocommerce');
                $payment_on_hold = esc_html__('Confirmação SMS dos detalhes de Pagamento:', 'eupago-gateway-for-woocommerce');
                $sms_order_confirmation = esc_html__('Confirmação de Pedido por SMS:', 'eupago-gateway-for-woocommerce');
            } elseif ($admin_language === 'es_ES') {
                $enable_disable_title = __('Activar/Desactivar', 'eupago-gateway-for-woocommerce');
                //Início de Adição
                $title_credit_card = __('Título','eupago-gateway-for-woocommerce');
                $enable_credit_card = __('Habilitar Tarjeta de Crédito (usando Eupago)','eupago-gateway-for-woocommerce');
                $controls_checkout = __('Esto controla el título que el usuario ve durante el proceso de pago','eupago-gateway-for-woocommerce');
                $credit_card = __('Tarjeta de Crédito','eupago-gateway-for-woocommerce');
                $description = __('Descripción','eupago-gateway-for-woocommerce');
                $description_checkout = __('Esto controla la descripción que ve el usuario durante el pago.','eupago-gateway-for-woocommerce');
                $logo = __('Logo','eupago-gateway-for-woocommerce');
                $shop_logo = __('Logotipo de la tienda para la página de pago','eupago-gateway-for-woocommerce');
                //Fim de adição
                $instructions_text = __('Instrucciones', 'eupago-gateway-for-woocommerce');
                $description_instructions_text = __('Instrucciones que se añadirán a la página de agradecimiento y al correo electrónico enviado al cliente.', 'eupago-gateway-for-woocommerce');
                $duplicated_payments_text = __('Pagos duplicados', 'eupago-gateway-for-woocommerce');
                $allow_duplicated_text = __('Permitir pagos duplicados.', 'eupago-gateway-for-woocommerce');
                $no_text = __('No', 'eupago-gateway-for-woocommerce');
                $yes_text = __('Sí', 'eupago-gateway-for-woocommerce');
                $data_expirada_text = __('Fecha de vencimiento', 'eupago-gateway-for-woocommerce');
                $numero_dias_expirado = __('Número de días para que caduque el pago.', 'eupago-gateway-for-woocommerce');
                $apenas_portugueses = __('¿Solo para clientes portugueses?', 'eupago-gateway-for-woocommerce');
                $endereço_português = __('Habilitar solo para clientes cuya dirección esté en Portugal', 'eupago-gateway-for-woocommerce');
                $orders_acima = __('Solo para pedidos superiores a', 'eupago-gateway-for-woocommerce');
                $orders_description = __('Activar solo para pedidos superiores a x &euro; (exclusivo). Deje en blanco (o cero) para permitir cualquier valor de pedido.', 'eupago-gateway-for-woocommerce') . ' <br/> ' . __('Por diseño, Multibanco solo permite pagos de 1 a 999999 &euro; (inclusive). Puede usar esta opción para limitar aún más este rango.', 'eupago-gateway-for-woocommerce');
                $orders_abaixo = __('Solo para pedidos inferiores a', 'eupago-gateway-for-woocommerce');
                $orders_abaixo_description = __('Activar solo para pedidos inferiores a x &euro; (exclusivo). Deje en blanco (o cero) para permitir cualquier valor de pedido.', 'eupago-gateway-for-woocommerce') . ' <br/> ' . __('Por diseño, Multibanco solo permite pagos de 1 a 999999 &euro; (inclusive). Puede usar esta opción para limitar aún más este rango.', 'eupago-gateway-for-woocommerce');
                $reduzir_stock = __('Reducir el stock', 'eupago-gateway-for-woocommerce');
                $escolher_reduzir_stock = __('Elegir cuándo reducir el stock.', 'eupago-gateway-for-woocommerce');
                $quando_order_paga = __('cuando el pedido se paga (requiere callback activo)', 'eupago-gateway-for-woocommerce');
                $quando_order_colocada = __('cuando el pedido se realiza (antes del pago)', 'eupago-gateway-for-woocommerce');
                $payment_on_hold = esc_html__('Pago SMS en espera:', 'eupago-gateway-for-woocommerce');
                $texto_enable = 'Habilitar';
                $payment_confirmation = esc_html__('Confirmación de pago SMS:', 'eupago-gateway-for-woocommerce');
                $sms_order_confirmation = esc_html__('Confirmación de pedido SMS:', 'eupago-gateway-for-woocommerce');
            }


            $this->form_fields = [
                'enabled' => [
                    'title' => esc_html($enable_disable_title),
                    'type' => 'checkbox',
                    'label' => esc_html($enable_credit_card),
                    'default' => 'no',
                ],
                'title' => [
                    'title' => esc_html($title_credit_card),
                    'type' => 'text',
                    'description' => esc_html($controls_checkout),
                    'default' => esc_html__($credit_card),
                ],
                'description' => [
                    'title' => esc_html($description),
                    'type' => 'textarea',
                    'description' => esc_html($description_checkout),
                ],
                'logo_url' => [
                    'title' => esc_html($logo),
                    'type' => 'text',
                    'description' => esc_html($shop_logo),
                    'default' => '',
                ],
                'instructions' => [
                    'title' => esc_html($instructions_text),
                    'type' => 'textarea',
                    'description' => esc_html($description_instructions_text),
                ],
                'only_portugal' => [
                    'title' => esc_html($apenas_portugueses),
                    'type' => 'checkbox',
                    'label' => esc_html($endereço_português),
                    'default' => 'no',
                ],
                'only_above' => [
                    'title' => esc_html($orders_acima),
                    'type' => 'number',
                    'description' => esc_html($orders_description),
                    'default' => 0,
                ],
                'only_below' => [
                    'title' => esc_html($orders_abaixo),
                    'type' => 'number',
                    'description' => esc_html($orders_abaixo_description),
                    'default' => 0,
                ],
                'stock_when' => [
                    'title' => esc_html ($reduzir_stock),
                    'type' => 'select',
                    'description' =>esc_html ($escolher_reduzir_stock),
                    'default' => '',
                    'options' => [
                      '' => esc_html($quando_order_paga),
                      'order' => esc_html($quando_order_colocada),
                    ],
                ],
                'sms_payment_hold_cc' => [
                    'title' => esc_html($payment_on_hold),
                    'type' => 'checkbox',
                    'label' => esc_html($texto_enable),
                    'default' => 'no',
                  ],
                'sms_payment_confirmation_cc' => [
                    'title' => esc_html($payment_confirmation),
                    'type' => 'checkbox',
                    'label' => esc_html($texto_enable),
                    'default' => 'no',
                ],
            ];
        }

        public function admin_options()
        {
            include 'views/html-admin-page.php';
        }

        /**
         * Icon HTML
         */
        public function get_icon()
        {
            $alt = (function_exists('icl_object_id') ? icl_t($this->id, $this->id . '_title', $this->title) : $this->title);
            $icon_html = '<img src="' . esc_attr($this->icon) . '" alt="' . esc_attr($alt) . '" />';

            return apply_filters('woocommerce_gateway_icon', $icon_html, $this->id);
        }

        /**
         * Thank You page message.
         *
         * @param int $order_id Order ID.
         *
         * @return string
         */
        public function thankyou_page($order_id)
        {
            $order = wc_get_order($order_id);

            $order_total = version_compare(WC_VERSION, '3.0', '>=') ? $order->get_total() : $order->order_total;
            $payment_method = version_compare(WC_VERSION, '3.0', '>=') ? $order->get_payment_method() : $order->payment_method;

            if ($payment_method == $this->id) {
                $referencia = $order->get_meta('eupago_cc_referencia', true);

                wc_get_template(
                    'payment-instructions.php',
                    [
                        'method' => $payment_method,
                        'payment_name' => (function_exists('icl_object_id') ? icl_t($this->id, $this->id . '_title', $this->title) : $this->title),
                        'referencia' => $referencia,
                        'order_total' => $order_total,
                    ],
                    'woocommerce/eupago/',
                    (new WC_Eupago())->get_templates_path()
                );
            }
        }

        /**
         *
         * View Order detail payment reference.
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
                    'referencia' => $order->get_meta('_eupago_cc_referencia', true),
                    'order_total' => $order_total,
                ], 'woocommerce/eupago/', (new WC_Eupago())->get_templates_path());
            } else {
                wc_get_template('emails/html-instructions.php', [
                    'method' => $payment_method,
                    'payment_name' => (function_exists('icl_object_id') ? icl_t($this->id, $this->id . '_title', $this->title) : $this->title),
                    'instructions' => isset($this->instructions) && !empty($this->instructions) ? $this->instructions : '',
                    'referencia' => $order->get_meta('_eupago_cc_referencia', true),
                    'order_total' => $order_total,
                ], 'woocommerce/eupago/', (new WC_Eupago())->get_templates_path());
            }
        }

        public function check_order_errors($order_id)
        {
            $order = wc_get_order($order_id);
            $order_total = version_compare(WC_VERSION, '3.0', '>=') ? $order->get_total() : $order->order_total;

            // A loja não está em Euros
            if (trim(get_woocommerce_currency()) != 'EUR') {
                return __('Configuration error. This store currency is not Euros (&euro;).', 'eupago-gateway-for-woocommerce');
            }

            // O valor da encomenda não é aceite
            if (($order_total <= 2) || ($order_total >= 1000000)) {
                return __('It\'s not possible to use credit card to pay values under 2&euro; or above 999999&euro;.', 'eupago-gateway-for-woocommerce');
            }

            return false;
        }

        /**
         * Process the Eupago payment for the given order.
         *
         * @param int $order_id The order ID.
         */
        public function process_payment($order_id)
        {
            global $woocommerce;

            $order = wc_get_order($order_id);
            $order_total = version_compare(WC_VERSION, '3.0', '>=') ? $order->get_total() : $order->order_total;
            $payment_method = version_compare(WC_VERSION, '3.0', '>=') ? $order->get_payment_method() : $order->payment_method;
            
            if ($error_message = $this->check_order_errors($order_id)) {
                wc_add_notice(__('Payment error:', 'eupago-gateway-for-woocommerce') . ' ' . $error_message, 'error');
                return;
            }

            // Determine language
            $lang = $this->determine_language($order);

            // Make Eupago CC request
            $eupagoCC = $this->client->pedidoCC($order, $order_total, $this->get_option('logo_url'), $this->get_return_url($order), $lang, $this->get_comment_table($order, $order_total));
            if (is_string($eupagoCC)) {
                $data = json_decode($eupagoCC, true);
                if ($data !== null) {
                    $redirect_url = $data['redirectUrl'];
                } else {
                    echo 'Erro ao decodificar JSON';
                }
            } else {
                $redirect_url = $eupagoCC->redirectUrl;
            }

            if ($eupagoCC->estado != 0) {
                $error_message = $eupagoCC->resposta;
                wc_add_notice(__('Payment error:', 'eupago-gateway-for-woocommerce') . ' ' . $error_message, 'error');
                return;
            }

            // Update order meta data
            $order->update_meta_data('_eupago_cc_referencia', $eupagoCC->referencia);
            $order->save();

        
            // Mark as on-hold
            $order->update_status('pending', __('Awaiting Credit Card payment.', 'eupago-gateway-for-woocommerce'));

            // Reduce stock levels
            $this->reduce_stock_levels($order);

            // Empty cart and session
            $this->clear_cart_and_session();

            
            if (file_exists(plugin_dir_path(__FILE__) . 'hooks/hooks-sms.php') && $this->get_option('sms_payment_hold_cc') === 'yes') {
                include_once(plugin_dir_path(__FILE__) . 'hooks/hooks-sms.php');
                if (function_exists('send_sms_cc')) {
                    send_sms_cc($order_id);
                } else {
                    $this->callback_log('Função send_sms_prossessing não encontrada.');
                }
            }
            
            // Return thankyou redirect
            return [
                'result' => 'success',
                'redirect' => $eupagoCC->url,
            ];
        }

        /**
         * Determine the language for the Eupago request.
         *
         * @param WC_Order $order The WooCommerce order.
         * @return string The language code.
         */
        private function determine_language($order)
        {
            $lang = 'pt'; // Default to PT

            if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
                $browser_language = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
                $lang = substr($browser_language, 0, 2); 
            }

            return $lang;
        }

        /**
         * Reduce stock levels based on configuration.
         *
         * @param WC_Order $order The WooCommerce order.
         */
        private function reduce_stock_levels($order)
        {
            if ($this->stock_when == 'order') {
                $order->reduce_order_stock();
            }
        }

        /**
         * Clear cart and session after successful payment.
         */
        private function clear_cart_and_session()
        {
            global $woocommerce;

            // Remove cart
            $woocommerce->cart->empty_cart();

            // Empty awaiting payment session
            if (isset($_SESSION['order_awaiting_payment'])) {
                unset($_SESSION['order_awaiting_payment']);
            }

            if (isset($eupagoCC->url) && !empty($eupagoCC->url)) {
                return [
                    'result' => 'success',
                    'redirect' => $eupagoCC->url,
                ];
            }else{
                return [
                    'result' => 'success',
                    'redirect' => $redirect_url,
                ];
                
            }
        }

        public function get_comment_table($order, $order_total)
        {
            $products = $order->get_items();

            $total_produtos = 0;
            $comentario = '<ul style=\'margin:0; padding:0; font-size:0.75em; color:#333; \'>';

            foreach ($products as $product) {
                $total_produtos += $product['line_total'];
                $comentario .= '<li style=\'list-style: none;\'><span style=\'margin:0; font-size:9px; margin-bottom:5px; padding:0;\' class=\'large-7 columns left\'>' . $product['name'] . '</span><span style=\'margin:0; padding:0; text-align:center;\' class=\'large-2 columns\'>x ' . $product['qty'] . '</span><span style=\'margin:0; padding:0; text-align:right\' class=\'large-3 columns right\'>' . $product['line_total'] . ' €</span></li>';
            }
            $envio_e_taxas = ($order_total - $total_produtos);
            $comentario .= '<li style=\'list-style: none; padding-top: 5px; border-top: 1px solid #ddd; display: inline-block; font-size:9px; width: 100%;\'><span style=\'margin:0; padding:0;\' class=\'large-7 columns left\'>Envio e taxas:</span><span style=\'margin:0; padding:0; text-align:center;\' class=\'large-2 columns\'></span><span style=\'margin:0; padding:0; text-align:right\' class=\'large-3 columns right\'>' . $envio_e_taxas . ' €</span></li></ul>';

            return $comentario;
        }

        /**
         * Just for Portugal
         */
        public function disable_unless_portugal($available_gateways)
        {
            if (!is_admin()) {
                if (isset($available_gateways[$this->id])) {
                    if (isset(WC()->customer)) {
                        $country = version_compare(WC_VERSION, '3.0', '>=') ? WC()->customer->get_billing_country() : WC()->customer->get_country();

                        if ($available_gateways[$this->id]->only_portugal == 'yes' && trim($country) != 'PT') {
                            unset($available_gateways[$this->id]);
                        }
                    }
                }
            }

            return $available_gateways;
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
    } // WC_Eupago_CC
} // class_exists()
