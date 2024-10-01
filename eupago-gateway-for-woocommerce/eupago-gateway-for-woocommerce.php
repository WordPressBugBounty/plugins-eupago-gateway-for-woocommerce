<?php
/**
* Plugin Name: Eupago Gateway For Woocommerce
* Plugin URI:
* Description: This plugin allows customers to pay their orders with Multibanco, MB WAY, Payshop, Credit Card and CofidisPay with Eupago’s gateway.
* Version: 4.2.0
* Author: Eupago
* Author URI: https://www.eupago.pt/
* Text Domain: eupago-gateway-for-woocommerce
* WC tested up to: 6.4.2
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
        public const VERSION = '4.2.0';

        /**
         * Instance of this class.
         *
         * @var object
         */
        protected static $instance = null;

        /**
         * Initialize the plugin public actions.
         */
        public function __construct()
        {

            // Load plugin text domain
            add_action('init', [ $this, 'load_plugin_textdomain' ]);

            // Load CSS and JS
            add_action('admin_enqueue_scripts', [ $this, 'load_scripts' ]);

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

            // Verifica o idioma atual do WooCommerce
            $current_language = get_locale();

            // Textos em inglês
            $api_settings_text_en = __('API Settings', 'eupago-gateway-for-woocommerce');
            $multibanco_text_en = __('Multibanco', 'eupago-gateway-for-woocommerce');
            $mbway_text_en = __('MB WAY', 'eupago-gateway-for-woocommerce');
            $cc_text_en = __('Credit Card', 'eupago-gateway-for-woocommerce');
            $payshop_text_en = __('Payshop', 'eupago-gateway-for-woocommerce');
            $cofidispay_text_en = __('CofidisPay', 'eupago-gateway-for-woocommerce');

            // Textos em português
            $api_settings_text_pt = __('Configurações API', 'eupago-gateway-for-woocommerce-pt');
            $multibanco_text_pt = __('Multibanco', 'eupago-gateway-for-woocommerce-pt');
            $mbway_text_pt = __('MB WAY', 'eupago-gateway-for-woocommerce-pt');
            $cc_text_pt = __('Cartão de Crédito', 'eupago-gateway-for-woocommerce-pt');
            $payshop_text_pt = __('Payshop', 'eupago-gateway-for-woocommerce-pt');
            $cofidispay_text_pt = __('CofidisPay', 'eupago-gateway-for-woocommerce-pt');

            // Textos em espanhol
            $api_settings_text_es = __('Configuracions API', 'eupago-gateway-for-woocommerce-es');
            $multibanco_text_es = __('Multibanco', 'eupago-gateway-for-woocommerce-es');
            $mbway_text_es = __('MB WAY', 'eupago-gateway-for-woocommerce-es');
            $cc_text_es = __('Tarjeta de Crédito', 'eupago-gateway-for-woocommerce-es');
            $payshop_text_es = __('Payshop', 'eupago-gateway-for-woocommerce-es');
            $cofidispay_text_es = __('CofidisPay', 'eupago-gateway-for-woocommerce-es');

            // Determinar os textos com base no idioma atual
            switch ($current_language) {
                case 'es_ES':
                    $api_settings_text = $api_settings_text_es;
                    $multibanco_text = $multibanco_text_es;
                    $mbway_text = $mbway_text_es;
                    $cc_text = $cc_text_es;
                    $payshop_text = $payshop_text_es;
                    $cofidispay_text = $cofidispay_text_es;
                    break;
                case 'pt_PT':
                    $api_settings_text = $api_settings_text_pt;
                    $multibanco_text = $multibanco_text_pt;
                    $mbway_text = $mbway_text_pt;
                    $cc_text = $cc_text_pt;
                    $payshop_text = $payshop_text_pt;
                    $cofidispay_text = $cofidispay_text_pt;
                    break;
                default:
                    $api_settings_text = $api_settings_text_en;
                    $multibanco_text = $multibanco_text_en;
                    $mbway_text = $mbway_text_en;
                    $cc_text = $cc_text_en;
                    $payshop_text = $payshop_text_en;
                    $cofidispay_text = $cofidispay_text_en;
                    break;
            }

            
            $api_settings_url = esc_url(admin_url('admin.php?page=eupago'));
            $multibanco_url = esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&section=eupago_multibanco'));
            $mbway_url = esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&section=eupago_mbway'));
            $cc_url = esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&section=eupago_cc'));
            $payshop_url = esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&section=eupago_payshop'));
            $cofidispay_url = esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&section=eupago_cofidispay'));

            // Criar os links
            $plugin_links = [
                '<a href="' . $api_settings_url . '">' . $api_settings_text . '</a>',
                '<a href="' . $multibanco_url . '">' . $multibanco_text . '</a>',
                '<a href="' . $mbway_url . '">' . $mbway_text . '</a>',
                '<a href="' . $cc_url . '">' . $cc_text . '</a>',
                '<a href="' . $payshop_url . '">' . $payshop_text . '</a>',
                '<a href="' . $cofidispay_url . '">' . $cofidispay_text . '</a>',
            ];

            // Retornar os links
            return array_merge($plugin_links, $links);
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

            include_once 'includes/class-wc-eupago-cofidispay.php';

            include_once 'includes/class-wc-eupago-cc.php';

            include_once 'includes/class-wc-eupago-paysafecard.php';

            include_once 'includes/class-wc-eupago-callback.php';

            include_once 'includes/hooks/hooks-refund.php';

            include_once 'includes/hooks/hooks-sms.php';

            include_once 'includes/views/eupago-admin-page.php';
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
            $methods[] = 'WC_Eupago_Multibanco';
            $methods[] = 'WC_Eupago_PayShop';
            $methods[] = 'WC_Eupago_MBWAY';
            $methods[] = 'WC_Eupago_CofidisPay';
            $methods[] = 'WC_Eupago_CC';
            $methods[] = 'WC_Eupago_PF';

            return $methods;
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
