<?php

/**
 * WC Eupago API Class.
 */
class WC_Eupago_API
{
  /**
   * Constructor.
   *
   * @param WC_Eupago_API
   */
  public $wc_blocks_active = false;

  public function __construct()
  {
    // $this->integration = new WC_Eupago_Integration;
    $this->wc_blocks_active        = class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType');
  }

  public function get_url()
  {
    if (get_option('eupago_endpoint') == 'sandbox') {
      return 'https://sandbox.eupago.pt/replica.eupagov20.wsdl';
    } else {
      return 'https://clientes.eupago.pt/eupagov20.wsdl';
    }
  }

  public function get_api_key()
  {
    return get_option('eupago_api_key');
  }

  public function get_failover()
  {
    return get_option('eupago_reminder');
  }

  /**
   * Money format.
   *
   * @param  int/float $value Value to fix.
   *
   * @return float            Fixed value.
   */
  protected function money_format($value)
  {
    return number_format($value, 2, '.', '');
  }

  /**
   * Error object in the shape the legacy API returns, so callers can treat a
   * transport failure like any other rejected request.
   */
  protected function legacy_error($message)
  {
    return (object) array(
      'sucesso'  => false,
      'estado'   => -1,
      'resposta' => $message,
    );
  }

  /**
   * Legacy REST API call (clientes/rest_api/<path>).
   *
   * @return object|null Decoded response, or null when the request itself could
   *                     not be completed (network error, non-2xx status, body
   *                     that is not a JSON object).
   */
  protected function legacy_rest_post($path, array $body)
  {
    $url = 'https://' . get_option('eupago_endpoint') . '.eupago.pt/clientes/rest_api/' . ltrim($path, '/');

    $response = wp_remote_post($url, array(
      'body'    => $body,
      'timeout' => 60,
      'headers' => array(
        'X-App-Source'   => WC_Eupago::SOURCE,
        'X-App-Version'  => WC_Eupago::VERSION,
        'X-Runtime-Info' => 'PHP ' . PHP_VERSION,
      ),
    ));

    if (is_wp_error($response)) {
      $this->log_error("REST {$path}: " . $response->get_error_message());
      return null;
    }

    $code = (int) wp_remote_retrieve_response_code($response);
    $raw  = wp_remote_retrieve_body($response);
    $data = json_decode($raw);

    if ($code < 200 || $code >= 300 || !is_object($data)) {
      $this->log_error("REST {$path}: HTTP {$code}, body: " . substr((string) $raw, 0, 500));
      return null;
    }

    return $data;
  }

  /**
   * Legacy SOAP API call. Only used when the REST request could not be completed.
   *
   * @return object|null Response object, or null when SOAP is unavailable or the call failed.
   */
  protected function legacy_soap_call($method, array $args)
  {
    if (!extension_loaded('soap')) {
      $this->log_error("SOAP {$method}: soap extension not loaded, no fallback available");
      return null;
    }

    try {
      $client = new SoapClient($this->get_url(), array('cache_wsdl' => WSDL_CACHE_NONE, 'exceptions' => true));
      $result = $client->{$method}($args);
    } catch (Throwable $e) {
      $this->log_error("SOAP {$method}: " . $e->getMessage());
      return null;
    }

    return is_object($result) ? $result : null;
  }

  /**
   * Legacy request: REST first, SOAP as fallback when REST could not be completed.
   * A response the API rejected (estado != 0) is returned as is, it is not retried.
   */
  protected function legacy_request($rest_path, $soap_method, array $args)
  {
    $response = $this->legacy_rest_post($rest_path, $args);

    if ($response === null) {
      $response = $this->legacy_soap_call($soap_method, $args);
    }

    if ($response === null) {
      return $this->legacy_error(__('Could not reach the Eupago service. Please try again.', 'eupago-gateway-for-woocommerce'));
    }

    return $response;
  }

