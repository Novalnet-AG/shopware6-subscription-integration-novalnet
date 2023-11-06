import NovalPaymentSubsService
    from '../../src/core/service/api/novalnet-payment-subs.service';

const { Application } = Shopware;

Application.addServiceProvider('NovalPaymentSubsService', (container) => {
    const initContainer = Application.getContainer('init');

    return new NovalPaymentSubsService(initContainer.httpClient, container.loginService);
});

