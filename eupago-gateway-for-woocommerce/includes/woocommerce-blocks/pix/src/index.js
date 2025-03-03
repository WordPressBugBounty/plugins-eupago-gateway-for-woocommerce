/**
 * External dependencies
 */

const settings_pix = window.wc.wcSettings.getSetting('eupago_pix_data', {});
const defaultLabel_pix = window.wp.i18n.__('Eupago EuroPix', 'eupago_pix');
const label_pix = window.wp.htmlEntities.decodeEntities(settings_pix.title) || window.wp.i18n.__('Eupago EuroPix', 'eupago_pix');

/**
 * Content component
 *
 * @param {*} props Props from payment API.
 */
const ContentPix = (props) => {
    // Empty component with no content
};

/**
 * Label component
 *
 * @param {*} props Props from payment API.
 */
const LabelPix = (props) => {
    var icon = React.createElement('img', {
        src: '/wp-content/plugins/eupago-gateway-for-woocommerce/includes/woocommerce-blocks/pix/pix_banner.png',
        style: {
            display: 'inline',
            marginLeft: '8px', // Adjust the value as needed
        },
    });
    var span = React.createElement('span', {
        className: 'wc-block-components-payment-method-label wc-block-components-payment-method-label--with-icon',
    }, window.wp.htmlEntities.decodeEntities(settings_pix.title) || defaultLabel_pix, icon);
    return span;
};

const Pix = {
    name: 'eupago_pix',
    label: React.createElement(LabelPix, null),
    content: React.createElement(ContentPix, null),
    edit: React.createElement(ContentPix, null),
    icons: null,
    canMakePayment: () => true,
    ariaLabel: label_pix,
    supports: {
        features: settings_pix.supports,
    },
};

window.wc.wcBlocksRegistry.registerPaymentMethod(Pix);