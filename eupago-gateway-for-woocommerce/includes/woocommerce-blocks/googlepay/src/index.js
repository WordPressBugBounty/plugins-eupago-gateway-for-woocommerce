const settings_googlepay = window.wc.wcSettings.getSetting('eupago_googlepay_data', {});
const defaultLabel_googlepay = window.wp.i18n.__('Eupago Google Pay', 'eupago_googlepay');
const label_googlepay = window.wp.htmlEntities.decodeEntities(settings_googlepay.title) || defaultLabel_googlepay;
var description = React.createElement('p', null, window.wp.htmlEntities.decodeEntities(settings_googlepay.description || 'Use Google Pay'));

const ContentGooglePay = (props) => {
  const decodedDescription = window.wp.htmlEntities.decodeEntities(
    settings_googlepay.description || 'Use Google Pay'
  );

  const decodedInstructions = window.wp.htmlEntities.decodeEntities(
    settings_googlepay.instructions || ''
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

const LabelGooglePay = (props) => {
  var icon = React.createElement('img', { 
    src: '/wp-content/plugins/eupago-gateway-for-woocommerce/includes/woocommerce-blocks/googlepay/googlepay_icon.png',
    style: {
      display: 'inline',
      marginLeft: '5px',
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
