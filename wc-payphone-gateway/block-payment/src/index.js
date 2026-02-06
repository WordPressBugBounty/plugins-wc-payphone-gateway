import {registerPaymentMethod} from '@woocommerce/blocks-registry';
import {getSetting} from '@woocommerce/settings';
import {translate} from './helper-translations';

const settings = getSetting('payphone_data', {});

const defaultLabel = translate('Credit or debit cards | Payphone', settings.lang);

/**
 * Content component
 */
const Content = () => {
  return (
    <div>
      {translate(
        'Use your Visa, Mastercard, Diners, or Discover credit or debit cards from any bank in the world, and if you have the Payphone app, use your balance.',
        settings.lang,
      )}
    </div>
  );
};
/**
 * Label component
 *
 */
const Label = () => {
  return (
    <div style={{display: 'flex', alignItems: 'center', flexWrap: 'wrap'}}>
      <span style={{marginRight: '8px'}}>{defaultLabel}</span>
      <div style={{flexShrink: 1, minWidth: 0}}>
        <img
          src={settings.icon}
          alt="payphone"
          style={{height: 'auto', maxWidth: '100%', maxHeight: '36px', width: 'auto', display: 'block'}}
        />
      </div>
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
