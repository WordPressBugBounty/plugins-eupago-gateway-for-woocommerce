/**
 * External dependencies
 */

const settings_cofidispay = window.wc.wcSettings.getSetting('eupago_cofidispay_data', {});
const label_cofidispay = window.wp.htmlEntities.decodeEntities(settings_cofidispay.title) || window.wp.i18n.__('Eupago Cofidis Pay', 'eupago_cofidispay');
const defaultLabel_cofidis = window.wp.i18n.__('Eupago Cofidis Pay', 'eupago_cofidispay');
const description_cofidispay = window.wp.htmlEntities.decodeEntities(settings_cofidispay.description || 'Use this method for payment');
const max_installments = window.wp.htmlEntities.decodeEntities(settings_cofidispay.maxInstallments) || window.wp.i18n.__('Up to 12 installments without interest.', 'eupago_cofidispay');

/**
 * Content component
 *
 * @param {*} props Props from payment API.
 */
const ContentCofidisPay = (props) => {
  const [nifNumber, setNifNumber] = React.useState('');
  const { eventRegistration, emitResponse } = props;
  const { onPaymentProcessing } = eventRegistration;
  React.useEffect(() => {
    const unsubscribe = onPaymentProcessing(async () => {
      const nif = nifNumber;
      const customDataIsValid = nif.length === 9;

      if (customDataIsValid) {
        return {
          type: emitResponse.responseTypes.SUCCESS,
          meta: {
            paymentMethodData: {
              nif,
            },
          },
        };
      }

      return {
        type: emitResponse.responseTypes.ERROR,
        message: __('Invalid NIF number', 'eupago_cofidispay'),
      };
    });
    return () => {
      unsubscribe();
    };
  }, [
    emitResponse.responseTypes.ERROR,
    emitResponse.responseTypes.SUCCESS,
    onPaymentProcessing,
    nifNumber,
  ]);

  /* Input value */
  const HandleNifChange = (event) => {
    const value = event.target.value.replace(/\D/g, '');
    setNifNumber(value);
  };

  /* Content */
  var nifinput = React.createElement('input', {
    type: 'text',
    name: 'nif',
    id: 'nif',
    // placeholder: '123456789',
    autoComplete: 'off',
    maxLength: '9',
    required: true,
    value: nifNumber,
    onChange: HandleNifChange,
  });
  var niflabel = React.createElement('label', {
    htmlFor: 'nif',
  }, window.wp.htmlEntities.decodeEntities(settings_cofidispay.nifText || ''));
  var nif = React.createElement('div', {
    className: 'wc-block-components-text-input is-active',
  }, '', nifinput, niflabel);
  var cofidisLink = React.createElement('a', {
    href: 'https://www.cofidis.pt/cofidispay',
    target: '_blank',
    style: {
      display: 'inline',
      marginLeft: '5px', // Adjust the value as needed
    },
  }, '+info');
  // Add a div with max_installments text
  var maxInstallmentsDiv = React.createElement('div', {
    className: 'max-installments',
    style: {
      fontWeight: 'bold',
      marginBottom: '5px', // Add a 5px space below
    },
  }, max_installments);
  return React.createElement('div', {
    className: 'eupago-cofidispay-content', // Added a class for styling
  }, maxInstallmentsDiv, description_cofidispay, cofidisLink, nif);
};

/**
 * Label component
 *
 * @param {*} props Props from payment API.
 */
const LabelCofidisPay = (props) => {
  var icon = React.createElement('img', {
    src: '/wp-content/plugins/eupago-gateway-for-woocommerce/includes/woocommerce-blocks/cofidispay/cofidispay.png',
    style: {
      display: 'inline',
      marginLeft: '5px', // Adjust the value as needed
    },
  });
  var span = React.createElement('span', {
    className: 'wc-block-components-payment-method-label wc-block-components-payment-method-label--with-icon',
  }, window.wp.htmlEntities.decodeEntities(settings_cofidispay.title) || defaultLabel_cofidis, icon);
  return span;
};

const CofidisPay = {
  name: 'eupago_cofidispay',
  label: React.createElement(LabelCofidisPay, null),
  content: React.createElement(ContentCofidisPay, null),
  edit: React.createElement(ContentCofidisPay, null),
  icons: null,
  canMakePayment: () => true,
  ariaLabel: label_cofidispay,
  supports: {
    features: settings_cofidispay.supports,
  },
};

window.wc.wcBlocksRegistry.registerPaymentMethod(CofidisPay);

