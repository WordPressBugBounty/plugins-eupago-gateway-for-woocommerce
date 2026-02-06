<?php

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

/*
* Eupago - Floa 
*
* @since 4.5.7
*/
if (!class_exists('WC_Eupago_Floa')) {
    class WC_Eupago_Floa extends WC_Payment_Gateway
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
        protected $sms_payment_hold_floa;
        protected $sms_payment_confirmation_floa;
        protected $sms_order_confirmation_floa;
        protected $client;
        public function __construct()
        {
            global $woocommerce;
            $this->id = 'eupago_floa';
            
            $this->icon = plugins_url('assets/images/floa_blue.png', dirname(__FILE__));
            $this->has_fields = false;
            $this->method_title = __('Floa (Eupago)', 'eupago-gateway-for-woocommerce');
            
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
            $this->sms_payment_hold_floa = $this->get_option('sms_payment_hold_floa');
            $this->sms_payment_confirmation_floa = $this->get_option('sms_payment_confirmation_floa');
            $this->sms_order_confirmation_floa = $this->get_option('sms_order_confirmation_floa');
            
            // Set the API.
            $this->client = new WC_Eupago_API($this);
            
            // Actions and filters
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
            //add_action('woocommerce_order_status_pending', array($this, 'send_sms_pending_floa'));
            //add_action('woocommerce_order_status_on-hold', array($this, 'send_sms_pending_floa'));
            //add_action('woocommerce_order_status_processing' , array($this, 'send_sms_processing_floa'));
            //add_action('woocommerce_order_status_completed', array($this, 'send_sms_completed_floa'));
            
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
        * Initialize form fields for the Eupago Gateway - Floa settings in WooCommerce.
        *
        * This method sets up the form fields that will be displayed in the WooCommerce admin settings of Floa page
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
            $payment_on_hold = __('Send SMS with payment details:', 'eupago-gateway-for-woocommerce');
            $enable_disable_title = __('Enable/Disable', 'eupago-gateway-for-woocommerce');
            $title_floa_pay = __('Title','eupago-gateway-for-woocommerce');
            $enable_floa_pay = __('Enable Floa','eupago-gateway-for-woocommerce');
            $controls_checkout = __('Use this field to define the title that the user sees during the checkout process.','eupago-gateway-for-woocommerce');
            $floa_pay = __('Floa','eupago-gateway-for-woocommerce');
            $description = __('Description','eupago-gateway-for-woocommerce');
            $description_checkout = __('Use this field to define the description that the user sees during the checkout process.','eupago-gateway-for-woocommerce');
            $pay_with_card = __('Pay with Card','eupago-gateway-for-woocommerce');
            $payment_confirmation = esc_html__('Payment Confirmation by SMS', 'eupago-gateway-for-woocommerce');
            $sms_order_confirmation = esc_html('SMS Order Confirmation:', 'eupago-gateway-for-woocommerce');
            //Fim de Adição
            $instructions_text = __('Instructions', 'eupago-gateway-for-woocommerce');
            $description_instructions_text = __('Use this field to enter instructions that will be added to the order confirmation page and in the email sent to the customer.', 'eupago-gateway-for-woocommerce');
            $duplicated_payments_text = __('Duplicated Payments', 'eupago-gateway-for-woocommerce');
            $allow_duplicated_text = __('Allow duplicated payments.', 'eupago-gateway-for-woocommerce');
            $no_text = __('No', 'eupago-gateway-for-woocommerce');
            $yes_text = __('Yes', 'eupago-gateway-for-woocommerce');
            $expired_date_text = __('Expiration Date', 'eupago-gateway-for-woocommerce');
            $number_days_expired = __('Number of days for the payment to expire.', 'eupago-gateway-for-woocommerce');
            $only_portuguese = __('Only for Portuguese customers?', 'eupago-gateway-for-woocommerce');
            $address_portuguese = __('Enable only for customers whose address is in Portugal', 'eupago-gateway-for-woocommerce');
            $orders_above = __('Only for orders above', 'eupago-gateway-for-woocommerce');
            $orders_description = __('Activate only for orders over X € (exclusive). Leave blank or set to zero to allow any order value.The order value must fall within the limits set by the Floa provider.', 'eupago-gateway-for-woocommerce');
            $orders_below = __('Only for orders below', 'eupago-gateway-for-woocommerce');
            $orders_below_description = __('Activate only for orders over X € (exclusive). Leave blank or set to zero to allow any order value.The order value must fall within the limits set by the Floa provider.', 'eupago-gateway-for-woocommerce');
            $reduce_stock = __('Reduce Stock', 'eupago-gateway-for-woocommerce');
            $choose_reduce_stock = __('Choose when to reduce stock.', 'eupago-gateway-for-woocommerce');
            $when_order_pays = __('when the order is paid (requires active callback)', 'eupago-gateway-for-woocommerce');
            $when_order_placed = __('when the order is placed (before payment)', 'eupago-gateway-for-woocommerce');
            
            // Translate title based on the selected language
            if ($admin_language === 'pt_PT' || $admin_language === 'pt_BR') {
                $enable_disable_title = __('Ativar/Desativar', 'eupago-gateway-for-woocommerce');
                //Início de Adição
                $title_floa_pay = __('Título','eupago-gateway-for-woocommerce');
                $enable_floa_pay = __('Ativar Floa','eupago-gateway-for-woocommerce');
                $controls_checkout = __('Utilize este campo para definir o título que o utilizador vê durante o processo de pagamento.','eupago-gateway-for-woocommerce');
                $floa_pay = __('Cartão de Crédito','eupago-gateway-for-woocommerce');
                $description = __('Descrição','eupago-gateway-for-woocommerce');
                $description_checkout = __('Utilize este campo para definir a descrição que o utilizador vê durante o processo de pagamento.','eupago-gateway-for-woocommerce');
                $pay_with_card = __('Pagar com Cartão','eupago-gateway-for-woocommerce');
                $texto_enable = 'Ativar';
                $payment_confirmation = esc_html__('Confirmação do pagamento por SMS:', 'eupago-gateway-for-woocommerce');
                //Fim de adição
                $instructions_text = __('Instruções', 'eupago-gateway-for-woocommerce');
                $description_instructions_text = __('Utilize este campo para inserir as instruções que serão adicionadas na página de confirmação de encomenda e no email enviado ao cliente.', 'eupago-gateway-for-woocommerce');
                $duplicated_payments_text = __('Pagamentos duplicados', 'eupago-gateway-for-woocommerce');
                $allow_duplicated_text = __('Permitir pagamentos duplicados.', 'eupago-gateway-for-woocommerce');
                $no_text = __('Não', 'eupago-gateway-for-woocommerce');
                $yes_text = __('Sim', 'eupago-gateway-for-woocommerce');
                $expired_date_text = __('Data de validade', 'eupago-gateway-for-woocommerce');
                $number_days_expired = __('Número de dias para que o pagamento expire.', 'eupago-gateway-for-woocommerce');
                $only_portuguese = __('Apenas para clientes portugueses?', 'eupago-gateway-for-woocommerce');
                $address_portuguese = __('Ativar apenas para os clientes cujo endereço é em Portugal', 'eupago-gateway-for-woocommerce');
                $orders_above = __('Apenas para encomendas acima de', 'eupago-gateway-for-woocommerce');
                $orders_description = __('Ative apenas para pedidos superiores a X € (exclusivo). Deixe em branco ou zero para permitir qualquer valor. O valor da encomenda deve estar dentro dos limites definidos pelo provedor do cartão de crédito.', 'eupago-gateway-for-woocommerce');
                $orders_below = __('Apenas para encomendas abaixo de', 'eupago-gateway-for-woocommerce');
                $orders_below_description = __('Ative apenas para pedidos superiores a X € (exclusivo). Deixe em branco ou zero para permitir qualquer valor. O valor da encomenda deve estar dentro dos limites definidos pelo provedor do cartão de crédito.', 'eupago-gateway-for-woocommerce');
                $reduce_stock = __('Reduzir o stock', 'eupago-gateway-for-woocommerce');
                $choose_reduce_stock = __('Escolher quando reduzir o stock.', 'eupago-gateway-for-woocommerce');
                $when_order_pays = __('quando a encomenda é paga (requer callback ativo)', 'eupago-gateway-for-woocommerce');
                $when_order_placed = __('quando a encomenda é registada (antes do pagamento)', 'eupago-gateway-for-woocommerce');
                $payment_on_hold = esc_html__('Envio de SMS dos detalhes de pagamento:', 'eupago-gateway-for-woocommerce');
                $sms_order_confirmation = esc_html__('Confirmação de Pedido por SMS:', 'eupago-gateway-for-woocommerce');
            } elseif ($admin_language === 'es_ES') {
                $enable_disable_title = __('Activar/Desactivar', 'eupago-gateway-for-woocommerce');
                //Início de Adição
                $title_floa_pay = __('Título','eupago-gateway-for-woocommerce');
                $enable_floa_pay = __('Activar Floa','eupago-gateway-for-woocommerce');
                $controls_checkout = __('Utilice este campo para definir el título que ve el usuario durante el proceso de pago.','eupago-gateway-for-woocommerce');
                $floa_pay = __('Tarjeta de Crédito','eupago-gateway-for-woocommerce');
                $description = __('Descripción','eupago-gateway-for-woocommerce');
                $description_checkout = __('Utilice este campo para definir la descripción que el usuario ve durante el proceso de pago.','eupago-gateway-for-woocommerce');
                //Fim de adição
                $instructions_text = __('Instrucciones', 'eupago-gateway-for-woocommerce');
                $description_instructions_text = __('Utilice este campo para ingresar instrucciones que se agregarán a la página de confirmación del pedido y al correo electrónico enviado al cliente.', 'eupago-gateway-for-woocommerce');
                $duplicated_payments_text = __('Pagos duplicados', 'eupago-gateway-for-woocommerce');
                $allow_duplicated_text = __('Permitir pagos duplicados.', 'eupago-gateway-for-woocommerce');
                $no_text = __('No', 'eupago-gateway-for-woocommerce');
                $yes_text = __('Sí', 'eupago-gateway-for-woocommerce');
                $expired_date_text = __('Fecha de vencimiento', 'eupago-gateway-for-woocommerce');
                $number_days_expired = __('Número de días para que caduque el pago.', 'eupago-gateway-for-woocommerce');
                $only_portuguese = __('¿Solo para clientes portugueses?', 'eupago-gateway-for-woocommerce');
                $address_portuguese = __('Activar sólo para clientes cuya dirección esté en Portugal.', 'eupago-gateway-for-woocommerce');
                $orders_above = __('Solo para pedidos superiores a', 'eupago-gateway-for-woocommerce');
                $orders_description = __('Activar solo para pedidos superiores a x &euro; (exclusivo). Deje en blanco (o cero) para permitir cualquier valor de pedido.', 'eupago-gateway-for-woocommerce') . ' <br/> ' . __('El valor del pedido debe estar dentro de los límites establecidos por el proveedor de la tarjeta de crédito.', 'eupago-gateway-for-woocommerce');
                $orders_below = __('Solo para pedidos inferiores a', 'eupago-gateway-for-woocommerce');
                $orders_below_description = __('Activar solo para pedidos inferiores a x &euro; (exclusivo). Deje en blanco (o cero) para permitir cualquier valor de pedido.', 'eupago-gateway-for-woocommerce') . ' <br/> ' . __('El valor del pedido debe estar dentro de los límites establecidos por el proveedor de la tarjeta de crédito.', 'eupago-gateway-for-woocommerce');
                $reduce_stock = __('Reducir el stock', 'eupago-gateway-for-woocommerce');
                $choose_reduce_stock = __('Elegir cuándo reducir el stock.', 'eupago-gateway-for-woocommerce');
                $when_order_pays = __('cuando el pedido se paga (requiere callback activo)', 'eupago-gateway-for-woocommerce');
                $when_order_placed = __('cuando el pedido se realiza (antes del pago)', 'eupago-gateway-for-woocommerce');
                $payment_on_hold = esc_html__('Envío de SMS con los detalles de pago:', 'eupago-gateway-for-woocommerce');
                $texto_enable = 'Habilitar';
                $payment_confirmation = esc_html__('Confirmación de pago por SMS', 'eupago-gateway-for-woocommerce');
                $sms_order_confirmation = esc_html__('Confirmación de pedido SMS:', 'eupago-gateway-for-woocommerce');
            }
            
            
            $this->form_fields = [
                'enabled' => [
                    'title' => esc_html($enable_disable_title),
                    'type' => 'checkbox',
                    'label' => esc_html($enable_floa_pay),
                    'default' => 'no',
                ],
                'language' => [
                    'title'       => $language_title,
                    'type'        => 'select',
                    'description' => $language_description,
                    'default'     => 'default',
                    'options'     => $language_options,
                ],
                'title' => [
                    'title' => esc_html($title_floa_pay),
                    'type' => 'text',
                    'description' => esc_html($controls_checkout),
                    'default' => esc_html__($floa_pay),
                ],
                'description' => [
                    'title' => esc_html($description),
                    'type' => 'textarea',
                    'description' => esc_html($description_checkout),
                    'default'     => __('You will be redirected to pay with Floa.', 'eupago-gateway-for-woocommerce'),
                ],
                'instructions' => [
                    'title' => esc_html($instructions_text),
                    'type' => 'textarea',
                    'description' => esc_html($description_instructions_text),
                    'default'     => __('Pay in 3 installments without interest.', 'eupago-gateway-for-woocommerce'),
                ],
                'only_portugal' => [
                    'title' => esc_html($only_portuguese),
                    'type' => 'checkbox',
                    'label' => esc_html($address_portuguese),
                    'default' => 'yes',
                ],
                'only_above' => [
                    'title' => esc_html($orders_above),
                    'type' => 'number',
                    'description' => wp_kses_post($orders_description),
                    'default' => '50',
                    'custom_attributes' => [
                        'min' => 50,
                        'max' => 2499,
                    ],
                ],
                'only_below' => [
                    'title' => esc_html($orders_below),
                    'type' => 'number',
                    'description' => wp_kses_post($orders_below_description),
                    'default' => '2500',
                    'custom_attributes' => [
                        'min' => 51,
                        'max' => 2500,
                    ],
                ],
                'stock_when' => [
                    'title' => esc_html ($reduce_stock),
                    'type' => 'select',
                    'description' =>esc_html ($choose_reduce_stock),
                    'default' => '',
                    'options' => [
                        '' => esc_html($when_order_pays),
                        'order' => esc_html($when_order_placed),
                    ],
                ],
                'sms_payment_hold_floa' => [
                    'title' => esc_html($payment_on_hold),
                    'type' => 'checkbox',
                    'label' => esc_html($texto_enable),
                    'default' => 'no',
                ],
                'sms_payment_confirmation_floa' => [
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
         * Get the gateway description for the checkout page.
         * Overrides the default description field to add the banner and dynamic installment text.
         *
         * @return string
         */
        public function get_description() {
            
            // 1. Get Banner URL
            $banner_url = plugins_url( '../assets/images/floa_banner_white.png', __FILE__ );

            $banner_html = sprintf(
                '<img src="%s" alt="%s" style="max-width: 100%%; margin-bottom: 10px;">',
                esc_url( $banner_url ),
                esc_attr__( 'Pay with Floa', 'eupago-gateway-for-woocommerce' )
            );

            // 2. Get Dynamic Installment Text (This logic is from your FloaBlock.php)
            $installmentsText = "";
            if ( function_exists('WC') && WC()->cart && ! WC()->cart->is_empty() ) {
                $total = (float) WC()->cart->get_total('edit');

                if ( $total > 0 ) {
                    $installment_value = $total / 3; 
                    $installment_value_formatted = wc_price( $installment_value );
                    
                    $locale = get_locale();
                    if ($locale === 'pt_PT') {
                        $installmentsText = sprintf(
                            __('Pague em 3x de %s sem juros.', 'eupago-gateway-for-woocommerce'),
                            $installment_value_formatted
                        );
                    } else {
                        $installmentsText = sprintf(
                            __('Pay in 3 installments of %s interest-free.', 'eupago-gateway-for-woocommerce'),
                            $installment_value_formatted
                        );
                    }
                }
            }

            // 3. Get Static Description Text (This logic is from your FloaBlock.php)
            $description = "";
            $locale = get_locale();
            if ($locale === 'pt_PT') {
                $description = __('Será redirecionado para uma página segura a fim de efetuar o pagamento. O pagamento das prestações será efetuado no cartão de débito ou crédito do cliente através da solução de pagamento assente em contrato de factoring entre a Floa e o comerciante.', 'eupago-gateway-for-woocommerce');
            } else {
                $description = __('You will be redirected to a secure page to make your payment. The payment of installments will be made on the customer´s debit or credit card through the payment solution based on factoring agreement between Floa and the merchant.', 'eupago-gateway-for-woocommerce');
            }
            
            // 4. Combine them all into the final HTML
            $full_description = $banner_html;
            if ( ! empty( $installmentsText ) ) {
                $full_description .= '<div class="installments-text" style="font-weight: bold; margin-bottom: 5px;">' . $installmentsText . '</div>';
            }
            $full_description .= '<div class="description-text">' . $description . '</div>';

            return $full_description;
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
                $transaction_id = $order->get_meta('_eupago_floa_tid', true);
                $reference      = $order->get_meta('_eupago_floa_reference', true);
                
                wc_get_template(
                    'payment-instructions.php',
                    [
                        'method' => $payment_method,
                        'payment_name' => (function_exists('icl_object_id') ? icl_t($this->id, $this->id . '_title', $this->title) : $this->title),
                        'transaction_id'=> $transaction_id,
                        'reference'     => $reference,
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
                    'referencia' => $order->get_meta('_eupago_floa_referencia', true),
                    'order_total' => $order_total,
                ], 'woocommerce/eupago/', (new WC_Eupago())->get_templates_path());
            } else {
                wc_get_template('emails/html-instructions.php', [
                    'method' => $payment_method,
                    'payment_name' => (function_exists('icl_object_id') ? icl_t($this->id, $this->id . '_title', $this->title) : $this->title),
                    'instructions' => isset($this->instructions) && !empty($this->instructions) ? $this->instructions : '',
                    'referencia' => $order->get_meta('_eupago_floa_referencia', true),
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
            if (($order_total < 50) || ($order_total > 2500)) {
                return __('It\'s not possible to use Floa to pay values under 50&euro; or above 2500&euro;.', 'eupago-gateway-for-woocommerce');
            }
            
            return false;
        }
        
        /**
        * Process the Eupago payment for the given order.
        *
        * @param int $order_id The order ID.
        */
        /**
        * Process the Eupago Floa payment for the given order.
        *
        * @param int $order_id The order ID.
        * @return array Payment result
        */
        public function process_payment($order_id)
        {
            $logger  = wc_get_logger();
            $context = ['source' => 'eupago-floa'];
            
            $order       = wc_get_order($order_id);
            $order_total = version_compare(WC_VERSION, '3.0', '>=') ? $order->get_total() : $order->order_total;
            
            $logger->debug("Floa: Starting process_payment for order {$order_id}, total={$order_total}", $context);
            
            if ($error_message = $this->check_order_errors($order_id)) {
                $logger->error("Floa: check_order_errors failed -> {$error_message}", $context);
                wc_add_notice(__('Payment error:', 'eupago-gateway-for-woocommerce') . ' ' . $error_message, 'error');
                return ['result' => 'fail', 'redirect' => ''];
            }
            
            // Determine language
            $lang = $this->determine_language();
            $return_url = $this->get_return_url($order);
            // Call API
            $responseFloa = $this->client->getReferenciaFloa(
                $order,
                $order_total,
                $lang,
                $return_url
            );
            
            $logger->debug("Floa: Raw response -> " . print_r($responseFloa, true), $context);
            
            if (is_string($responseFloa)) {
                $responseFloa = json_decode($responseFloa, true);
            }
            
            if (!is_array($responseFloa)) {
                $logger->error("Floa: Unexpected API response format", $context);
                wc_add_notice(__('Erro ao processar a resposta do gateway.', 'eupago-gateway-for-woocommerce'), 'error');
                return ['result' => 'fail', 'redirect' => ''];
            }
            
            $redirect_url = $responseFloa['redirectUrl'] ?? '';
            
            // Handle errors
            if (isset($responseFloa['transactionStatus']) && strtolower($responseFloa['transactionStatus']) !== 'success') {
                $error_message = $responseFloa['text'] ?? __('Unknown error', 'eupago-gateway-for-woocommerce');
                $logger->error("Floa: Eupago returned estado={$responseFloa['code']} -> {$error_message}", $context);
                wc_add_notice(__('Payment error:', 'eupago-gateway-for-woocommerce') . ' ' . $error_message, 'error');
                return ['result' => 'fail', 'redirect' => ''];
            }
            
            if (empty($redirect_url)) {
                $logger->error("Floa: No redirectUrl found in response", $context);
                wc_add_notice(__('Erro ao obter URL de redirecionamento do gateway.', 'eupago-gateway-for-woocommerce'), 'error');
                return ['result' => 'fail', 'redirect' => ''];
            }
            
            // Save metadata
            $order->update_meta_data('_eupago_floa_tid', $responseFloa['transactionID'] ?? '');
            $order->update_meta_data('_eupago_floa_reference', $responseFloa['reference'] ?? '');
            $order->save();
            
            // Mark order on-hold
            $order->update_status('on-hold', __('Awaiting Floa payment.', 'eupago-gateway-for-woocommerce'));
            
            // Reduce stock
            $this->reduce_stock_levels($order);
            
            // Empty cart/session
            $this->clear_cart_and_session();
            
            // Optional SMS hook
            if (file_exists(plugin_dir_path(__FILE__) . 'hooks/hooks-sms.php') && $this->get_option('sms_payment_hold_floa') === 'yes') {
                include_once(plugin_dir_path(__FILE__) . 'hooks/hooks-sms.php');
                if (function_exists('send_sms_floa')) {
                    send_sms_floa($order_id);
                }
            }
            
            return [
                'result'   => 'success',
                'redirect' => esc_url_raw($redirect_url),
            ];
        }
        
        /**
        * Determine the language for the Eupago request.
        *
        * @param WC_Order $order The WooCommerce order.
        * @return string The language code.
        */private function determine_language() {
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
            
            // 3. Safe default
            return 'PT';
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
            
            if (isset($eupagoFloa->url) && !empty($eupagoFloa->url)) {
                return [
                    'result' => 'success',
                    'redirect' => $eupagoFloa->url,
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
                    if ($woocommerce->cart && $woocommerce->cart->total < floatval($available_gateways[$this->id]->only_above)) {
                        unset($available_gateways[$this->id]);
                    }
                }
                
                if (@floatval($available_gateways[$this->id]->only_below) > 0) {
                    if ($woocommerce->cart && $woocommerce->cart->total > floatval($available_gateways[$this->id]->only_below)) {
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
    } // WC_Eupago_Floa
} // class_exists()
