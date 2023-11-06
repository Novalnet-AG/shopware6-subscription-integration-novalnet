// import all necessary storefront plugins
import NovalnetSubsProductForm from './novalnet-subscription/novalnet-subscription.plugin';
import NovalnetSubsPaymentForm from './novalnet-subscription/novalnet-subs-payment.plugin';
import FormSubmitLoaderWithText from './novalnet-subscription/novalnet-confirm-button-submit.plugin';

// register them via the existing PluginManager
const PluginManager = window.PluginManager;
PluginManager.register('NovalnetSubsProductForm', NovalnetSubsProductForm, '.novalnet-subscription-price-content');
PluginManager.register('NovalnetSubsPaymentForm', NovalnetSubsPaymentForm, '#confirmFormSubmit');
PluginManager.register('SubmitLoaderText', FormSubmitLoaderWithText, '[data-submit-loader-text]');
