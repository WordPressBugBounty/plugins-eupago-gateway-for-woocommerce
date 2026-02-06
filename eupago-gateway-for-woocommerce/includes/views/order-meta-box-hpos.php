<?php
$order = wc_get_order($post->ID);
$order_id = 0;

// 1. From query var (thank-you page)
if ( isset( $_GET['order-received'] ) ) {
    $order_id = absint( $_GET['order-received'] );

// 2. From admin edit URL like ?id=1332
} elseif ( isset( $_GET['id'] ) ) {
    $order_id = absint( $_GET['id'] );

// 3. If you already have a WC_Order object
} elseif ( isset( $order ) && $order instanceof WC_Order ) {
    $order_id = $order->get_id();
}
$order = wc_get_order($order_id);
$client = new WC_Eupago_API();
$payment_method = version_compare(WC_VERSION, '3.0', '>=') ? $order->get_payment_method() : $order->payment_method;
$payment_method_title = version_compare(WC_VERSION, '3.0', '>=') ? $order->get_payment_method_title() : $order->payment_method_title;
$order_total = version_compare(WC_VERSION, '3.0', '>=') ? $order->get_total() : $order->order_total;

echo '<p>';
switch ($payment_method) {

  case 'eupago_multibanco':
    if (trim($order->get_meta('_eupago_multibanco_referencia')) == '') {
      $pedido = $client->getReferenciaMB($order->get_id(), $order_total);
      if ($pedido->estado == 0) {
        $order->update_meta_data('_eupago_multibanco_entidade', $pedido->entidade ?? '');
        $order->update_meta_data('_eupago_multibanco_referencia', $pedido->referencia ?? '');
        $order->update_meta_data('_eupago_multibanco_data_fim', $pedido->validade ?? '');
        $order->save();
      }
    }
    echo '<img src="' . plugins_url('assets/images/multibanco_banner.png', dirname(dirname(__FILE__))) . '" /><br />';
    echo '<b>Entity</b>: ' . esc_html($order->get_meta('_eupago_multibanco_entidade')) . '<br/>';
    echo '<b>Reference</b>: ' . chunk_split(trim($order->get_meta('_eupago_multibanco_referencia')), 3, ' ') . '<br/>';
    echo '<b>Value</b>: ' . wc_price($order_total) . '<br/>';
    echo '<b>Limit Date</b>: ' . esc_html($order->get_meta('_eupago_multibanco_data_fim')) . '<br/>';
    break;

  case 'eupago_mbway':
    if (trim($order->get_meta('_eupago_mbway_referencia')) == '') {
      $phone = $order->get_meta('_eupago_mbway_phone');
      $country_code = $order->get_meta('_eupago_mbway_country_code') ?: '+351'; // Default to Portugal if not set
      $pedido = $client->getReferenciaMBW($order->get_id(), $order_total, $phone, $country_code);
      $pedido = json_decode($pedido);
      if ($pedido->transactionStatus == 'Success') {
        $order->update_meta_data('_eupago_mbway_referencia', $pedido->reference ?? '');
        $order->save();
      }
    }
    echo '<img src="' . plugins_url('assets/images/mbway_banner.png', dirname(dirname(__FILE__))) . '" /><br />';
    echo '<b>Reference</b>: ' . esc_html($order->get_meta('_eupago_mbway_referencia')) . '<br/>';
    echo '<b>Value</b>: ' . wc_price($order_total) . '<br/>';
    break;

  case 'eupago_cc':
    if (trim($order->get_meta('_eupago_cc_referencia')) == '') {
      $lang = get_post_meta( $post->ID, 'wpml_language', true );
      if ( empty( $lang ) ) {
        $lang = 'pt-pt';
      }
      $logo='https://woo.eupago.pt/wp-content/plugins/eupago-gateway-for-woocommerce/includes/views/images/avatar.png'; 
      $return_url='https://eupago.pt'; 
      $comment='';

      $pedido = $client->pedidoCC($order->get_id(), $order_total, $logo, $return_url, $lang, $comment);
      if ($pedido['status'] == 0) {
        $order->update_meta_data('_eupago_cc_referencia', $pedido['reference'] ?? '');
        $order->update_meta_data('_eupago_cc_link', $pedido['redirectUrl'] ?? '');
        $order->save();
      }
    }
    echo '<img src="' . plugins_url('assets/images/cc_icon.jpg', dirname(dirname(__FILE__))) . '" /><br />';
    echo '<b>Reference</b>: ' . esc_html($order->get_meta('_eupago_cc_referencia')) . '<br/>';
    echo '<b>Value</b>: ' . wc_price($order_total) . '<br/>';
    echo '<b>Payment Link</b>: <a href="' . esc_url($order->get_meta('_eupago_cc_link')) . '" target="_blank">Click here</a><br/>';
    break;

  case 'eupago_cofidispay':
    if (trim($order->get_meta('_eupago_cofidispay_referencia')) == '') {
      $cofidispay_vat_number = get_post_meta($post->ID, 'nif', true);
      update_post_meta($post->ID, '_eupago_cofidis_vat_number', $cofidispay_vat_number);

      $pedido = $client->cofidispay_create($post->ID);
      $pedido = json_decode($pedido);
      if ($pedido->transactionStatus == 'Success') {
        $order->update_meta_data('_eupago_cofidispay_referencia', $pedido->referencia ?? '');
        $order->update_meta_data('_eupago_cofidispay_redirectUrl', $pedido->redirectUrl ?? '');
        $order->save();
      }
    }
    echo '<img src="' . plugins_url('assets/images/cofidispay.png', dirname(dirname(__FILE__))) . '" /><br />';
    echo '<b>Reference</b>: ' . esc_html($order->get_meta('_eupago_cofidispay_referencia')) . '<br/>';
    echo '<b>Value</b>: ' . wc_price($order_total) . '<br/>';
    echo '<b>Payment Link</b>: <a href="' . esc_url($order->get_meta('_eupago_cofidispay_redirectUrl')) . '" target="_blank">Click here</a><br/>';
    break;

  case 'eupago_payshop':
    if (trim($order->get_meta('_eupago_payshop_referencia')) == '') {
      $pedido = $client->getReferenciaPS($order->get_id(), $order_total);
      if ($pedido->estado == 0) {
        $order->update_meta_data('_eupago_payshop_referencia', $pedido->referencia ?? '');
        $order->save();
      }
    }
    echo '<img src="' . plugins_url('assets/images/payshop_banner.png', dirname(dirname(__FILE__))) . '" /><br />';
    echo '<b>Reference</b>: ' . chunk_split(trim($order->get_meta('_eupago_payshop_referencia')), 3, ' ') . '<br/>';
    echo '<b>Value</b>: ' . wc_price($order_total) . '<br/>';
    break;

  case 'eupago_bizum':
    if (trim($order->get_meta('_eupago_bizum_referencia')) == '') {
      $pedido = $client->getReferenciaBizum($order->get_id(), $order_total);
      if ($pedido->estado == 0) {
        $order->update_meta_data('_eupago_bizum_referencia', $pedido->referencia ?? '');
        $order->update_meta_data('_eupago_bizum_redirect_url', $pedido->redirect_url ?? '');
        $order->save();
      }
    }
    echo '<img src="' . plugins_url('assets/images/bizum_icon.png', dirname(dirname(__FILE__))) . '" /><br />';
    echo '<b>Reference</b>: ' . esc_html($order->get_meta('_eupago_bizum_referencia')) . '<br/>';
    echo '<b>Value</b>: ' . wc_price($order_total) . '<br/>';
    echo '<b>Payment Link</b>: <a href="' . esc_url($order->get_meta('_eupago_bizum_redirect_url')) . '" target="_blank">Click here</a><br/>';
    break;

  case 'eupago_pix':
    if (trim($order->get_meta('_eupago_pix_referencia')) == '') {
      $pedido = $client->getReferencePix($order->get_id(), $order_total);
      $pedido = json_decode($pedido);
      if ($pedido->transactionStatus == 'Success') {
        $order->update_meta_data('_eupago_pix_referencia', $pedido->referencia ?? '');
        $order->update_meta_data('_eupago_pix_pixCode', $pedido->pixCode ?? '');
        $order->update_meta_data('_eupago_pix_pixImage', $pedido->pixImage ?? '');
        $order->save();
      }
    }
    echo '<img src="' . plugins_url('assets/images/pix_icon.png', dirname(dirname(__FILE__))) . '" /><br />';
    echo '<b>Reference</b>: ' . esc_html($order->get_meta('_eupago_pix_referencia')) . '<br/>';
    echo '<b>Value</b>: ' . wc_price($order_total) . '<br/>';
    echo '<b>EuroPix Code</b>: ' . esc_html($order->get_meta('_eupago_pix_pixCode')) . '<br/>';
    echo '<b>QR Code</b>: <img src="' . esc_url($order->get_meta('_eupago_pix_pixImage')) . '" /><br/>';
    break;

  case 'eupago_googlepay':
    echo '<img src="' . plugins_url('assets/images/googlepay_icon.png', dirname(dirname(__FILE__))) . '" /><br />';
    echo '<b>Reference</b>: ' . esc_html($order->get_meta('_eupago_googlepay_reference')) . '<br/>';
    echo '<b>Value</b>: ' . wc_price($order_total) . '<br/>';
    break;

  case 'eupago_applepay':
    echo '<img src="' . plugins_url('assets/images/applepay_icon.png', dirname(dirname(__FILE__))) . '" /><br />';
    echo '<b>Reference</b>: ' . esc_html($order->get_meta('_eupago_applepay_reference')) . '<br/>';
    echo '<b>Value</b>: ' . wc_price($order_total) . '<br/>';
    break;

    case 'eupago_floa':
    echo '<img src="' . plugins_url('assets/images/floa_blue.png', dirname(dirname(__FILE__))) . '" /><br />';
    echo '<b>Reference</b>: ' . esc_html($order->get_meta('_eupago_floa_reference')) . '<br/>';
    echo '<b>Value</b>: ' . wc_price($order_total) . '<br/>';
    break;
  case 'eupago_pagaqui':
    if (trim($order->get_meta('_eupago_pagaqui_reference')) == '') {
      $pedido = $client->getReferenciaPagaqui($order, $order_total);
      if ($pedido->estado == 0) {
        $order->update_meta_data('_eupago_pagaqui_reference', $pedido->referencia ?? '');
        $order->save();
      }
    }
    echo '<img src="' . plugins_url('assets/images/pagaqui_banner.png', dirname(dirname(__FILE__))) . '" /><br />';
    echo '<b>Reference</b>: ' . chunk_split(trim($order->get_meta('_eupago_pagaqui_reference')), 3, ' ') . '<br/>';
    echo '<b>Value</b>: ' . wc_price($order_total) . '<br/>';
    break;
  default:
    echo __('No details available', 'eupago-gateway-for-woocommerce');
    break;
}
echo '</p>';
