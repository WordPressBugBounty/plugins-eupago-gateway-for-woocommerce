<?php
/**
* Plugin Name: Eupago Gateway For Woocommerce
* Plugin URI:
* Description: This plugin allows customers to pay their orders with Multibanco, MB WAY, Payshop, Credit Card, CofidisPay, Bizum and EuroPix with Eupago’s gateway.
* Version: 4.5.4
* Author: Eupago
* Author URI: https://www.eupago.pt/
* Text Domain: eupago-gateway-for-woocommerce
* WC tested up to: 9.9.5
* Tested up to: 6.8.1
**/

use Automattic\WooCommerce\Internal\Admin\Orders\CustomOrdersTableController;
use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (!class_exists('WC_Eupago')) :
    class WC_Eupago
    {
        /**
         * Plugin version.
         *
         * @var string
         */
        public const VERSION = '4.5.4';

        /**
         * Instance of this class.
         *
         * @var object
         */
        protected static $instance = null;
        public $show_error_message = false;


        private $option_name = 'eupago_terms_accepted';
        private $settings_page_slug = 'eupago-settings';
        /**
         * Initialize the plugin public actions.
         */
        public function __construct()
        {

            // Load plugin text domain
            add_action('init', [ $this, 'load_plugin_textdomain' ]);

            // Load CSS and JS
            add_action('admin_enqueue_scripts', [ $this, 'load_scripts' ]);

            // Adiciona a página de configurações ao menu de administração
            add_action('admin_menu', [$this, 'add_admin_menu']);
            // Registra as configurações do plugin
            add_action('admin_init', [$this, 'register_settings']);
            // Redireciona para a página de aceitação após a ativação do plugin
            add_action('activated_plugin', [$this, 'redirect_to_accept_terms'], 10, 2);
            // Adiciona uma mensagem de erro se os termos não foram aceitos
            add_action('admin_notices', [$this, 'check_terms_acceptance']);
            // Adiciona o script de redirecionamento para a página de aceitação
            add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);

            add_action('admin_notices', array($this, 'display_admin_notice'));

            

            // Checks with WooCommerce is installed.
            if (class_exists('WC_Payment_Gateway')) {
                $this->includes();
                $wc_blocks_active = false;
                $wc_blocks_active = class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType');

                add_filter('woocommerce_payment_gateways', [ $this, 'add_gateway' ]);
                add_action('woocommerce_blocks_loaded', [ $this, 'woocommerce_blocks_add_payment_methods' ]);

                add_filter('plugin_action_links_' . plugin_basename(__FILE__), [ $this, 'plugin_action_links' ]);

                // Register the integration.
                // add_filter( 'woocommerce_integrations', array( $this, 'add_integration' ) );

                // Check if it is HPOS and Blocks compliant
                add_action('before_woocommerce_init', function () {
                    \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
                    \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
                });

                add_action('add_meta_boxes', [ $this, 'eupago_order_add_meta_box' ]);

                add_action('woocommerce_admin_order_data_after_order_details', [ $this, 'order_meta_vat_number' ]);

                add_action('woocommerce_process_shop_order_meta', [ $this, 'save_vat_number' ]);

                add_action('woocommerce_order_item_add_action_buttons', [ $this, 'new_ref_order_button' ]);

                add_action('wp_ajax_generate_ref', [ $this, 'new_ref_order_button_action' ]);

                add_action('wp_ajax_nopriv_generate_ref', [ $this, 'new_ref_order_button_action' ]);

                // Set Callback.
                new WC_Eupago_Callback();
            } else {
                add_action('admin_notices', [ $this, 'woocommerce_missing_notice' ]);
            }
        }

    /**
     * Adiciona a página de configurações ao menu de administração.
     */
    public function add_admin_menu()
    {
        $terms_accepted = get_option('eupago_terms_accepted', false);
        
        // Verifique se os termos foram aceitos
        if (!$terms_accepted) {
            $menu_title = '';
            $page_title = '';
    
            // Obtenha o idioma atual
            $current_language = get_locale();
    
            // Defina os títulos com base no idioma
            if ($current_language == 'es_ES') { // Para Espanhol
                $menu_title = 'Eupago - Términos y Condiciones';
                $page_title = 'Eupago - Términos y Condiciones';
            } elseif ($current_language == 'en_US') { // Para Inglês
                $menu_title = 'Eupago - Terms and Conditions';
                $page_title = 'Eupago - Terms and Conditions';
            } else { // Idioma padrão, por exemplo, Português
                $menu_title = 'Eupago - Termos e Condições';
                $page_title = 'Eupago - Termos e Condições';
            }
    
            // Adiciona a página ao menu
            add_menu_page(
                $page_title,
                $menu_title,
                'manage_options',
                $this->settings_page_slug,
                [$this, 'settings_page']
            );
        }
    }
    


    /**
     * Registra as configurações do plugin.
     */
    public function register_settings()
    {
        register_setting('eupago_settings_group', $this->option_name);
    }

    /**
     * Exibe a página de configurações.
     */
    public function settings_page()
{
    // Defina as variáveis de texto com base no idioma atual do site
    $page_title = '';
    $terms_title = '';
    $terms_text = '';
    $accept_label = '';
    $save_button_text = '';
    $accepted_message = '';
    $read_accept_message = '';

    $current_language = get_locale();

    if ($current_language == 'es_ES') {
        $page_title = 'Configuraciones del Gateway de Eupago';
        $terms_title = 'Términos y Condiciones';
        $terms_text = 'Por favor, lea y acepte los términos y condiciones a continuación para continuar.';
        $read_accept_message = 'Términos y Condiciones
        1. Introducción
        Estos Términos y Condiciones regulan la instalación y/o el uso del Módulo/Plugin Eupago para la integración de métodos de pago en [Plataforma].
        Por favor, léalos atentamente antes de proceder con su instalación y/o uso.
        Responsabilidad
        2.1. El Módulo/Plugin se proporciona tal como está, sin ninguna garantía de rendimiento, disponibilidad o compatibilidad con futuras actualizaciones de [Plataforma] o servicios de terceros.
        2.2. Eupago no se responsabiliza por modificaciones realizadas al código del módulo por usted o por terceros. Los cambios no autorizados en el código o en el funcionamiento del módulo pueden afectar su rendimiento y no estarán cubiertos por el soporte ofrecido.
        2.3. Eupago no asume ninguna responsabilidad por problemas resultantes de cambios o fallas en los servidores, servicios de terceros (incluyendo pasarelas de pago) o configuraciones externas que puedan impactar el funcionamiento del módulo.
        2.4. El uso del módulo es completamente responsabilidad del usuario, y cualquier pérdida de datos, interrupciones del servicio u otros daños resultantes del uso o la incapacidad de usar el módulo no estarán cubiertos por nuestro equipo de soporte.
        Aceptación
        3.1. Al instalar y/o utilizar el Módulo/Plugin para la integración de métodos de pago en [Plataforma], el usuario declara que ha leído, comprendido y aceptado los Términos y Condiciones descritos anteriormente, sin necesidad de ningún acto de consentimiento expreso, el cual se presume por la continuidad de la instalación y/o uso.
        3.2. Si el usuario no está de acuerdo con alguno de los términos descritos, debe interrumpir inmediatamente el proceso de instalación y/o uso de este módulo.';
        $accept_label = 'Acepto los términos y condiciones.';
        $save_button_text = 'Guardar cambios';
        $accepted_message = 'Se han aceptado los términos y condiciones. Ahora puede configurar el complemento.';
    } elseif ($current_language == 'en_US') {
        $page_title = 'Eupago Gateway Settings';
        $terms_title = 'Terms and Conditions';
        $terms_text = 'Please read and accept the terms and conditions below to continue.';
        $read_accept_message = 'Terms and Conditions 
        1. Introduction
        These Terms and Conditions govern the installation and/or use of the Eupago Module/Plugin for integrating payment methods for [Platform].
        Please read them carefully before proceeding with its installation and/or use.
        Responsibility
        2.1. The Module/Plugin is provided as-is, without any guarantees of performance, availability, or compatibility with future updates of [Platform] or third-party services.
        2.2. Eupago is not responsible for any modifications made to the modules code by you or third parties. Unauthorized changes to the code or operation of the module may affect its performance and fall outside the scope of the provided support.
        2.3. Eupago assumes no responsibility for issues arising from changes or failures in servers, third-party services (including payment gateways), or external configurations that may impact the module’s functionality.
        2.4. The use of the module is entirely at the user risk, and any data loss, service interruptions, or other damages resulting from the use or inability to use the module will not be covered by our support team
        Acceptance
        3.1. By installing and/or using the Module/Plugin for integrating payment methods for [Platform], the user declares that they have read, understood, and accepted the Terms and Conditions described above, without the need for any express consent, which is presumed by continuing the installation and/or use.
        3.2. If the user does not agree with any of the terms described, they must immediately stop the installation and/or use of this module.
        ';
        $accept_label = 'I accept the terms and conditions.';
        $save_button_text = 'Save Changes';
        $accepted_message = 'The terms and conditions have been accepted. You can now configure the plugin.';
    } else {
        $page_title = 'Configurações do Gateway de Eupago';
        $terms_title = 'Termos e Condições';
        $terms_text = 'Por favor, leia e aceite os termos e condições abaixo para continuar.';
        $read_accept_message = 'Termos e Condições
            1. Introdução
            Os presentes Termos e Condições regulam a instalação e/ou utilização do Módulo/Plugin Eupago para integração de métodos de pagamento para [Plataforma].
            Por favor, leia atentamente os mesmos antes de avançar com a sua instalação e/ou utilização.
            2. Responsabilidade
            2.1. O Módulo/Plugin é fornecido nas condições apresentadas, sem quaisquer garantias de desempenho, disponibilidade ou compatibilidade com futuras atualizações do/a [Plataforma] ou de serviços de terceiros.
            2.2. A Eupago não se responsabiliza por quaisquer modificações ao código do módulo, realizadas por si ou por terceiros. Alterações não autorizadas ao código ou ao funcionamento do módulo podem comprometer o seu desempenho, ficando fora do âmbito de suporte oferecido.
            2.3. A Eupago não assume responsabilidade por problemas resultantes de alterações ou falhas nos servidores, serviços de terceiros (incluindo gateways de pagamento) ou configurações externas que possam impactar o funcionamento do módulo.
            2.4. A utilização do módulo é inteiramente da responsabilidade do utilizador, e quaisquer perdas de dados, interrupções de serviço ou outros danos decorrentes do uso ou incapacidade de uso do módulo não serão cobertos pela nossa equipa de suporte.
            3. Aceitação
            3.1. Ao instalar e/ou utilizar o Módulo/Plugin para integração de métodos de pagamento para [Plafaforma], o utilizador declara ter lido, compreendido e aceite os Termos e Condições acima descritos, sem necessidade de qualquer ato de consentimento expresso, o qual é, desde já, presumido pela continuidade da instalação e/ou utilização.
            3.2. Se o utilizador não concordar com algum dos termos descritos, deve interromper imediatamente o processo de instalação e/ou utilização deste módulo.';
        $accept_label = 'Eu aceito os termos e condições.';
        $save_button_text = 'Salvar mudanças';
        $accepted_message = 'Os termos e condições foram aceitos. Você pode agora configurar o plugin.';
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['eupago_accept_terms']) && $_POST['eupago_accept_terms'] === 'yes') {
            update_option('eupago_terms_accepted', true);
            wp_redirect(admin_url('admin.php?page=eupago'));
            exit;
        }
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html($page_title); ?></h1>
        <?php if (!$this->is_terms_accepted()): ?>
            <form method="post" action="">
                <h2><?php echo esc_html($terms_title); ?></h2>
                <p><?php echo esc_html($terms_text); ?></p>
                <textarea rows="10" style="width:100%;" readonly><?php echo esc_html($read_accept_message); ?></textarea>
                <p>
                    <label>
                        <input type="checkbox" name="eupago_accept_terms" value="yes" />
                        <?php echo esc_html($accept_label); ?>
                    </label>
                </p>
                <input type="submit" class="button button-primary" value="<?php echo esc_html($save_button_text); ?>" />
            </form>
        <?php else: ?>
            <p><?php echo esc_html($accepted_message); ?></p>
        <?php endif; ?>
    </div>
    <?php
}


    /**
     * Verifica se os termos e condições foram aceitos e exibe uma mensagem de erro se necessário.
     */
    public function check_terms_acceptance()
    {
        // Defina as variáveis de texto com base no idioma atual do site
        $terms_error_message = '';
    
        // Exemplo de verificação de idioma (você pode usar a função que melhor se adequar ao seu projeto)
        $current_language = get_locale(); // Esta função retorna o idioma atual do WordPress
    
        if ($current_language == 'es_ES') { // Para Espanhol
            $terms_error_message = 'Debe aceptar los términos y condiciones para configurar el complemento.';
        } elseif ($current_language == 'en_US') { // Para Inglês
            $terms_error_message = 'You must accept the terms and conditions to configure the plugin.';
        } else { // Idioma padrão, por exemplo, Português
            $terms_error_message = 'Você deve aceitar os termos e condições para configurar o plugin.';
        }
    
        if ($this->is_terms_accepted() || !isset($_GET['page']) || $_GET['page'] !== $this->settings_page_slug) {
            return;
        }
    
        // Exibe a mensagem de erro na tela, dependendo do idioma selecionado
        echo '<div class="error"><p>' . esc_html($terms_error_message) . '</p></div>';
    }
    

    /**
     * Verifica se os termos e condições foram aceitos.
     *
     * @return bool
     */
    private function is_terms_accepted()
    {
        return get_option($this->option_name) == '1';
    }

    /**
     * Redireciona para a página de aceitação dos termos após a ativação do plugin.
     */
    public function redirect_to_accept_terms($plugin, $network_wide)
    {
        if ($plugin === plugin_basename(__FILE__) && !$this->is_terms_accepted()) {
            wp_redirect(admin_url('admin.php?page=' . $this->settings_page_slug));
            exit;
        }
    }

            /**
         * Redefine a aceitação dos termos e condições.
         */
        public function reset_terms_acceptance()
        {
            delete_option($this->option_name);
        }


        /**
         * Adiciona o script de redirecionamento para a página de aceitação.
         */
        public function enqueue_scripts()
        {
            if (!isset($_GET['page']) || $_GET['page'] !== $this->settings_page_slug) {
                return;
            }
            wp_enqueue_script('eupago-redirect', plugin_dir_url(__FILE__) . 'assets/js/redirect.js', [], false, true);
        }
    
        /**
         * Return an instance of this class.
         *
         * @return object A single instance of this class.
         */
        public static function get_instance()
        {
            // If the single instance hasn't been set, set it now.
            if (null == self::$instance) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        /**
         * Get templates path.
         *
         * @return string
         */
        public static function get_templates_path()
        {
            return plugin_dir_path(__FILE__) . 'templates/';
        }

        /**
         * Load the plugin text domain for translation.
         */
        public function load_plugin_textdomain()
        {
            load_plugin_textdomain('eupago-gateway-for-woocommerce', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        }

        /**
         * Load css.
         */
        public function load_scripts()
        {
            wp_enqueue_style('admin_style', plugin_dir_url(__FILE__) . 'assets/css/admin_style.css');
            $hpos_enabled = $this->is_hpos_compliant();

            if ($hpos_enabled) {
                wp_enqueue_script('admin_script', plugin_dir_url(__FILE__) . 'assets/js/admin_js_hpos.js', ['jquery'], true);
            } else {
                wp_enqueue_script('admin_script', plugin_dir_url(__FILE__) . 'assets/js/admin_js.js', ['jquery'], true);
            }
            wp_localize_script('admin_script', 'MYajax', [ 'ajax_url' => admin_url('admin-ajax.php') ]);
        }

        /**
         * Action links.
         *
         * @param  array $links
         *
         * @return array
         */
        public function plugin_action_links($links)
        {   
            $current_language = get_locale();

            // English
            $applepay_text_en = __('Apple Pay', 'eupago-gateway-for-woocommerce');
            $api_settings_text_en = __('API Settings', 'eupago-gateway-for-woocommerce');
            $multibanco_text_en = __('Multibanco', 'eupago-gateway-for-woocommerce');
            $mbway_text_en = __('MB WAY', 'eupago-gateway-for-woocommerce');
            $cc_text_en = __('Credit Card', 'eupago-gateway-for-woocommerce');
            $payshop_text_en = __('Payshop', 'eupago-gateway-for-woocommerce');
            $cofidispay_text_en = __('CofidisPay', 'eupago-gateway-for-woocommerce');
            $bizum_text_en = __('Bizum', 'eupago-gateway-for-woocommerce');
            $pix_text_en = __('EuroPix', 'eupago-gateway-for-woocommerce');
            $googlepay_text_en = __('Google Pay', 'eupago-gateway-for-woocommerce');

            // Portuguese
            $applepay_text_pt = __('Apple Pay', 'eupago-gateway-for-woocommerce-pt');
            $api_settings_text_pt = __('Configurações API', 'eupago-gateway-for-woocommerce-pt');
            $multibanco_text_pt = __('Multibanco', 'eupago-gateway-for-woocommerce-pt');
            $mbway_text_pt = __('MB WAY', 'eupago-gateway-for-woocommerce-pt');
            $cc_text_pt = __('Cartão de Crédito', 'eupago-gateway-for-woocommerce-pt');
            $payshop_text_pt = __('Payshop', 'eupago-gateway-for-woocommerce-pt');
            $cofidispay_text_pt = __('CofidisPay', 'eupago-gateway-for-woocommerce-pt');
            $bizum_text_pt = __('Bizum', 'eupago-gateway-for-woocommerce-pt');
            $pix_text_pt = __('EuroPix', 'eupago-gateway-for-woocommerce-pt');
            $googlepay_text_pt = __('Google Pay', 'eupago-gateway-for-woocommerce-pt');

            // Spanish
            $applepay_text_es = __('Apple Pay', 'eupago-gateway-for-woocommerce-es');
            $api_settings_text_es = __('Configuracions API', 'eupago-gateway-for-woocommerce-es');
            $multibanco_text_es = __('Multibanco', 'eupago-gateway-for-woocommerce-es');
            $mbway_text_es = __('MB WAY', 'eupago-gateway-for-woocommerce-es');
            $cc_text_es = __('Tarjeta de Crédito', 'eupago-gateway-for-woocommerce-es');
            $payshop_text_es = __('Payshop', 'eupago-gateway-for-woocommerce-es');
            $cofidispay_text_es = __('CofidisPay', 'eupago-gateway-for-woocommerce-es');
            $bizum_text_es = __('Bizum', 'eupago-gateway-for-woocommerce-es');
            $pix_text_es = __('EuroPix', 'eupago-gateway-for-woocommerce-es');
            $googlepay_text_es = __('Google Pay', 'eupago-gateway-for-woocommerce-es');

            switch ($current_language) {
                case 'es_ES':
                    $api_settings_text = $api_settings_text_es;
                    $multibanco_text = $multibanco_text_es;
                    $mbway_text = $mbway_text_es;
                    $cc_text = $cc_text_es;
                    $payshop_text = $payshop_text_es;
                    $cofidispay_text = $cofidispay_text_es;
                    $bizum_text = $bizum_text_es;
                    $pix_text = $pix_text_es;
                    $googlepay_text = $googlepay_text_es;
                    $applepay_text = $applepay_text_es;
                    break;
                case 'pt_PT':
                    $api_settings_text = $api_settings_text_pt;
                    $multibanco_text = $multibanco_text_pt;
                    $mbway_text = $mbway_text_pt;
                    $cc_text = $cc_text_pt;
                    $payshop_text = $payshop_text_pt;
                    $cofidispay_text = $cofidispay_text_pt;
                    $bizum_text = $bizum_text_pt;
                    $pix_text = $pix_text_pt;
                    $googlepay_text = $googlepay_text_pt;
                    $applepay_text = $applepay_text_pt;
                    break;
                default:
                    $api_settings_text = $api_settings_text_en;
                    $multibanco_text = $multibanco_text_en;
                    $mbway_text = $mbway_text_en;
                    $cc_text = $cc_text_en;
                    $payshop_text = $payshop_text_en;
                    $cofidispay_text = $cofidispay_text_en;
                    $bizum_text = $bizum_text_en;
                    $pix_text = $pix_text_en;
                    $googlepay_text = $googlepay_text_en;
                    $applepay_text = $applepay_text_en;
                    break;
            }

            $api_settings_url = esc_url(admin_url('admin.php?page=eupago'));
            $multibanco_url = esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&section=eupago_multibanco'));
            $mbway_url = esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&section=eupago_mbway'));
            $cc_url = esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&section=eupago_cc'));
            $payshop_url = esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&section=eupago_payshop'));
            $cofidispay_url = esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&section=eupago_cofidispay'));
            $bizum_url = esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&section=eupago_bizum'));
            $pix_url = esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&section=eupago_pix'));
            $googlepay_url = esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&section=eupago_googlepay'));
            $applepay_url = esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&section=eupago_applepay'));

            $plugin_links = [];

            if (!$this->is_terms_accepted()) {
                $plugin_links[] = '<a href="' . admin_url('admin.php?page=' . $this->settings_page_slug) . '">' . __('Termos e Condições', 'eupago-gateway-for-woocommerce') . '</a>';
            }

            $plugin_links[] = '<a href="' . $api_settings_url . '">' . $api_settings_text . '</a>';
            $plugin_links[] = '<a href="' . $multibanco_url . '">' . $multibanco_text . '</a>';
            $plugin_links[] = '<a href="' . $mbway_url . '">' . $mbway_text . '</a>';
            $plugin_links[] = '<a href="' . $cc_url . '">' . $cc_text . '</a>';
            $plugin_links[] = '<a href="' . $payshop_url . '">' . $payshop_text . '</a>';
            $plugin_links[] = '<a href="' . $cofidispay_url . '">' . $cofidispay_text . '</a>';
            $plugin_links[] = '<a href="' . $bizum_url . '">' . $bizum_text . '</a>';
            $plugin_links[] = '<a href="' . $pix_url . '">' . $pix_text . '</a>';
            $plugin_links[] = '<a href="' . $googlepay_url . '">' . $googlepay_text . '</a>';
            $plugin_links[] = '<a href="' . $applepay_url . '">' . $applepay_text . '</a>';

            foreach ($plugin_links as $link) {
                if (!in_array($link, $links)) {
                    $links[] = $link;
                }
            }

            return $links;
        }

        /**
         * Includes.
         */
        private function includes()
        {
            include_once 'includes/class-wc-eupago-integration.php';

            include_once 'includes/class-wc-eupago-api.php';

            include_once 'includes/class-wc-eupago-multibanco.php';

            include_once 'includes/class-wc-eupago-payshop.php';

            include_once 'includes/class-wc-eupago-mbway.php';

            include_once 'includes/class-wc-eupago-bizum.php';

            include_once 'includes/class-wc-eupago-pix.php';

            include_once 'includes/class-wc-eupago-cofidispay.php';

            include_once 'includes/class-wc-eupago-cc.php';

            include_once 'includes/class-wc-eupago-paysafecard.php';

            include_once 'includes/class-wc-eupago-callback.php';

            include_once 'includes/hooks/hooks-refund.php';

            include_once 'includes/hooks/hooks-sms.php';

            include_once 'includes/views/eupago-admin-page.php';

            include_once 'includes/class-wc-eupago-googlepay.php';

            include_once 'includes/class-wc-eupago-applepay.php';
        }


        /**
         * Add the gateway to WooCommerce.
         *
         * @param   array $methods WooCommerce payment methods.
         *
         * @return  array          Payment methods with Eupago.
         */
        public function add_gateway($methods)
        {

            if ($this->is_terms_accepted()) {
                $methods[] = 'WC_Eupago_Multibanco';
                $methods[] = 'WC_Eupago_PayShop';
                $methods[] = 'WC_Eupago_MBWAY';
                $methods[] = 'WC_Eupago_CofidisPay';
                $methods[] = 'WC_Eupago_CC';
                $methods[] = 'WC_Eupago_PF';
                $methods[] = 'WC_Eupago_Bizum';
                $methods[] = 'WC_Eupago_Pix';
                $methods[] = 'WC_Eupago_GooglePay';
                $methods[] = 'WC_Eupago_ApplePay';
            }else{
                $this->show_error_message = true;
            }
            return $methods;
        }

        public function display_admin_notice() {
            if (!empty($this->show_error_message)) {
                $terms_page_url = admin_url('admin.php?page=' . $this->settings_page_slug);
        
                // Defina as variáveis de texto com base no idioma atual do site
                $error_message_title = '';
                $error_message_body = '';
                $error_message_link_text = '';
        
                // Exemplo de verificação de idioma (você pode usar a função que melhor se adequar ao seu projeto)
                $current_language = get_locale(); // Esta função retorna o idioma atual do WordPress
        
                if ($current_language == 'es_ES') { // Para Espanhol
                    $error_message_title = 'Error:';
                    $error_message_body = 'Los términos y condiciones no han sido aceptados. Los métodos de pago no están disponibles.';
                    $error_message_link_text = 'Haga clic aquí para revisar y aceptar los términos y condiciones.';
                } elseif ($current_language == 'en_US') { // Para Inglês
                    $error_message_title = 'Error:';
                    $error_message_body = 'Terms and conditions have not been accepted. Payment gateways are not available.';
                    $error_message_link_text = 'Click here to review and accept the terms and conditions.';
                } else { // Idioma padrão, por exemplo, Português
                    $error_message_title = 'Erro:';
                    $error_message_body = 'Os termos e condições não foram aceitos. Os gateways de pagamento não estão disponíveis.';
                    $error_message_link_text = 'Clique aqui para revisar e aceitar os termos e condições.';
                }
        
                echo '<div class="notice notice-error is-dismissible">';
                echo '<p><strong>' . esc_html($error_message_title) . '</strong> ' . esc_html($error_message_body) . ' ';
                echo '<a href="' . esc_url($terms_page_url) . '">' . esc_html($error_message_link_text) . '</a></p>';
                echo '</div>';
            }
        }
        

        /* Add a new integration to WooCommerce. */
        public function add_integration($integrations)
        {
            $integrations[] = 'WC_Eupago_Integration';

            return $integrations;
        }
        /* Add a new generate ref after recalculate on admin panel. */
        public function new_ref_order_button($order_id)
        {
            if (current_user_can('manage_options')) { // Check if user is an admin
                $order_status = $order_id->get_status();
                $payment_method = $order_id->get_payment_method(); // Get payment method
                $button_text = 'Gerar nova referência'; // Set your button text here
                $id = $order_id->get_id();

                if ($order_status == 'on-hold' && $payment_method == 'eupago_multibanco' || $order_status == 'on-hold' && $payment_method == 'eupago_payshop') {
                    echo '<a class="button generate-ref" href="' . wp_nonce_url(admin_url('admin-ajax.php?action=generate_ref&order_id=' . $order_id), 'generate_ref_' . $id) . '">' . $button_text . '</a>';
                }
            }
        }

        // Custom action function for the custom button
        public function new_ref_order_button_action()
        {
            if (isset($_GET['order_id'])) {
                $order_id = $_GET['order_id'];
                $order_data = json_decode(str_replace('\"', '"', $order_id));
                $id = intval($order_data->id);
                $payment_method = $order_data->payment_method;

                if (wp_verify_nonce($_REQUEST['_wpnonce'], 'generate_ref_' . $id) && current_user_can('manage_options') && ($payment_method == 'eupago_multibanco' || $payment_method == 'eupago_payshop')) { // Verify the nonce and user capability
                    if($payment_method == 'eupago_multibanco') {
                        include 'includes/class-wc-eupago-multibanco.php'; // Include the file that contains the function
                        // Instantiate the class
                        $eupago_multibanco = new WC_Eupago_Multibanco();
                        // Call the function on the class instance
                        $eupago_multibanco->process_payment($id);
                    } elseif ($payment_method == 'eupago_payshop') {
                        include 'includes/class-wc-eupago-payshop.php'; // Include the file that contains the function
                        // Instantiate the class
                        $eupago_payshop = new WC_EuPago_PayShop();
                        // Call the function on the class instance
                        $eupago_payshop->process_payment($id);
                    }
                }
            }
        }

        /* Order metabox to show Multibanco payment details */
        public function eupago_order_add_meta_box()
        {
            $hpos_enabled = $this->is_hpos_compliant();

            $metabox = 'mbeupago_order_meta_box_html';
            $screen = $hpos_enabled ? wc_get_page_screen_id('shop-order') : 'shop_order';

            if ($hpos_enabled) {
                $metabox = 'mbeupago_order_meta_box_html_hpos';
            }

            add_meta_box('woocommerce_eupago', __('EuPago Payment Details', 'eupago-gateway-for-woocommerce'), [$this, $metabox], $screen, 'side', 'core');
        }

        public function mbeupago_order_meta_box_html($post)
        {
            include 'includes/views/order-meta-box.php';
        }

        public function mbeupago_order_meta_box_html_hpos($post)
        {
            include 'includes/views/order-meta-box-hpos.php';
        }

        private function is_hpos_compliant()
        {
            // Check if HPOS compliance is enabled
            if (version_compare(WC_VERSION, '7.1', '>=')) {
                $customOrdersTableController = wc_get_container()->get(\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class);

                if ($customOrdersTableController->custom_orders_table_usage_is_enabled()) {
                    return true;
                }
            }

            return false;
        }

        // function save_vat_number( $order_id ){
        // 	update_post_meta( $order_id, 'nif', wc_clean( $_POST[ 'nif' ] ) );
        // }

        public function save_vat_number($order_id)
        {
            $order = wc_get_order($order_id);
            $order->update_meta_data('nif', wc_clean($_POST['nif']));
            $order->save();
        }

        public function order_meta_vat_number($order)
        {
            ?>
				<br class="clear" />
				<h3>VAT identification number <a href="#" class="edit_address">Edit</a></h3>
				<?php
                    /*
                    * get all the meta data values we need
                    */
                    $nif = $order->get_meta('nif');
            ?>
				<div class="address">
							<p><strong>NIF:</strong> <?php echo esc_html($nif) ?></p>
				</div>
				<div class="edit_address">
					<?php

                    woocommerce_wp_text_input([
                        'id' => 'nif',
                        'label' => 'NIF:',
                        'value' => $nif,
                        'wrapper_class' => 'form-field-wide',
                    ]);

            ?>
				</div>
			<?php
        }

        /* WooCommerce fallback notice. */
        public function woocommerce_missing_notice()
        {
            echo '<div class="error"><p>' . sprintf(__('Eupago for WooCommerce Gateway depends on the last version of %s to work!', 'eupago-gateway-for-woocommerce'), '<a href="https://wordpress.org/plugins/woocommerce/">' . __('WooCommerce', 'eupago-gateway-for-woocommerce') . '</a>') . '</p></div>';
        }

        public function woocommerce_payment_complete_reduce_order_stock($reduce, $order, $payment_method, $stock_when)
        {
            if ($reduce) {
                // $order = new WC_Order( $order_id );
                if ($order->get_payment_method() == $payment_method) {
                    if (version_compare(WC_VERSION, '3.4.0', '>=')) {
                        // After 3.4.0
                        if ($order->has_status([ 'pending', 'on-hold' ])) {
                            // Pending payment
                            return $stock_when == 'order' ? true : false;
                        } else {
                            // Payment done
                            return $stock_when == '' ? true : false;
                        }
                    } else {
                        // Before 3.4.0 - This only runs for paid orders
                        return $stock_when == 'order' ? true : false;
                    }
                } else {
                    return $reduce;
                }
            } else {
                // Already reduced
                return false;
            }
        }

        public function woocommerce_blocks_add_payment_methods()
        {
            $file_path_mb = __DIR__ . '/includes/woocommerce-blocks/multibanco/MultibancoBlock.php';
            $file_path_mbw = __DIR__ . '/includes/woocommerce-blocks/mbway/MbwBlock.php';
            $file_path_cc = __DIR__ . '/includes/woocommerce-blocks/cc/CcBlock.php';
            $file_path_payshop = __DIR__ . '/includes/woocommerce-blocks/payshop/PayshopBlock.php';
            $file_path_cofidispay = __DIR__ . '/includes/woocommerce-blocks/cofidispay/CofidisPayBlock.php';
            $file_path_bizum = __DIR__ . '/includes/woocommerce-blocks/bizum/BizumBlock.php';
            $file_path_pix = __DIR__ . '/includes/woocommerce-blocks/pix/PixBlock.php';
            $file_path_googlepay = __DIR__ . '/includes/woocommerce-blocks/googlepay/GooglePayBlock.php';
            $file_path_applepay = __DIR__ . '/includes/woocommerce-blocks/applepay/ApplePayBlock.php';

            if (file_exists($file_path_applepay)) {
                require_once $file_path_applepay;

                add_action(
                    'woocommerce_blocks_payment_method_type_registration',
                    function (\Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
                        $payment_method_registry->register(new \Automattic\WooCommerce\Blocks\Payments\Integrations\ApplePayBlock());
                    }
                );
            }

            if ( file_exists( $file_path_googlepay ) ) {
                require_once $file_path_googlepay;

                add_action(
                    'woocommerce_blocks_payment_method_type_registration',
                    function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
                        $payment_method_registry->register(new \Automattic\WooCommerce\Blocks\Payments\Integrations\GooglePayBlock());
                    }
                );
            }

            if (file_exists($file_path_bizum)) {
                require_once $file_path_bizum;
            
                add_action(
                    'woocommerce_blocks_payment_method_type_registration',
                    function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
                        $payment_method_registry->register(new \Automattic\WooCommerce\Blocks\Payments\Integrations\BizumBlock());
                    }
                );
            }

            if (file_exists($file_path_pix)) {
                require_once $file_path_pix;
            
                add_action(
                    'woocommerce_blocks_payment_method_type_registration',
                    function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
                        $payment_method_registry->register(new \Automattic\WooCommerce\Blocks\Payments\Integrations\PixBlock());
                    }
                );
            }

            if (file_exists($file_path_mb)) {
                require_once $file_path_mb;

                add_action(
                    'woocommerce_blocks_payment_method_type_registration',
                    function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
                        $payment_method_registry->register(new \Automattic\WooCommerce\Blocks\Payments\Integrations\MultibancoBlock());
                    }
                );
            }

            if (file_exists($file_path_mbw)) {
                require_once $file_path_mbw;

                add_action(
                    'woocommerce_blocks_payment_method_type_registration',
                    function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
                        $payment_method_registry->register(new \Automattic\WooCommerce\Blocks\Payments\Integrations\MbwBlock());
                    }
                );
            }

            if (file_exists($file_path_cc)) {
                require_once $file_path_cc;

                add_action(
                    'woocommerce_blocks_payment_method_type_registration',
                    function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
                        $payment_method_registry->register(new \Automattic\WooCommerce\Blocks\Payments\Integrations\CcBlock());
                    }
                );
            }

            if (file_exists($file_path_payshop)) {
                require_once $file_path_payshop;

                add_action(
                    'woocommerce_blocks_payment_method_type_registration',
                    function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
                        $payment_method_registry->register(new \Automattic\WooCommerce\Blocks\Payments\Integrations\PayshopBlock());
                    }
                );
            }

            if (file_exists($file_path_cofidispay)) {
                require_once $file_path_cofidispay;

                add_action(
                    'woocommerce_blocks_payment_method_type_registration',
                    function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
                        $payment_method_registry->register(new \Automattic\WooCommerce\Blocks\Payments\Integrations\CofidisPayBlock());
                    }
                );
            }
        }
    }

add_action('plugins_loaded', [ 'WC_Eupago', 'get_instance' ]);

endif;

register_activation_hook(__FILE__, function () {
    $plugin = WC_Eupago::get_instance();
    $plugin->reset_terms_acceptance();
});
