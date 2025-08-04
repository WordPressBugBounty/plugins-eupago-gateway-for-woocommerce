const settings_applepay = window.wc.wcSettings.getSetting('eupago_applepay_data', {});
const defaultLabel = window.wp.i18n.__('Eupago Apple Pay', 'eupago_applepay');
const applePayLabel = window.wp.htmlEntities.decodeEntities(settings_applepay.title) || defaultLabel;

const applePayDescription = React.createElement(
  'p',
  null,
  window.wp.htmlEntities.decodeEntities(settings_applepay.description || 'Use Apple Pay')
);

const ContentApplePay = (props) => {
  const decodedDescription = window.wp.htmlEntities.decodeEntities(
    settings_applepay.description || 'Use Apple Pay'
  );

  const decodedInstructions = window.wp.htmlEntities.decodeEntities(
    settings_applepay.instructions || ''
  );

  return React.createElement(
    'div',
    null,
    React.createElement('p', null, decodedDescription),
    decodedInstructions
      ? React.createElement('p', { style: { fontSize: '0.85em', color: '#555' } }, decodedInstructions)
      : null
  );
};


const LabelApplePay = (props) => {
  const icon = React.createElement('img', {
    src: '/wp-content/plugins/eupago-gateway-for-woocommerce/includes/woocommerce-blocks/applepay/applepay_icon.png',
    style: {
      display: 'inline',
      marginLeft: '5px',
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