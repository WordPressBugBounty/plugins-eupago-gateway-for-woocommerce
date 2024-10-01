<?php
/**
* Eupago Refund.
*/

// Refund request Ajax
add_action('wp_ajax_refund', 'refund_func');
add_action('wp_ajax_nopriv_refund', 'refund_func');

function refund_func()
{
    // check_ajax_referer('refund_nonce', 'security');

    $endpoint = get_option('eupago_endpoint');
    $order = wc_get_order(sanitize_text_field($_POST['refund_order']));
    $trid = $order->get_meta('_transaction_id', true);

    $payment_method = $order->get_payment_method();

    if (!empty(sanitize_text_field($_POST['refund_amount']))) {
        // Token
        $url = 'https://' . $endpoint . '.eupago.pt/api/auth/token';
        $data = [
            'grant_type' => 'client_credentials',
            'client_id' => get_option('eupago_client_id'),
            'client_secret' => get_option('eupago_client_secret'),
        ];
        $headers = [
            'Content-Type: application/json',
        ];
        $jsonData = json_encode($data);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $responseData = json_decode($response, true);
        // Check if the request was successful

        if ($responseData['transactionStatus'] === 'Rejected') {
            // Handle the error
            $output_class = 'eupago-output-error';
            $output_request = __('Invalid Credentials: Check your client id and client secret', 'eupago-gateway-for-woocommerce');
        } else {
            curl_close($ch);
            if ($responseData['transactionStatus'] === 'Success') {
                $accessToken = $responseData['access_token'];
                $headerRefund = [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $accessToken,
                ];

                $dataRefund = [
                    'amount' => floatval(sanitize_text_field($_POST['refund_amount'])),
                ];

                if (!empty($_POST['refund_reason'])) {
                    $dataRefund['reason'] = sanitize_text_field($_POST['refund_reason']);
                }

                if ($payment_method !== "eupago_mbway" && $payment_method !== 'eupago_cc') {
                    $dataRefund['iban'] = sanitize_text_field($_POST['refund_iban']);
                    $dataRefund['bic'] = sanitize_text_field($_POST['refund_bic']);
                }

                if (!empty($_POST['refund_bic']) || !empty($_POST['refund_iban'])) {
                    $dataRefund['bic'] = sanitize_text_field($_POST['refund_bic']);
                    $dataRefund['iban'] = sanitize_text_field($_POST['refund_iban']);
                }

                if (!empty($_POST['refund_name'])) {
                    $dataRefund['name'] = sanitize_text_field($_POST['refund_name']);
                }

                $url_refund = 'https://' . $endpoint . '.eupago.pt/api/management/v1.02/refund/' . $trid;

                // Convert the new payload to JSON
                $dataRefundJson = json_encode($dataRefund);
                // Initialize cURL session for the new request
                $newCh = curl_init($url_refund);
                curl_setopt($newCh, CURLOPT_POST, 1);
                curl_setopt($newCh, CURLOPT_POSTFIELDS, $dataRefundJson);
                curl_setopt($newCh, CURLOPT_HTTPHEADER, $headerRefund);
                curl_setopt($newCh, CURLOPT_RETURNTRANSFER, true);
                // Execute the new cURL request
                $newResponse = curl_exec($newCh);
                $jsonResponseRefund = json_decode($newResponse, true);
                if ($jsonResponseRefund['transactionStatus'] === 'Success') {
                    // Add a note to the order with the refunded amount
                    $order = wc_get_order(sanitize_text_field($_POST['refund_order']));
                    $refunded_amount = floatval(sanitize_text_field($_POST['refund_amount']));
                    $current_time = current_time('Y-m-d H:i:s'); // Get the current timestamp
                    $order->add_order_note('Refunded ' . $refunded_amount . ' EUR at: ' . $current_time);
                    $order->save();
                    $output_class = 'eupago-output-success';
                    $output_request = __('Request made successfully', 'eupago-gateway-for-woocommerce');
                } else {
                    $output_class = 'eupago-output-error';
                    if ($jsonResponseRefund['code'] == 'IBAN_INVALID') {
                        $output_request = __('IBAN Invalid', 'eupago-gateway-for-woocommerce');
                    } elseif ($jsonResponseRefund['code'] == 'BIC_INVALID') {
                        $output_request = __('BIC Invalid', 'eupago-gateway-for-woocommerce');
                    } elseif ($jsonResponseRefund['code'] == 'AMOUNT_INVALID') {
                        $output_request = __('Amount Invalid', 'eupago-gateway-for-woocommerce');
                    } else {
                        $output_request = __('Request error', 'eupago-gateway-for-woocommerce');
                    }
                }
            }
        }
    } else {
        // Close the cURL session
        curl_close($ch);
        $output_class = 'eupago-output-error';
        $output_request = __('Fill all fields', 'eupago-gateway-for-woocommerce');
    }
    echo '<p class="' . esc_html($output_class) . '">' . esc_html($output_request) . '</p>';
    wp_die();
}

