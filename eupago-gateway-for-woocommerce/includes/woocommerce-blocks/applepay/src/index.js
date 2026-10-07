const settings_applepay = window.wc.wcSettings.getSetting('eupago_applepay_data', {});
const defaultLabel = window.wp.i18n.__('Eupago Apple Pay', 'eupago_applepay');
const applePayLabel = window.wp.htmlEntities.decodeEntities(settings_applepay.title) || defaultLabel;

const ContentApplePay = (props) => {
  const description = window.wp.htmlEntities.decodeEntities(settings_applepay.description || '');
  return description ? React.createElement('p', null, description) : null;
};


const LabelApplePay = (props) => {
  const icon = React.createElement('img', {
    src: '/wp-content/plugins/eupago-gateway-for-woocommerce/includes/woocommerce-blocks/applepay/applepay_icon.png',
    // Size comes from assets/css/checkout.css (one rule for both checkouts).
    style: {
      display: 'inline',
      marginLeft: '6px',
    },
  });

  return React.createElement(
    'span',
    {
      className: 'wc-block-components-payment-method-label wc-block-components-payment-method-label--with-icon',
    },
    applePayLabel,
    icon
  );
};

const ApplePay = {
  name: 'eupago_applepay',
  label: React.createElement(LabelApplePay, null),
  content: React.createElement(ContentApplePay, null),
  edit: React.createElement(ContentApplePay, null),
  icons: null,
  canMakePayment: () => true,
  ariaLabel: applePayLabel,
  supports: {
    features: settings_applepay.supports,
  },
};

window.wc.wcBlocksRegistry.registerPaymentMethod(ApplePay);