<?php

namespace Automattic\WooCommerce\Blocks\Payments\Integrations;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class PixBlock extends AbstractPaymentMethodType {

    protected $name = 'eupago_pix';

    public function initialize() {
        $this->settings = get_option('woocommerce_eupago_pix_settings');
        $gateways = WC()->payment_gateways->payment_gateways();
        if (array_key_exists($this->name, $gateways)) {
            $this->gateway = $gateways[$this->name];
        } else {
            error_log("Payment gateway '{$this->name}' is not registered.");
            $this->gateway = null;
        }
    }

    public function is_active() {
        return !empty($this->settings['enabled']) && 'yes' === $this->settings['enabled'];
    }

    public function get_payment_method_script_handles() {
        wp_register_script(
            'wc-eupago-pix',
            plugins_url('src/index.js', __FILE__),
            [
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
                'wp-i18n',
            ],
            false,
            true
        );
        return ['wc-eupago-pix'];
    }

    public function get_payment_method_data() {
        return [
            'title' => $this->get_setting('title'),
            'description' => $this->get_setting('description'),
        ];
    }
}