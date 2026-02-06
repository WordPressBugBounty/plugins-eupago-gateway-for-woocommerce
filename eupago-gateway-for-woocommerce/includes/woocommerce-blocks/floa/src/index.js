/**
 * External dependencies
 */
const { registerPaymentMethod } = wc.wcBlocksRegistry;
const { getSetting } = wc.wcSettings;
const { decodeEntities } = wp.htmlEntities;
const { __ } = wp.i18n;
const React = window.React;

/**
 * Internal dependencies
 */
const settings_floa = getSetting('eupago_floa_data', {});
const defaultLabel_floa = __('Eupago Floa', 'eupago_floa');
const label_floa = decodeEntities(settings_floa.title) || defaultLabel_floa;

/**
 * Content component
 */
const ContentFloa = () => {
  const installmentsText = decodeEntities(settings_floa.installmentsText || '');
  const descriptionText = decodeEntities(settings_floa.description || '');
  const bannerUrl = settings_floa.bannerUrl || '';

  // Create the banner image element
  const bannerImg = bannerUrl ? React.createElement('img', {
    src: bannerUrl,
    alt: __('Floa Banner', 'eupago_floa'), // Translatable alt text
    className: 'floa-checkout-banner',
    style: {
      maxWidth: '100%',
      marginBottom: '10px',
    },
  }) : null; // If bannerUrl is empty, render nothing
  
  // Use dangerouslySetInnerHTML to render the price HTML from WooCommerce correctly.
  const installmentsTextDiv = installmentsText ? React.createElement('div', {
    className: 'installments-text',
    style: {
      fontWeight: 'bold',
      marginBottom: '5px',
    },
    dangerouslySetInnerHTML: { __html: installmentsText },
  }) : null;

  const descriptionDiv = descriptionText ? React.createElement('div', {
    className: 'description-text',
  }, descriptionText) : null;

  return React.createElement('div', {
    className: 'eupago-floa-content',
  }, bannerImg, installmentsTextDiv, descriptionDiv);
};
  
/**
 * Label component
 */
const LabelFloa = () => {
  // It's best practice to pass the icon URL from PHP settings.
  // The hardcoded path is an unreliable fallback.
  const iconUrl = '/wp-content/plugins/eupago-gateway-for-woocommerce/includes/woocommerce-blocks/floa/floa_blue.png';

  const icon = React.createElement('img', {
    src: iconUrl,
    alt: 'Floa',
    style: {
      display: 'inline-block',
      marginLeft: '5px',
      verticalAlign: 'middle',
      width: '40px', // Example width, adjust as needed
    },
  });
  return React.createElement('span', {
    className: 'wc-block-components-payment-method-label',
  }, label_floa, icon);
};

const Floa = {
  name: 'eupago_floa',
  label: React.createElement(LabelFloa, null),
  content: React.createElement(ContentFloa, null),
  edit: React.createElement(ContentFloa, null),
  canMakePayment: () => true,
  ariaLabel: label_floa,
  supports: {
    features: settings_floa.supports || [],
  },
};
  
registerPaymentMethod(Floa);

