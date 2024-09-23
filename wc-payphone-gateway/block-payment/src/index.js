import {registerPaymentMethod} from '@woocommerce/blocks-registry';
import {getSetting} from '@woocommerce/settings';
import {decodeEntities} from '@wordpress/html-entities';
import {__} from '@wordpress/i18n';

const settings = getSetting('payphone_data', {});

const defaultLabel = __('Tarjetas de crédito o débito Visa y Mastercard | Payphone', 'payphone');

/**
 * Content component
 */
const Content = props => {
  return <div>{decodeEntities(settings.description || '')}</div>;
};
/**
 * Label component
 *
 */
const Label = () => {
  return (
    <div style={{display: 'flex', alignItems: 'center'}}>
      <span>{defaultLabel}</span>
      <img
        src={settings.icon}
        alt="payphone"
        style={{marginLeft: '8px', height: 'auto', maxWidth: '100%', maxHeight: '100%'}}
      />
    </div>
  );
};

/**
 * Dummy payment method config object.
 */
const PayphoneGateway = {
  name: 'payphone',
  label: <Label />,
  content: <Content />,
  edit: <Content />,
  canMakePayment: () => true,
  ariaLabel: defaultLabel,
  supports: {
    features: settings.supports,
  },
};

registerPaymentMethod(PayphoneGateway);