  protected function log_error($message)
  {
    if (function_exists('wc_get_logger')) {
      wc_get_logger()->error($message, array('source' => 'eupago-api'));
    }
  }

  public function getReferenciaMB($order_id, $valor, $per_dup = 0, $deadline = null)
  {
    $order = wc_get_order($order_id);

    $args = array(
      'chave'    => $this->get_api_key(),
      'valor'    => $this->money_format($valor),
      'id'       => $order_id,
      'per_dup'  => $per_dup,
      'failOver' => (int) $this->get_failover(),
      'email'    => $order ? $order->get_billing_email() : '',
      'contacto' => $order ? (int) $order->get_billing_phone() : 0,
    );

    $soap_method = 'gerarReferenciaMB';

    if (isset($deadline) && !empty($deadline)) {
      $args['data_inicio'] = date('Y-m-d');
      $args['data_fim']    = date('Y-m-d', strtotime('+' . $deadline . ' day', strtotime($args['data_inicio'])));
      $soap_method = 'gerarReferenciaMBDL';
    }

    return $this->legacy_request('multibanco/create', $soap_method, $args);
  }

  public function getReferenciaApplePay($order, $valor, $lang, $return_url, $cancel_url)
  {
    $url = 'https://' . get_option('eupago_endpoint') . '.eupago.pt/api/v1.02/euapplepay/create';

    $data = array(
      'payment' => array(
        'amount' => array(
          'value'    => $valor,
          'currency' => 'EUR',
        ),
        'identifier'  => (string) $order->get_id(),
        'lang'        => $lang,
        'successUrl'  => $return_url,
        'failUrl'     => $cancel_url,
        'backUrl'     => $cancel_url,
      ),
      'customer' => array(
        'notify'      => false,
        'email'       => $order->get_billing_email(),
        'firstName'   => $order->get_billing_first_name(),
        'lastName'    => $order->get_billing_last_name(),
        'countryCode' => $order->get_billing_country(),
      ),
    );

    $headers = array(
      'Content-Type: application/json',
      'Accept: application/json',
      'Authorization: ApiKey ' . $this->get_api_key(),
      'X-App-Source: ' . WC_Eupago::SOURCE,
      'X-App-Version: ' . WC_Eupago::VERSION,
      'X-Runtime-Info: PHP ' . PHP_VERSION
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
      error_log('ApplePay cURL Error: ' . curl_error($curl));
      return null;
    }

    curl_close($curl);

    return json_decode($response, true);
  }

  public function getReferenciaGooglePay($order, $valor, $lang, $return_url, $cancel_url)
  {
    $url = 'https://' . get_option('eupago_endpoint') . '.eupago.pt/api/v1.02/googlepay/create';

    $data = array(
      'payment' => array(
        'amount' => array(
          'value'    => $valor,
          'currency' => 'EUR',
        ),
        'identifier'  => (string) $order->get_id(),
        'lang' => 'PT',
        'successUrl' => $return_url,
        'failUrl'    => $cancel_url,
        'backUrl'    => $cancel_url,
      ),
      'customer' => array(
        'notify'      => false,
        'email'       => $order->get_billing_email(),
        'firstName'   => $order->get_billing_first_name(),
        'lastName'    => $order->get_billing_last_name(),
        'countryCode' => $order->get_billing_country(),
      )
    );

    $headers = array(
      'Content-Type: application/json',
      'Accept: application/json',
      'Authorization: ApiKey ' . $this->get_api_key(),
      'X-App-Source: ' . WC_Eupago::SOURCE,
      'X-App-Version: ' . WC_Eupago::VERSION,
      'X-Runtime-Info: PHP ' . PHP_VERSION
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
      error_log('GPay cURL Error: ' . curl_error($curl));
      return null;
    }

    curl_close($curl);

    return json_decode($response, true);
  }

  public function getReferenciaPS($order_id, $valor)
  {
    return $this->legacy_request('payshop/create', 'gerarReferenciaPS', array(
      'chave' => $this->get_api_key(),
      'valor' => $this->money_format($valor),
      'id'    => $order_id,
    ));
  }

