<?php
/**
* WC Eupago API Class.
*/
class WC_Eupago_API {
  /**
  * Constructor.
  *
  * @param WC_Eupago_API
  */
  public $wc_blocks_active = false;

  public function __construct() {
    // $this->integration = new WC_Eupago_Integration;
		$this->wc_blocks_active        = class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' );

  }

  public function get_url() {
    if (get_option('eupago_endpoint') == 'sandbox') {
      return 'https://sandbox.eupago.pt/replica.eupagov20.wsdl';
    } else {
      return 'https://clientes.eupago.pt/eupagov20.wsdl';
    }
  }

  public function get_cofidis_url()
  {
    if (get_option('eupago_endpoint') == 'sandbox') {
      return 'https://sandbox.eupago.pt/api/v1.02/cofidis/create';
    } else {
      return 'https://clientes.eupago.pt/api/v1.02/cofidis/create';
    }
  }

  public function get_api_key() {
    return get_option('eupago_api_key');
  }

  public function get_failover() {
    return get_option('eupago_reminder');
  }

  /**
  * Money format.
  *
  * @param  int/float $value Value to fix.
  *
  * @return float            Fixed value.
  */
  protected function money_format( $value ) {
    return number_format( $value, 2, '.', '' );
  }

  public function getReferenciaMB($order_id, $valor, $per_dup = 0, $deadline = null) {

    if (extension_loaded('soap')) {

      $get_order = wc_get_order($order_id);
      $email = $get_order->get_billing_email();
      $phone = $get_order->get_billing_phone();

      $client = @new SoapClient($this->get_url(), array('cache_wsdl' => WSDL_CACHE_NONE));

      $args = array(
        'chave' => $this->get_api_key(),
        'valor' => $this->money_format( $valor ),
        'id' => $order_id,
        'per_dup' => $per_dup,
        "failOver" => (int)$this->get_failover(),
        'email'   => $email,
        'contacto' => (int)$phone
      );
  
      if ( isset( $deadline ) && !empty( $deadline ) ) {

        $args['data_inicio'] = date('Y-m-d');
        $args['data_fim'] = date('Y-m-d', strtotime('+' . $deadline . ' day', strtotime( $args['data_inicio'] ) ) );

        return $client->gerarReferenciaMBDL( $args );

      }

      return $client->gerarReferenciaMB( $args );
    } else {

      //$curl = curl_init();

      if ( isset( $deadline ) && !empty( $deadline ) ) {
        $body = array(
          'chave'         => $this->get_api_key(),
          'valor'         => $this->money_format($valor),
          'id'            => $order_id,
          'per_dup'       => $per_dup,
          'data_inicio'   => date('Y-m-d'),
          'data_fim'      => date('Y-m-d', strtotime('+' . $deadline . ' day', strtotime( date('Y-m-d') ) ) )
        );
      } else {
        $body = array(
          'chave'         => $this->get_api_key(),
          'valor'         => $this->money_format($valor),
          'id'            => $order_id,
          'per_dup'       => $per_dup
        );
      }

      $url = 'https://' . get_option('eupago_endpoint') . '.eupago.pt/clientes/rest_api/multibanco/create';
      $args = array(
          'body' => $body,
          'timeout'     => '60',
      );
      
      $response = wp_remote_post( $url, $args );
      $client     = wp_remote_retrieve_body( $response );

      return $client;
    }
  }

  public function getReferenciaPS($order_id, $valor) {
    if (extension_loaded('soap')) {
      $client = @new SoapClient($this->get_url(), array('cache_wsdl' => WSDL_CACHE_NONE));
      return $client->gerarReferenciaPS(array(
        "chave" => $this->get_api_key(),
        "valor" => $this->money_format($valor),
        "id" => $order_id
      ));
    } else {
      $url = 'https://' . get_option('eupago_endpoint') . '.eupago.pt/clientes/rest_api/payshop/create';
      $args = array(
          'body' => array(
            "chave" => $this->get_api_key(),
            "valor" => $this->money_format($valor),
            "id" => $order_id
          ),
          'timeout'     => '60',
      );
      
      $response = wp_remote_post( $url, $args );
      $client     = wp_remote_retrieve_body( $response );

      return $client;
    }
  }

  public function getReferenciaPQ( $order_id, $valor ) {
    $client = @new SoapClient($this->get_url(), array('cache_wsdl' => WSDL_CACHE_NONE));
    return $client->gerarReferenciaPQ(array(
      "chave" => $this->get_api_key(),
      "valor" => $this->money_format( $valor ),
      "id" => $order_id
    ));
  }

