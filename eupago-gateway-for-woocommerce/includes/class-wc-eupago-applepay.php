<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WC_Eupago_ApplePay')) {
    class WC_Eupago_ApplePay extends WC_Payment_Gateway {
        
        protected $instructions;
        protected $only_portugal;
        protected $only_above;
        protected $only_below;
        protected $stock_when;
        protected $client;
        
        public function __construct() {
            $this->id = 'eupago_applepay';
            $this->icon = plugins_url('assets/images/applepay_icon.png', dirname(__FILE__));
            $this->has_fields = false;
            $this->method_title = __('Apple Pay (Eupago)', 'eupago-gateway-for-woocommerce');
            
            $this->init_form_fields();
            $this->init_settings();
            
            $this->title         = $this->get_option('title');
            $this->description   = $this->get_option('description');
            $this->instructions  = $this->get_option('instructions');
            $this->only_portugal = $this->get_option('only_portugal');
            $this->only_above    = $this->get_option('only_above');
            $this->only_below    = $this->get_option('only_below');
            $this->stock_when    = $this->get_option('stock_when');
            
            $this->client = new WC_Eupago_API($this);
            
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, [ $this, 'process_admin_options' ]);
            add_action('woocommerce_thankyou_' . $this->id, [ $this, 'thankyou_page' ]);
            add_action('woocommerce_order_details_after_order_table', [ $this, 'order_details_after_order_table' ], 20);
            add_filter('woocommerce_available_payment_gateways', [ $this, 'disable_unless_portugal' ]);
            add_filter('woocommerce_available_payment_gateways', [ $this, 'disable_only_above_or_below' ]);
            add_filter('woocommerce_payment_complete_reduce_order_stock', [ $this, 'woocommerce_payment_complete_reduce_order_stock' ], 10, 2);
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
            
            // Shared translated labels
            $enable_disable_title = __('Enable/Disable', 'eupago-gateway-for-woocommerce');
            $enable_applepay = __('Enable Apple Pay', 'eupago-gateway-for-woocommerce');
            $title_applepay = __('Title', 'eupago-gateway-for-woocommerce');
            $checkout_title = __('Use this field to define the title shown during checkout.', 'eupago-gateway-for-woocommerce');
            $applepay_label = __('Apple Pay', 'eupago-gateway-for-woocommerce');
            $description = __('Description', 'eupago-gateway-for-woocommerce');
            $description_checkout = __('Use this field to define the description shown during checkout.', 'eupago-gateway-for-woocommerce');
            $instructions_text = __('Instructions', 'eupago-gateway-for-woocommerce');
            $description_instructions_text = __('Use this field to enter instructions that will be added to the order confirmation page and in the email sent to the customer.', 'eupago-gateway-for-woocommerce');
            $instructions_checkout = __('Shown on thank you page and emails.', 'eupago-gateway-for-woocommerce');
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
            $sms_hold = __('Send SMS with payment details:', 'eupago-gateway-for-woocommerce');
            $sms_confirmation = __('Payment Confirmation by SMS', 'eupago-gateway-for-woocommerce');
            $sms_order_confirmation = __('SMS Order Confirmation:', 'eupago-gateway-for-woocommerce');
            $enable_text = __('Enable', 'eupago-gateway-for-woocommerce');
            
            // Language Overrides
            if ($admin_language === 'pt_PT' || $admin_language === 'pt_BR') {
                $enable_disable_title = __('Ativar/Desativar', 'eupago-gateway-for-woocommerce');
                $enable_applepay = __('Ativar Apple Pay', 'eupago-gateway-for-woocommerce');
                $title_applepay = __('Título', 'eupago-gateway-for-woocommerce');
                $checkout_title = __('Utilize este campo para definir o título que o utilizador vê durante o processo de pagamento.', 'eupago-gateway-for-woocommerce');
                $applepay_label = __('Apple Pay', 'eupago-gateway-for-woocommerce');
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
                
            } elseif ($admin_language === 'es_ES') {
                $enable_disable_title = __('Activar/Desactivar', 'eupago-gateway-for-woocommerce');
                $enable_applepay = __('Activar Apple Pay', 'eupago-gateway-for-woocommerce');
                $title_applepay = __('Título', 'eupago-gateway-for-woocommerce');
                $checkout_title = __('Utilice este campo para definir el título que ve el usuario durante el proceso de pago.', 'eupago-gateway-for-woocommerce');
                $applepay_label = __('Apple Pay', 'eupago-gateway-for-woocommerce');
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
            }
            
            $this->form_fields = [
                'enabled' => [
                    'title'   => esc_html($enable_disable_title),
                    'type'    => 'checkbox',
                    'label'   => esc_html($enable_applepay),
                    'default' => 'no',
                ],
                'title' => [
                    'title'       => esc_html($title_applepay),
                    'type'        => 'text',
                    'description' => esc_html($checkout_title),
                    'default'     => esc_html($applepay_label),
                ],
                'description' => [
                    'title'       => esc_html($description),
                    'type'        => 'textarea',
                    'description' => esc_html($description_checkout),
                    'default'     => __('You will be redirected to pay with Apple Pay.', 'eupago-gateway-for-woocommerce'),
                ],
                'instructions' => [
                    'title'       => esc_html($instructions_text),
                    'type'        => 'textarea',
                    'description' => esc_html($description_instructions_text),
                    'default'     => __('You will be redirected to complete your payment with Apple Pay.', 'eupago-gateway-for-woocommerce'),
                ],
                'language' => [
                    'title'       => __('Language', 'eupago-gateway-for-woocommerce'),
                    'type'        => 'select',
                    'description' => __('Select the language for the payment process.', 'eupago-gateway-for-woocommerce'),
                    'default'     => 'default',
                    'options'     => $language_options,
                ],
                'logo_url' => [
                    'title'       => esc_html($logo),
                    'type'        => 'text',
                    'description' => esc_html($shop_logo),
                    'default'     => '',
                ],
                'only_portugal' => [
                    'title'   => esc_html($only_portuguese),
                    'type'    => 'checkbox',
                    'label'   => esc_html($address_portuguese),
                    'default' => 'no',
                ],
                'only_above' => [
                    'title'       => esc_html($orders_above),
                    'type'        => 'number',
                    'description' => wp_kses_post($orders_description),
                    'default'     => 0,
                ],
                'only_below' => [
                    'title'       => esc_html($orders_below),
                    'type'        => 'number',
                    'description' => wp_kses_post($orders_description),
                    'default'     => 0,
                ],
                'stock_when' => [
                    'title'       => esc_html($reduce_stock),
                    'type'        => 'select',
                    'description' => esc_html($stock_hint),
                    'default'     => '',
                    'options'     => [
                        ''      => esc_html($when_paid),
                        'order' => esc_html($when_created),
                    ],
                ],
                'sms_payment_confirmation_applepay' => [
                    'title'   => esc_html($sms_confirmation),
                    'type'    => 'checkbox',
                    'label'   => esc_html($enable_text),
                    'default' => 'no',
                ],
                'sms_order_confirmation_applepay' => [
                    'title'   => esc_html($sms_order_confirmation),
                    'type'    => 'checkbox',
                    'label'   => esc_html($enable_text),
                    'default' => 'no',
                ],
            ];  
        }
        
        public function process_payment($order_id) {
            $logger  = wc_get_logger();
            $context = ['source' => 'eupago-applepay'];
            
            $order       = wc_get_order($order_id);
            $order_total = $order->get_total();
            
            if ($error_message = $this->check_order_errors($order)) {
                $logger->error("check_order_errors failed: {$error_message}", $context);
                wc_add_notice(__('Payment error:', 'eupago-gateway-for-woocommerce') . ' ' . $error_message, 'error');
                return [ 'result' => 'fail' ];
            }
            
            $lang       = $this->determine_language();
            $return_url = $this->get_return_url($order);
            
            $response = $this->client->getReferenciaApplePay($order, $order_total, $lang, $return_url);
            
            $logger->info('Raw API response: ' . print_r($response, true), $context);
            
            if (!is_array($response)) {
                $logger->error("API response not array: " . print_r($response, true), $context);
                wc_add_notice(__('Error processing Apple Pay request.', 'eupago-gateway-for-woocommerce'), 'error');
                return [ 'result' => 'fail' ];
            }
            
            $redirect_url = $response['redirectUrl'] ?? '';
            if (empty($redirect_url)) {
                $logger->error("No redirectUrl in response. Full response: " . print_r($response, true), $context);
                wc_add_notice(__('No redirect URL received from Eupago.', 'eupago-gateway-for-woocommerce'), 'error');
                return [ 'result' => 'fail' ];
            }
            
            if (isset($response['transactionStatus']) && strtolower($response['transactionStatus']) !== 'success') {
                $error_message = $response['text'] ?? __('Unknown error.', 'eupago-gateway-for-woocommerce');
                $logger->error("Transaction rejected: {$error_message}. Full response: " . print_r($response, true), $context);
                wc_add_notice(__('Payment error:', 'eupago-gateway-for-woocommerce') . ' ' . $error_message, 'error');
                return [ 'result' => 'fail' ];
            }
            
            $order->update_meta_data('_eupago_applepay_tid', $response['transactionID'] ?? '');
            $order->update_meta_data('_eupago_applepay_reference', $response['reference'] ?? '');
            $order->save();
            
            $order->update_status('on-hold', __('Awaiting Apple Pay payment.', 'eupago-gateway-for-woocommerce'));
            
            $this->reduce_stock_levels($order_id);
            WC()->cart->empty_cart();
            WC()->session->__unset('order_awaiting_payment');
            
            return [
                'result'   => 'success',
                'redirect' => esc_url_raw($redirect_url),
            ];
        }
        
        private function reduce_stock_levels($order) {
            if ($this->stock_when == 'order') {
                $order->reduce_order_stock();
            }
        }

        public function thankyou_page($order_id) {
            $order = wc_get_order($order_id);
            
            $transaction_id = $order->get_meta('_eupago_applepay_tid', true);
            $reference      = $order->get_meta('_eupago_applepay_reference', true);
            $payment_method = version_compare(WC_VERSION, '3.0', '>=') ? $order->get_payment_method() : $order->payment_method;
            
            if ($payment_method == $this->id) {
                wc_get_template(
                    'payment-instructions.php',
                    [
                        'method'         => $this->id,
                        'payment_name'   => $this->title,
                        'instructions' => isset($this->instructions) && !empty($this->instructions) ? $this->instructions : '',
                        'transaction_id' => $transaction_id,
                        'reference'      => $reference,
                        'order_total'    => $order->get_total(),
                    ],
                    'woocommerce/eupago/',
                    (new WC_Eupago())->get_templates_path()
                );
            }
        }
        
        public function order_details_after_order_table($order) {
            if (is_wc_endpoint_url('view-order')) {
                $this->thankyou_page($order->get_id());
            }
        }
            
        public function disable_only_above_or_below($gateways) {
            if (!is_admin() && isset($gateways[$this->id])) {
                $total = WC()->cart->total;
                if ($this->only_above && $total < floatval($this->only_above)) {
                    unset($gateways[$this->id]);
                }
                if ($this->only_below && $total > floatval($this->only_below)) {
                    unset($gateways[$this->id]);
                }
            }
            return $gateways;
        }
        
        public function disable_unless_portugal($available_gateways) {
            if (!is_admin() && isset($available_gateways[$this->id])) {
                $country = WC()->customer->get_billing_country();
                if ($this->only_portugal === 'yes' && strtoupper($country) !== 'PT') {
                    unset($available_gateways[$this->id]);
                }
            }
            return $available_gateways;
        }
        
        public function woocommerce_payment_complete_reduce_order_stock($bool, $order_id) {
            $order = wc_get_order($order_id);
            if ($order->get_payment_method() === $this->id) {
                return (new WC_Eupago())->woocommerce_payment_complete_reduce_order_stock($bool, $order, $this->id, $this->stock_when);
            }
            return $bool;
        }
        
        private function determine_language() {
            // 1. Admin override
            $option = $this->get_option('language', 'default');
            if ($option && $option !== 'default') {
                return strtoupper($option); // 'PT', 'EN', 'ES'
            }
            
            // 2. Browser fallback
            if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
                $browser_language = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
                if (in_array($browser_language, ['pt', 'en', 'es'], true)) {
                    return strtoupper($browser_language);
                }
            }
            
            return 'PT';
        }
        
        private function check_order_errors($order) {
            $total = $order->get_total();
            if (get_woocommerce_currency() !== 'EUR') {
                return __('Store currency must be Euros (€).', 'eupago-gateway-for-woocommerce');
            }
            
            if ($total < 1 || $total > 99999) {
                return __('Order value must be between 1€ and 99.999€ for Apple Pay.', 'eupago-gateway-for-woocommerce');
            }
            
            return false;
        }
        
    }
}
