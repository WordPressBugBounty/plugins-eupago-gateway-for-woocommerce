/**
 * External dependencies
 */

const settings_cc = window.wc.wcSettings.getSetting('eupago_cc_data', {});
const defaultLabel_cc = window.wp.i18n.__('Eupago Credit Card', 'eupago_cc');
const label_cc = window.wp.htmlEntities.decodeEntities(settings_cc.title) || window.wp.i18n.__('Eupago Credit Card', 'eupago_cc');
var description = React.createElement('p', null, window.wp.htmlEntities.decodeEntities(settings_cc.description || ''));

/**
 * Content component
 *
 * @param {*} props Props from payment API.
 */
const ContentCC = (props) => {
	// Empty component with no content
  };
  
/**
 * Label component
 *
 * @param {*} props Props from payment API.
 */
const LabelCC = (props) => {
	var icon = React.createElement('img', {
	  src: '/wp-content/plugins/eupago-gateway-for-woocommerce/includes/woocommerce-blocks/cc/cc_icon.jpg',
	  style: {
		display: 'inline',
		marginLeft: '5px', // Adjust the value as needed
	  },
	});
	var span = React.createElement('span', {
	  className: 'wc-block-components-payment-method-label wc-block-components-payment-method-label--with-icon',
	}, window.wp.htmlEntities.decodeEntities(settings_cc.title) || defaultLabel_cc, icon);
	return span;
  };

  const CC = {
	name: 'eupago_cc',
	label: React.createElement(LabelCC, null),
	content: React.createElement(ContentCC, null),
	edit: React.createElement(ContentCC, null),
	icons: null,
	canMakePayment: () => true,
	ariaLabel: label_cc,
	supports: {
	  features: settings_cc.supports,
	},
  };
  
  window.wc.wcBlocksRegistry.registerPaymentMethod(CC);