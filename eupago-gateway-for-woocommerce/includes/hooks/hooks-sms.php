<?php

/**
* Eupago SMS.
*/
// Send sms for pending order.
function send_sms_pending($order_id) {
   $order = wc_get_order($order_id);

   $phone = $order->get_meta( '_billing_phone', true);
   $total_amount = $order->get_total();
   if ((get_option('eupago_sms_enable') == 'yes') && (!empty($phone))) { 
      $payment_method = $order->get_meta( '_payment_method', true);
      switch ($payment_method) {
         case 'eupago_multibanco': //multibanco
               $entity        = $order->get_meta( '_eupago_multibanco_entidade', true);
               $reference     = $order->get_meta( '_eupago_multibanco_referencia', true);
               $amount        = $total_amount . $order->get_meta( '_order_currency', true);
               $payment_data  = __( 'Entity:', 'eupago-gateway-for-woocommerce' ) . ' ' . $entity . ' ' . __( 'Reference:', 'eupago-gateway-for-woocommerce' ) . ' ' . $reference . ' ' . __( 'Value:', 'eupago-gateway-for-woocommerce' ) . ' ' . $amount; 
            break;

         case 'eupago_mbway': //mbway
               $reference     = $order->get_meta( '_eupago_mbway_referencia', true);
               $amount        =$total_amount . $order->get_meta( '_order_currency', true);
               $payment_data  =  __( 'Reference:', 'eupago-gateway-for-woocommerce' ) . ' ' . $reference . ' ' . __( 'Value:', 'eupago-gateway-for-woocommerce' ) . ' ' . $amount;  
            break;

         case 'eupago_payshop': //payshop
               $reference     = $order->get_meta( '_eupago_payshop_referencia', true);
               $amount        = $total_amount . $order->get_meta( '_order_currency', true);
               $payment_data  =  __( 'Reference:', 'eupago-gateway-for-woocommerce' ) . ' ' . $reference . ' ' . __( 'Value:', 'eupago-gateway-for-woocommerce' ) . ' ' . $amount;  
            break;

         case 'eupago_pf': //paysafecard
               $reference     = $order->get_meta( '_eupago_pf_referencia', true);
               $amount        = $total_amount . $order->get_meta( '_order_currency', true);
               $payment_data  =  __( 'Reference:', 'eupago-gateway-for-woocommerce' ) . ' ' . $reference . ' ' . __( 'Value:', 'eupago-gateway-for-woocommerce' ) . ' ' . $amount;  
            break;

         case 'eupago_cc': //creditcard
               $reference     = $order->get_meta( '_eupago_cc_referencia', true);
               $amount        = $total_amount . $order->get_meta( '_order_currency', true);
               $payment_data  =  __( 'Reference:', 'eupago-gateway-for-woocommerce' ) . ' ' . $reference . ' ' . __( 'Value:', 'eupago-gateway-for-woocommerce' ) . ' ' . $amount;  
            break;
         
         case 'eupago_cofidispay': //cofidispay
               $reference     = $order->get_meta( '_eupago_cofidispay_referencia', true);
               $amount        = $total_amount . $order->get_meta( '_order_currency', true);
               $payment_data  =  __( 'Reference:', 'eupago-gateway-for-woocommerce' ) . ' ' . $reference . ' ' . __( 'Value:', 'eupago-gateway-for-woocommerce' ) . ' ' . $amount;  
            break;

         default:
               #Exit Switch
            break;
      }

      if (get_option('biziq_environment') == 'sandbox'){
         $url = 'https://sandboxapi.biziq.app/sms/add?accountid=' . get_option('eupago_sms_intelidus_id') . '&apikey=' . get_option('eupago_sms_intelidus_api');
      }
      elseif (get_option('biziq_environment') == 'live'){
         $url = 'https://api.biziq.app/sms/add?accountid=' . get_option('eupago_sms_intelidus_id') . '&apikey=' . get_option('eupago_sms_intelidus_api');
      }



      $message = __( 'Your order', 'eupago-gateway-for-woocommerce' ) . ' #' . $order_id . ' ' . __( 'on', 'eupago-gateway-for-woocommerce' ) . ' ' . get_bloginfo('name') . ' ' . __( 'is completed. Payment details:', 'eupago-gateway-for-woocommerce' ) . ' ' . $payment_data;

      $args = array(
         'headers' => array(
            'Accept: application/json',
            'Content-Type: application/x-www-form-urlencoded'
         ),
         'body' => array(
            'mobile_num'    => $phone,
            'message'       => $message,
            'sender'        => get_option('eupago_intelidus_sender')
         ),
         'timeout'     => '60',
      );

      $response_body = wp_remote_post( $url, $args );
      $response     = wp_remote_retrieve_body( $response_body );

   }
}
// Send sms for paid order.
function send_sms_processing($order_id) {
   $order = wc_get_order($order_id);
   $phone = $order->get_meta( '_billing_phone', true);
   if ((get_option('eupago_sms_enable') == 'yes')  && (!empty($phone))) {
      if (get_option('biziq_environment') == 'sandbox'){
         $url = 'https://sandboxapi.biziq.app/sms/add?accountid=' . get_option('eupago_sms_intelidus_id') . '&apikey=' . get_option('eupago_sms_intelidus_api');
      }
      elseif (get_option('biziq_environment') == 'live'){
         $url = 'https://api.biziq.app/sms/add?accountid=' . get_option('eupago_sms_intelidus_id') . '&apikey=' . get_option('eupago_sms_intelidus_api');
      }

      $message = __( 'We have received your payment regarding your order', 'eupago-gateway-for-woocommerce' ) . ' #' . $order_id . ' ' . __( 'on', 'eupago-gateway-for-woocommerce' ) . ' ' . get_bloginfo('name') . '.';


      $args = array(
         'headers' => array(
            'Accept: application/json',
            'Content-Type: application/x-www-form-urlencoded'
         ),
         'body' => array(
            'mobile_num'    => $phone,
            'message'       => $message,
            'sender'        => get_option('eupago_intelidus_sender')
         ),
         'timeout'     => '60',
      );
    
      $response_body = wp_remote_post( $url, $args );
      $response     = wp_remote_retrieve_body( $response_body );
   }
}


