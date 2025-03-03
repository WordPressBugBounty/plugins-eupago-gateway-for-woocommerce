/**
 * External dependencies
 */

const settings_mbway = window.wc.wcSettings.getSetting('eupago_mbway_data', {});
const defaultLabel_mbway = window.wp.i18n.__('Eupago MB Way', 'eupago_mbway');
const label_mbway = window.wp.htmlEntities.decodeEntities(settings_mbway.title) || window.wp.i18n.__('Eupago MB Way', 'eupago_mbway');


/**
 * Content component
 *
 * @param {*} props Props from payment API.
 */
const ContentMBWay = (props) => {
	const [phoneNumber, setPhoneNumber] = React.useState('');
	const [countryCode, setCountryCode] = React.useState('+351');
	const { eventRegistration, emitResponse } = props;
	const { onPaymentProcessing } = eventRegistration;
	React.useEffect(() => {
		const unsubscribe = onPaymentProcessing(async () => {
			const mbway_phone = phoneNumber;
			const mbway_country_code = countryCode;
	
			// Concatenate the country code and phone number
			const fullPhoneNumber = mbway_country_code + mbway_phone;
	
			// Check if the length is between 8 and 16 characters
			const customDataIsValid = fullPhoneNumber.length >= 8 && fullPhoneNumber.length <= 16;
	
			if (customDataIsValid) {
				return {
					type: emitResponse.responseTypes.SUCCESS,
					meta: {
						paymentMethodData: {
							mbway_phone,
							mbway_country_code,
						},
					},
				};
			}
	
			return {
				type: emitResponse.responseTypes.ERROR,
				message: window.wp.i18n.__('Invalid Phone number or country code', 'eupago_mbway'),
			};
		});
		return () => {
			unsubscribe();
		};
	}, [
		emitResponse.responseTypes.ERROR,
		emitResponse.responseTypes.SUCCESS,
		onPaymentProcessing,
		phoneNumber,
		countryCode,
	]);
	

	/* Input value */
	const HandlePhoneChange = (event) => {
		const value = event.target.value.replace(/\D/g, '');
		setPhoneNumber(value);
	};

	/* Handle country code change */
	const HandleCountryCodeChange = (event) => {
		setCountryCode(event.target.value);
	};

	/* Country code options */
	const countryCodeOptions = [
		{ value: '+351', label: 'Portugal (+351)' },
		{ value: '+34', label: 'Spain (+34)' },
		{ value: '+39', label: 'Italy (+39)' },
	];

	const selectStyle = {
		width: '20%',
		maxWidth: '20%',
		borderRadius: 4,
		lineHeight: 16,
		margin: 0,
		height: 50,
		paddingTop: 15,
		paddingBottom: 15,
	};

	const inputStyle = {
		width: '80%',
		maxWidth: '80%',
		paddingTop: 15,
		paddingBottom: 15,
	};
	
	const labelStyle = {
		position: 'relative',
	}

	const divStyle = {
		display: 'flex',
		alignItems: 'center',
	}

	/* Content */
	var description = React.createElement('p', null, window.wp.htmlEntities.decodeEntities(settings_mbway.description || ' '));
	var countryCodeDropdown = React.createElement('select', {
		value: countryCode,
		onChange: HandleCountryCodeChange,
		required: true,
		style: selectStyle,
	}, countryCodeOptions.map(option => React.createElement('option', {
		value: option.value,
		key: option.value,
	}, option.label)));
	var phoneinput = React.createElement('input', {
		type: 'text',
		name: 'phone',
		id: 'phone',
		placeholder: '9XXXXXXXX',
		autoComplete: 'off',
		required: true,
		value: phoneNumber,
		style: inputStyle,
		onChange: HandlePhoneChange,
	});
	var phoneLabel = React.createElement('label', {
		htmlFor: 'phone',
		style: labelStyle
	}, window.wp.htmlEntities.decodeEntities(settings_mbway.phoneText || ''));
	var phone = React.createElement('div', {
		className: 'wc-block-components-text-input is-active',
		style: divStyle,
	}, '', countryCodeDropdown, phoneinput);
	return React.createElement('div', {
		className: 'eupago-mbway-content', // Added a class for styling
	}, description,phoneLabel, phone);
};

/**
 * Label component
 *
 * @param {*} props Props from payment API.
 */
const LabelMBWay = (props) => {
	var icon = React.createElement('img', {
		src: '/wp-content/plugins/eupago-gateway-for-woocommerce/includes/woocommerce-blocks/mbway/mbway_banner.png',
		style: {
			display: 'inline',
			marginLeft: '5px', // Adjust the value as needed
		},
	});
	var span = React.createElement('span', {
		className: 'wc-block-components-payment-method-label wc-block-components-payment-method-label--with-icon',
	}, window.wp.htmlEntities.decodeEntities(settings_mbway.title) || defaultLabel_mbway, icon);
	return span;
};

const MBWay = {
	name: 'eupago_mbway',
	label: React.createElement(LabelMBWay, null),
	content: React.createElement(ContentMBWay, null),
	edit: React.createElement(ContentMBWay, null),
	icons: null,
	canMakePayment: () => true,
	ariaLabel: label_mbway,
	supports: {
		features: settings_mbway.supports,
	},
};

window.wc.wcBlocksRegistry.registerPaymentMethod(MBWay);
