<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WC_Eupago_GooglePay')) {
    class WC_Eupago_GooglePay extends WC_Payment_Gateway {
        
        protected $instructions;
        protected $only_portugal;
        protected $only_above;
        protected $only_below;
        protected $stock_when;
        protected $sms_payment_hold_googlepay;
        protected $sms_payment_confirmation_googlepay;
        protected $sms_order_confirmation_googlepay;
        protected $client;
        
        public function __construct() {
            global $woocommerce;
            
            $this->id = 'eupago_googlepay';
            $this->icon = plugins_url('assets/images/googlepay_icon.png', dirname(__FILE__));
            $this->has_fields = false;
            $this->method_title = __('Google Pay (Eupago)', 'eupago-gateway-for-woocommerce');
            
            // Plugin settings
            $this->init_form_fields();
            $this->init_settings();
            
            // Loaded settings
            $this->title            = $this->get_option('title');
            $this->description      = $this->get_option('description');
            $this->instructions     = $this->get_option('instructions');
            $this->only_portugal    = $this->get_option('only_portugal');
            $this->only_above       = $this->get_option('only_above');
            $this->only_below       = $this->get_option('only_below');
            $this->stock_when       = $this->get_option('stock_when');
            $this->sms_payment_hold_googlepay = $this->get_option('sms_payment_hold_googlepay');
            $this->sms_payment_confirmation_googlepay = $this->get_option('sms_payment_confirmation_googlepay');
            $this->sms_order_confirmation_googlepay = $this->get_option('sms_order_confirmation_googlepay');
            
            // Instantiate API
            $this->client = new WC_Eupago_API($this);
            
            // Admin hooks
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
            
            // Frontend display hooks
            add_action('woocommerce_thankyou_' . $this->id, [ $this, 'thankyou_page' ]);
            add_action('woocommerce_order_details_after_order_table', [ $this, 'order_details_after_order_table' ], 20);
            add_filter('woocommerce_available_payment_gateways', [ $this, 'disable_unless_portugal' ]);
            add_filter('woocommerce_available_payment_gateways', [ $this, 'disable_only_above_or_below' ]);
            add_filter('woocommerce_payment_complete_reduce_order_stock', [ $this, 'woocommerce_payment_complete_reduce_order_stock' ], 10, 2);
            add_action('woocommerce_order_status_pending', [ $this, 'send_sms_pending_googlepay' ]);
            add_action('woocommerce_order_status_on-hold', [ $this, 'send_sms_pending_googlepay' ]);
            add_action('woocommerce_order_status_processing', [ $this, 'send_sms_processing_googlepay' ]);
            add_action('woocommerce_order_status_completed', [ $this, 'send_sms_completed_googlepay' ]);
            add_action('woocommerce_email_before_order_table', [ $this, 'email_instructions' ], 10, 2);
            
            // Emails
            add_action('woocommerce_email_before_order_table', [ $this, 'email_instructions' ], 10, 2);
        }
        
        public function init_form_fields()
        {
            $admin_language = get_locale();
            
            $language_options = [
                'default' => __('Default', 'eupago-gateway-for-woocommerce'),
                'pt'      => __('Portuguese', 'eupago-gateway-for-woocommerce'),
                'en'      => __('English', 'eupago-gateway-for-woocommerce'),
                'es'      => __('Spanish', 'eupago-gateway-for-woocommerce'),
            ];
            
            // Override language names in native language
            if ($admin_language === 'pt_PT' || $admin_language === 'pt_BR') {
                $language_options['default'] = __('Por padrão', 'eupago-gateway-for-woocommerce');
                $language_options['pt'] = __('Português', 'eupago-gateway-for-woocommerce');
                $language_options['en'] = __('Inglês', 'eupago-gateway-for-woocommerce');
                $language_options['es'] = __('Espanhol', 'eupago-gateway-for-woocommerce');
            } elseif ($admin_language === 'es_ES') {
                $language_options['default'] = __('Por defecto', 'eupago-gateway-for-woocommerce');
                $language_options['pt'] = __('Portugués', 'eupago-gateway-for-woocommerce');
                $language_options['en'] = __('Inglés', 'eupago-gateway-for-woocommerce');
                $language_options['es'] = __('Español', 'eupago-gateway-for-woocommerce');
            }
            
            // Default (English) labels
            $enable_disable_title = __('Enable/Disable', 'eupago-gateway-for-woocommerce');
            $payment_on_hold = __('Send SMS with payment details:', 'eupago-gateway-for-woocommerce');
            $enable_googlepay = __('Enable Google Pay', 'eupago-gateway-for-woocommerce');
            $title_googlepay = __('Title', 'eupago-gateway-for-woocommerce');
            $checkout_title = __('Use this field to define the title shown during checkout.', 'eupago-gateway-for-woocommerce');
            $googlepay_label = __('Google Pay', 'eupago-gateway-for-woocommerce');
            $description = __('Description', 'eupago-gateway-for-woocommerce');
            $description_checkout = __('Use this field to define the description shown during checkout.', 'eupago-gateway-for-woocommerce');
            $logo = __('Logo', 'eupago-gateway-for-woocommerce');
            $shop_logo = __('Store logo to display on the Eupago hosted payment form.', 'eupago-gateway-for-woocommerce');
            $only_portuguese = __('Only for Portuguese customers?', 'eupago-gateway-for-woocommerce');
            $address_portuguese = __('Enable only for customers with addresses in Portugal.', 'eupago-gateway-for-woocommerce');
            $orders_above = __('Only for orders above', 'eupago-gateway-for-woocommerce');
            $orders_below = __('Only for orders below', 'eupago-gateway-for-woocommerce');
            $orders_description = __('Activate only for orders over X € (exclusive). Leave blank or zero to allow any value.', 'eupago-gateway-for-woocommerce');
            $reduce_stock = __('Reduce stock', 'eupago-gateway-for-woocommerce');
            $stock_hint = __('Choose when to reduce stock levels.', 'eupago-gateway-for-woocommerce');
            $when_paid = __('when order is paid (requires callback)', 'eupago-gateway-for-woocommerce');
            $when_created = __('when order is placed (before payment)', 'eupago-gateway-for-woocommerce');
            $sms_confirmation = __('Payment Confirmation by SMS', 'eupago-gateway-for-woocommerce');
            $sms_order_confirmation = __('SMS Order Confirmation:', 'eupago-gateway-for-woocommerce');
            $enable_text = __('Enable', 'eupago-gateway-for-woocommerce');
            $instructions_text = __('Instructions', 'eupago-gateway-for-woocommerce');
            $description_instructions_text = __('Use this field to enter instructions that will be added to the order confirmation page and in the email sent to the customer.', 'eupago-gateway-for-woocommerce');


            // Portuguese overrides
            if ($admin_language === 'pt_PT' || $admin_language === 'pt_BR') {
                $enable_disable_title = __('Ativar/Desativar', 'eupago-gateway-for-woocommerce');
                $enable_googlepay = __('Ativar Google Pay', 'eupago-gateway-for-woocommerce');
                $title_googlepay = __('Título', 'eupago-gateway-for-woocommerce');
                $checkout_title = __('Utilize este campo para definir o título que o utilizador vê durante o processo de pagamento.', 'eupago-gateway-for-woocommerce');
                $googlepay_label = __('Google Pay', 'eupago-gateway-for-woocommerce');
                $description = __('Descrição','eupago-gateway-for-woocommerce');
                $description_checkout = __('Utilize este campo para definir a descrição que o utilizador vê durante o processo de pagamento.','eupago-gateway-for-woocommerce');
                $instructions_text = __('Instruções', 'eupago-gateway-for-woocommerce');
                $description_instructions_text = __('Utilize este campo para inserir as instruções que serão adicionadas na página de confirmação de encomenda e no email enviado ao cliente.', 'eupago-gateway-for-woocommerce');
                
                $logo = __('Logo','eupago-gateway-for-woocommerce');
                $shop_logo = __('Logotipo da loja para a página de pagamento.','eupago-gateway-for-woocommerce');
                $only_portuguese = __('Apenas para clientes portugueses?', 'eupago-gateway-for-woocommerce');
                $address_portuguese = __('Ativar apenas para os clientes cujo endereço é em Portugal', 'eupago-gateway-for-woocommerce');
                $orders_above = __('Apenas para encomendas acima de', 'eupago-gateway-for-woocommerce');
                $orders_below = __('Apenas para encomendas abaixo de', 'eupago-gateway-for-woocommerce');
                $orders_description = __('Ative apenas para pedidos superiores a X € (exclusivo). Deixe em branco ou zero para permitir qualquer valor.', 'eupago-gateway-for-woocommerce');
                $reduce_stock = __('Reduzir o stock', 'eupago-gateway-for-woocommerce');
                $stock_hint = __('Escolher quando reduzir o stock.', 'eupago-gateway-for-woocommerce');
                $when_paid = __('quando a encomenda é paga (requer callback ativo)', 'eupago-gateway-for-woocommerce');
                $when_created = __('quando a encomenda é registada (antes do pagamento)', 'eupago-gateway-for-woocommerce');
                $sms_confirmation = __('Confirmação do pagamento por SMS', 'eupago-gateway-for-woocommerce');
                $sms_order_confirmation = __('Confirmação de Pedido por SMS:', 'eupago-gateway-for-woocommerce');
                $enable_text = __('Ativar', 'eupago-gateway-for-woocommerce');
                $payment_on_hold = esc_html__('Envio de SMS dos detalhes de pagamento:', 'eupago-gateway-for-woocommerce');

                // Spanish overrides
            } elseif ($admin_language === 'es_ES') {
                $enable_disable_title = __('Activar/Desactivar', 'eupago-gateway-for-woocommerce');
                $enable_googlepay = __('Activar Google Pay', 'eupago-gateway-for-woocommerce');
                $title_googlepay = __('Título', 'eupago-gateway-for-woocommerce');
                $checkout_title = __('Utilice este campo para definir el título que ve el usuario durante el proceso de pago.', 'eupago-gateway-for-woocommerce');
                $googlepay_label = __('Google Pay', 'eupago-gateway-for-woocommerce');
                $description = __('Descripción','eupago-gateway-for-woocommerce');
                $description_checkout = __('Utilice este campo para definir la descripción que el usuario ve durante el proceso de pago.','eupago-gateway-for-woocommerce');
                $instructions_text = __('Instrucciones', 'eupago-gateway-for-woocommerce');
                $description_instructions_text = __('Utilice este campo para ingresar instrucciones que se agregarán a la página de confirmación del pedido y al correo electrónico enviado al cliente.', 'eupago-gateway-for-woocommerce');
                $logo = __('Logo','eupago-gateway-for-woocommerce');
                $shop_logo = __('Logotipo de la tienda para la página de pago.','eupago-gateway-for-woocommerce');
                $only_portuguese = __('¿Solo para clientes portugueses?', 'eupago-gateway-for-woocommerce');
                $address_portuguese = __('Activar sólo para clientes cuya dirección esté en Portugal.', 'eupago-gateway-for-woocommerce');
                $orders_above = __('Solo para pedidos superiores a', 'eupago-gateway-for-woocommerce');
                $orders_below = __('Solo para pedidos inferiores a', 'eupago-gateway-for-woocommerce');
                $orders_description = __('Activar solo para pedidos superiores a X € (exclusivo). Deje en blanco o cero para permitir cualquier valor.', 'eupago-gateway-for-woocommerce');
                $reduce_stock = __('Reducir el stock', 'eupago-gateway-for-woocommerce');
                $stock_hint = __('Elegir cuándo reducir el stock.', 'eupago-gateway-for-woocommerce');
                $when_paid = __('cuando el pedido se paga (requiere callback activo)', 'eupago-gateway-for-woocommerce');
                $when_created = __('cuando el pedido se realiza (antes del pago)', 'eupago-gateway-for-woocommerce');
                $sms_confirmation = __('Confirmación de pago por SMS', 'eupago-gateway-for-woocommerce');
                $sms_order_confirmation = __('Confirmación de pedido SMS:', 'eupago-gateway-for-woocommerce');
                $enable_text = __('Habilitar', 'eupago-gateway-for-woocommerce');
                $payment_on_hold = esc_html__('Envío de SMS con los detalles de pago:', 'eupago-gateway-for-woocommerce');

            }
            
            $this->form_fields = [
                'enabled' => [
                    'title'   => esc_html($enable_disable_title),
                    'type'    => 'checkbox',
                    'label'   => esc_html($enable_googlepay),
                    'default' => 'no',
                ],
                'title' => [
                    'title'       => esc_html($title_googlepay),
                    'type'        => 'text',
                    'description' => esc_html($checkout_title),
                    'default'     => esc_html($googlepay_label),
                ],
                'description' => [
                    'title'       => esc_html($description),
                    'type'        => 'textarea',
                    'description' => esc_html($description_checkout),
                    'default'     => __('You will be redirected to pay with Google Pay.', 'eupago-gateway-for-woocommerce'),
                ],
                'instructions' => [
                    'title'       => esc_html($instructions_text), // was $instructions
                    'type'        => 'textarea',
                    'description' => esc_html($description_instructions_text), // was $instructions_checkout
                    'default'     => __('You will be redirected to complete your payment with Google Pay.', 'eupago-gateway-for-woocommerce'),
                ],
                'logo_url' => [
                    'title'       => esc_html($logo),
                    'type'        => 'text',
                    'description' => esc_html($shop_logo),
                    'default'     => '',
                ],
                'only_portugal' => [
                    'title' => esc_html($only_portuguese),
                    'type'  => 'checkbox',
                    'label' => esc_html($address_portuguese),
                    'default' => 'no',
                ],
                'only_above' => [
                    'title' => esc_html($orders_above),
                    'type'  => 'number',
                    'description' => esc_html($orders_description),
                    'default' => 0,
                ],
                'only_below' => [
                    'title' => esc_html($orders_below),
                    'type'  => 'number',
                    'description' => esc_html($orders_description),
                    'default' => 0,
                ],
                'stock_when' => [
                    'title'       => esc_html($reduce_stock),
                    'type'        => 'select',
                    'description' => esc_html($stock_hint),
                    'default'     => '',
                    'options'     => [
                        ''       => esc_html($when_paid),
                        'order'  => esc_html($when_created),
                    ],
                ],
                'sms_payment_hold_googlepay' => [
                    'title' => esc_html($payment_on_hold),
                    'type'  => 'checkbox',
                    'label' => esc_html($enable_text),
                    'default' => 'no',
                ],
                'sms_payment_confirmation_googlepay' => [
                    'title' => esc_html($sms_confirmation),
                    'type'  => 'checkbox',
                    'label' => esc_html($enable_text),
                    'default' => 'no',
                ],
                'sms_order_confirmation_googlepay' => [
                    'title' => esc_html($sms_order_confirmation),
                    'type'  => 'checkbox',
                    'label' => esc_html($enable_text),
                    'default' => 'no',
                ],
            ];
        }
        
        public function admin_options()
        {
            include 'views/html-admin-page.php';
        }
        
        public function thankyou_page($order_id) {
            $order = wc_get_order($order_id);
            $order_total = $order->get_total();
            $payment_method = $order->get_payment_method();
            
            if ($payment_method === $this->id) {
                $transaction_id = $order->get_meta('_eupago_googlepay_tid', true);
                $reference      = $order->get_meta('_eupago_googlepay_reference', true);
                
                wc_get_template(
                    'payment-instructions.php',
                    [
                        'method'        => $payment_method,
                        'payment_name'  => (function_exists('icl_object_id') ? icl_t($this->id, $this->id . '_title', $this->title) : $this->title),
                        'instructions' => isset($this->instructions) && !empty($this->instructions) ? $this->instructions : '',
                        'transaction_id'=> $transaction_id,
                        'reference'     => $reference,
                        'order_total'   => $order_total,
                    ],
                    'woocommerce/eupago/',
                    (new WC_Eupago())->get_templates_path()
                );
            }
        }
        
        public function email_instructions($order, $sent_to_admin, $plain_text = false) {
            if ($sent_to_admin || $order->get_payment_method() !== $this->id) {
                return;
            }
            
            wc_get_template(
                'emails/email-instructions.php',
                [
                    'order'         => $order,
                    'email_heading' => $this->title,
                    'payment_method'=> $this->id,
                    'instructions'  => $this->instructions,
                    'transaction_id'=> $order->get_meta('_eupago_googlepay_tid'),
                    'reference'     => $order->get_meta('_eupago_googlepay_reference'),
                    'order_total'   => $order->get_total(),
                ],
                'woocommerce/eupago/',
                (new WC_Eupago())->get_templates_path()
            );
        }
        
        private function reduce_stock_levels($order) {
            if ($this->stock_when == 'order') {
                $order->reduce_order_stock();
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
        * Determine the language code based on browser headers or default to PT.
        *
        * @param WC_Order $order WooCommerce order (not used, but kept for consistency).
        * @return string ISO 2-letter language code.
        */
        private function determine_language($order) {
            $lang = 'pt'; // default fallback
            
            if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
                $browser_language = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
                if (in_array($browser_language, ['pt', 'en', 'es'])) {
                    $lang = $browser_language;
                }
            }
            
            return $lang;
        }
        
        public function process_payment($order_id)
        {
            global $woocommerce;
            
            $order = wc_get_order($order_id);
            $order_total = version_compare(WC_VERSION, '3.0', '>=') ? $order->get_total() : $order->order_total;
            
            if ($error_message = $this->check_order_errors($order_id)) {
                wc_add_notice(__('Payment error:', 'eupago-gateway-for-woocommerce') . ' ' . $error_message, 'error');
                return [ 'result' => 'fail' ];
            }
            
            $lang = $this->determine_language($order);
            $return_url = $this->get_return_url($order);
            
            $gpayResponse = $this->client->getReferenciaGooglePay($order, $order_total, $lang, $return_url);
            
            if (!is_array($gpayResponse)) {
                wc_add_notice(__('Erro ao processar a resposta do gateway.', 'eupago-gateway-for-woocommerce'), 'error');
                return [ 'result' => 'fail' ];
            }
            
            $redirect_url = $gpayResponse['redirectUrl'] ?? '';
            
            if (empty($redirect_url)) {
                wc_add_notice(__('Erro ao obter URL de redirecionamento do gateway.', 'eupago-gateway-for-woocommerce'), 'error');
                return [ 'result' => 'fail' ];
            }
            
            if (
                isset($gpayResponse['transactionStatus']) &&
                strtolower($gpayResponse['transactionStatus']) !== 'success'
                ) {
                    $error_message = $gpayResponse['text'] ?? __('Erro desconhecido.', 'eupago-gateway-for-woocommerce');
                    wc_add_notice(__('Payment error:', 'eupago-gateway-for-woocommerce') . ' ' . $error_message, 'error');
                    return [ 'result' => 'fail' ];
                }
                
                $order->update_meta_data('_eupago_googlepay_tid', $gpayResponse['transactionID'] ?? '');
                $order->update_meta_data('_eupago_googlepay_reference', $gpayResponse['reference'] ?? '');
                
                $order->save();
                
                $order->update_status('on-hold', __('Aguardando pagamento via Google Pay.', 'eupago-gateway-for-woocommerce'));
                
                $this->reduce_stock_levels($order->get_id());
                
                WC()->cart->empty_cart();
                WC()->session->__unset('order_awaiting_payment');
                
                if (file_exists(plugin_dir_path(__FILE__) . 'hooks/hooks-sms.php') && $this->get_option('sms_payment_hold_googlepay') === 'yes') {
                    include_once(plugin_dir_path(__FILE__) . 'hooks/hooks-sms.php');
                    if (function_exists('send_sms_googlepay')) {
                        send_sms_googlepay($order->get_id());
                    }
                }
                
                return [
                    'result'   => 'success',
                    'redirect' => esc_url_raw($redirect_url),
                ];
            }
            
            public function check_order_errors($order_id) {
                $order = wc_get_order($order_id);
                $order_total = $order->get_total();
                
                // Must be in EUR
                if (trim(get_woocommerce_currency()) !== 'EUR') {
                    return __('Configuração inválida: a loja deve estar em Euros (€).', 'eupago-gateway-for-woocommerce');
                }
                
                // Limit based on Eupago spec
                if ($order_total < 1 || $order_total > 99999) {
                    return __('O valor da encomenda deve estar entre 1€ e 99.999€ para pagamento com Google Pay.', 'eupago-gateway-for-woocommerce');
                }
                
                return false;
            }
            
            public function disable_only_above_or_below($gateways) {
                if (!is_admin() && isset($gateways[$this->id])) {
                    $total = WC()->cart->total;
                    if (floatval($this->only_above) > 0 && $total < floatval($this->only_above)) {
                        unset($gateways[$this->id]);
                    }
                    if (floatval($this->only_below) > 0 && $total > floatval($this->only_below)) {
                        unset($gateways[$this->id]);
                    }
                }
                return $gateways;
            }
            
            public function woocommerce_payment_complete_reduce_order_stock($bool, $order_id) {
                $order = wc_get_order($order_id);
                if ($order->get_payment_method() === $this->id) {
                    return (new WC_Eupago())->woocommerce_payment_complete_reduce_order_stock($bool, $order, $this->id, $this->stock_when);
                }
                return $bool;
            }
            
            public function get_comment_table($order, $order_total) {
                $products = $order->get_items();
                $total_produtos = 0;
                $comentario = '<ul style="margin:0; padding:0; font-size:0.75em; color:#333;">';
                
                foreach ($products as $product) {
                    $total_produtos += $product->get_total();
                    $comentario .= '<li style="list-style: none;">
                    <span style="font-size:9px;">' . esc_html($product->get_name()) . '</span>
                    <span style="margin-left:10px;">x ' . esc_html($product->get_quantity()) . '</span>
                    <span style="float:right;">' . wc_price($product->get_total()) . '</span>
                </li>';
                }
                
                $envio_e_taxas = $order_total - $total_produtos;
                $comentario .= '<li style="list-style: none; border-top: 1px solid #ddd; padding-top: 5px;">
                <span>Envio e taxas:</span>
                <span style="float:right;">' . wc_price($envio_e_taxas) . '</span>
            </li>';
                
                $comentario .= '</ul>';
                return $comentario;
            }
            
            public function disable_unless_portugal($available_gateways) {
                if (!is_admin() && isset($available_gateways[$this->id])) {
                    $gateway = $available_gateways[$this->id];
                    
                    if (isset(WC()->customer)) {
                        $country = WC()->customer->get_billing_country();
                        
                        if ($gateway->get_option('only_portugal') === 'yes' && strtoupper($country) !== 'PT') {
                            unset($available_gateways[$this->id]);
                        }
                    }
                }
                
                return $available_gateways;
            }
            
            public function payment_complete($order, $txn_id = '', $note = '') {
                if (!empty($note)) {
                    $order->add_order_note($note);
                }
                
                $order->payment_complete($txn_id);
            }
            
            public function send_sms_pending_googlepay($order_id) {
                if ($this->get_option('sms_payment_hold_googlepay') !== 'yes') {
                    return;
                }
                
                if (file_exists(plugin_dir_path(__FILE__) . 'hooks/hooks-sms.php')) {
                    include_once(plugin_dir_path(__FILE__) . 'hooks/hooks-sms.php');
                    if (function_exists('send_sms_googlepay')) {
                        send_sms_googlepay($order_id);
                    } else {
                        $this->callback_log('Função send_sms_googlepay não encontrada.');
                    }
                }
            }
            
            public function send_sms_completed_googlepay($order_id) {
                if ($this->get_option('sms_order_confirmation_googlepay') !== 'yes') {
                    return;
                }
                
                if (file_exists(plugin_dir_path(__FILE__) . 'hooks/hooks-sms.php')) {
                    include_once(plugin_dir_path(__FILE__) . 'hooks/hooks-sms.php');
                    if (function_exists('send_sms_googlepay')) {
                        send_sms_googlepay($order_id);
                    } else {
                        $this->callback_log('Função send_sms_googlepay não encontrada.');
                    }
                }
            }
        }
    }