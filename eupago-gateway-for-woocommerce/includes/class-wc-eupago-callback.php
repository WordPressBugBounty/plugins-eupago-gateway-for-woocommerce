<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
* WC Eupago API Class.
*/
class WC_Eupago_Callback {
   /**
  * Instance of this class.
  *
  * @var object
  */
  protected static $instance = null;


  /**
  * Constructor.
  *
  * @param WC_Eupago_Gateway $gateway
  */
  public function __construct() {
    $this->id = 'eupago-gateway-for-woocommerce';

    $this->integration = new WC_Eupago_Integration();
    if ($this->integration->debug) $this->log = new WC_Logger();

    // Callback for old version of plugin
    add_action( 'woocommerce_api_wc_eupago_webatual', array($this, 'callback_handler') );

    // Callback for new versions of plugin
    add_action( 'woocommerce_api_wc_eupago', array($this, 'callback_handler') );
  }



  function callback_log($message, $error = false) {

    if ( $error == true ) {
      $title = __('Error', 'eupago-gateway-for-woocommerce');
      $response = 500;
    } else {
      $title = __('Success', 'eupago-gateway-for-woocommerce');
      $response = 200;
    }

    if ($this->integration->debug) {
      $this->log->add($this->id, '- Callback ('.$_SERVER['REQUEST_URI'].') '.$_SERVER['REMOTE_ADDR'] . ' - ' . $message);
    }
    if ($this->integration->debug_email != '') {
      wp_mail($this->integration->debug_email, $this->id.' - Error: Callback with missing arguments', 'Callback ( '.$_SERVER['HTTP_HOST'].' '.$_SERVER['REQUEST_URI'].' ) with missing arguments from '.$_SERVER['REMOTE_ADDR'] . $message);
    }

    wp_die($message, $title, array('response' => $response));

  }