// // Add nonce to the page
// function eupago_add_ajax_nonce() {
//     echo '<script>var eupago_ajax_nonce = "' . wp_create_nonce('refund_nonce') . '";</script>';
// }
// add_action('wp_head', 'eupago_add_ajax_nonce');

// Add meta box for refund order.
function eupago_refund()
{
    $hpos_enabled = false;

    // Check if it is HPOS compliant
    if (version_compare(WC_VERSION, '7.1', '>=')) {
        if (wc_get_container()->get(\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class)->custom_orders_table_usage_is_enabled()) {
            $hpos_enabled = true;
        }
    }

    $screen = $hpos_enabled ? wc_get_page_screen_id('shop-order') : 'shop_order';
    $metabox = $hpos_enabled ? 'eupago_refund_content_hpos' : 'eupago_refund_content';

    add_meta_box(
        'woocommerce-order-refund',
        __('Refund Request', 'eupago-gateway-for-woocommerce'),
        $metabox,
        $screen,
        'side',
        'default'
    );
}

add_action('add_meta_boxes', 'eupago_refund');

// Refund request form
function eupago_refund_content()
{ ?>
   <div class="eupago-site-url"><?php echo site_url(); ?></div>
   <form method="POST" action="">
      <p><input class="eupago-field" type="text" name="refund_name" value="" placeholder="<?php esc_html_e('Name', 'eupago-gateway-for-woocommerce'); ?>"></p>
      <p><input class="eupago-field" type="text" name="refund_iban" value="" placeholder="<?php esc_html_e('IBAN', 'eupago-gateway-for-woocommerce'); ?>"></p>
      <p><input class="eupago-field" type="text" name="refund_bic" value="" placeholder="<?php esc_html_e('BIC', 'eupago-gateway-for-woocommerce'); ?>"></p>
      <p><input class="eupago-field" type="text" name="refund_amount" value="<?php echo esc_attr(get_post_meta($_GET['post'], '_order_total', true)); ?>" placeholder="<?php esc_html_e('Amount', 'eupago-gateway-for-woocommerce'); ?>"></p>
      <p><input class="eupago-field" type="text" name="refund_reason" value="" placeholder="<?php esc_html_e('Reason', 'eupago-gateway-for-woocommerce'); ?>"></p>
      <div class="button button-primary eupago-refund-request"><?php esc_html_e('Request a refund', 'eupago-gateway-for-woocommerce'); ?></div>
   </form>

    <div class="eupago-refund-response"></div>
<?php
}

// Refund request form
function eupago_refund_content_hpos() {
    $order_id    = get_the_ID();
    $order       = wc_get_order($order_id);
    $order_total = $order->get_total();
?>

    <div class="eupago-site-url">
        <?php echo site_url(); ?>
    </div>

    <form method="POST" action="">
        <p><input class="eupago-field" type="text" name="refund_name" value="" placeholder="<?php esc_html_e('Name', 'eupago-gateway-for-woocommerce'); ?>"></p>
        <p><input class="eupago-field" type="text" name="refund_iban" value="" placeholder="<?php esc_html_e('IBAN', 'eupago-gateway-for-woocommerce'); ?>"></p>
        <p><input class="eupago-field" type="text" name="refund_bic" value="" placeholder="<?php esc_html_e('BIC', 'eupago-gateway-for-woocommerce'); ?>"></p>
        <p><input class="eupago-field" type="text" name="refund_amount" value="<?php echo esc_attr($order_total); ?>" placeholder="<?php esc_html_e('Amount', 'eupago-gateway-for-woocommerce'); ?>"></p>
        <p><input class="eupago-field" type="text" name="refund_reason" value="" placeholder="<?php esc_html_e('Reason', 'eupago-gateway-for-woocommerce'); ?>"></p>
        <div class="button button-primary eupago-refund-request"><?php esc_html_e('Request a refund', 'eupago-gateway-for-woocommerce'); ?></div>

    </form>

    <div class="eupago-refund-response"></div>
    <?php
}
?>