  public function getReferenciaMBW($order_id, $valor, $telefone, $countryCode) {
      $telefone = str_replace(' ', '', $telefone);
      $url = 'https://' . get_option('eupago_endpoint') . '.eupago.pt/api/v1.02/mbway/create';
      $data = array(
        'payment' => array(
            'amount' => array(
                'currency' => 'EUR',
                'value' => $valor
            ),
            "identifier" =>(string) $order_id,
            'countryCode' => $countryCode,
            'customerPhone' => $telefone,
        ),
        'customer' => array(
            'notify' => true
        )
    );
    
    $headers = array(
        'Authorization:ApiKey ' . $this->get_api_key(),
        'Accept: application/json',
        'Content-Type: application/json'
    );

      $curl = curl_init();
    
      curl_setopt($curl, CURLOPT_URL, $url);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($curl, CURLOPT_POST, true);
      curl_setopt($curl, CURLOPT_POSTFIELDS,json_encode($data));
      curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
      curl_setopt($curl, CURLOPT_TIMEOUT, 60);

      $response = curl_exec($curl);

      if (curl_errno($curl)) {
          echo 'cURL Error: ' . curl_error($curl);
      }

      curl_close($curl);
      return $response;
    }

  public function getReferenciaBizum($order_id, $valor, $successUrl, $failUrl)
  {
    $order = wc_get_order($order_id);
    $url = 'https://' . get_option('eupago_endpoint') . '.eupago.pt/api/v1.02/bizum/create';
    $data = array(
      'payment' => array(
        'amount' => array(
          'currency' => 'EUR',
          'value' => $valor
        ),
        'identifier' => (string) $order_id,
        'successUrl' => $successUrl,
        'failUrl' => $failUrl
      ),
      'customer' => array(
        'notify' => true,
        'name' => $order->get_formatted_billing_full_name(),
        'email' => $order->get_billing_email()
      )
    );

    $headers = array(
      'Authorization: ApiKey ' . $this->get_api_key(),
      'Accept: application/json',
      'Content-Type: application/json'
    );

    $curl = curl_init();

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_TIMEOUT, 60);

    $response = curl_exec($curl);

    if (curl_errno($curl)) {
      echo 'cURL Error: ' . curl_error($curl);
    }

