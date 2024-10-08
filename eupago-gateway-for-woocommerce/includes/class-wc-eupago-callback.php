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
    if ( !$order->has_status( array('on-hold', 'pending') ) ) {
      $this->callback_log('A encomenda poderá já ter sido paga.', true);
    }
   

    // Confirma se existe valor e coincide com a encomenda
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
        'eupago_codifispay' => 'WC_Eupago_CofidisPay'
      );
      return $eupago_gateways[$gateway];
    } else {
      return false;
    }
  }
}
