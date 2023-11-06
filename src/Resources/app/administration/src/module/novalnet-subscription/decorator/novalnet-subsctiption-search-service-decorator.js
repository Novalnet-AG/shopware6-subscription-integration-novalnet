const { Application } = Shopware;

Application.addServiceProviderDecorator('searchTypeService', searchTypeService => {
    searchTypeService.upsertType('novalnet_subscription', {
        entityName: 'novalnet_subscription',
        entityService: 'NovalnetSubscriptionConfiguration',
        placeholderSnippet: 'novalnet-subscription.searchPlaceholder',
        listingRoute: 'novalnet.subscription.list'
    });

    return searchTypeService;
});
