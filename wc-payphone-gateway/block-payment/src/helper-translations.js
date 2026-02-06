export const translate = (text, lang = 'en') => {
  if (lang.includes('es')) {
    const spanishTranslations = {
      'Credit or debit cards | Payphone': 'Tarjetas de crédito o débito | Payphone',
      'Use your Visa, Mastercard, Diners, or Discover credit or debit cards from any bank in the world, and if you have the Payphone app, use your balance.':
        'Usa tus tarjetas de crédito o débito Visa, Mastercard, Diners o Discover de cualquier banco del mundo y, si tienes la aplicación Payphone, utiliza tu saldo.',
    };
    return spanishTranslations[text] || text;
  }
  return text;
};