  public function getReferenciaPQ($order_id, $valor)
  {
    $client = @new SoapClient($this->get_url(), array('cache_wsdl' => WSDL_CACHE_NONE));
    return $client->gerarReferenciaPQ(array(
      "chave" => $this->get_api_key(),
      "valor" => $this->money_format($valor),
      "id" => $order_id
    ));
  }

  public function getReferenciaMBW($order_id, $valor, $telefone, $countryCode)
  {
    $telefone = str_replace(' ', '', $telefone);
    $url = 'https://' . get_option('eupago_endpoint') . '.eupago.pt/api/v1.02/mbway/create';
    $data = array(
      'payment' => array(
        'amount' => array(
          'currency' => 'EUR',
          'value' => $valor
        ),
        "identifier" => (string) $order_id,
        'countryCode' => $countryCode,
        'customerPhone' => $telefone,
      ),
      'customer' => array(
        'notify' => false
      )
    );

    $headers = array(
      'Authorization:ApiKey ' . $this->get_api_key(),
      'Accept: application/json',
      'Content-Type: application/json',
      'X-App-Source: ' . WC_Eupago::SOURCE,
      'X-App-Version: ' . WC_Eupago::VERSION,
      'X-Runtime-Info: PHP ' . PHP_VERSION
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
        'notify' => false,
        'name' => $order->get_formatted_billing_full_name(),
        'email' => $order->get_billing_email()
      )
    );

