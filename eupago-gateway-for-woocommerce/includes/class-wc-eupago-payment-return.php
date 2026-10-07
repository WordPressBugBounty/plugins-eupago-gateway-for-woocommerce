<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Landing endpoint for the "back" button and the failure return of the Eupago
 * payment page (backUrl and failUrl of Credit Card, Google Pay and Apple Pay).
 *
 * URL: wc-api/WC_Eupago_Payment_Return/?order_id=<id>&key=<order key>
 */
class WC_Eupago_Payment_Return
{
    const ENDPOINT = 'WC_Eupago_Payment_Return';

    public function __construct()
    {
        // WooCommerce lowercases the wc-api request before firing the action.
        add_action('woocommerce_api_wc_eupago_payment_return', [$this, 'handle']);
    }

    /**
     * URL the Eupago payment page sends the buyer to when the payment fails or
     * they press "back".
     *
     * @param WC_Order $order
     * @return string
     */
    public static function get_url($order)
    {
        return add_query_arg(
            [
                'order_id' => $order->get_id(),
                'key'      => $order->get_order_key(),
            ],
            WC()->api_request_url(self::ENDPOINT)
        );
    }

    public function handle()
    {
        $logger  = wc_get_logger();
        $context = ['source' => 'eupago-payment-return'];

        $order_id = isset($_GET['order_id']) ? absint($_GET['order_id']) : 0;
        $key      = isset($_GET['key']) ? wc_clean(wp_unslash($_GET['key'])) : '';
        $order    = $order_id ? wc_get_order($order_id) : false;

        // The order id travels in a URL the buyer can see, so the order key is
        // what proves the request belongs to the customer who placed it.
        if (!$order || $key === '' || !hash_equals((string) $order->get_order_key(), $key)) {
            $logger->warning("Payment return: refused request for order {$order_id}, missing or mismatched order key", $context);
            $this->redirect(wc_get_checkout_url());
        }

        if (strpos((string) $order->get_payment_method(), 'eupago_') !== 0) {
            $logger->warning("Payment return: order {$order_id} was not placed with an Eupago gateway, left untouched", $context);
            $this->redirect(wc_get_checkout_url());
        }

        if (!$order->has_status(['on-hold', 'pending', 'cancelled', 'failed'])) {
            $logger->info("Payment return: order {$order_id} came back from the payment page already {$order->get_status()}, sent to the thank-you page", $context);
            $this->redirect($order->get_checkout_order_received_url());
        }

        if ($order->has_status(['on-hold', 'pending'])) {
            $order->add_order_note(__('The customer left the Eupago payment page without paying. The cart was restored so they can try again.', 'eupago-gateway-for-woocommerce'));
            $logger->info("Payment return: order {$order_id} left on-hold, the buyer came back from the payment page unpaid", $context);
        }

        if (WC()->cart && WC()->cart->is_empty()) {
            $this->restore_cart($order, $logger, $context);
        }

        if (WC()->session) {
            WC()->session->set('order_awaiting_payment', false);
            wc_add_notice(
                __('The payment was not completed. Your cart has been restored so you can try again.', 'eupago-gateway-for-woocommerce'),
                'notice'
            );
        }

        $this->redirect(wc_get_checkout_url());
    }

    /**
     * Gives the buyer their basket back
     *
     * @param WC_Order  $order
     * @param WC_Logger $logger
     * @param array     $context
     */
    private function restore_cart($order, $logger, $context)
    {
        $cart     = WC()->cart;
        $restored = 0;
        $skipped  = 0;

        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if (!$product) {
                $skipped++;
                continue;
            }

            $product_id   = (int) $item->get_product_id();
            $variation_id = (int) $item->get_variation_id();

            // A variable product without a chosen variation cannot go in a cart.
            if (!$variation_id && $product->is_type('variable')) {
                $skipped++;
                continue;
            }

            $variations = [];
            foreach ($item->get_meta_data() as $meta) {
                if (taxonomy_is_product_attribute($meta->key)) {
                    $variations['attribute_' . sanitize_title($meta->key)] = sanitize_title($meta->value);
                } elseif (meta_is_product_attribute($meta->key, $meta->value, $product_id)) {
                    $variations['attribute_' . sanitize_title($meta->key)] = html_entity_decode(wc_clean($meta->value), ENT_QUOTES, get_bloginfo('charset'));
                }
            }

            // Same filter WooCommerce runs on "order again", so add-on plugins
            // can put their custom cart item data back.
            $cart_item_data = apply_filters('woocommerce_order_again_cart_item_data', [], $item, $order);

            // add_to_cart() validates stock and adds its own error notice when a
            // line cannot come back (sold out meanwhile); the rest is still restored.
            $cart_key = $cart->add_to_cart($product_id, $item->get_quantity(), $variation_id, $variations, $cart_item_data);
            if ($cart_key) {
                $restored++;
            } else {
                $skipped++;
            }
        }

        foreach ($order->get_coupon_codes() as $code) {
            $cart->apply_coupon($code);
        }

        // Triggers woocommerce_after_calculate_totals, which is what writes the
        // cart back into the session.
        $cart->calculate_totals();

        $logger->info("Payment return: cart restored from order {$order->get_id()} ({$restored} lines restored, {$skipped} skipped)", $context);
    }

    private function redirect($url)
    {
        wp_safe_redirect($url);
        exit;
    }
}