    curl_close($curl);
    return $response;
  }

  public function pedidoCC($order, $valor, $logo_url, $return_url, $lang, $comment) {
    if (extension_loaded('soap')) {
      $client = @new SoapClient($this->get_url(), array('cache_wsdl' => WSDL_CACHE_NONE));
      return $client->pedidoCC(array(
        'chave' => $this->get_api_key(),
        'valor' => $this->money_format( $valor ),
        'id' => $order->get_id(),
        'url_logotipo' => $logo_url,
        'url_retorno' => $return_url,
        'nome' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
        'email' => $order->get_billing_email(),
        'lang' => $lang,
        'comentario' => $comment,
        'tds' => 1
      ));
  }else{
    $url = 'https://' . get_option('eupago_endpoint') . '.eupago.pt/api/v1.02/creditcard/create';
      $data = array(
          'payment' => array(
              'amount' => array(
                  'value' => $valor,
                  'currency' => 'EUR'
              ),
              'identifier' =>(string) $order->get_id(),
              'lang' => $lang,
              'successUrl' => $return_url,
              'failUrl' => $return_url,
              'backUrl' => $return_url
          ),
          'customer' => array(
              'notify' => true,
              'email' => $order->get_billing_email()
          )
      );

      $headers = array(
          'Content-Type: application/json',
          'Accept: application/json',
          'Authorization: ApiKey ' . $this->get_api_key(),
      );

      $curl = curl_init();

      curl_setopt($curl, CURLOPT_URL, $url);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($curl, CURLOPT_POST, true);
      curl_setopt($curl, CURLOPT_POSTFIELDS,json_encode($data));
      curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
      curl_setopt($curl, CURLOPT_TIMEOUT, 60);

      $response = curl_exec($curl);

      if (curl_errno($curl)) {
          echo 'cURL Error: ' . curl_error($curl);
      }

      curl_close($curl);

      return $response;
  }
}

  public function pedidoPF($order, $valor, $return_url, $comment) {
    if (extension_loaded('soap')) {
      $client = @new SoapClient($this->get_url(), array('cache_wsdl' => WSDL_CACHE_NONE));
      return $client->pedidoPF(array(
        'chave' => $this->get_api_key(),
        'valor' => $this->money_format($valor),
        'id' => $order->get_id(),
        'admin_callback' => '',
        'url_retorno' => $return_url,
        'comentario' => $comment,
      ));
    } else {

      $url = 'https://' . get_option('eupago_endpoint') . '.eupago.pt/clientes/rest_api/paysafecard/create';
      $args = array(
          'body' => array(
            'chave' => $this->get_api_key(),
            'valor' => $this->money_format($valor),
            'id' => $order->get_id(),
            'admin_callback' => '',
            'url_retorno' => $return_url,
            'comentario' => $comment,
          ),
          'timeout'     => '60',
      );
      
      $response = wp_remote_post( $url, $args );
      $client     = wp_remote_retrieve_body( $response );

      return $client;
    }
  }

  public function pedidoPSC($order, $valor, $return_url, $lang, $comment) {
      $client = @new SoapClient($this->get_url(), array('cache_wsdl' => WSDL_CACHE_NONE));
      return $client->pedidoPSC(array(
        'chave' => $this->get_api_key(),
        'valor' => $this->money_format($valor),
        'id' => $order->get_id(),
        'url_retorno' => $return_url,
        'comentario' => $comment,
        'admin_callback' => '',
        'nome' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
        'email' => $order->get_billing_email(),
        'lang' => $lang,
      ));
  }

  public function cofidispay_create($order_id){

    $nonce = wp_create_nonce('wp_rest');

    $order = wc_get_order( $order_id );

    $tax_code_string = get_option('woocommerce_eupago_cofidispay_settings');
    $code = $tax_code_string['zero_tax_code'];

    $data = [
      'payment' => [
        'identifier' => $order->get_order_number(),
        'amount' => [
          'value' => $order->get_total(),
          'currency' => 'EUR'
        ],
        'successUrl' => $order->get_checkout_order_received_url(),
        'failUrl' => $order->get_checkout_payment_url(),
      ],
      'customer' => [
        'notify' => false,
        'email' => $order->get_billing_email(),
        'name' => $order->get_formatted_billing_full_name(),
        'vatNumber' => $order->get_meta('_eupago_cofidis_vat_number', true),
        'phoneNumber' => $order->get_billing_phone(),
        'billingAddress' => [
          'address' => $order->get_billing_address_1() . ' ' . $order->get_billing_address_2(),
          'zipCode' => $order->get_billing_postcode(),
          'city' => $order->get_billing_city(),
        ],
      ],
      'items' => [],
      'taxCode' => $code,
    ];

    $tax = new WC_Tax();
    foreach ($order->get_items() as $item) {
      $product_variation_id = $item['variation_id'];
  
      // Check if product has variation.
      if ($product_variation_id) {
        $_product = wc_get_product($item['variation_id']);
      } else {
        $_product = wc_get_product($item['product_id']);
      }

      // Get SKU
      $item_sku = $_product->get_sku();
      
      //For taxes
      $taxes = $tax->get_rates($item->get_tax_class());
      $rates = array_shift($taxes);

      //Take only the item rate and round it.  NULL is for default tax
      ($rates == NULL ? $item_rate = 23 : $item_rate = round(array_shift($rates)));

      $data['items'][] = [
        'reference' => $item_sku,
        'price' => (float) $item->get_total() / $item->get_quantity(),
        'quantity' => $item->get_quantity(),
        'tax' => $item_rate,
        'discount' => 0,
        'description' => $item->get_name(),
      ];
    }


    $portes = 0;

    $portes = $order->get_shipping_total() + $order->get_shipping_tax();

    foreach ($order->fee_lines as $fee_item) {
      $portes += $fee_item->total;
    }

    if ($portes > 0) {
       $data['items'][] = [
        'reference' => 'PORTES',
        'price' => $portes,
        'quantity' => 1,
        'tax' => 23,
        'discount' => 0,
        'description' => 'Custos de expedição',
      ];
    }


    $response = wp_remote_request(
        $this->get_cofidis_url(),
        [
            'method' => 'POST',
            'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.8; rv:20.0) Gecko/20100101 Firefox/20.0',
            'headers' => [
                'Content-Type' => 'application/json',
                'Cache-Control' => 'no-cache',
                'X-WP-Nonce' => $nonce,
                'Authorization' => 'ApiKey ' . $this->get_api_key(),
            ],
            'body' => json_encode($data),
        ]
    );

    $response_body = wp_remote_retrieve_body($response);

    return json_decode($response_body);
  }

  

}