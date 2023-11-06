import template from './sw-order-detail-details.html.twig';


const { Context, Component, Filter, Utils } = Shopware;
const { Criteria } = Shopware.Data;
const { currency } = Shopware.Utils.format;

Component.override('sw-order-detail-details', {
    template,
    
    inject: [
        'NovalPaymentSubsService',
        'repositoryFactory',
    ],
    
    mixins: ['notification'],
    
    props: {
        orderId: {
            type: String,
            required: true
        },
        paymentDetails: {
            type: Object,
            required: true
        },
    },
    
    data() {
        return {
            isLoading: false,
            isSubscription : false,
            abos : null
        };
	},
    
    watch: {
        orderId: {
            deep: true,
            handler() {
                if (!this.orderId) {
                    this.isSubscription = false;
                    return;
                } else if( this.isVerified ) {
                    return;
                }

				this.NovalPaymentSubsService.getSubsOrder(this.orderId).then((result) => {
					if(result.subscriptions != '' && result.subscriptions != undefined)
					{
						this.isSubscription = true;
						this.abos = result.subscriptions;
					}
				}).catch((errorResponse) => {
					this.createNotificationError({
						message: `${errorResponse.title}: ${errorResponse.message}`
					});
				});
            },
            immediate: true
        }
    }
});
