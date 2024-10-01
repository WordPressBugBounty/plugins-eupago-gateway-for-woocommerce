<?php

namespace Automattic\WooCommerce\Blocks\Payments\Integrations;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * Eupago Gateway Integration Checkout Blocks
 */
final class CofidisPayBlock extends AbstractPaymentMethodType
{
    /**
     * The gateway instance.
     *
     * @var WC_Eupago_CofidisPay
     */
    private $gateway;

    /**
     * Payment method name/id/slug.
     *
     * @var string
     */
    protected $name = 'eupago_cofidispay';

    /**
     * Initializes the payment method type.
     */
    public function initialize()
    {
        $this->settings = get_option('woocommerce_eupago_cofidispay_settings');

        $gateways = WC()->payment_gateways->payment_gateways();
        $this->gateway = $gateways[ $this->name ];
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
    public function get_payment_method_script_handles()
    {
        wp_register_script(
            'wc-eupago-cofidispay',
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

        return [ 'wc-eupago-cofidispay' ];
    }

    /**
     * Returns an array of key=>value pairs of data made available to the payment methods script.
     *
     * @return array
     */
    public function get_payment_method_data()
    {

        $nifText = "";
        $description = "";
        $maxInstallments = "";

        // Check the store language
        $locale = get_locale();

        if ($locale === 'pt_PT') {
            $nifText = __('Adicione o seu NIF', 'eupago-cofidispay');
            $description = __('
            Será redirecionado para uma página segura a fim de efetuar o pagamento. O pagamento das prestações com 0% de juros e encargos será efetuado no cartão de débito ou crédito do cliente através da solução de pagamento assente em contrato de factoring entre a Cofidis e o comerciante. Informe-se na Cofidis, registada no Banco de Portugal com o N.º 921.', 'eupago-cofidispay');
            $maxInstallments = "De 1 até " . $this->get_setting('max_installments') . " vezes sem juros.";
        } else {
            $nifText = __('Add your NIF number', 'eupago-cofidispay');
            $description = __('You will be redirected to a secure page to make your payment. The payment of installments with 0% interest and charges will be made on the customer´s debit or credit card through the payment solution based on factoring agreement between Cofidis and the merchant. More information at Cofidis, registered with the Bank of Portugal under No. 921.', 'eupago-cofidispay');
            $maxInstallments = "From 1 to " . $this->get_setting('max_installments') . " installments without interest.";
        }


        

        
        return [
            'title' => $this->get_setting('title'),
            'description' => __($description, 'eupago-cofidispay'),
            'nifText' => __($nifText, 'eupago-cofidispay'),
            'maxInstallments' => __($maxInstallments, 'eupago-cofidispay'),
        ];
    }

    /**
     * Enqueue the JavaScript file with the proper script handles.
     */
    public function enqueue_scripts()
    {
        parent::enqueue_scripts();
        wp_enqueue_script('wc-eupago-cofidispay');
    }
}
