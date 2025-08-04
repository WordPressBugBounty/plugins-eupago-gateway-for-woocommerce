<?php
$order = new WC_Order( $post->ID );
$client = new WC_Eupago_API();
echo '<p>';
$payment_method = version_compare( WC_VERSION, '3.0', '>=' ) ? $order->get_payment_method() : $order->payment_method;
$payment_method_title = version_compare( WC_VERSION, '3.0', '>=' ) ? $order->get_payment_method_title() : $order->payment_method_title;
$order_total = version_compare( WC_VERSION, '3.0', '>=' ) ? $order->get_total() : $order->order_total;

$billing_phone = version_compare(WC_VERSION, '3.0', '>=') ? $order->get_billing_phone() : $order->billing_phone;
$payment_method_ref = '_'.$payment_method.'_referencia';

switch ($payment_method) {

  case 'eupago_multibanco':
    if (trim(get_post_meta($post->ID, $payment_method_ref, true)) == 0) {
      $mb = new WC_Eupago_Multibanco();
      $deadline = $mb->get_option('deadline');
      $duplicate_payments = $mb->get_option('duplicate_payments');
      if ( isset( $deadline ) && $deadline > 0 ) {
        $data_inicio = date('Y-m-d');
        $data_fim = date('Y-m-d', strtotime('+' . $deadline . ' day', strtotime( $data_inicio ) ) );
		    $eupagoMultibanco = $pedido = $client->getReferenciaMB($post->ID, $order_total, $duplicate_payments, $deadline);
        update_post_meta ($post->ID, '_eupago_multibanco_data_fim', $data_fim);
      } else {
        $eupagoMultibanco = $pedido = $client->getReferenciaMB($post->ID, $order_total, $duplicate_payments);
      }
      if (extension_loaded('soap')) {
        if ( $eupagoMultibanco->estado != 0 ) {
          $error_message = $eupagoMultibanco->resposta;
          wc_add_notice( __('Payment error:', 'eupago-gateway-for-woocommerce') . $error_message, 'error' );
          return;
        }
      } else {
        $eupagoMultibanco_decode = json_decode($eupagoMultibanco, true);
        if ( $eupagoMultibanco_decode['estado'] != 0 ) {
          $error_message = $eupagoMultibanco_decode['resposta'];
          wc_add_notice( __('Payment error:', 'eupago-gateway-for-woocommerce') . $error_message, 'error' );
          return;
        }
      }
      if (extension_loaded('soap')) {
        update_post_meta ($post->ID, '_eupago_multibanco_entidade', $eupagoMultibanco->entidade);
        update_post_meta ($post->ID, '_eupago_multibanco_referencia', $eupagoMultibanco->referencia);
      } else {
        $eupagoMultibanco_decode = json_decode($eupagoMultibanco, true);
        update_post_meta ($post->ID, '_eupago_multibanco_entidade', $eupagoMultibanco_decode['entidade']);
        update_post_meta ($post->ID, '_eupago_multibanco_referencia', $eupagoMultibanco_decode['referencia']);
      }
    }
  echo '<img src="' . plugins_url('assets/images/multibanco_banner.png', dirname(dirname(__FILE__))) . '" alt="' . esc_attr($payment_method_title) . '" title="' . esc_attr($payment_method_title) . '" /><br />';
  echo '<b>'.__('Entity', 'eupago-gateway-for-woocommerce').'</b>: '.trim(get_post_meta($post->ID, '_eupago_multibanco_entidade', true)).'<br/>';
  echo '<b>'.__('Reference', 'eupago-gateway-for-woocommerce').'</b>: '.chunk_split(trim(get_post_meta($post->ID, '_eupago_multibanco_referencia', true)), 3, ' ').'<br/>';
  echo '<b>'.__('Value', 'eupago-gateway-for-woocommerce').'</b>: '.wc_price( $order_total ).'<br/>';
  echo !empty(get_post_meta($post->ID, "_eupago_multibanco_data_fim", true)) ? '<b>'.__('Limit Date', 'eupago-gateway-for-woocommerce').'</b>: '.get_post_meta($post->ID, "_eupago_multibanco_data_fim", true) : '';
  break;

  case 'eupago_mbway':
    // if (trim(get_post_meta($post->ID, $payment_method_ref, true)) == 0) {
    //   $pedido = $client->getReferenciaMBW($post->ID, $order_total, $billing_phone);
    //   $pedido_decode = json_decode($pedido, true);
    //   if ($pedido_decode['estado'] == 0) {
    //     update_post_meta($post->ID, '_eupago_mbway_referencia', $pedido->referencia);
    //   }
    // }
  echo '<img src="' . plugins_url('assets/images/mbway_banner.png', dirname(dirname(__FILE__))) . '" alt="' . esc_attr($payment_method_title) . '" title="' . esc_attr($payment_method_title) . '" /><br />';
  echo '<b>'.__('Reference', 'eupago-gateway-for-woocommerce').'</b>: '.trim(get_post_meta($post->ID, '_eupago_mbway_referencia', true)).'<br/>';
  echo '<b>'.__('Value', 'eupago-gateway-for-woocommerce').'</b>: '.wc_price( $order_total );
  break;

  case 'eupago_cc':   
    if (trim(get_post_meta($post->ID, $payment_method_ref, true)) == 0) {
      $lang = get_post_meta( $post->ID, 'wpml_language', true );
      if ( empty( $lang ) ) {
        $lang = 'pt-pt';
      }
      $logo='https://woo.eupago.pt/wp-content/plugins/eupago-gateway-for-woocommerce/includes/views/images/avatar.png'; 
      $return_url='https://eupago.pt'; 
      $comment='';
      $pedido = $client->pedidoCC($order, $order_total, $logo, $return_url, $lang, $comment);
      if ( $pedido->estado != 0 ) {
        $error_message = $pedido->resposta;
        wc_add_notice( __('Payment error:', 'eupago-gateway-for-woocommerce') . ' ' . $error_message, 'error' );
        return;
      } else {
        update_post_meta($post->ID, '_eupago_cc_referencia', $pedido->referencia);
        update_post_meta($post->ID, '_eupago_cc_link', $pedido->url);
      }
    }
  echo '<img src="' . plugins_url('assets/images/cc_icon.jpg', dirname(dirname(__FILE__))) . '" alt="' . esc_attr($payment_method_title) . '" title="' . esc_attr($payment_method_title) . '" /><br />';
  echo '<b>'.__('Reference', 'eupago-gateway-for-woocommerce').'</b>: '.chunk_split(trim(get_post_meta($post->ID, '_eupago_cc_referencia', true)), 3, ' ').'<br/>';
  echo '<b>'.__('Value', 'eupago-gateway-for-woocommerce').'</b>: '.wc_price( $order_total ).'<br/>';
  echo !empty(get_post_meta($post->ID, "_eupago_cc_link", true)) ? '<b>'.__('Payment Link', 'eupago-gateway-for-woocommerce').'</b>: <a href='.get_post_meta($post->ID, "_eupago_cc_link", true).' target="_black">Click here</a>' : '';
  break;

  case 'eupago_cofidispay':
    if (trim(get_post_meta($post->ID, $payment_method_ref, true)) == 0) {
      
      $cofidispay_vat_number = get_post_meta($post->ID, 'nif', true);
      update_post_meta($post->ID, '_eupago_cofidis_vat_number', $cofidispay_vat_number);
      $pedido = $client->cofidispay_create($post->ID);
      if ( $pedido->transactionStatus != 'Success' ) {
        wc_add_notice(__('Payment error:', 'eupago-for-woocommerce') . ' Ocorreu um erro com o pedido de pagamento', 'error');
        return;
      } else {
        update_post_meta($post->ID, '_eupago_cofidispay_transactionID', $pedido->transactionID);
        update_post_meta($post->ID, '_eupago_cofidispay_referencia', $pedido->reference);
        update_post_meta($post->ID, '_eupago_cofidispay_redirectUrl', $pedido->redirectUrl);
      }
    }
  echo '<img src="' . plugins_url('assets/images/cofidispay.png', dirname(dirname(__FILE__))) . '" alt="' . esc_attr($payment_method_title) . '" title="' . esc_attr($payment_method_title) . '" /><br />';
  echo '<b>'.__('Reference', 'eupago-gateway-for-woocommerce').'</b>: '.chunk_split(trim(get_post_meta($post->ID, '_eupago_cofidispay_referencia', true)), 3, ' ').'<br/>';
  echo '<b>'.__('Value', 'eupago-gateway-for-woocommerce').'</b>: '.wc_price( $order_total ).'<br/>';
  echo !empty(get_post_meta($post->ID, "_eupago_cofidispay_redirectUrl", true)) ? '<b>'.__('Payment Link', 'eupago-gateway-for-woocommerce').'</b>: <a href='.get_post_meta($post->ID, "_eupago_cofidispay_redirectUrl", true).' target="_black">Click here</a>' : '';
  break;

  case 'eupago_payshop':
    if (trim(get_post_meta($post->ID, $payment_method_ref, true)) == 0) {
      $pedido = $client->getReferenciaPS($post->ID, $order_total);
      if ($pedido->estado == 0) {
        update_post_meta($post->ID, '_eupago_payshop_referencia', $pedido->referencia);
      }
    }
  echo '<img src="' . plugins_url('assets/images/payshop_banner.png', dirname(dirname(__FILE__))) . '" alt="' . esc_attr($payment_method_title) . '" title="' . esc_attr($payment_method_title) . '" /><br />';
  echo '<b>'.__('Reference', 'eupago-gateway-for-woocommerce').'</b>: '.chunk_split(trim(get_post_meta($post->ID, '_eupago_payshop_referencia', true)), 3, ' ').'<br/>';
  echo '<b>'.__('Value', 'eupago-gateway-for-woocommerce').'</b>: '.wc_price( $order_total );
  break;

  case 'eupago_bizum':
    if (trim(get_post_meta($post->ID, $payment_method_ref, true)) == 0) {
        $pedido = $client->getReferenciaBizum($post->ID, $order_total, $order->get_checkout_order_received_url(), $order->get_checkout_payment_url());
        $pedido_data = json_decode($pedido, true);
        if (isset($pedido_data['transactionStatus']) && $pedido_data['transactionStatus'] === 'Success') {
            update_post_meta($post->ID, '_eupago_bizum_referencia', $pedido_data['referencia']);
            update_post_meta($post->ID, '_eupago_bizum_redirect_url', $pedido_data['redirectUrl']);
        }
    }
    echo '<img src="' . plugins_url('assets/images/bizum_icon.png', dirname(dirname(__FILE__))) . '" alt="' . esc_attr($payment_method_title) . '" title="' . esc_attr($payment_method_title) . '" /><br />';
    echo '<b>' . __('Reference', 'eupago-gateway-for-woocommerce') . '</b>: ' . esc_html(get_post_meta($post->ID, '_eupago_bizum_referencia', true)) . '<br/>';
    echo '<b>' . __('Value', 'eupago-gateway-for-woocommerce') . '</b>: ' . wc_price($order_total) . '<br/>';
    echo !empty(get_post_meta($post->ID, "_eupago_bizum_redirect_url", true)) ? '<b>' . __('Payment Link', 'eupago-gateway-for-woocommerce') . '</b>: <a href=' . get_post_meta($post->ID, "_eupago_bizum_redirect_url", true) . ' target="_black">Click here</a>' : '';
    break;

    case 'eupago_pix':
      if (trim(get_post_meta($post->ID, $payment_method_ref, true)) == 0) {
          $pedido = $client->getReferencePix($post->ID, $order_total);
          $pedido_data = json_decode($pedido, true);
          if (isset($pedido_data['transactionStatus']) && $pedido_data['transactionStatus'] === 'Success') {
              update_post_meta($post->ID, '_eupago_pix_referencia', $pedido_data['reference']);
              update_post_meta($post->ID, '_eupago_pix_pixCode', $pedido_data['pixCode']);
              update_post_meta($post->ID, '_eupago_pix_pixImage', $pedido_data['pixImage']);
          }
      }
      echo '<img src="' . plugins_url('assets/images/pix_icon.png', dirname(dirname(__FILE__))) . '" alt="' . esc_attr($payment_method_title) . '" title="' . esc_attr($payment_method_title) . '" /><br />';
      echo '<b>' . __('Reference', 'eupago-gateway-for-woocommerce') . '</b>: ' . esc_html(get_post_meta($post->ID, '_eupago_pix_referencia', true)) . '<br/>';
      echo '<b>' . __('Value', 'eupago-gateway-for-woocommerce') . '</b>: ' . wc_price($order_total) . '<br/>';
      echo '<b>' . __('EuroPix Code', 'eupago-gateway-for-woocommerce') . '</b>: ' . esc_html(get_post_meta($post->ID, '_eupago_pix_pixCode', true)) . '<br/>';
      echo !empty(get_post_meta($post->ID, "_eupago_pix_pixImage", true)) ? '<b>' . __('QR Code', 'eupago-gateway-for-woocommerce') . '</b>: <img src="' . esc_url(get_post_meta($post->ID, "_eupago_pix_pixImage", true)) . '" alt="' . esc_attr($payment_method_title) . '" /><br/>' : '';
      break;
    case 'eupago_googlepay':
      $reference = $order->get_meta('_eupago_googlepay_reference');
      $value     = $order->get_total();
      ?>
      <p>
          <img src="<?php echo plugins_url('assets/images/googlepay_icon.png', dirname(dirname(__FILE__))); ?>" alt="<?php echo esc_attr($payment_method_title); ?>" title="<?php echo esc_attr($payment_method_title); ?>" /><br>
      </p>
      <p><strong><?php _e('Reference:', 'eupago-gateway-for-woocommerce'); ?></strong><br><?php echo esc_html($reference); ?></p>
      <p><strong><?php _e('Value:', 'eupago-gateway-for-woocommerce'); ?></strong><br><?php echo wc_price($value); ?></p>
      <?php
    break;
    case 'eupago_applepay':
      $reference = $order->get_meta('_eupago_applepay_reference');
      $value     = $order->get_total();
      ?>
      <p>
          <img src="<?php echo plugins_url('assets/images/applepay_icon.png', dirname(dirname(__FILE__))); ?>" alt="<?php echo esc_attr($payment_method_title); ?>" title="<?php echo esc_attr($payment_method_title); ?>" /><br>
      </p>
      <p><strong><?php _e('Reference:', 'eupago-gateway-for-woocommerce'); ?></strong><br><?php echo esc_html($reference); ?></p>
      <p><strong><?php _e('Value:', 'eupago-gateway-for-woocommerce'); ?></strong><br><?php echo wc_price($value); ?></p>
      <?php
    break;

  default:
  echo __('No details available', 'eupago-gateway-for-woocommerce');
  break;
}
echo '</p>';