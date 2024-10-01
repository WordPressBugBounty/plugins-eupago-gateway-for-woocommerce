/**
 * External dependencies
 */

const settings_multibanco = window.wc.wcSettings.getSetting('eupago_multibanco_data', {});
const defaultLabel_multibanco = window.wp.i18n.__('Eupago Multibanco', 'eupago_multibanco');
const label_multibanco = window.wp.htmlEntities.decodeEntities(settings_multibanco.title) || window.wp.i18n.__('Eupago Multibanco', 'eupago_multibanco');
var description = React.createElement('p', null, window.wp.htmlEntities.decodeEntities(settings_multibanco.description || 'Use this method for payment'));


/**
 * Content component
 *
 * @param {*} props Props from payment API.
 */
const ContentMultibanco = (props) => {
	// Empty component with no content
  };
  
	

/**
 * Label component
 *
 * @param {*} props Props from payment API.
 */

const LabelMultibanco = (props) => {
	var icon = React.createElement('img', {
	  src: '/wp-content/plugins/eupago-gateway-for-woocommerce/includes/woocommerce-blocks/multibanco/multibanco_banner.png',
	  style: {
		display: 'inline',
		marginLeft: '5px', // Adjust the value as needed
	  },
	});
	var span = React.createElement('span', {
	  className: 'wc-block-components-payment-method-label wc-block-components-payment-method-label--with-icon',
	}, window.wp.htmlEntities.decodeEntities(settings_multibanco.title) || defaultLabel_multibanco, icon);
	return span;
  };

  const Multibanco = {
	name: 'eupago_multibanco',
	label: React.createElement(LabelMultibanco, null),
	content: React.createElement(ContentMultibanco, null),
	edit: React.createElement(ContentMultibanco, null),
	icons: null,
	canMakePayment: () => true,
	ariaLabel: label_multibanco,
	supports: {
	  features: settings_multibanco.supports,
	},
  };
  
  window.wc.wcBlocksRegistry.registerPaymentMethod(Multibanco);
