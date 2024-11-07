<?php
if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

/*
 * Eupago - CofidisPay
 *
 * @since 0.1
 */
if (!class_exists('WC_Eupago_CofidisPay')) {
    class WC_Eupago_CofidisPay extends WC_Payment_Gateway
    {
        /**
         * Constructor for your payment class
         *
         * @access public
         *
         * @return void
         */
        public $instructions;
        public $only_portugal;
        public $only_above;
        public $only_below;
        public $stock_when;
        public $sms_payment_hold_cofidis;
        public $sms_payment_confirmation_cofidis;
        public $sms_order_confirmation_cofidis;
        public $client;

        public function __construct()
        {
            global $woocommerce;
            $this->id = 'eupago_cofidispay';

            $this->icon = plugins_url('assets/images/cofidispay.png', dirname(__FILE__));
            $this->has_fields = true;
            $this->method_title = __('CofidisPay (Eupago)', 'eupago-for-woocommerce');

            // Plugin options and settings
            $this->init_form_fields();
            $this->init_settings();

            // if ($this->get_option('only_below') < 60) {
            //   wc_add_notice('oops', 'error');
            // }

            // User settings
            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');
            $this->instructions = $this->get_option('instructions');
            $this->only_portugal = $this->get_option('only_portugal');
            $this->only_above = $this->get_option('only_above');
            $this->only_below = $this->get_option('only_below');
            $this->stock_when = $this->get_option('stock_when');
            $this->sms_payment_hold_cofidis = $this->get_option('sms_payment_hold_cofidis');
            $this->sms_payment_confirmation_cofidis = $this->get_option('sms_payment_confirmation_cofidis');
            $this->sms_order_confirmation_cofidis = $this->get_option('sms_order_confirmation_cofidis');

            // Set the API.
            $this->client = new WC_Eupago_API($this);

            // Actions and filters
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
           

            if (function_exists('icl_object_id') && function_exists('icl_register_string')) {
                add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'register_wpml_strings']);
            }
            add_action('woocommerce_thankyou_' . $this->id, [$this, 'thankyou_page']);
            add_action('woocommerce_order_details_after_order_table', [$this, 'order_details_after_order_table'], 20);

            add_filter('woocommerce_available_payment_gateways', [$this, 'disable_unless_portugal']);
            add_filter('woocommerce_available_payment_gateways', [$this, 'disable_only_above_or_below']);
            add_filter('woocommerce_available_payment_gateways', [$this, 'change_title'], 99);

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

        public function init_form_fields()
        {
            $admin_language = get_locale();
            $enable_disable_title = __('Enable/Disable', 'eupago-gateway-for-woocommerce');

            // Translate title based on the selected language
            if ($admin_language === 'pt_PT' || $admin_language === 'pt_BR') {
                $enable_disable_title = __('Ativar/Desativar', 'eupago-gateway-for-woocommerce');
            } elseif ($admin_language === 'es_ES') {
                $enable_disable_title = __('Activar/Desactivar', 'eupago-gateway-for-woocommerce');
            }

        
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

            $texts = [
                'enable_disable' => __('Enable/Disable', 'woocommerce'),
                'payment_hold' => __('SMS Payment On Hold:', 'eupago-gateway-for-woocommerce'),
                'payment_confirmation' => __('SMS Payment Confirmation:', 'eupago-gateway-for-woocommerce'),
                'enable_label' => __('Enable CofidisPay (using Eupago)', 'eupago-for-woocommerce'),
                'sms_order_confirmation' => __('SMS Order Confirmation:', 'eupago-gateway-for-woocommerce'),
                'enable_label2' => 'Enable',
                'title' => __('Title', 'woocommerce'),
                'title_description' => __('This controls the title which the user sees during checkout.', 'woocommerce'),
                'title_default' => __('Cofidis Pay', 'eupago-for-woocommerce'),
                'instructions' => __('Instructions', 'eupago-for-woocommerce'),
                'instructions_description' => __('Instructions that will be added to the thank you page and email sent to customer.', 'eupago-for-woocommerce'),
                'only_portugal' => __('Only for Portuguese customers?', 'eupago-for-woocommerce'),
                'only_portugal_label' => __('Enable only for customers whose address is in Portugal', 'eupago-for-woocommerce'),
                'only_above' => __('Only for orders above', 'eupago-gateway-for-woocommerce'),
                'only_above_description' => __('Enable only for orders above x &euro; (exclusive). Leave blank (or zero) to allow for any order value.', 'eupago-gateway-for-woocommerce') . ' <br/> ' . __('The order value must fall within the limits set by Cofidis.', 'eupago-gateway-for-woocommerce'),
                'only_below' => __('Only for orders below', 'eupago-gateway-for-woocommerce'),
                'only_below_description' => __('Enable only for orders below x &euro; (exclusive). Leave blank (or zero) to allow for any order value.', 'eupago-gateway-for-woocommerce') . ' <br/> ' . __('The order value must fall within the limits set by Cofidis.', 'eupago-gateway-for-woocommerce'),
                'reduce_stock' => __('Reduce stock', 'eupago-for-woocommerce'),
                'reduce_stock_description' => __('Choose when to reduce stock.', 'eupago-for-woocommerce'),
                'when_order_is_paid' => __('when order is paid (requires active callback)', 'eupago-gateway-for-woocommerce'),
                'when_order_is_placed' =>  __('when order is placed (before payment)', 'eupago-gateway-for-woocommerce'),
                'zero_tax_code' => __('Code for Zero Tax', 'eupago-for-woocommerce'),
                'zero_tax_code_description' => __('Select the code justification for enabling zero tax, keep disabled for default', 'eupago-for-woocommerce'),
                'zero_tax_options' => [
                    'null' => __('Disable zero tax justification', 'eupago-for-woocommerce'),
                    'M01' => __('M01 - Article 16º no. 6 of the VAT Code.', 'eupago-for-woocommerce'),
                    'M02' => __('M02 - Article 6º of Decree-Law no. 198/90, of June 19.', 'eupago-for-woocommerce'),
                    'M03' => __('M03 - Cash demand.', 'eupago-for-woocommerce'),
                    'M04' => __('M04 - Exempt Article 13º of the VAT Code.', 'eupago-for-woocommerce'),
                    'M05' => __('M05 - Exempt Article 14º of the VAT Code.', 'eupago-for-woocommerce'),
                    'M06' => __('M06 - Exempt Article 15º of the VAT Code.', 'eupago-for-woocommerce'),
                    'M07' => __('M07 - Exempt Article 9º of the VAT Code.', 'eupago-for-woocommerce'),
                    'M08' => __('M08 - VAT is self-assessment.', 'eupago-for-woocommerce'),
                    'M09' => __('M09 - VAT - does not confer right to deduction.', 'eupago-for-woocommerce'),
                    'M10' => __('M10 - VAT is Exemption Regime.', 'eupago-for-woocommerce'),
                    'M11' => __('M11 - Special tobacco regime.', 'eupago-for-woocommerce'),
                    'M12' => __('M12 - Profit margin scheme - Travel agencies.', 'eupago-for-woocommerce'),
                    'M13' => __('M13 - Profit margin scheme - Second-hand goods.', 'eupago-for-woocommerce'),
                    'M14' => __('M14 - Profit margin scheme - Works of art.', 'eupago-for-woocommerce'),
                    'M15' => __('M15 - Profit margin scheme - Collection items and antiques.', 'eupago-for-woocommerce'),
                    'M16' => __('M16 - Exempt Article 14º of the RITI.', 'eupago-for-woocommerce'),
                    'M19' => __('M19 - Other exemptions', 'eupago-for-woocommerce'),
                    'M20' => __('M20 - VAT - flat-rate scheme', 'eupago-for-woocommerce'),
                    'M21' => __('M21 - VAT - does not confer right to deduction (or similar expression)', 'eupago-for-woocommerce'),
                    'M25' => __('M25 - Goods on consignment', 'eupago-for-woocommerce'),
                    'M26' => __('M26 - VAT exemption with right to deduction in the food basket', 'eupago-for-woocommerce'),
                    'M30' => __('M30 - VAT is self-assessment.', 'eupago-for-woocommerce'),
                    'M31' => __('M31 - VAT is self-assessment.', 'eupago-for-woocommerce'),
                    'M32' => __('M32 - VAT is self-assessment.', 'eupago-for-woocommerce'),
                    'M33' => __('M33 - VAT is self-assessment.', 'eupago-for-woocommerce'),
                    'M34' => __('M34 - VAT is self-assessment.', 'eupago-for-woocommerce'),
                    'M40' => __('M40 - VAT is self-assessment.', 'eupago-for-woocommerce'),
                    'M41' => __('M41 - VAT is self-assessment.', 'eupago-for-woocommerce'),
                    'M42' => __('M42 - VAT is self-assessment.', 'eupago-for-woocommerce'),
                    'M43' => __('M43 - VAT is self-assessment.', 'eupago-for-woocommerce'),
                    'M99' => __('M99 - Not subject; not taxed (or similar).', 'eupago-for-woocommerce')
                ],
                'max_installments' => __('Maximum Amount of Installments', 'eupago-for-woocommerce'),
                'max_installments_description' => __('Change the text for Cofidis Pay to display a maximum amount of installments', 'eupago-for-woocommerce'),
                'installment_options' => [
                    '0' => __('Disable this option', 'eupago-for-woocommerce'),
                    '3' => __('x3', 'eupago-for-woocommerce'),
                    '4' => __('x4', 'eupago-for-woocommerce'),
                    '5' => __('x5', 'eupago-for-woocommerce'),
                    '6' => __('x6', 'eupago-for-woocommerce'),
                    '7' => __('x7', 'eupago-for-woocommerce'),
                    '8' => __('x8', 'eupago-for-woocommerce'),
                    '9' => __('x9', 'eupago-for-woocommerce'),
                    '10' => __('x10', 'eupago-for-woocommerce'),
                    '11' => __('x11', 'eupago-for-woocommerce'),
                    '12' => __('x12', 'eupago-for-woocommerce'),
                ],
            ];
            // Traduzir com base no idioma selecionado
            if ($admin_language === 'pt_PT' || $admin_language === 'pt_BR') {
                $texts = [
                    'enable_disable' => 'Ativar/Desativar',
                    'enable_label2' => 'Ativar',
                    'payment_hold' => __('Confirmação SMS dos detalhes de Pagamento:', 'eupago-gateway-for-woocommerce'),
                    'payment_confirmation' => __('SMS Confirmação do Pagamento:', 'eupago-gateway-for-woocommerce'),
                    'enable_label' => 'Ativar CofidisPay (usando Eupago)',
                    'payment_confirmation' => __('Confirmação do pagamento por SMS:', 'eupago-gateway-for-woocommerce'),
                    'sms_order_confirmation' => __('Confirmação de Pedido por SMS:', 'eupago-gateway-for-woocommerce'),
                    'title' => 'Título',
                    'title_description' => 'Controla o título que o usuário vê durante o checkout.',
                    'title_default' => 'Cofidis Pay',
                    'instructions' => 'Instruções',
                    'instructions_description' => 'Instruções que serão adicionadas à página de agradecimento e ao e-mail enviado ao cliente.',
                    'only_portugal' => 'Apenas para clientes portugueses?',
                    'only_portugal_label' => 'Ativar apenas para clientes cujo endereço é em Portugal',
                    'only_above' => 'Apenas para pedidos acima de',
                    'only_above_description' => 'Ativar apenas para pedidos acima de x € (exclusivo). Deixe em branco (ou zero) para permitir qualquer valor de pedido. O valor do pedido deve estar dentro dos limites estabelecidos pela Cofidis.',
                    'only_below' => 'Apenas para pedidos abaixo de',
                    'only_below_description' => 'Ativar apenas para pedidos abaixo de x € (exclusivo). Deixe em branco (ou zero) para permitir qualquer valor de pedido. O valor do pedido deve estar dentro dos limites estabelecidos pela Cofidis.',
                    'reduce_stock' => 'Reduzir o stock',
                    'reduce_stock_description' => 'Escolha quando reduzir o stock.',
                    'when_order_is_paid' => __('quando o pedido é pago (requer callback ativo)', 'eupago-for-woocommerce'),
                    'when_order_is_placed' => __('quando o pedido é feito (antes do pagamento)', 'eupago-for-woocommerce'),
                    'zero_tax_code' => 'Código para Imposto Zero',
                    'zero_tax_code_description' => 'Selecione a justificação do código para habilitar o imposto zero, mantenha desativado para o padrão.',
                    'zero_tax_options' => [
                        'null' => 'Desativar justificação de imposto zero',
                        'M01' => 'M01 - Artigo 16º nº 6 do CIVA.',
                        'M02' => 'M02 - Artigo 6º do Decreto-Lei nº 198/90, de 19 de Junho.',
                        'M03' => 'M03 - Exigibilidade de caixa.',
                        'M04' => 'M04 - Isento Artigo 13º do CIVA.',
                        'M05' => 'M05 - Isento Artigo 14º do CIVA.',
                        'M06' => 'M06 - Isento Artigo 15º do CIVA.',
                        'M07' => 'M07 - Isento Artigo 9º do CIVA.',
                        'M08' => 'M08 - IVA é autoliquidação.',
                        'M09' => 'M09 - IVA - não confere direito à dedução.',
                        'M10' => 'M10 - IVA é Regime de Isenção.',
                        'M11' => 'M11 - Regime especial de tabaco.',
                        'M12' => 'M12 - Esquema de margem de lucro - Agências de viagens.',
                        'M13' => 'M13 - Esquema de margem de lucro - Artigos de segunda mão.',
                        'M14' => 'M14 - Esquema de margem de lucro - Obras de arte.',
                        'M15' => 'M15 - Esquema de margem de lucro - Itens de coleção e antiguidades.',
                        'M16' => 'M16 - Isento Artigo 14º do RITI.',
                        'M19' => 'M19 - Outras isenções',
                        'M20' => 'M20 - IVA - regime de taxa fixa',
                        'M21' => 'M21 - IVA - não confere direito à dedução (ou expressão similar)',
                        'M25' => 'M25 - Mercadorias em consignação',
                        'M26' => 'M26 - Isenção de IVA com direito à dedução na cesta de alimentos',
                        'M30' => 'M30 - IVA é autoliquidação.',
                        'M31' => 'M31 - IVA é autoliquidação.',
                        'M32' => 'M32 - IVA é autoliquidação.',
                        'M33' => 'M33 - IVA é autoliquidação.',
                        'M34' => 'M34 - IVA é autoliquidação.',
                        'M40' => 'M40 - IVA é autoliquidação.',
                        'M41' => 'M41 - IVA é autoliquidação.',
                        'M42' => 'M42 - IVA é autoliquidação.',
                        'M43' => 'M43 - IVA é autoliquidação.',
                        'M99' => 'M99 - Não sujeito; não tributado (ou similar).',

                    ],
                    'max_installments' => 'Valor Máximo de Parcelas',
                    'max_installments_description' => 'Altere o texto para Cofidis Pay para exibir um valor máximo de parcelas.',
                    'installment_options' => [
                        '0' => 'Desativar esta opção',
                        '3' => 'x3',
                        '4' => 'x4',
                        '5' => 'x5',
                        '6' => 'x6',
                        '7' => __('x7', 'eupago-for-woocommerce'),
                        '8' => __('x8', 'eupago-for-woocommerce'),
                        '9' => __('x9', 'eupago-for-woocommerce'),
                        '10' => __('x10', 'eupago-for-woocommerce'),
                        '11' => __('x11', 'eupago-for-woocommerce'),
                        '12' => __('x12', 'eupago-for-woocommerce'),
                    ],
                ];
                
                
            } elseif ($admin_language ==='es_ES') {
                $texts = [
                    'enable_disable' => 'Activar/Desactivar',
                    'enable_label' => 'Activar CofidisPay (usando Eupago)',
                    'enable_label2' => 'Activar',
                    'payment_confirmation' => __('Confirmación de pago SMS:', 'eupago-gateway-for-woocommerce'),
                    'sms_order_confirmation' => __('Confirmación del Pedido SMS:', 'eupago-gateway-for-woocommerce'),
                    'payment_hold' => __('Pago SMS en espera:', 'eupago-gateway-for-woocommerce'),
                    'title' => 'Título',
                    'title_description' => 'Controla el título que el usuario ve durante el proceso de pago.',
                    'title_default' => 'Cofidis Pay',
                    'instructions' => 'Instrucciones',
                    'instructions_description' => 'Instrucciones que se añadirán a la página de agradecimiento y al correo electrónico enviado al cliente.',
                    'only_portugal' => '¿Solo para clientes portugueses?',
                    'only_portugal_label' => 'Activar solo para clientes cuya dirección está en Portugal',
                    'only_above' => 'Solo para pedidos superiores a',
                    'only_above_description' => 'Activar solo para pedidos superiores a x € (exclusivo). Deje en blanco (o cero) para permitir cualquier valor de pedido. El valor del pedido debe estar dentro de los límites establecidos por Cofidis.',
                    'only_below' => 'Solo para pedidos inferiores a',
                    'only_below_description' => 'Activar solo para pedidos inferiores a x € (exclusivo). Deje en blanco (o cero) para permitir cualquier valor de pedido. El valor del pedido debe estar dentro de los límites establecidos por Cofidis.',
                    'reduce_stock' => 'Reducir el stock',
                    'reduce_stock_description' => 'Elija cuándo reducir el stock.',
                    'when_order_is_paid' => __('cuando el pedido está pagado (requiere callback activo)', 'eupago-for-woocommerce'),
                    'when_order_is_placed' => __('cuando se realiza el pedido (antes del pago)', 'eupago-for-woocommerce'),
                    'zero_tax_code' => 'Código para Impuesto Cero',
                    'zero_tax_code_description' => 'Seleccione la justificación del código para habilitar el impuesto cero, mantenga desactivado para el valor predeterminado.',
                    'zero_tax_options' => [
                        'null' => 'Desactivar justificación de impuesto cero',
                        'M01' => 'M01 - Artículo 16º nº 6 del CIVA.',
                        'M02' => 'M02 - Artículo 6º del Decreto-Ley nº 198/90, de 19 de Junio.',
                        'M03' => 'M03 - Exigibilidad de caja.',
                        'M04' => 'M04 - Exento Artículo 13º del CIVA.',
                        'M05' => 'M05 - Exento Artículo 14º del CIVA.',
                        'M06' => 'M06 - Exento Artículo 15º del CIVA.',
                        'M07' => 'M07 - Exento Artículo 9º del CIVA.',
                        'M08' => 'El IVA es autoretenido.',
                        'M09' => 'M09 - IVA - no confiere derecho a deducción.',
                        'M10' => 'M10 - IVA es Régimen de Exención.',
                        'M11' => 'M11 - Régimen especial de tabaco.',
                        'M12' => 'M12 - Esquema de margen de beneficio - Agencias de viajes.',
                        'M13' => 'M13 - Esquema de margen de beneficio - Artículos de segunda mano.',
                        'M14' => 'M14 - Esquema de margen de beneficio - Obras de arte.',
                        'M15' => 'M15 - Esquema de margen de beneficio - Artículos de colección y antigüedades.',
                        'M16' => 'M16 - Exento Artículo 14º del RITI.',
                        'M19' => 'M19 - Otras exenciones',
                        'M20' => 'M20 - IVA - régimen de tasa fija',
                        'M21' => 'M21 - IVA - no confiere derecho a deducción (o expresión similar)',
                        'M25' => 'M25 - Mercancías en consignación',
                        'M26' => 'M26 - Exención de IVA con derecho a deducción en la cesta de alimentos',
                        'M30' => 'M30 - El IVA es autoretenido.',
                        'M31' => 'M31 - El IVA es autoretenido.',
                        'M32' => 'M32 - El IVA es autoretenido.',
                        'M33' => 'M33 - El IVA es autoretenido.',
                        'M34' => 'M34 - El IVA es autoretenido.',
                        'M40' => 'M40 - El IVA es autoretenido.',
                        'M41' => 'M41 - El IVA es autoretenido.',
                        'M42' => 'M42 - El IVA es autoretenido.',
                        'M43' => 'M43 - El IVA es autoretenido.',
                        'M99' => 'M99 - No sujeto; no gravado (o similar).',
                    ],
                    'max_installments' => 'Valor Máximo de Cuotas',
                    'max_installments_description' => 'Cambie el texto para Cofidis Pay para mostrar un valor máximo de cuotas.',
                    'installment_options' => [
                        '0' => 'Desactivar esta opción',
                        '3' => 'x3',
                        '4' => 'x4',
                        '5' => 'x5',
                        '6' => 'x6',
                        '7' => __('x7', 'eupago-for-woocommerce'),
                        '8' => __('x8', 'eupago-for-woocommerce'),
                        '9' => __('x9', 'eupago-for-woocommerce'),
                        '10' => __('x10', 'eupago-for-woocommerce'),
                        '11' => __('x11', 'eupago-for-woocommerce'),
                        '12' => __('x12', 'eupago-for-woocommerce'),
                    ],
                ];
                
            }
    
            $this->form_fields = [
                'enabled' => [
                    'title' => $texts['enable_disable'],
                    'type' => 'checkbox',
                    'label' => $texts['enable_label'],
                    'default' => 'no',
                ],
                'title' => [
                    'title' => $texts['title'],
                    'type' => 'text',
                    'description' => $texts['title_description'],
                    'default' => $texts['title_default'],
                ],
                'instructions' => [
                    'title' => $texts['instructions'],
                    'type' => 'textarea',
                    'description' => $texts['instructions_description'],
                ],
                'only_portugal' => [
                    'title' => $texts['only_portugal'],
                    'type' => 'checkbox',
                    'label' => $texts['only_portugal_label'],
                    'default' => 'no',
                ],
                'only_above' => [
                    'title' => $texts['only_above'],
                    'type' => 'number',
                    'description' => $texts['only_above_description'],
                    'default' => '60',
                    'custom_attributes' => [
                        'min' => 60,
                        'max' => 2500,
                    ],
                ],
                'only_below' => [
                    'title' => $texts['only_below'],
                    'type' => 'number',
                    'description' => $texts['only_below_description'],
                    'default' => '2500',
                    'custom_attributes' => [
                        'min' => 60,
                        'max' => 2500,
                    ],
                ],
                'stock_when' => [
                    'title' => $texts['reduce_stock'],
                    'type' => 'select',
                    'description' => $texts['reduce_stock_description'],
                    'default' => '',
                    'options' => [
                        '' => $texts['when_order_is_paid'],
                        'order' => $texts['when_order_is_placed'],
                    ],
                ],
                'zero_tax_code' => [
                    'title' => $texts['zero_tax_code'],
                    'type' => 'select',
                    'description' => $texts['zero_tax_code_description'],
                    'default' => '',
                    'options' => $texts['zero_tax_options'],
                ],
                'max_installments' => [
                    'title' => $texts['max_installments'],
                    'type' => 'select',
                    'description' => $texts['max_installments_description'],
                    'default' => '',
                    'options' => $texts['installment_options'],
                ],
                'sms_payment_hold_cofidis' => [
                    'title' => $texts['payment_hold'],
                    'type' => 'checkbox',
                    'label' => $texts['enable_label2'],
                    'default' => 'no',
                ],
                'sms_payment_confirmation_cofidis' => [
                    'title' => $texts['payment_confirmation'],
                    'type' => 'checkbox',
                    'label' =>  $texts['enable_label2'],
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

        public function check_order_errors($order_id)
        {
            $order = wc_get_order($order_id);
            $order_total = version_compare(WC_VERSION, '3.0', '>=') ? $order->get_total() : $order->order_total;

            // A loja não está em Euros
            if (trim(get_woocommerce_currency()) != 'EUR') {
                return __('Configuration error. This store currency is not Euros (&euro;).', 'eupago-for-woocommerce');
            }

            // O valor da encomenda não é aceita
            if (($order_total < 1) || ($order_total >= 1000000)) {
                return __('It\'s not possible to use CofidisPay to pay values under 1&euro; or above 999999&euro;.', 'eupago-for-woocommerce');
            }

            if (!isset($_POST['nif']) || empty($_POST['nif'])) {
                return __('Por favor, insira um NIF válido para prosseguir com o pagamento!', 'eupago-for-woocommerce');
            }

            return false;
        }

        /**
         * Thank You page message.
         *
         * @param  int    $order_id Order ID.
         *
         * @return string
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
                  'referencia' => $order->get_meta('_eupago_cofidispay_referencia', true),
                  'order_total' => $order_total,
                ], 'woocommerce/eupago/', (new WC_Eupago())->get_templates_path());
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
                  'referencia' => $order->get_meta('_eupago_cofidispay_referencia', true),
                  'order_total' => $order_total,
                ], 'woocommerce/eupago/', (new WC_Eupago())->get_templates_path());
            } else {
                wc_get_template('emails/html-instructions.php', [
                  'method' => $payment_method,
                  'payment_name' => (function_exists('icl_object_id') ? icl_t($this->id, $this->id . '_title', $this->title) : $this->title),
                  'instructions' => isset($this->instructions) && !empty($this->instructions) ? $this->instructions : '',
                  'referencia' => $order->get_meta('_eupago_cofidispay_referencia', true),
                  'order_total' => $order_total,
                ], 'woocommerce/eupago/', (new WC_Eupago())->get_templates_path());
            }
        }

        public function payment_fields()
        {
            // if ($description = $this->get_description()) {
            //   echo wpautop(wptexturize($description));
            // }

            echo '<p>' . __('Será redirecionado para uma página segura a fim de efetuar o pagamento.<br/>O pagamento das prestações com 0% de juros e encargos serão efetuado no cartão de débito ou crédito do cliente através da solução de pagamento assente em contrato de factoring entre a Cofidis e o comerciante. Informe-se na <a href="https://www.cofidis.pt/cofidispay" target="_blank">Cofidis</a>, registada no Banco de Portugal com o N. 921.', 'eupago-for-woocommerce') . '</p>';
            $this->cofidispay_form();
        }

        public function cofidispay_form()
        {
            ?>
      <fieldset id="wc-<?php echo esc_attr($this->id); ?>-cofidispay-form" class="wc-cofidispay-form wc-payment-form" style="background:transparent;">
        <p class="form-row form-row-first">
            <label for="nif"><?php esc_html_e('Número de identificação fiscal', 'eupago-for-woocommerce'); ?></label>
            <input type="text" id="nif" autocorrect="off" spellcheck="false" name="nif" class="input-text" aria-label="<?php _e('Número de identificação fiscal', 'eupago-for-woocommerce'); ?>" aria-placeholder="" aria-invalid="false" required />
        </p>
        <div class="clear"></div>
      </fieldset>
      <?php
        }

        /**
         * Process it
         */
        public function process_payment($order_id)
        {
            global $woocommerce;
            // $order = new WC_Order($order_id);
            $order = wc_get_order($order_id);

            $order_total = version_compare(WC_VERSION, '3.0', '>=') ? $order->get_total() : $order->order_total;
            $cofidispay_vat_number = isset($_POST['nif']) && !empty($_POST['nif']) ? $_POST['nif'] : '';

            $order->update_meta_data('_eupago_cofidis_vat_number', $cofidispay_vat_number);
            $order->save();

            if ($error_message = $this->check_order_errors($order_id)) {
                wc_add_notice($error_message, 'error');
                return;
            }

            $pedidoCofidis = $this->client->cofidispay_create($order_id);

            if ($pedidoCofidis->transactionStatus != 'Success') {
                wc_add_notice(__('Payment error:', 'eupago-for-woocommerce') . ' Ocorreu um erro com o pedido de pagamento', 'error');

                return;
            }

            // update_post_meta($order_id, '_eupago_cofidispay_transactionID', $pedidoCofidis->transactionID);
            // update_post_meta($order_id, '_eupago_cofidispay_referencia', $pedidoCofidis->reference);
            // update_post_meta($order_id, '_eupago_cofidispay_redirectUrl', $pedidoCofidis->redirectUrl);

            $order->update_meta_data('_eupago_cofidispay_transactionID', $pedidoCofidis->transactionID);
            $order->update_meta_data('_eupago_cofidispay_referencia', $pedidoCofidis->reference);
            $order->update_meta_data('_eupago_cofidispay_redirectUrl', $pedidoCofidis->redirectUrl);

            $order->save();
            // Mark as on-hold
            // $order->update_status('pending', __('Awaiting CofidisPay payment.', 'eupago-for-woocommerce'));

            // Reduce stock levels
            if ($this->stock_when == 'order') {
                $order->reduce_order_stock();
            }

            // Remove cart
            $woocommerce->cart->empty_cart();

            // Empty awaiting payment session
            if (isset($_SESSION['order_awaiting_payment'])) {
                unset($_SESSION['order_awaiting_payment']);
            }

            if (file_exists(plugin_dir_path(__FILE__) . 'hooks/hooks-sms.php') && $this->get_option('sms_payment_hold_cofidis') === 'yes') {
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
              'redirect' => $pedidoCofidis->redirectUrl,
            ];
        }

        /**
         * Just for Portugal
         */
        public function disable_unless_portugal($available_gateways)
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
        }

        /**
         * Just above/below certain amounts
         */
        public function disable_only_above_or_below($available_gateways)
        {
            global $woocommerce;

            $current_price = 0;
            if (isset($available_gateways[$this->id])) {
                if (get_query_var('order-pay')) {
                    // order-pay page
                    $order = new WC_Order(get_query_var('order-pay'));
                    $current_price = floatval(preg_replace('#[^\d.]#', '', $order->get_total()));
                } else {
                    if (isset($woocommerce->cart)) {
                        $current_price = floatval(preg_replace('#[^\d.]#', '', $woocommerce->cart->total));
                    }
                }

                // CofidisPay apenas permite pagamentos entre 60 e 2500 EUR
                if ($current_price < 60 || $current_price > 2500) {
                    unset($available_gateways[$this->id]);
                }

                if (@floatval($available_gateways[$this->id]->only_above) > 0) {
                    if ($current_price < floatval($available_gateways[$this->id]->only_above)) {
                        unset($available_gateways[$this->id]);
                    }
                }

                if (@floatval($available_gateways[$this->id]->only_below) > 0) {
                    if ($current_price > floatval($available_gateways[$this->id]->only_below)) {
                        unset($available_gateways[$this->id]);
                    }
                }
            }

            return $available_gateways;
        }

        /* Payment complete - Stolen from PayPal method */
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

        public function change_title($available_gateways)
        {
            global $woocommerce;

            if (isset($available_gateways[$this->id])) {
                if (get_query_var('order-pay')) {
                    // order-pay page
                    $order = new WC_Order(get_query_var('order-pay'));
                    $order_total = floatval(preg_replace('#[^\d.]#', '', $order->get_total()));
                } else {
                    if (isset($woocommerce->cart)) {
                        $order_total = floatval(preg_replace('#[^\d.]#', '', $woocommerce->cart->total));
                    }
                }

                $this->title = 'Até ' . $this->get_numero_prestacoes($order_total) . 'x sem juros';
            }

            return $available_gateways;
        }

        public function get_numero_prestacoes($order_total)
        {
            switch (true) {
                case $order_total >= 240:
                    $number = 12;

                    break;

                case $order_total >= 220:
                    $number = 11;

                    break;

                case $order_total >= 200:
                    $number = 10;

                    break;

                case $order_total >= 180:
                    $number = 9;

                    break;

                case $order_total >= 160:
                    $number = 8;

                    break;

                case $order_total >= 140:
                    $number = 7;

                    break;

                case $order_total >= 120:
                    $number = 6;

                    break;

                case $order_total >= 100:
                    $number = 5;

                    break;

                case $order_total >= 80:
                    $number = 4;

                    break;

                default:
                    $number = 3;

                    break;
            }

            $cofidis_settings = get_option('woocommerce_eupago_cofidispay_settings');
            $max_installments = $cofidis_settings['max_installments'];

            // Check if the MAX_INSTALLMENTS_ACTIVE configuration is enabled
            if ($max_installments > 0) {
                if ($number > $max_installments) {
                    $number = $max_installments;
                }
            }

            return $number;
        }
    } // WC_CofidisPay_Eupago
} // class_exists()