  public function callback_handler() {
    $errors = array();

    // Verificar CHAVE API
    if ( sanitize_text_field(!isset( $_GET['chave_api'] )) || empty( sanitize_text_field($_GET['chave_api']) ) || sanitize_text_field($_GET['chave_api']) != $this->integration->get_api() ) {
      $this->callback_log('erro na chave api', true);
    }

    //Confirmar Identificador
    if ( sanitize_text_field(!isset( $_GET['identificador'] )) || empty( sanitize_text_field($_GET['identificador']) ) ) {
      $this->callback_log('Identificador Vazio', true);
    } elseif ( 'shop_order' != get_post_type( sanitize_text_field($_GET['identificador']) ) ) { // verifica se pertence a uma encomenda
      $this->callback_log('O ID não pertence a uma encomenda', true);
    }

    // object da encomenda
    $order = new WC_Order( sanitize_text_field($_GET['identificador'] ));

    // Verificar se encomenda ainda não está paga!!
    /*if ( !$order->has_status( array('on-hold', 'pending') ) ) {
      $this->callback_log('A encomenda poderá já ter sido paga.', true);
    }*/
   

    
    $order_total = version_compare( WC_VERSION, '3.0', '>=' ) ? $order->get_total() : $order->order_total;
    if ( sanitize_text_field(!isset( $_GET['valor'] )) || empty( sanitize_text_field($_GET['valor']) ) ) {
      $this->callback_log( 'Erro no valor', true );
    } else {
      if ( !($order_total == sanitize_text_field($_GET['valor'])) && !apply_filters( 'eupago_for_woocommerce_callback_value_check', false, $order, sanitize_text_field($_GET['valor']) ) ) {
        $this->callback_log('Erro no Valor', true);
      }
    }

    $note = 'Pagamento Efectuado ';
    if ( sanitize_text_field(isset( $_GET['data'] ) )) $note.= '<br />Data/Hora: ' . sanitize_text_field($_GET['data']);
    if ( sanitize_text_field(isset( $_GET['local'] ) )) $note.= '<br />Local: ' . sanitize_text_field($_GET['local']);
    if ( sanitize_text_field(isset( $_GET['transacao'] )) ) $note.= '<br />Transação: ' . sanitize_text_field($_GET['transacao']);
    $order->add_order_note( $note );
    $order->payment_complete( sanitize_text_field($_GET['transacao'] ));






    if (file_exists(plugin_dir_path(__FILE__) . 'hooks/hooks-sms.php')) {
      include_once(plugin_dir_path(__FILE__) . 'hooks/hooks-sms.php');
    }
    $payment_method = $order->get_payment_method();
    $payment_gateway = WC()->payment_gateways->payment_gateways;

    switch ($payment_method) {
          case 'eupago_multibanco':
              $payment_method = $payment_gateway[4];//eupago_multibanco
              $option_key = $payment_method->settings['sms_payment_confirmation_multibanco'];//yes
              $option_text = 'sms_payment_confirmation_multibanco'; //sms_payment_confirmation_multibanco
              break;
          case 'eupago_mbway':
              $payment_method = $payment_gateway[6];//eupago_mbway
              $option_key = $payment_method->settings['sms_payment_confirmation_mbway'];//yes
              $option_text = 'sms_payment_confirmation_mbway'; //sms_payment_confirmation_mbway
              break;
          case 'eupago_payshop':
              $payment_method = $payment_gateway[5];//eupago_payshop
              $option_key = $payment_method->settings['sms_payment_confirmation_payshop'];//yes
              $option_text = 'sms_payment_confirmation_payshop'; //sms_payment_confirmation_payshop
              break;
          case 'eupago_cc':
              $payment_method = $payment_gateway[8];//eupago_cc
              $option_key = $payment_method->settings['sms_payment_confirmation_cc'];//yes
              $option_text = 'sms_payment_confirmation_cc'; //sms_payment_confirmation_cc
              break;
          case 'eupago_cofidispay':
              $payment_method = $payment_gateway[7];//eupago_cofidis
              $option_key = $payment_method->settings['sms_payment_confirmation_cofidis'];//yes
              $option_text = 'sms_payment_confirmation_cofidis'; //sms_payment_confirmation_cofidis
              break;
          case 'eupago_bizum':
              $payment_method = $payment_gateway[999];//eupago_bizum
              $option_key = $payment_method->settings['sms_payment_confirmation_bizum'];//yes
              $option_text = 'sms_payment_confirmation_bizum'; //sms_payment_confirmation_bizum
              break;
          default:
              $option_key = null;
              break;
      }
    
      if ($option_text && $option_key === 'yes') {
          if (function_exists('send_sms_processing')) {
              $order_id = isset($_GET['identificador']) ? sanitize_text_field($_GET['identificador']) : null;
              send_sms_processing($order_id);
          } else {
              $this->callback_log('Função send_sms_prossessing não encontrada.');
          }
      } else {
          $this->callback_log('Opção do método de pagamento não é igual a "yes" ou não está definida.');
      }
      $this->callback_log( 'Pagamento com Sucesso!' );
  }

  function get_gateway($gateway = null) {
    if ( isset( $gateway ) && !empty( $gateway ) ) {
      $eupago_gateways = array(
        'PC:PT' => ['eupago_multibanco'],
        'PS:PT' => ['eupago_payshop'],
        'MW:PT' => ['eupago_mbway'],
        'PQ:PT' => ['eupago_pagaqui'],
        'CC:PT' => ['eupago_cc'],
        'PSC:PT' => ['eupago_psc'],
        'PF:PT' => ['eupago_pf'],
        'CP:PT' => ['eupago_cofidispay'],
        'BZ:PT' => ['eupago_bizum']
      );
      $eupago_gateways = apply_filters( 'eupago_for_woocommerce_callback_gateways', $eupago_gateways );

      return $eupago_gateways[$gateway];
    } else {
      return false;
    }
  }

  function get_gateway_class($gateway = null) {
    if ($gateway) {
      $eupago_gateways = array(
        'eupago_multibanco' => 'WC_Eupago_Multibanco',
        'eupago_payshop' => 'WC_Eupago_PayShop',
        'eupago_mbway' => 'WC_Eupago_MBWAY',
        'eupago_pagaqui' => 'WC_Eupago_Pagaqui',
        'eupago_cc' => 'WC_Eupago_CC',
        'eupago_psc' => 'WC_Eupago_PSC',
        'eupago_pf' => 'WC_Eupago_PF',
        'eupago_codifispay' => 'WC_Eupago_CofidisPay',
        'eupago_bizum' => 'WC_Eupago_Bizum'
      );
      return $eupago_gateways[$gateway];
    } else {
      return false;
    }
  }
}
