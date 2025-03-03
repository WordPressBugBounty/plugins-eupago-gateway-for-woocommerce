/**
 * External dependencies
 */

const settings_bizum = window.wc.wcSettings.getSetting('eupago_bizum_data', {});
const defaultLabel_bizum = window.wp.i18n.__('Eupago Bizum', 'eupago_bizum');
const label_bizum = window.wp.htmlEntities.decodeEntities(settings_bizum.title) || window.wp.i18n.__('Eupago Bizum', 'eupago_bizum');

/**
 * Content component
 *
 * @param {*} props Props from payment API.
 */
const ContentBizum = (props) => {
    // Empty component with no content
};

/**
 * Label component
 *
 * @param {*} props Props from payment API.
 */
const LabelBizum = (props) => {
    var icon = React.createElement('img', {
        src: '/wp-content/plugins/eupago-gateway-for-woocommerce/includes/woocommerce-blocks/bizum/bizum_banner.png',
        style: {
            display: 'inline',
            marginLeft: '8px', // Adjust the value as needed
        },
    });
    var span = React.createElement('span', {
        className: 'wc-block-components-payment-method-label wc-block-components-payment-method-label--with-icon',
    }, window.wp.htmlEntities.decodeEntities(settings_bizum.title) || defaultLabel_bizum, icon);
    return span;
};

const Bizum = {
    name: 'eupago_bizum',
    label: React.createElement(LabelBizum, null),
    content: React.createElement(ContentBizum, null),
    edit: React.createElement(ContentBizum, null),
    icons: null,
    canMakePayment: () => true,
    ariaLabel: label_bizum,
    supports: {
        features: settings_bizum.supports,
    },
};

window.wc.wcBlocksRegistry.registerPaymentMethod(Bizum);