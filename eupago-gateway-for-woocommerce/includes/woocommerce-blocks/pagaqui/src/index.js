/**
 * External dependencies
 */

const settings_pagaqui = window.wc.wcSettings.getSetting('eupago_pagaqui_data', {});
const defaultLabel_pagaqui = window.wp.i18n.__('Eupago Pagaqui', 'eupago_pagaqui');
const label_pagaqui = window.wp.htmlEntities.decodeEntities(settings_pagaqui.title) || window.wp.i18n.__('Eupago Pagaqui', 'eupago_pagaqui');
var description = React.createElement('p', null, window.wp.htmlEntities.decodeEntities(settings_pagaqui.description || 'Use this method for payment'));


/**
 * Content component
 *
 * @param {*} props Props from payment API.
 */
const ContentPagaqui = (props) => {
	// Empty component with no content
  };
  
	

/**
 * Label component
 *
 * @param {*} props Props from payment API.
 */

const LabelPagaqui = (props) => {
	var icon = React.createElement('img', {
	  src: '/wp-content/plugins/eupago-gateway-for-woocommerce/assets/images/pagaqui_banner.png',
	  style: {
		display: 'inline',
		marginLeft: '5px', // Adjust the value as needed
	  },
	});
	var span = React.createElement('span', {
	  className: 'wc-block-components-payment-method-label wc-block-components-payment-method-label--with-icon',
	}, window.wp.htmlEntities.decodeEntities(settings_pagaqui.title) || defaultLabel_pagaqui, icon);
	return span;
  };

  const Pagaqui = {
	name: 'eupago_pagaqui',
	label: React.createElement(LabelPagaqui, null),
	content: React.createElement(ContentPagaqui, null),
	edit: React.createElement(ContentPagaqui, null),
	icons: null,
	canMakePayment: () => true,
	ariaLabel: label_pagaqui,
	supports: {
	  features: settings_pagaqui.supports,
	},
  };
  
  window.wc.wcBlocksRegistry.registerPaymentMethod(Pagaqui);
