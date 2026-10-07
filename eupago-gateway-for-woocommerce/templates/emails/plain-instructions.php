<?php
/**
 * Plain-text email instructions.
 *
 * Same data as the HTML card (WC_Eupago_Email_Instructions), one "label: value"
 * line per row.
 *
 * @package eupago-gateway-for-woocommerce/Templates
 * @version 0.2
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

$block = WC_Eupago_Email_Instructions::build($method, get_defined_vars());

if (empty($block['rows'])) {
    return;
}

if (!empty($instructions)) {
    echo esc_html(wp_strip_all_tags(wptexturize($instructions))) . "\n\n";
}

echo esc_html(strtoupper($block['title'])) . "\n";
echo str_repeat('-', 40) . "\n";

foreach ($block['rows'] as $row) {
    $value = $row['type'] === 'html' ? wp_strip_all_tags($row['value']) : $row['value'];
    echo esc_html($row['label']) . ': ' . esc_html(html_entity_decode($value, ENT_QUOTES, get_bloginfo('charset'))) . "\n";
}

if ($block['note'] !== '') {
    echo "\n" . esc_html($block['note']) . "\n";
}

echo "\n";