function send_sms_cc($order_id){
   $order = wc_get_order($order_id);
   send_sms_pending($order_id);
}

function send_sms($order_id){
   $order = wc_get_order($order_id);
   send_sms_pending($order_id);
}






// Send sms for completed order.
/*function send_sms_completed($order_id) {
   $order = wc_get_order($order_id);
   $phone = $order->get_meta( '_billing_phone', true);
   if ((get_option('eupago_sms_enable') == 'yes') && (get_option('eupago_sms_order_confirmation') == 'yes') && (!empty($phone))) {
      $message = sprintf( __( 'Your order #%d on %s is now finished.', 'eupago-gateway-for-woocommerce' ), $order_id, get_bloginfo('name') );

      if (get_option('biziq_environment') == 'sandbox'){
         $url = 'https://sandboxapi.biziq.app/sms/add?accountid=' . get_option('eupago_sms_intelidus_id') . '&apikey=' . get_option('eupago_sms_intelidus_api');
      }
      elseif (get_option('biziq_environment') == 'live'){
         $url = 'https://api.biziq.app/sms/add?accountid=' . get_option('eupago_sms_intelidus_id') . '&apikey=' . get_option('eupago_sms_intelidus_api');
      }
      
      $args = array(
         'headers' => array(
            'Accept: application/json',
            'Content-Type: application/x-www-form-urlencoded'
         ),
         'body' => array(
            'mobile_num'    => $phone,
            'message'       => $message,
            'sender'        => get_option('eupago_intelidus_sender')
         ),
         'timeout'     => '60',
      );
    
      $response_body = wp_remote_post( $url, $args );
      $response     = wp_remote_retrieve_body( $response_body );
   }
}*/
//add_action('woocommerce_order_status_completed', 'send_sms_completed');
?>
?>