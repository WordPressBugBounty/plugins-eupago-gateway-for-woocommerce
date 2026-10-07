const settings_googlepay = window.wc.wcSettings.getSetting('eupago_googlepay_data', {});
const defaultLabel_googlepay = window.wp.i18n.__('Eupago Google Pay', 'eupago_googlepay');
const label_googlepay = window.wp.htmlEntities.decodeEntities(settings_googlepay.title) || defaultLabel_googlepay;

const ContentGooglePay = (props) => {
  const description = window.wp.htmlEntities.decodeEntities(settings_googlepay.description || '');
  return description ? React.createElement('p', null, description) : null;
};

const LabelGooglePay = (props) => {
  var icon = React.createElement('img', {
    src: '/wp-content/plugins/eupago-gateway-for-woocommerce/includes/woocommerce-blocks/googlepay/googlepay_icon.png',
    // Size comes from assets/css/checkout.css (one rule for both checkouts).
    style: {
      display: 'inline',
      marginLeft: '6px',
    },
  });
  var span = React.createElement('span', {
    className: 'wc-block-components-payment-method-label wc-block-components-payment-method-label--with-icon',
  }, window.wp.htmlEntities.decodeEntities(settings_googlepay.title) || defaultLabel_googlepay, icon);
  return span;
};

const GooglePay = {
  name: 'eupago_googlepay',
  label: React.createElement(LabelGooglePay, null),
  content: React.createElement(ContentGooglePay, null),
  edit: React.createElement(ContentGooglePay, null),
  icons: null,
  canMakePayment: () => true,
  ariaLabel: label_googlepay,
  supports: {
    features: settings_googlepay.supports,
  },
};

window.wc.wcBlocksRegistry.registerPaymentMethod(GooglePay);
