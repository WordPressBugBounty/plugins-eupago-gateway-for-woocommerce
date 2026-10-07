<?php
/**
 * HTML email instructions.
 *
 * Renders the "payment instructions" card inside WooCommerce's order emails.
 * The data (logo, rows, note) comes from WC_Eupago_Email_Instructions so every
 * payment method gets a block and the plain-text template shows the same thing.
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
    echo wp_kses_post(wpautop(wptexturize($instructions)));
}

$row_count = count($block['rows']);
$label_css = 'padding: 11px 0; font-size: 13px; line-height: 1.4; color: #6b7785; vertical-align: top;';
$value_css = 'padding: 11px 0 11px 16px; font-size: 15px; line-height: 1.4; color: #1f2933; font-weight: 600; text-align: right; vertical-align: top;';
$divider   = ' border-bottom: 1px solid #edf0f3;';
?>
<table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" width="100%" style="max-width: 420px; margin: 18px auto; border-collapse: separate; border-spacing: 0; border: 1px solid #dfe3e8; border-radius: 10px; background-color: #ffffff; font-family: Helvetica, Arial, sans-serif; color: #1f2933;">
    <tr>
        <td style="padding: 18px 20px 14px; text-align: center; background-color: #f7f9fb; border-bottom: 1px solid #e6eaef; border-radius: 10px 10px 0 0;">
            <div style="font-size: 11px; line-height: 1.4; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; color: #6b7785;"><?php echo esc_html($block['title']); ?></div>
            <?php if ($block['logo'] !== '') : ?>
                <?php $logo_height = !empty($block['logo_height']) ? (int) $block['logo_height'] : 36; ?>
                <img src="<?php echo esc_url($block['logo']); ?>" alt="<?php echo esc_attr($payment_name); ?>" title="<?php echo esc_attr($payment_name); ?>" height="<?php echo $logo_height; ?>" style="height: <?php echo $logo_height; ?>px; width: auto; max-width: 180px; margin: 10px 0 0; display: inline-block; border: 0;" />
            <?php endif; ?>
        </td>
    </tr>
    <tr>
        <td style="padding: 4px 20px 6px;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse: collapse;">
                <?php foreach ($block['rows'] as $index => $row) :
                    $last = ($index === $row_count - 1);
                    $tdl  = $label_css . ($last ? '' : $divider);
                    $tdv  = $value_css . ($last ? '' : $divider);
                ?>
                    <?php if ($row['type'] === 'image') : ?>
                        <tr>
                            <td colspan="2" style="padding: 14px 0; text-align: center;<?php echo $last ? '' : $divider; ?>">
                                <div style="font-size: 13px; color: #6b7785; margin-bottom: 8px;"><?php echo esc_html($row['label']); ?></div>
                                <img src="<?php echo esc_url($row['value']); ?>" alt="<?php echo esc_attr($row['label']); ?>" width="160" style="width: 160px; height: auto; margin: 0; display: inline-block; border: 0;" />
                            </td>
                        </tr>
                    <?php elseif ($row['type'] === 'code') : ?>
                        <tr>
                            <td colspan="2" style="padding: 11px 0;<?php echo $last ? '' : $divider; ?>">
                                <div style="font-size: 13px; color: #6b7785; margin-bottom: 6px;"><?php echo esc_html($row['label']); ?></div>
                                <div style="font-family: Menlo, Consolas, 'Courier New', monospace; font-size: 12px; line-height: 1.5; color: #1f2933; word-break: break-all; background-color: #f7f9fb; border: 1px solid #e6eaef; border-radius: 6px; padding: 8px 10px;"><?php echo esc_html($row['value']); ?></div>
                            </td>
                        </tr>
                    <?php elseif ($row['type'] === 'link') : ?>
                        <tr>
                            <td colspan="2" style="padding: 11px 0;<?php echo $last ? '' : $divider; ?>">
                                <div style="font-size: 13px; color: #6b7785; margin-bottom: 6px;"><?php echo esc_html($row['label']); ?></div>
                                <a href="<?php echo esc_url($row['value']); ?>" style="font-size: 13px; line-height: 1.5; color: #1465aa; word-break: break-all; text-decoration: underline;"><?php echo esc_html($row['value']); ?></a>
                            </td>
                        </tr>
                    <?php elseif ($row['type'] === 'html') : ?>
                        <tr>
                            <td style="<?php echo $tdl; ?>"><?php echo esc_html($row['label']); ?></td>
                            <td style="<?php echo $tdv; ?> white-space: nowrap;"><?php echo wp_kses_post($row['value']); ?></td>
                        </tr>
                    <?php else : ?>
                        <tr>
                            <td style="<?php echo $tdl; ?>"><?php echo esc_html($row['label']); ?></td>
                            <td style="<?php echo $tdv; ?> white-space: nowrap; letter-spacing: 0.3px;"><?php echo esc_html($row['value']); ?></td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </table>
        </td>
    </tr>
    <?php if ($block['note'] !== '' || !empty($block['footer_logo'])) : ?>
        <tr>
            <td style="padding: 15px 5px 15px; text-align: center; background-color: #1f2933; border-radius: 0 0 10px 10px;">
                <?php if ($block['note'] !== '') : ?>
                    <div style="font-size: 12px; line-height: 1.5; color: #cbd2d9;<?php echo !empty($block['footer_logo']) ? ' margin-bottom: 8px;' : ''; ?>"><?php echo esc_html($block['note']); ?></div>
                <?php endif; ?>
                <?php if (!empty($block['footer_logo'])) : ?>
                    <a href="https://www.eupago.pt/"><img src="<?php echo esc_url($block['footer_logo']); ?>" alt="Eupago" title="Eupago" height="16" style="height: 16px; width: auto; margin: 0; display: inline-block; border: 0; vertical-align: middle;" /></a>
                <?php endif; ?>
            </td>
        </tr>
    <?php endif; ?>
</table>
