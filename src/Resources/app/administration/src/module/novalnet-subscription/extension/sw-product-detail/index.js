import template from './sw-product-detail.html.twig';

const { mapPageErrors, mapState, mapGetters } = Shopware.Component.getComponentHelper();
const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

// Override your template here, using the actual template from the core
Shopware.Component.override('sw-product-detail', {
    template,
    
    inject: [
        'repositoryFactory',
        'NovalPaymentSubsService',
        'acl'
    ],
    
    methods: {
		onSave() {
			if (this.$route.name == 'sw.product.detail.novalnet.subscription' && this.product.extensions.novalnetConfiguration != undefined)
			{
				if (this.parentProduct.extensions != undefined && this.parentProduct.extensions.novalnetConfiguration != undefined)
				{
					['active', 'type', 'period', 'interval', 'subscriptionLength', 'signUpFee', 'freeTrial', 'freeTrialPeriod', 'multipleSubscription', 'multiSubscriptionOptions', 'operationalMonth', 'discount', 'discountScope', 'discountType', 'detailPageText', 'predefinedSelection', 'discountDetails'].forEach((element) => {
						if ((this.product.extensions.novalnetConfiguration[element] == undefined || this.product.extensions.novalnetConfiguration[element] == null) && this.parentProduct.extensions.novalnetConfiguration != undefined)
						{
							this.product.extensions.novalnetConfiguration[element] = this.parentProduct.extensions.novalnetConfiguration[element];
						}
					});
				} else {
					if (this.product.extensions.novalnetConfiguration.type == null)
					{
						this.product.extensions.novalnetConfiguration.type = 'opt_abo';
					}
					
					if (this.product.extensions.novalnetConfiguration.predefinedSelection == null)
					{
						this.product.extensions.novalnetConfiguration.predefinedSelection = 'subscription';
					}
					
					if (this.product.extensions.novalnetConfiguration.interval == null)
					{
						this.product.extensions.novalnetConfiguration.interval = 1;
					}
					
					if (this.product.extensions.novalnetConfiguration.period == null)
					{
						this.product.extensions.novalnetConfiguration.period = 'days';
					}
					
					if (this.product.extensions.novalnetConfiguration.freeTrial == null)
					{
						this.product.extensions.novalnetConfiguration.freeTrial = 0;
					}
					
					if (this.product.extensions.novalnetConfiguration.freeTrialPeriod == null)
					{
						this.product.extensions.novalnetConfiguration.freeTrialPeriod = 'days';
					}
					
					if (this.product.extensions.novalnetConfiguration.subscriptionLength == null)
					{
						this.product.extensions.novalnetConfiguration.subscriptionLength = 0;
					}
					
					if (this.product.extensions.novalnetConfiguration.discount == null)
					{
						this.product.extensions.novalnetConfiguration.discount = 0;
					}
					
					if (this.product.extensions.novalnetConfiguration.discountType == null)
					{
						this.product.extensions.novalnetConfiguration.discountType = 'percentage';
					}
					
					if (this.product.extensions.novalnetConfiguration.discountScope == null)
					{
						this.product.extensions.novalnetConfiguration.discountScope = 'all';
					}
					
					if (this.product.extensions.novalnetConfiguration['multiSubscriptionOptions'] != null && this.product.extensions.novalnetConfiguration['multiSubscriptionOptions'].length == 0)
					{
						this.product.extensions.novalnetConfiguration['multiSubscriptionOptions'] = null;
					}
				}
			}

			this.$super('onSave');
		},
	}
});
