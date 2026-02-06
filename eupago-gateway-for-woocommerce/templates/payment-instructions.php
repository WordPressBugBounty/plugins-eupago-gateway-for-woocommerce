<?php
/**
* Payment instructions.
*
* @author  WebAtual
* @package WooCommerce_EuPago/Templates
* @version 0.1
*/

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly.
}
?>
<style type="text/css">
table.woocommerce_eupago_table { width: auto !important; margin: auto; }
table.woocommerce_eupago_table td,	table.woocommerce_eupago_table th { background-color: #FFFFFF; color: #000000; padding: 10px; vertical-align: middle; }
table.woocommerce_eupago_table th { text-align: center; font-weight: bold; }
table.woocommerce_eupago_table th img { margin: auto; margin-top: 10px; }
</style>
<?php if ($method == 'eupago_multibanco') : ?>
  <table class="woocommerce_eupago_table" cellpadding="0" cellspacing="0">
    <tr>
      <th colspan="2">
        <?php _e('Payment instructions', 'eupago-gateway-for-woocommerce'); ?>
        <br/>
        <span style="font-weight: normal;"> <?php echo nl2br(esc_html($instructions)) ?> </span>
        <br/>
        <img src="<?php echo plugins_url('assets/images/multibanco_banner.png', dirname(__FILE__)); ?>" alt="<?php echo esc_attr($payment_name); ?>" title="<?php echo esc_attr($payment_name); ?>"/>
      </th>
    </tr>
    <tr>
      <td><?php _e('Entity', 'eupago-gateway-for-woocommerce'); ?>:</td>
      <td><?php echo esc_html($entidade); ?></td>
    </tr>
    <tr>
      <td><?php _e('Reference', 'eupago-gateway-for-woocommerce'); ?>:</td>
      <td><?php echo esc_html(chunk_split($referencia, 3, ' ')); ?></td>
    </tr>
    <tr>
      <td><?php _e('Value', 'eupago-gateway-for-woocommerce'); ?>:</td>
      <td><?php echo esc_html($order_total.'€'); ?></td>
    </tr>
    <?php if ( isset( $data_fim ) && !empty( $data_fim ) ) : ?>
    <tr>
      <td><?php _e('Limit Date', 'eupago-gateway-for-woocommerce'); ?>:</td>
      <td><?php echo date_i18n( wc_date_format(), strtotime( $data_fim ) ); ?></td>
    </tr>
  <?php endif; ?>
    <tr>
      <td colspan="2" style="font-size: small;"><?php _e('The receipt issued by the ATM machine is a proof of payment. Keep it.', 'eupago-gateway-for-woocommerce'); ?></td>
    </tr>
  </table>
  <?php
  elseif ($method == 'eupago_payshop') : ?>
  <table class="woocommerce_eupago_table" cellpadding="0" cellspacing="0">
    <tr>
      <th colspan="2">
        <?php _e('Payment instructions', 'eupago-gateway-for-woocommerce'); ?>
        <br/>
        <span style="font-weight: normal;"> <?php  echo nl2br(esc_html($instructions)) ?> </span>
        <br/>
        <img src="<?php echo plugins_url('assets/images/payshop_banner.png', dirname(__FILE__)); ?>" alt="<?php echo esc_attr($payment_name); ?>" title="<?php echo esc_attr($payment_name); ?>"/>
      </th>
    </tr>
    <tr>
      <td><?php _e('Reference', 'eupago-gateway-for-woocommerce'); ?>:</td>
      <td><?php echo chunk_split($referencia, 3, ' '); ?></td>
    </tr>
    <tr>
      <td><?php _e('Value', 'eupago-gateway-for-woocommerce'); ?>:</td>
      <td><?php echo esc_html($order_total.'€'); ?></td>
    </tr>
    <tr>
      <td colspan="2" style="font-size: small;"><?php _e('The receipt issued by the ATM machine is proof of payment. Keep it.', 'eupago-gateway-for-woocommerce'); ?></td>
    </tr>
  </table>
  <?php
  elseif ($method == 'eupago_pagaqui') : ?>
  <table class="woocommerce_eupago_table" cellpadding="0" cellspacing="0">
    <tr>
      <th colspan="2">
        <?php _e('Payment instructions', 'eupago-gateway-for-woocommerce'); ?>
        <br/>
        <span style="font-weight: normal;"> <?php echo nl2br(esc_html($instructions)) ?> </span>
        <br/>
        <img src="<?php echo plugins_url('assets/images/pagaqui_banner.png', dirname(__FILE__)); ?>" alt="<?php echo esc_attr($payment_name); ?>" title="<?php echo esc_attr($payment_name); ?>"/>
      </th>
    </tr>
    <tr>
      <td><?php _e('Reference', 'eupago-gateway-for-woocommerce'); ?>:</td>
      <td><?php echo esc_html(chunk_split($referencia, 3, ' ')); ?></td>
    </tr>
    <tr>
      <td><?php _e('Value', 'eupago-gateway-for-woocommerce'); ?>:</td>
      <td><?php echo esc_html($order_total.'€'); ?></td>
    </tr>
    <tr>
      <td colspan="2" style="font-size: small;"><?php _e('The receipt issued by Pagaqui serves as proof of payment. Please keep it.', 'eupago-gateway-for-woocommerce'); ?></td>
    </tr>
  </table>
<?php elseif ($method == 'eupago_mbway') : ?>
  <table class="woocommerce_eupago_table" cellpadding="0" cellspacing="0">
    <tr>
      <th colspan="2">
        <?php _e('Payment instructions', 'eupago-gateway-for-woocommerce'); ?>
        <br/>
        <span style="font-weight: normal;"> <?php echo nl2br(esc_html($instructions)) ?> </span>
        <br/>
        <img src="<?php echo plugins_url('assets/images/mbway_banner.png', dirname(__FILE__)); ?>" alt="<?php echo esc_attr($payment_name); ?>" title="<?php echo esc_attr($payment_name); ?>"/>
      </th>
    </tr>
    <tr>
      <td><?php _e('Reference', 'eupago-gateway-for-woocommerce'); ?>:</td>
      <td><?php echo esc_html(chunk_split($referencia, 3, ' ')); ?></td>
    </tr>
    <tr>
      <td><?php _e('Value', 'eupago-gateway-for-woocommerce'); ?>:</td>
      <td><?php echo esc_html($order_total.'€'); ?></td>
    </tr>
    <tr>
      <td colspan="2" style="font-size: small;"><?php _e('Accept this payment with your MBWAY mobile app', 'eupago-gateway-for-woocommerce'); ?></td>
    </tr>
  </table>
<?php elseif ($method == 'eupago_pix') : ?>
  <table class="woocommerce_eupago_table" cellpadding="0" cellspacing="0">
    <tr>
      <th colspan="4">
        <?php _e('Payment instructions', 'eupago-gateway-for-woocommerce'); ?>
        <br/>
        <span style="font-weight: normal;"> <?php echo nl2br(esc_html($instructions)) ?> </span>
        <br/>
        <img style="padding-right: 20px" src="<?php echo plugins_url('assets/images/pix_icon.png', dirname(__FILE__)); ?>" alt="<?php echo esc_attr($payment_name); ?>" title="<?php echo esc_attr($payment_name); ?>"/>
      </th>
    </tr>
    <tr>
      <td></td>
      <td style="font-size: large;"><?php _e('QR Code', 'eupago-gateway-for-woocommerce'); ?>:</td>
      <td><img src="<?php echo esc_url($pixImage); ?>" alt="<?php echo esc_attr($payment_name); ?>" title="<?php echo esc_attr($payment_name); ?>"/></td>
      <td></td>
    </tr>
    <tr>
      <td></td>
      <td width="100px" style="font-size: large;"><?php _e('EuroPix Code', 'eupago-gateway-for-woocommerce'); ?>:</td>
      <td width="250px" style="word-break: break-word;">
        <span style="font-size: medium;" id="pixCodeText"><?php echo esc_html($pixCode); ?></span>
        <br><br/>
        <button style="padding-right: 10px; 
                        padding-left: 10px;
                        padding-top: 8px;
                        padding-bottom: 3px; 
                        margin-right: 60px;
                        margin-left: 95px;
                        border-radius: 10px;
                        background-color: rgb(42, 42, 42);
                        color: white;
                        font-size: small;
                        font-family: inherit;" 
                        onclick="copyPixCode()">
            <svg xmlns="http://www.w3.org/2000/svg" 
                viewBox="0 0 24 24" 
                fill="white" 
                width="16px" 
                height="16px">
                <path d="M0 0h24v24H0z" fill="none"/>
                <path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"/>
            </svg>
        </button>
        <br><br/>
      </td>
      <td width="110px"></td>
    </tr>
  <table>
    <?php elseif ($method == 'eupago_googlepay') : ?>
  <table class="woocommerce_eupago_table" cellpadding="0" cellspacing="0">
    <tr>
      <th colspan="4">
        <?php _e('Payment instructions', 'eupago-gateway-for-woocommerce'); ?>
        <br/>
        <span style="font-weight: normal;">
          <?php echo nl2br(esc_html($instructions)); ?>
        </span>
        <br/>
        <img src="<?php echo plugins_url('assets/images/googlepay_icon.png', dirname(__FILE__)); ?>" alt="<?php echo esc_attr($payment_name); ?>" title="<?php echo esc_attr($payment_name); ?>"/>
      </th>
    </tr>
    <tr>
      <td><?php _e('Transaction ID', 'eupago-gateway-for-woocommerce'); ?>:</td>
      <td><?php echo esc_html($transaction_id ?? '—'); ?></td>
    </tr>
    <?php if (!empty($reference)) : ?>
    <tr>
      <td><?php _e('Reference', 'eupago-gateway-for-woocommerce'); ?>:</td>
      <td><?php echo esc_html($reference); ?></td>
    </tr>
    <?php endif; ?>
    <tr>
      <td><?php _e('Value', 'eupago-gateway-for-woocommerce'); ?>:</td>
      <td><?php echo esc_html($order_total . '€'); ?></td>
    </tr>
    <tr>
      <td colspan="2" style="font-size: small;"><?php _e('You were redirected to complete your payment with Google Pay.', 'eupago-gateway-for-woocommerce'); ?></td>
    </tr>
  </table>
<?php elseif ($method == 'eupago_applepay') : ?>
  <table class="woocommerce_eupago_table" cellpadding="0" cellspacing="0">
    <tr>
      <th colspan="4">
        <?php _e('Payment instructions', 'eupago-gateway-for-woocommerce'); ?>
        <br/>
        <span style="font-weight: normal;">
          <?php echo nl2br(esc_html($instructions)); ?>
        </span>
        <br/>
        <img src="<?php echo plugins_url('assets/images/applepay_icon.png', dirname(__FILE__)); ?>" alt="<?php echo esc_attr($payment_name); ?>" title="<?php echo esc_attr($payment_name); ?>"/>
      </th>
    </tr>
    <tr>
      <td><?php _e('Transaction ID', 'eupago-gateway-for-woocommerce'); ?>:</td>
      <td><?php echo esc_html($transaction_id ?? '—'); ?></td>
    </tr>
    <?php if (!empty($reference)) : ?>
    <tr>
      <td><?php _e('Reference', 'eupago-gateway-for-woocommerce'); ?>:</td>
      <td><?php echo esc_html($reference); ?></td>
    </tr>
    <?php endif; ?>
    <tr>
      <td><?php _e('Value', 'eupago-gateway-for-woocommerce'); ?>:</td>
      <td><?php echo esc_html($order_total . '€'); ?></td>
    </tr>
    <tr>
      <td colspan="2" style="font-size: small;"><?php _e('You were redirected to complete your payment with Apple Pay.', 'eupago-gateway-for-woocommerce'); ?></td>
    </tr>
  </table>
<?php elseif ($method == 'eupago_floa') : ?>
  <table class="woocommerce_eupago_table" cellpadding="0" cellspacing="0">
    <tr>
      <th colspan="4">
        <?php _e('Payment instructions', 'eupago-gateway-for-woocommerce'); ?>
        <br/>
        <span style="font-weight: normal;">
          <?php echo nl2br(esc_html($instructions)); ?>
        </span>
        <br/>
        <img src="<?php echo plugins_url('assets/images/floa_blue.png', dirname(__FILE__)); ?>" alt="<?php echo esc_attr($payment_name); ?>" title="<?php echo esc_attr($payment_name); ?>"/>
      </th>
    </tr>
    <tr>
      <td><?php _e('Transaction ID', 'eupago-gateway-for-woocommerce'); ?>:</td>
      <td><?php echo esc_html($transaction_id ?? '—'); ?></td>
    </tr>
    <?php if (!empty($reference)) : ?>
    <tr>
      <td><?php _e('Reference', 'eupago-gateway-for-woocommerce'); ?>:</td>
      <td><?php echo esc_html($reference); ?></td>
    </tr>
    <?php endif; ?>
    <tr>
      <td><?php _e('Value', 'eupago-gateway-for-woocommerce'); ?>:</td>
      <td><?php echo esc_html($order_total . '€'); ?></td>
    </tr>
    <tr>
      <td colspan="2" style="font-size: small;"><?php _e('You were redirected to complete your payment with Floa.', 'eupago-gateway-for-woocommerce'); ?></td>
    </tr>
  </table>
<?php endif; ?>

<script>
  function copyPixCode() {
    const pixCode = document.getElementById('pixCodeText').innerText;
    navigator.clipboard.writeText(pixCode);
  }
</script>
