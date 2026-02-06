<?php

namespace Automattic\WooCommerce\Blocks\Payments\Integrations;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * Eupago FLOA Blocks integration
 *
 * @since 4.5.7
 */
final class FloaBlock extends AbstractPaymentMethodType
{
    /**
     * The gateway instance.
     *
     * @var WC_Eupago_Floa
     */
    private $gateway;

    /**
     * Payment method name/id/slug.
     *
     * @var string
     */
    protected $name = 'eupago_floa';

    /**
     * Initializes the payment method type.
     */
    public function initialize()
    {
        $this->settings = get_option('woocommerce_eupago_floa_settings');
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
            'wc-eupago-floa',
            plugins_url('src/index.js', __FILE__),
            [
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
                'wp-i18n',
            ],
            // Note: WC Blocks now recommends passing the file path for automatic dependency generation.
            // For now, this approach is fine.
            filemtime(plugin_dir_path(__FILE__) . 'src/index.js'),
            true
        );
        return [ 'wc-eupago-floa' ];
    }

    /**
     * Returns an array of key=>value pairs of data made available to the payment methods script.
     *
     * @return array
     */
    public function get_payment_method_data()
    {
        $description = "";
        $installmentsText = "";

        // Get cart total to calculate installment value.
        $total = 0;
        if ( function_exists('WC') && WC()->cart && ! WC()->cart->is_empty() ) {
            $total = (float) WC()->cart->get_total('edit');
        }

        $installment_value_formatted = '';
        if ( $total > 0 ) {
            $installment_value = $total / 3; 
            $installment_value_formatted = wc_price( $installment_value ); // Format it as a price
        }

        // Check the store language
        $locale = get_locale();

        if ($locale === 'pt_PT') {
            $description = __('Será redirecionado para uma página segura a fim de efetuar o pagamento. O pagamento das prestações será efetuado no cartão de débito ou crédito do cliente através da solução de pagamento assente em contrato de factoring entre a Floa e o comerciante.', 'eupago-floa');
            
            // Create the dynamic text string if the installment value is valid.
            if ( ! empty($installment_value_formatted) ) {
                $installmentsText = sprintf(
                    __('Pague em 3x de %s sem juros.', 'eupago-floa'),
                    $installment_value_formatted
                );
            }

        } else {
            $description = __('You will be redirected to a secure page to make your payment. The payment of installments will be made on the customer´s debit or credit card through the payment solution based on factoring agreement between Floa and the merchant.', 'eupago-floa');

            // Create the dynamic text string for the default language.
            if ( ! empty($installment_value_formatted) ) {
                $installmentsText = sprintf(
                    __('Pay in 3 installments of %s interest-free.', 'eupago-floa'),
                    $installment_value_formatted
                );
            }
        }
        $banner_url = plugins_url( '../../../assets/images/floa_banner_white.png', __FILE__ );
        return [
            'title'             => $this->get_setting('title'),
            'description'       => $description,
            'installmentsText'  => $installmentsText,
            'bannerUrl'         => esc_url( $banner_url ),
            'supports'          => array_filter( $this->gateway->supports, [ $this->gateway, 'supports' ] )
        ];
    }
}
