<?php

namespace Automattic\WooCommerce\Blocks\Payments\Integrations;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * Dummy Payments Blocks integration
 *
 * @since 1.0.3
 */
final class PagaquiBlock extends AbstractPaymentMethodType
{
    /**
     * The gateway instance.
     *
     * @var WC_Eupago_Pagaqui
     */
    private $gateway;

    /**
     * Payment method name/id/slug.
     *
     * @var string
     */
    protected $name = 'eupago_pagaqui';

    /**
     * Initializes the payment method type.
     */
    public function initialize()
    {
        $this->settings = get_option('woocommerce_eupago_pagaqui_settings');
        $gateways = WC()->payment_gateways->payment_gateways();
        if (array_key_exists($this->name, $gateways)) {
            $this->gateway = $gateways[$this->name];
        } else {
            // Log a message if the gateway is not found
            error_log("Payment gateway '{$this->name}' is not registered.");
            $this->gateway = null; // Handle accordingly
        }
    }

    /**
     * Returns if this payment method should be active. If false, the scripts will not be enqueued.
     *
     * @return boolean
     */
    public function is_active()
    {
        return ! empty($this->settings['enabled']) && 'yes' === $this->settings['enabled'];
    }

    /**
     * Returns an array of scripts/handles to be registered for this payment method.
     *
     * @return array
     */
    public function get_payment_method_script_handles() {
		wp_register_script(
			'wc-eupago-pagaqui',
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
		return [ 'wc-eupago-pagaqui' ];
	}

    /**
     * Returns an array of key=>value pairs of data made available to the payment methods script.
     *
     * @return array
     */
    public function get_payment_method_data()
    {
        return [
            'title' => $this->get_setting('title'),
            'description' => $this->get_setting('description'),
        ];
    }
}
