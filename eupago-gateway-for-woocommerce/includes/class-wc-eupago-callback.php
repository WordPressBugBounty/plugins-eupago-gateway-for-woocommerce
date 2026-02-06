<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * WC Eupago API Class.
 */
class WC_Eupago_Callback
{
    protected static $instance = NULL;
    public $id;
    public $integration;
    public $log;

    public function __construct()
    {
        $this->id = 'eupago-gateway-for-woocommerce';
        $this->integration = new WC_Eupago_Integration();
        if ($this->integration->debug) $this->log = new WC_Logger();

        add_action('woocommerce_api_wc_eupago_webatual', array($this, 'callback_handler'));
        add_action('woocommerce_api_wc_eupago', array($this, 'callback_handler'));
    }

    function callback_log($message, $error = false)
    {
        $title = $error ? __('Error', 'eupago-gateway-for-woocommerce') : __('Success', 'eupago-gateway-for-woocommerce');
        $response = $error ? 500 : 200;

        if ($this->integration->debug) {
            $this->log->add($this->id, '- Callback (' . $_SERVER['REQUEST_URI'] . ') ' . $_SERVER['REMOTE_ADDR'] . ' - ' . $message);
        }
        if ($this->integration->debug_email != '') {
            wp_mail($this->integration->debug_email, $this->id . ' - Error: Callback with missing arguments', 'Callback ( ' . $_SERVER['HTTP_HOST'] . ' ' . $_SERVER['REQUEST_URI'] . ' ) with missing arguments from ' . $_SERVER['REMOTE_ADDR'] . $message);
        }

        wp_die($message, $title, array('response' => $response));
    }

    public function callback_handler()
    {
        $this->log->add($this->id, 'Callback handler triggered.');

        $request_method = $_SERVER['REQUEST_METHOD'];
        $order = null;
        $transacao = null;
        $data = null;
        $local = null;
        $identificador = null;
        $valor = null;
        $status = null;

        if ($request_method === 'POST') {
            $this->log->add($this->id, 'Processing POST request...');

            if (function_exists('getallheaders')) {
                $headers = getallheaders();
            } elseif (function_exists('apache_request_headers')) {
                $headers = apache_request_headers();
            } else {
                $headers = [];
                foreach ($_SERVER as $key => $value) {
                    if (strpos($key, 'HTTP_') === 0) {
                        $header_key = str_replace('_', '-', substr($key, 5));
                        $headers[$header_key] = $value;
                    } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'], true)) {
                        $header_key = str_replace('_', '-', $key);
                        $headers[$header_key] = $value;
                    }
                }
            }

            $headers = array_change_key_case($headers, CASE_LOWER);

            $iv = $headers['x-initialization-vector'] ?? ''; 
            $signature = $headers['x-signature'] ?? '';
            $key = get_option('eupago_webhook_encrypt_key');

            if (empty($key)) {
                $this->callback_log('Chave criptográfica não configurada.', true);
                return;
            } else {
                $post_body = json_decode(file_get_contents('php://input'), true);
                if ($key == 'NA') {
                    $response_data = $post_body;
                } else {
                    $encrypted_data = $post_body['data'] ?? '';
                    $response_data = json_decode($this->decryptData($encrypted_data, $iv, $key), true);
                    $isSignatureVerified = $this->verifySignature($encrypted_data, $signature, $key);

                    if (!$isSignatureVerified || empty($response_data)) {
                        $this->callback_log('Assinatura inválida ou dados desencriptados inválidos', true);
                        return;
                    }
                }
            }

            $identificador = isset($response_data['transaction']['identifier']) ? sanitize_text_field($response_data['transaction']['identifier']) : '';
            $valor = isset($response_data['transaction']['amount']['value']) ? sanitize_text_field($response_data['transaction']['amount']['value']) : '';
            $data = isset($response_data['transaction']['date']) ? sanitize_text_field($response_data['transaction']['date']) : '';
            $local = isset($response_data['transaction']['local']) ? sanitize_text_field($response_data['transaction']['local']) : '';
            $transacao = isset($response_data['transaction']['trid']) ? sanitize_text_field($response_data['transaction']['trid']) : '';
            $status = isset($response_data['transaction']['status']) ? strtoupper(sanitize_text_field($response_data['transaction']['status'])) : '';

        } elseif ($request_method === 'GET') {
            $this->log->add($this->id, 'Processing GET request...');

            $api_key = isset($_GET['chave_api']) ? sanitize_text_field($_GET['chave_api']) : '';
            if (!$api_key || $api_key !== $this->integration->get_api()) {
                $this->callback_log('Erro na chave API', true);
                return;
            }

            $identificador = isset($_GET['identificador']) ? sanitize_text_field($_GET['identificador']) : '';
            if (!$identificador) {
                $this->callback_log('Identificador inválido.', true);
                return;
            }

            $valor = isset($_GET['valor']) ? sanitize_text_field($_GET['valor']) : '';
            $data = isset($_GET['data']) ? sanitize_text_field($_GET['data']) : '';
            $local = isset($_GET['local']) ? sanitize_text_field($_GET['local']) : '';
            $transacao = isset($_GET['transacao']) ? sanitize_text_field($_GET['transacao']) : '';
            $status = "PAID";

        } else {
            $this->callback_log('Método HTTP não suportado.', true);
            return;
        }

