<?php

namespace Automattic\WooCommerce\Blocks\Payments\Integrations;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class GooglePayBlock extends AbstractPaymentMethodType {

    /**
     * Unique ID for the block (matches the frontend name: 'eupago_googlepay').
     *
     * @var string  
     */
    protected $name = 'eupago_googlepay';

    /**
     * Load gateway settings.
     */
    public function initialize() {
        $this->settings = get_option('woocommerce_eupago_googlepay_settings', []);
    }

    /**
     * Register the JS block file for this payment method.
     *
     * @return array
     */
      public function get_payment_method_script_handles() {
        wp_register_script(
            'wc-eupago-googlepay',
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

        return ['wc-eupago-googlepay'];
    }

    /**
     * Pass gateway data to the frontend block.
     *
     * @return array
     */
    public function get_payment_method_data() {
        return [
            'title'       => $this->settings['title'] ?? 'Google Pay',
            'description' => $this->settings['description'] ?? 'Pague com Google Pay via Eupago.',
            'instructions' => $this->settings['instructions'] ?? 'Pague com Google Pay via Eupago.',
            'supports'    => [ 'products' ],
        ];
    }
}
