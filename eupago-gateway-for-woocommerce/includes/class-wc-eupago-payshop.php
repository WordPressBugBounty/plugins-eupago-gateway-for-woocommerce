<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
* Eupago - PayShop
*
* @since 0.1
*/
if ( !class_exists( 'WC_Eupago_PayShop' ) ) {
  class WC_EuPago_PayShop extends WC_Payment_Gateway {

    /**
    * Constructor for your payment class
    *
    * @access public
    * @return void
    */
    protected $instructions;
    protected $only_portugal;
    protected $only_above;
    protected $only_below;
    protected $stock_when;
    protected $sms_payment_hold_payshop;
    protected $sms_payment_confirmation_payshop;
    protected $sms_order_confirmation_payshop;
    protected $client;
    
    public function __construct() {

      global $woocommerce;
      $this->id = 'eupago_payshop';

      $this->icon = plugins_url('assets/images/payshop_icon.png', dirname(__FILE__));
      $this->has_fields = false;
      $this->method_title = __('PayShop (Eupago)', 'eupago-gateway-for-woocommerce');

      //Plugin options and settings
      $this->init_form_fields();
      $this->init_settings();

      //User settings
      $this->title = $this->get_option('title');
      $this->description = $this->get_option('description');
      $this->instructions = $this->get_option('instructions');
      $this->only_portugal = $this->get_option('only_portugal');
      $this->only_above = $this->get_option('only_above');
      $this->only_below = $this->get_option('only_below');
      $this->stock_when = $this->get_option('stock_when');
      $this->sms_payment_hold_payshop = $this->get_option('sms_payment_hold_payshop');
      $this->sms_payment_confirmation_payshop = $this->get_option('sms_payment_confirmation_payshop');
      $this->sms_order_confirmation_payshop = $this->get_option('sms_order_confirmation_payshop');

      // Set the API.
      $this->client = new WC_Eupago_API( $this );

      // Actions and filters
      add_action('woocommerce_update_options_payment_gateways_'.$this->id, array($this, 'process_admin_options'));
      //add_action('woocommerce_order_status_on-hold', array($this, 'send_sms_pending_payshop'));
      //dd_action('woocommerce_order_status_processing', array($this, 'send_sms_processing_payshop'));
      //add_action('woocommerce_order_status_completed', array($this, 'send_sms_completed_payshop'));

      if (function_exists('icl_object_id') && function_exists('icl_register_string')) add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'register_wpml_strings'));
      add_action('woocommerce_thankyou_'.$this->id, array($this, 'thankyou_page'));
      add_action('woocommerce_order_details_after_order_table', array( $this, 'order_details_after_order_table' ), 20 );

      add_filter('woocommerce_available_payment_gateways', array($this, 'disable_unless_portugal'));
      add_filter('woocommerce_available_payment_gateways', array($this, 'disable_only_above_or_below'));

      // APG SMS Notifications Integration
      // https://wordpress.org/plugins/woocommerce-apg-sms-notifications/
      // add_filter('apg_sms_message', array($this, 'sms_instructions_apg'), 10, 2);

      // Customer Emails
      add_action('woocommerce_email_before_order_table', array($this, 'email_instructions'), 10, 2);

      // Filter to decide if payment_complete reduces stock, or not
      add_filter('woocommerce_payment_complete_reduce_order_stock', array($this, 'woocommerce_payment_complete_reduce_order_stock'), 10, 2);

    }

    /**
    * WPML compatibility
    */
    function register_wpml_strings() {
      //These are already registered by WooCommerce Multilingual
      /*$to_register=array('title','description',);*/
      $to_register = array();
      foreach($to_register as $string) {
        icl_register_string($this->id, $this->id.'_'.$string, $this->settings[$string]);
      }
    }

    /**
    * Initialise Gateway Settings Form Fields
    */
    function init_form_fields() {
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
    $enable_disable_title = __('Enable/Disable', 'eupago-gateway-for-woocommerce');
    $texto_enable = esc_html__('Enable', 'eupago-gateway-for-woocommerce');
    $title_payshop = __('Title','eupago-gateway-for-woocommerce');
    $sms_order_confirmation = esc_html('SMS Order Confirmation:', 'eupago-gateway-for-woocommerce');
    $instructions_text = __('Instructions', 'eupago-gateway-for-woocommerce');
    $description_instructions_text = __('Use this field to enter instructions that will be added to the order confirmation page and in the email sent to the customer.','eupago-gateway-for-woocommerce');
    $duplicated_payments_text = __('Duplicate payments', 'eupago-gateway-for-woocommerce');
    $controls_checkout = __('Use this field to define the title that the user sees during the checkout process.','eupago-gateway-for-woocommerce');
    $allow_duplicated_text = __('Allow duplicate payments.', 'eupago-gateway-for-woocommerce');
    $no_text = __('No', 'eupago-gateway-for-woocommerce');
    $payshop = __('Payshop','eupago-gateway-for-woocommerce');
    $description = __('Description','eupago-gateway-for-woocommerce');
    $yes_text = __('Yes', 'eupago-gateway-for-woocommerce');
    $expired_date_text = __('Expire Date', 'eupago-gateway-for-woocommerce');
    $description_checkout = __('Use this field to define the description that the user sees during the checkout process.','eupago-gateway-for-woocommerce');
    $number_days_expired = __('Number of days to payment expire.', 'eupago-gateway-for-woocommerce');
    $only_portuguese = __('Only for Portuguese customers?', 'eupago-gateway-for-woocommerce');
    $address_portuguese = __('Enable only for customers whose address is in Portugal', 'eupago-gateway-for-woocommerce');
    $orders_above = __('Only for orders above', 'eupago-gateway-for-woocommerce');
    $orders_description = __('Activate only for orders over X € (exclusive). Leave blank (or zero) to allow any order value. The order value must fall within the limits set by Payshop.', 'eupago-gateway-for-woocommerce');
    $orders_below = __('Only for orders below', 'eupago-gateway-for-woocommerce');
    $orders_below_description = __('Activate only for orders over X € (exclusive). Leave blank (or zero) to allow any order value. The order value must fall within the limits set by Payshop.', 'eupago-gateway-for-woocommerce');
    $reduce_stock = __('Reduce stock', 'eupago-gateway-for-woocommerce');
    $choose_reduce_stock = __('Choose when to reduce stock.', 'eupago-gateway-for-woocommerce');
    $when_order_pays = __('when order is paid (requires active callback)', 'eupago-gateway-for-woocommerce');
    $when_order_placed =  __('when order is placed (before payment)', 'eupago-gateway-for-woocommerce');
    $enable_payshop = __('Enable Payshop','eupago-gateway-for-woocommerce');
    $payment_confirmation = esc_html__('Payment Confirmation by SMS', 'eupago-gateway-for-woocommerce');
    // Translate title based on the selected language
    if ($admin_language === 'pt_PT' || $admin_language === 'pt_BR') {
        $enable_disable_title = __('Ativar/Desativar', 'eupago-gateway-for-woocommerce');
        $enable_payshop = __('Ativar Payshop','eupago-gateway-for-woocommerce');
        $title_payshop = __('Título','eupago-gateway-for-woocommerce');
        $description_checkout = __('Utilize este campo para definir a descrição que o utilizador vê durante o processo de pagamento.','eupago-gateway-for-woocommerce');
        $controls_checkout = __('Utilize este campo para definir o título que o utilizador vê durante o processo de pagamento.','eupago-gateway-for-woocommerce');
        $description_instructions_text = __('Utilize este campo para inserir as instruções que serão adicionadas na página de confirmação de encomenda e no email enviado ao cliente.', 'eupago-gateway-for-woocommerce');
        $payshop = __('Payshop','eupago-gateway-for-woocommerce');
        $description = __('Descrição','eupago-gateway-for-woocommerce');
        $texto_enable = 'Ativar';
        $payment_on_hold = esc_html__('Envio de SMS dos detalhes de pagamento:', 'eupago-gateway-for-woocommerce');
        $payment_confirmation = esc_html__('Confirmação do pagamento por SMS:', 'eupago-gateway-for-woocommerce');
        $sms_order_confirmation = esc_html__('Confirmação de Pedido por SMS:', 'eupago-gateway-for-woocommerce');
        $address_portuguese = __('Ativar apenas para os clientes cujo endereço é em Portugal', 'eupago-gateway-for-woocommerce');
        $only_portuguese = __('Apenas para clientes portugueses?', 'eupago-gateway-for-woocommerce');
        $orders_above = __('Apenas para encomendas acima de', 'eupago-gateway-for-woocommerce');
        $orders_below = __('Apenas para encomendas abaixo de', 'eupago-gateway-for-woocommerce');

        $orders_description = __('Ativar apenas para encomendas acima de x € (exclusivo). Deixe em branco (ou zero) para permitir qualquer valor de encomenda.', 'eupago-gateway-for-woocommerce') . ' ' . __('Por definição, o Payshop apenas permite pagamentos de 1 a 999999 € (inclusive). Pode usar esta opção para limitar ainda mais este intervalo.', 'eupago-gateway-for-woocommerce');
        $orders_below_description = __('Ativar apenas para encomendas abaixo de x € (exclusivo). Deixe em branco (ou zero) para permitir qualquer valor de encomenda.', 'eupago-gateway-for-woocommerce') . '  ' . __('Por definição, o Payshop apenas permite pagamentos de 1 a 999999 € (inclusive). Pode usar esta opção para limitar ainda mais este intervalo.', 'eupago-gateway-for-woocommerce');
        $instructions_text = __('Instruções', 'eupago-gateway-for-woocommerce');
        $reduce_stock = __('Reduzir stock', 'eupago-gateway-for-woocommerce');

        $choose_reduce_stock = __('Escolha quando reduzir o stock.', 'eupago-gateway-for-woocommerce');

        $when_order_pays = __('quando a encomenda é paga (requer callback ativo)', 'eupago-gateway-for-woocommerce');
        $when_order_placed =  __('quando a encomenda é feita (antes do pagamento)', 'eupago-gateway-for-woocommerce');
    } elseif ($admin_language === 'es_ES') {
        $enable_disable_title = __('Activar/Desactivar', 'eupago-gateway-for-woocommerce');
        $enable_payshop = __('Activar Payshop','eupago-gateway-for-woocommerce');
        $instructions_text = __('Instrucciones', 'eupago-gateway-for-woocommerce');
        $description = __('Descripción','eupago-gateway-for-woocommerce');
        $description_instructions_text = __('Utilice este campo para ingresar instrucciones que se agregarán a la página de confirmación del pedido y al correo electrónico enviado al cliente.', 'eupago-gateway-for-woocommerce');
        $duplicated_payments_text = __('Pagos duplicados', 'eupago-gateway-for-woocommerce');
        $controls_checkout = __('Utilice este campo para definir el título que ve el usuario durante el proceso de pago.','eupago-gateway-for-woocommerce');
        $allow_duplicated_text = __('Permitir pagos duplicados.', 'eupago-gateway-for-woocommerce');
        $no_text = __('No', 'eupago-gateway-for-woocommerce');
        $title_payshop = __('Título','eupago-gateway-for-woocommerce');
        $payshop = __('Payshop','eupago-gateway-for-woocommerce');
        $description_checkout = __('Utilice este campo para definir la descripción que el usuario ve durante el proceso de pago.','eupago-gateway-for-woocommerce');
        $yes_text = __('Sí', 'eupago-gateway-for-woocommerce');
        $expired_date_text = __('Fecha de vencimiento', 'eupago-gateway-for-woocommerce');
        $number_days_expired = __('Número de días para que caduque el pago.', 'eupago-gateway-for-woocommerce');
        $only_portuguese = __('¿Solo para clientes portugueses?', 'eupago-gateway-for-woocommerce');
        $address_portuguese = __('Activar sólo para clientes cuya dirección esté en Portugal.', 'eupago-gateway-for-woocommerce');
        $orders_above = __('Solo para pedidos superiores a', 'eupago-gateway-for-woocommerce');
        $orders_description = __('Activar sólo para pedidos superiores a X € (exclusivo). Déjelo en blanco (o cero) para permitir cualquier valor de pedido. El valor del pedido debe estar dentro de los límites establecidos por Payshop.', 'eupago-gateway-for-woocommerce');
        $orders_below = __('Solo para pedidos inferiores a', 'eupago-gateway-for-woocommerce');
        $orders_below_description = __('Activar sólo para pedidos superiores a X € (exclusivo). Déjelo en blanco (o cero) para permitir cualquier valor de pedido. El valor del pedido debe estar dentro de los límites establecidos por Payshop.', 'eupago-gateway-for-woocommerce');
        $reduce_stock = __('Reducir el stock', 'eupago-gateway-for-woocommerce');
        $choose_reduce_stock = __('Elegir cuándo reducir el stock.', 'eupago-gateway-for-woocommerce');
        $when_order_pays = __('cuando el pedido se paga (requiere callback activo)', 'eupago-gateway-for-woocommerce');
        $when_order_placed = __('cuando se realiza el pedido (antes del pago)', 'eupago-gateway-for-woocommerce');
        $payment_confirmation = esc_html__('Confirmación de pago SMS:', 'eupago-gateway-for-woocommerce');
        $payment_on_hold = esc_html__('Envío de SMS con los detalles de pago:', 'eupago-gateway-for-woocommerce');
        $texto_enable = 'Habilitar';
        $sms_order_confirmation = esc_html__('Confirmación de pedido SMS:', 'eupago-gateway-for-woocommerce');

    }

    $this->form_fields = [
      'enabled' => [
        'title' => esc_html($enable_disable_title),
        'type' => 'checkbox',
        'default' => 'no',
        'label' => esc_html($enable_payshop),
      ],
      'title' => [
        'title' => esc_html($title_payshop),
        'type' => 'text',
        'description' => esc_html($controls_checkout),
        'default' => esc_html__($payshop),
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
      'only_portugal' => [
        'title' => esc_html($only_portuguese),
        'type' => 'checkbox',
        'label' => esc_html($address_portuguese),
        'default' => 'no',
      ],
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
        'title' => esc_html ($reduce_stock),
        'type' => 'select',
        'description' =>esc_html ($choose_reduce_stock),
        'default' => '',
        'options' => [
          '' => esc_html($when_order_pays),
          'order' => esc_html($when_order_placed),
        ],
      ],
      'sms_payment_hold_payshop' => [
        'title' => esc_html($payment_on_hold),
        'type' => 'checkbox',
        'label' => esc_html($texto_enable),
        'default' => 'no',
      ],
      'sms_payment_confirmation_payshop' => [
        'title' => esc_html($payment_confirmation),
        'type' => 'checkbox',
        'label' => esc_html($texto_enable),
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
      $alt = (function_exists('icl_object_id') ? icl_t($this->id, $this->id.'_title', $this->title) : $this->title);
      $icon_html = '<img src="'.esc_attr($this->icon).'" alt="'.esc_attr($alt).'" />';
      return apply_filters('woocommerce_gateway_icon', $icon_html, $this->id);
    }

    /**
    * Thank You page message.
    *
    * @param  int    $order_id Order ID.
    *
    * @return string
    */
    public function thankyou_page( $order_id ) {
      $order = wc_get_order($order_id);
      $order_total = version_compare( WC_VERSION, '3.0', '>=' ) ? $order->get_total() : $order->order_total;
      $payment_method = version_compare( WC_VERSION, '3.0', '>=' ) ? $order->get_payment_method() : $order->payment_method;

      if ( $payment_method == $this->id ) {

        wc_get_template( 'payment-instructions.php', array(
          'method' => $payment_method,
          'payment_name' => (function_exists('icl_object_id') ? icl_t($this->id, $this->id.'_title', $this->title) : $this->title),
          'referencia' => $order->get_meta( '_eupago_payshop_referencia', true),
          'order_total' => $order_total,
          'instructions' => $this->instructions,
        ), 'woocommerce/eupago/', (new WC_Eupago())->get_templates_path() );

      }
    }

    /**
    *
    * View Order detail payment reference.
    */
    function order_details_after_order_table( $order ) {
      if ( is_wc_endpoint_url( 'view-order' ) ) {
        $this->thankyou_page( $order->get_id() );
      }
    }

    /**
    * Email instructions
    */
    function email_instructions($order, $sent_to_admin, $plain_text = false) {
      $order_id = version_compare( WC_VERSION, '3.0', '>=' ) ? $order->get_id() : $order->id;
      $order_total = version_compare( WC_VERSION, '3.0', '>=' ) ? $order->get_total() : $order->order_total;
      $payment_method = version_compare( WC_VERSION, '3.0', '>=' ) ? $order->get_payment_method() : $order->payment_method;

      if ($sent_to_admin || !$order->has_status( 'on-hold' ) || $this->id !== $payment_method ) {
        return;
      }

      if ($plain_text) {
        wc_get_template( 'emails/plain-instructions.php', array(
          'method' => $payment_method,
          'payment_name' => (function_exists('icl_object_id') ? icl_t($this->id, $this->id.'_title', $this->title) : $this->title),
          'instructions' => isset( $this->instructions ) && !empty( $this->instructions ) ? $this->instructions : '',
          'referencia' => $order->get_meta( '_eupago_payshop_referencia', true),
          'order_total' => $order_total,
        ), 'woocommerce/eupago/', (new WC_Eupago())->get_templates_path() );
      } else {
        wc_get_template( 'emails/html-instructions.php', array(
          'method' => $payment_method,
          'payment_name' => (function_exists('icl_object_id') ? icl_t($this->id, $this->id.'_title', $this->title) : $this->title),
          'instructions' => isset( $this->instructions ) && !empty( $this->instructions ) ? $this->instructions : '',
          'referencia' => $order->get_meta( '_eupago_payshop_referencia', true),
          'order_total' => $order_total,
        ), 'woocommerce/eupago/', (new WC_Eupago())->get_templates_path() );
      }
    }

    function check_order_errors($order_id) {
      $order = wc_get_order($order_id);
      $order_total = version_compare( WC_VERSION, '3.0', '>=' ) ? $order->get_total() : $order->order_total;


      // A loja não está em Euros
      if ( trim(get_woocommerce_currency()) != 'EUR' ) {
        return __('Configuration error. This store currency is not Euros (&euro;).', 'eupago-gateway-for-woocommerce');
      }

      //O valor da encomenda não é aceita
      if ( ($order_total < 1) || ($order_total >= 1000000) ) {
        return __('It\'s not possible to use Multibanco to pay values under 1&euro; or above 999999&euro;.', 'eupago-gateway-for-woocommerce');
      }
      return false;
    }

    /**
    * Process it
    */
    function process_payment($order_id) {
      global $woocommerce;
      // $order = new WC_Order($order_id);
      $order = wc_get_order( $order_id );

      $order_total = version_compare( WC_VERSION, '3.0', '>=' ) ? $order->get_total() : $order->order_total;
      $payment_method = version_compare( WC_VERSION, '3.0', '>=' ) ? $order->get_payment_method() : $order->payment_method;


      if ($error_message = $this->check_order_errors($order_id)) {
        wc_add_notice( __('Payment error:', 'eupago-gateway-for-woocommerce') . $error_message, 'error' );
        return;
      }

      $eupagoPayShop = $this->client->getReferenciaPS( $order_id, $order_total );

      if (extension_loaded('soap')) {
        if ( $eupagoPayShop->estado != 0 ) {
          $error_message = $eupagoPayShop->resposta;
          wc_add_notice( __('Payment error:', 'eupago-gateway-for-woocommerce') . $error_message, 'error' );
          return;
        }
      } else {
        $eupagoPayShop_decode = json_decode($eupagoPayShop, true);
        if ( $eupagoPayShop_decode['estado'] != 0 ) {
          $error_message = $eupagoPayShop_decode['resposta'];
          wc_add_notice( __('Payment error:', 'eupago-gateway-for-woocommerce') . $error_message, 'error' );
          return;
        }
      }

      if (extension_loaded('soap')) {
        // update_post_meta ($order_id, '_eupago_payshop_referencia', $eupagoPayShop->referencia);
        $order->update_meta_data( '_eupago_payshop_referencia', $eupagoPayShop->referencia);
        $order->save();
      } else {
        $eupagoPayShop_decode = json_decode($eupagoPayShop, true);
        // update_post_meta ($order_id, '_eupago_payshop_referencia', $eupagoPayShop_decode['referencia']);
        $order->update_meta_data( '_eupago_payshop_referencia', $eupagoPayShop_decode['referencia']);
        $order->save();
      }

      // Mark as on-hold
      $order->update_status('on-hold', __('Awaiting PayShop payment.', 'eupago-gateway-for-woocommerce'));

      // Reduce stock levels
      if ($this->stock_when == 'order') $order->reduce_order_stock();

      // Remove cart
      $woocommerce->cart->empty_cart();

      // Empty awaiting payment session
      if (isset($_SESSION['order_awaiting_payment'])) unset($_SESSION['order_awaiting_payment']);


      if (file_exists(plugin_dir_path(__FILE__) . 'hooks/hooks-sms.php') && $this->get_option('sms_payment_hold_payshop') === 'yes') {
        include_once(plugin_dir_path(__FILE__) . 'hooks/hooks-sms.php');
        if (function_exists('send_sms')) {
          send_sms($order_id);
        } else {
          $this->callback_log('Função send_sms_prossessing não encontrada.');
        }
      }
      
      // Return thankyou redirect
      return array(
        'result' => 'success',
        'redirect' => $this->get_return_url($order)
      );
    }

    /**
     * Just for Portugal
     */
    function disable_unless_portugal($available_gateways)
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
    function disable_only_above_or_below($available_gateways) {
      global $woocommerce;
      if (isset($available_gateways[$this->id])) {
        if (@floatval($available_gateways[$this->id]->only_above) > 0) {
          if ( $woocommerce->cart->total < floatval($available_gateways[$this->id]->only_above) ) {
            unset($available_gateways[$this->id]);
          }
        }
        if ( @floatval($available_gateways[$this->id]->only_below) > 0 ) {
          if ( $woocommerce->cart->total > floatval($available_gateways[$this->id]->only_below) ) {
            unset($available_gateways[$this->id]);
          }
        }
      }
      return $available_gateways;
    }

    /* Payment complete - Stolen from PayPal method */
    function payment_complete( $order, $txn_id = '', $note = '' ) {
      $order->add_order_note( $note );
      $order->payment_complete( $txn_id );
    }

    /* Reduce stock on 'wc_maybe_reduce_stock_levels'? */
		function woocommerce_payment_complete_reduce_order_stock( $bool, $order_id ) {
			$order = new WC_Order( $order_id );
			if ( $order->get_payment_method() == $this->id ) {
				return ( (new WC_Eupago())->woocommerce_payment_complete_reduce_order_stock( $bool, $order, $this->id, $this->stock_when ) );
			} else {
				return $bool;
			}
		}

  } // WC_Multibanco_Eupago
} // class_exists()