        $order = wc_get_order($identificador);
        if (!$order) {
            $this->callback_log('Pedido não encontrado.', true);
            return;
        }

        if (!$order->has_status(['on-hold', 'pending'])) {
            $this->callback_log('Pedido já não se encontra pendente.', true);
            return;
        }

        $order_total = version_compare(WC_VERSION, '3.0', '>=') ? $order->get_total() : $order->order_total;

        $valor_clean = str_replace(',', '.', $valor);
        $order_total_clean = str_replace(',', '.', $order_total);
        $valor_rounded = round(floatval($valor_clean), 2);
        $order_total_rounded = round(floatval($order_total_clean), 2);

        if (!$valor || floatval($valor_rounded) != floatval($order_total_rounded)) {
            $this->callback_log('Valor do pagamento não corresponde ao total do pedido.', true);
            return;
        }

        switch ($status) {
            case 'PAID':
                $note = 'Pagamento efetuado ';
                if ($data) $note .= '<br />Data/Hora: ' . $data;
                if ($local) $note .= '<br />Local: ' . $local;
                if ($transacao) $note .= '<br />Transação: ' . $transacao;
                $order->add_order_note($note);

                $order->payment_complete($transacao);
                $this->process_sms_if_enabled($order);
                $this->callback_log('Pagamento com Sucesso!');
                break;

            case 'CANCELED':
                $order->update_status('cancelled', 'Pagamento cancelado pelo utilizador.');
                $this->restore_stock_if_reduced($order);
                $this->callback_log('Pagamento cancelado.', false);
                break;

            case 'ERROR':
            case 'EXPIRED':
                $order->update_status('failed', 'Pagamento com erro ou expirado.');
                $this->restore_stock_if_reduced($order);
                $this->callback_log('Pagamento com erro ou expirado.', false);
                break;

            default:
                $this->callback_log('Estado da transação desconhecido ou não tratado: ' . $status, true);
                break;
        }
    }

    private function process_sms_if_enabled( $order ) {
        if ( ! file_exists( plugin_dir_path( __FILE__ ) . 'hooks/hooks-sms.php' ) ) {
            return;
        }
        include_once plugin_dir_path( __FILE__ ) . 'hooks/hooks-sms.php';

        $payment_method   = $order->get_payment_method();
        $payment_gateways = WC()->payment_gateways->payment_gateways;

        if ( isset( $payment_gateways[ $payment_method ] ) ) {
            $gateway    = $payment_gateways[ $payment_method ];
            $option_key = $gateway->settings[ "sms_payment_confirmation_{$payment_method}" ] ?? null;

            if ( $option_key === 'yes' && function_exists( 'send_sms_processing' ) ) {
                send_sms_processing( $order->get_id() );
            }
        }
    }

    public function decryptData($encrypted_data, $iv, $key)
    {
        $this->log->add($this->id, 'Decrypting data...');
        $cipher = 'aes-256-cbc';
        $options = OPENSSL_RAW_DATA;
        $response_data = openssl_decrypt(base64_decode($encrypted_data), $cipher, $key, $options, base64_decode($iv));

        if ($response_data) {
            $this->log->add($this->id, 'Data decrypted successfully.');
        } else {
            $this->log->add($this->id, 'Failed to decrypt data.', true);
        }

        return $response_data;
    }

    public function verifySignature($encrypted_data, $signature, $key)
    {
        $this->log->add($this->id, 'Verifying signature...');
        $generated_signature = hash_hmac('sha256', $encrypted_data, $key, true);

        if ($generated_signature) {
            $this->log->add($this->id, 'Signature verified successfully.');
        } else {
            $this->log->add($this->id, 'Failed to verify signature.', true);
        }

        return hash_equals($generated_signature, base64_decode($signature));
    }
    function get_gateway($gateway = null)
    {
        if (isset($gateway) && !empty($gateway)) {
            $eupago_gateways = array(
                'PC:PT' => ['eupago_multibanco'],
                'PS:PT' => ['eupago_payshop'],
                'MW:PT' => ['eupago_mbway'],
                'PQ:PT' => ['eupago_pagaqui'],
                'CC:PT' => ['eupago_cc'],
                'PSC:PT' => ['eupago_psc'],
                'PF:PT' => ['eupago_pf'],
                'CP:PT' => ['eupago_cofidispay'],
                'BZ:PT' => ['eupago_bizum'],
                'PX:PT' => ['eupago_pix'],
                'PQ:PT' => ['eupago_pagaqui'],
            );
            $eupago_gateways = apply_filters('eupago_for_woocommerce_callback_gateways', $eupago_gateways);

            return $eupago_gateways[$gateway];
        } else {
            return false;
        }
    }

    function get_gateway_class($gateway = null)
    {
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
                'eupago_bizum' => 'WC_Eupago_Bizum',
                'eupago_pix' => 'WC_Eupago_Pix',
                'eupago_pagaqui' => 'WC_Eupago_Pagaqui',
            );
            return $eupago_gateways[$gateway];
        } else {
            return false;
        }
    }

    /**
     * Restore stock for an order if it was previously reduced and the order is not paid.
     *
     * @param WC_Order $order The WooCommerce order object.
     */
    public function restore_stock_if_reduced( $order ) {
        if ( ! $order instanceof WC_Order ) {
            return;
        }

        // Only restore stock if the order was cancelled or failed
        if ( $order->has_status( [ 'cancelled', 'failed' ] ) ) {
            foreach ( $order->get_items() as $item_id => $item ) {
                $product = $item->get_product();

                if ( ! $product || ! $product->managing_stock() ) {
                    continue;
                }

                // Check if stock was reduced previously
                $stock_reduced = wc_get_order_item_meta( $item_id, '_reduced_stock', true );

                if ( $stock_reduced ) {
                    $qty = $item->get_quantity();

                    // WooCommerce 6.3+ helper function
                    if ( function_exists( 'wc_increase_stock_level_for_order_item' ) ) {
                        wc_increase_stock_level_for_order_item( $item, $qty );
                    } else {
                        // Fallback for older versions
                        $product->increase_stock( $qty );
                    }

                    // Remove the _reduced_stock flag
                    wc_delete_order_item_meta( $item_id, '_reduced_stock' );
                }
            }

            // Add a note to the order
            $order->add_order_note( 'Stock restored due to payment failure or cancellation.' );
        }
    }
}
