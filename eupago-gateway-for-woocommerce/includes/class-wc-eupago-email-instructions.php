<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

class WC_Eupago_Email_Instructions
{
    /**
     * Logo shown in the header of the block, per gateway id: file in
     * assets/images and the height it is displayed at, in px.
     *
     */
    const LOGOS = [
        'eupago_multibanco' => ['multibanco_banner.png', 36],
        'eupago_mbway'      => ['mbway_banner.png', 36],
        'eupago_payshop'    => ['payshop_banner.png', 36],
        'eupago_pagaqui'    => ['pagaqui_logo.png', 25],
        'eupago_pix'        => ['pix_icon.png', 28],
        'eupago_bizum'      => ['bizum_icon.png', 32],
        'eupago_cc'         => ['cc_icon.jpg', 25],
        'eupago_googlepay'  => ['googlepay_icon.png', 52],
        'eupago_applepay'   => ['applepay_icon.png', 52],
        'eupago_floa'       => ['floa_blue.png', 36],
        'eupago_cofidispay' => ['cofidispay.png', 25],
        'eupago_pf'         => ['pf_icon.png', 36],
    ];

    /**
     * Methods whose reference is a run of digits shown in groups of three
     * (Multibanco style).
     */
    const GROUPED_REFERENCE = ['eupago_multibanco', 'eupago_mbway', 'eupago_payshop', 'eupago_pagaqui'];

    /**
     * Describes the block for one order/method.
     *
     * @param string $method Gateway id (eupago_*).
     * @param array  $vars   Template variables: payment_name, referencia, entidade,
     *                       data_fim, pixCode, pixImage, redirect_url, order_total.
     * @return array {
     *   @type string $title Block title.
     *   @type string $logo  Logo URL, empty when unknown.
     *   @type array  $rows  List of ['label' => string, 'value' => string, 'type' => text|link|image|code].
     *   @type string $note  Footer note, empty when there is none.
     * }
     */
    public static function build($method, array $vars)
    {
        $get = function ($key) use ($vars) {
            return isset($vars[$key]) ? (string) $vars[$key] : '';
        };

        $reference = trim($get('referencia'));
        if ($reference !== '' && in_array($method, self::GROUPED_REFERENCE, true)) {
            $reference = trim(chunk_split(preg_replace('/\s+/', '', $reference), 3, ' '));
        }

        $amount = wc_price($get('order_total'), ['currency' => 'EUR']);
        $rows   = [];
        $note   = '';

        switch ($method) {
            case 'eupago_multibanco':
                $rows[] = self::row(__('Entity', 'eupago-gateway-for-woocommerce'), $get('entidade'));
                $rows[] = self::row(__('Reference', 'eupago-gateway-for-woocommerce'), $reference);
                $rows[] = self::row(__('Value', 'eupago-gateway-for-woocommerce'), $amount, 'html');
                if ($get('data_fim') !== '') {
                    $rows[] = self::row(__('Limit Date', 'eupago-gateway-for-woocommerce'), $get('data_fim'));
                }
                $note = __('The receipt issued by the ATM machine is a proof of payment. Keep it.', 'eupago-gateway-for-woocommerce');
                break;

            case 'eupago_mbway':
                $rows[] = self::row(__('Entity', 'eupago-gateway-for-woocommerce'), 'EUPAGO.PT');
                $rows[] = self::row(__('Reference', 'eupago-gateway-for-woocommerce'), $reference);
                $rows[] = self::row(__('Value', 'eupago-gateway-for-woocommerce'), $amount, 'html');
                $note = __('Accept this payment at your MBWAY mobile app.', 'eupago-gateway-for-woocommerce');
                break;

            case 'eupago_payshop':
                $rows[] = self::row(__('Reference', 'eupago-gateway-for-woocommerce'), $reference);
                $rows[] = self::row(__('Value', 'eupago-gateway-for-woocommerce'), $amount, 'html');
                $note = __('The receipt issued by the ATM machine is a proof of payment. Keep it.', 'eupago-gateway-for-woocommerce');
                break;

            case 'eupago_pagaqui':
                $rows[] = self::row(__('Reference', 'eupago-gateway-for-woocommerce'), $reference);
                $rows[] = self::row(__('Value', 'eupago-gateway-for-woocommerce'), $amount, 'html');
                $note = __('The receipt issued by Pagaqui serves as proof of payment. Please keep it.', 'eupago-gateway-for-woocommerce');
                break;

            case 'eupago_pix':
                $rows[] = self::row(__('Reference', 'eupago-gateway-for-woocommerce'), $reference);
                $rows[] = self::row(__('Value', 'eupago-gateway-for-woocommerce'), $amount, 'html');
                if ($get('pixImage') !== '') {
                    $rows[] = self::row(__('QR Code', 'eupago-gateway-for-woocommerce'), $get('pixImage'), 'image');
                }
                if ($get('pixCode') !== '') {
                    $rows[] = self::row(__('EuroPix Code', 'eupago-gateway-for-woocommerce'), $get('pixCode'), 'code');
                }
                break;

            case 'eupago_bizum':
                $rows[] = self::row(__('Reference', 'eupago-gateway-for-woocommerce'), $reference);
                $rows[] = self::row(__('Value', 'eupago-gateway-for-woocommerce'), $amount, 'html');
                if ($get('redirect_url') !== '') {
                    $rows[] = self::row(__('Payment Link', 'eupago-gateway-for-woocommerce'), $get('redirect_url'), 'link');
                }
                break;

            default:
                $rows[] = self::row(__('Payment method', 'eupago-gateway-for-woocommerce'), $get('payment_name'));
                if ($reference !== '') {
                    $rows[] = self::row(__('Reference', 'eupago-gateway-for-woocommerce'), $reference);
                }
                $rows[] = self::row(__('Value', 'eupago-gateway-for-woocommerce'), $amount, 'html');
                break;
        }

        // Drop rows whose value came back empty (missing meta) instead of
        // printing a blank cell.
        $rows = array_values(array_filter($rows, function ($row) {
            return $row['value'] !== '';
        }));

        $block = [
            'title'       => __('Payment instructions', 'eupago-gateway-for-woocommerce'),
            'logo'        => self::logo_url($method),
            'logo_height' => self::logo_height($method),
            'rows'        => $rows,
            'note'        => $note,
            'footer_logo' => plugins_url('includes/views/images/eupago_logo.png', dirname(__FILE__)),
        ];

        /**
         * Lets a theme or plugin adjust the payment block of the order emails
         * without overriding the templates: change the note, add or drop rows
         * (['label', 'value', 'type' => text|html|link|image|code]), swap the logo.
         *
         * @param array  $block  Block as described in the return doc of build().
         * @param string $method Gateway id (eupago_*).
         * @param array  $vars   Template variables the block was built from.
         */
        return apply_filters('eupago_email_payment_block', $block, $method, $vars);
    }

    /**
     * @param string $method
     * @return string Empty when the method has no logo.
     */
    public static function logo_url($method)
    {
        if (!isset(self::LOGOS[$method])) {
            return '';
        }

        return plugins_url('assets/images/' . self::LOGOS[$method][0], dirname(__FILE__));
    }

    /**
     * @param string $method
     * @return int Display height in px (0 when the method has no logo).
     */
    public static function logo_height($method)
    {
        return isset(self::LOGOS[$method]) ? (int) self::LOGOS[$method][1] : 0;
    }

    private static function row($label, $value, $type = 'text')
    {
        return ['label' => $label, 'value' => (string) $value, 'type' => $type];
    }
}