    $headers = array(
      'Authorization: ApiKey ' . $this->get_api_key(),
      'Accept: application/json',
      'Content-Type: application/json',
      'X-App-Source: ' . WC_Eupago::SOURCE,
      'X-App-Version: ' . WC_Eupago::VERSION,
      'X-Runtime-Info: PHP ' . PHP_VERSION
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

  public function getReferencePix($order_id, $valor)
  {
    $order = wc_get_order($order_id);
    $url = 'https://' . get_option('eupago_endpoint') . '.eupago.pt/api/v1.02/pix/create';

    $data = array(
      'payment' => array(
        'amount' => array(
          'currency' => 'EUR',
          'value' => $valor
        ),
        'identifier' => (string) $order_id
      ),
      'customer' => array(
        'notify' => false,
        //'countryCode' => '+351',
        'phoneNumber' => $order->get_billing_phone(),
        'email' => $order->get_billing_email(),
        // 'vat' => $order->get_meta('_billing_vat'),
        'name' => $order->get_formatted_billing_full_name(),
        'address' => array(
          'zipCode' => $order->get_billing_postcode(),
          'city' => $order->get_billing_city(),
          'state' => $order->get_billing_state(),
          'street' => $order->get_billing_address_1() . ' ' . $order->get_billing_address_2()
        )
      )
    );

    $headers = array(
      'Authorization: ApiKey ' . $this->get_api_key(),
      'Content-Type: application/json',
      'X-App-Source: ' . WC_Eupago::SOURCE,
      'X-App-Version: ' . WC_Eupago::VERSION,
      'X-Runtime-Info: PHP ' . PHP_VERSION
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

  /**
   * Creates a Credit Card payment through the REST API (v1.02).
   * 
   * @param WC_Order $order
   * @param float    $amount
   * @param string   $return_url Thank-you page: only a successful payment lands here.
   * @param string   $lang       PT, EN or ES.
   * @param string   $cancel_url Where the payment page sends the buyer when the payment fails
   *                             or they press "back" (WC_Eupago_Payment_Return endpoint).
   * @return array Decoded API response, or ['error' => message] when the call itself failed.
   */
  public function pedidoCC($order, $amount, $return_url, $lang, $cancel_url)
  {
    $url = 'https://' . get_option('eupago_endpoint') . '.eupago.pt/api/v1.02/creditcard/create';

    $data = [
      'payment' => [
        'amount' => [
          'value' => $this->money_format($amount),
          'currency' => 'EUR',
        ],
        'identifier' => (string) $order->get_id(),
        'lang' => strtoupper($lang),
        'successUrl' => $return_url,
        'failUrl'    => $cancel_url,
        'backUrl'    => $cancel_url,
      ],
      'customer' => [
        'notify' => false,
        'email'  => $order->get_billing_email(),
      ],
    ];

    $headers = [
      'Content-Type: application/json',
      'Accept: application/json',
      'Authorization: ApiKey ' . $this->get_api_key(),
      'X-App-Source: ' . WC_Eupago::SOURCE,
      'X-App-Version: ' . WC_Eupago::VERSION,
      'X-Runtime-Info: PHP ' . PHP_VERSION
    ];

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_TIMEOUT, 60);

    $response = curl_exec($curl);

    if (curl_errno($curl)) {
      $error = 'cURL Error: ' . curl_error($curl);
      curl_close($curl);
      return ['error' => $error];
    }

    curl_close($curl);

    // Decode JSON to array
    $decoded = json_decode($response, true);

    // Handle invalid JSON response
    if (json_last_error() !== JSON_ERROR_NONE) {
      return ['error' => 'Invalid JSON response', 'raw_response' => $response];
    }

    return $decoded;
  }

  public function pedidoPF($order, $valor, $return_url, $comment)
  {
    return $this->legacy_request('paysafecard/create', 'pedidoPF', array(
      'chave'          => $this->get_api_key(),
      'valor'          => $this->money_format($valor),
      'id'             => $order->get_id(),
      'admin_callback' => '',
      'url_retorno'    => $return_url,
      'comentario'     => $comment,
    ));
  }

  public function pedidoPSC($order, $valor, $return_url, $lang, $comment)
  {
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

  public function getReferenciaFloa($order, $valor, $lang, $return_url) {
      $url = 'https://' . get_option('eupago_endpoint') . '.eupago.pt/api/v1.02/floa/create';
      

      $billingCountry = $order->get_billing_country();

      if ( $billingCountry === 'ES' ) {
          $installmentCode = 'BC3XFES';
      } else {
          $installmentCode = 'BC3XFPT';
      }

      $data = array(
        'payment' => array(
          'amount' => array(
            'value'    => round((float) $valor, 2),
            'currency' => 'EUR',
          ),
          'identifier'  => (string) $order->get_order_number(),
          'lang' => strtoupper($lang),
          'successUrl' => $return_url,
          'failUrl' => $return_url,
          'backUrl' => $return_url
        ),
        'installmentCode' => $installmentCode,
        'customer' => array(
          'notify'      => false,
          'email'       => $order->get_billing_email(),
          'firstName'   => $order->get_billing_first_name(),
          'lastName'    => $order->get_billing_last_name(),
          'countryCode' => $order->get_billing_country(),
          'billingAddress' => array(
              'address' => $order->get_billing_address_1() . ' ' . $order->get_billing_address_2(),
              'zipCode' => $order->get_billing_postcode(),
              'city'    => $order->get_billing_city(),
              'countryCode' => $order->get_billing_country()
          )
        ),
        'items' => []
      );

      foreach ($order->get_items() as $item_id => $item) {
          $product = $item->get_product();
          $category_ids = $product->get_category_ids();
          $category_name = 'General';
          
          if (!empty($category_ids)) {
              $term = get_term($category_ids[0], 'product_cat');
              if ($term && !is_wp_error($term)) {
                  $category_name = $term->name;
              }
          }
          
          $data['items'][] = array(
              'name'        => $item->get_name(),
              'price'       => round((float) $item->get_total(), 2),
              'quantity'    => $item->get_quantity(),
              'category'    => $category_name,
              'subCategory' => 'General'
          );
      }
      
      // Check if a shipping address exists and add the shipping object
      if ($order->has_shipping_address() || !$order->needs_shipping()) {
          $data['shippingAddress'] = array(
              'shippingMethod' => $this->map_shipping_method_to_floa_slug($order),
              'address'        => $order->get_shipping_address_1() . ' ' . $order->get_shipping_address_2(),
              'zipCode'        => $order->get_shipping_postcode(),
              'city'           => $order->get_shipping_city(),
              'countryCode'    => $order->get_shipping_country()
          );
      }
      $headers = array(
          'Content-Type: application/json',
          'Accept: application/json',
          'Authorization: ApiKey ' . $this->get_api_key(),
          'X-App-Source: ' . WC_Eupago::SOURCE,
          'X-App-Version: ' . WC_Eupago::VERSION,
          'X-Runtime-Info: PHP ' . PHP_VERSION
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
          error_log('FPay cURL Error: ' . curl_error($curl));
          return null;
      }
      
      curl_close($curl);
      
      return json_decode($response, true);
  }

  public function getReferenciaPagaqui($order, $valor)
  {
    $url = 'https://' . get_option('eupago_endpoint') . '.eupago.pt/api/v1.02/pagaqui/create';

    $data = array(
      'payment' => array(
        'amount' => array(
          'value'    => $valor,
          'currency' => 'EUR',
        ),
        'identifier'  => (string) $order->get_id(),
      ),
      'customer' => array(
        'notify' => false,
        'email'       => $order->get_billing_email(),
        'nome'   => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
      )
    );

    $headers = array(
      'Content-Type: application/json',
      'Accept: application/json',
      'Authorization: ApiKey ' . $this->get_api_key(),
      'X-App-Source: ' . WC_Eupago::SOURCE,
      'X-App-Version: ' . WC_Eupago::VERSION,
      'X-Runtime-Info: PHP ' . PHP_VERSION
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
      error_log('Pagaqui cURL Error: ' . curl_error($curl));
      return null;
    }

    curl_close($curl);

    return json_decode($response, true);
  }

  //For Floa
  /**
   * Maps WooCommerce shipping method to a valid Floa API slug.
   *
   * @param WC_Order $order The WooCommerce order object.
   * @return string The corresponding Floa shipping slug.
   */
  private function map_shipping_method_to_floa_slug($order) {
      // 1. Handle virtual products first
      $is_virtual_order = true;
      foreach ($order->get_items() as $item) {
          $product = $item->get_product();
          if ($product && !$product->is_virtual()) {
              $is_virtual_order = false;
              break; // Found a physical product, so stop checking
          }
      }
      if ($is_virtual_order) {
          return 'VIR';
      }

      // Get the first shipping method from the order
      $shipping_methods = $order->get_shipping_methods();
      if (empty($shipping_methods)) {
          // If there are no shipping methods but the order isn't virtual, it's an edge case.
          // Returning a default or 'VIR' might be safest.
          return 'VIR';
      }

      $shipping_item = reset($shipping_methods); // Get the first shipping item
      $method_id = $shipping_item->get_method_id(); // The ID, e.g., 'local_pickup', 'flat_rate'
      $method_name = strtolower($shipping_item->get_name()); // The display name, e.g., 'express delivery'

      // 2. Direct mapping based on method ID (most reliable)
      $direct_map = [
          'local_pickup' => 'COL', // Collection
          'ups' => 'UPS',
          'tnt' => 'TNT',
          // Add other direct mappings for specific shipping plugins you support
      ];

      if (isset($direct_map[$method_id])) {
          return $direct_map[$method_id];
      }
      
      // 3. Keyword-based mapping for generic methods like 'flat_rate'
      if (strpos($method_name, 'express') !== false) {
          return 'EXP';
      }
      if (strpos($method_name, 'pickup point') !== false || strpos($method_name, 'ponto de recolha') !== false) {
          return 'REL'; // Relay point
      }
      if (strpos($method_name, 'tracked') !== false) {
          return 'TRK';
      }

      // 4. If no specific match, return a safe default
      return 'STD'; // Standard delivery
  }
}
