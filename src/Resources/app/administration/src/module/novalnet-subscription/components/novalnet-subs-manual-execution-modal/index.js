import template from './novalnet-subs-manual-execution-modal.html.twig';

const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('novalnet-subs-manual-execution-modal', {
    template,

    props: {
        actionToConfirm: {
            type: String,
            required: true
        },
        aboId: {
            type: String,
            required: true
        }
    },
    
    inject: [
        'repositoryFactory',
        'NovalPaymentSubsService',
        'acl'
    ],
    
    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('sw-inline-snippet')
    ],

    data() {
        return {
            isLoading: true,
            cancelReason: '',
            disabled: false,
            subsOptions: [],
            subscriptionLength: 0,
            subscriptionInterval: 0
        }
    },

    created() {
        this.isLoading = false
    },

    methods: {
        closeModal() {
            this.$emit('modal-close');
        },
        confirm() {
			this.isLoading	= true;
			this.disabled	= true;
			
			if(this.actionToConfirm == 'manualExecutionAbo')
			{
				this.NovalPaymentSubsService.manualExecutionSubscription(this.aboId).then((response) => {
					if(response.success == true)
					{
						this.createNotificationSuccess({
							message: this.$tc('novalnet-subscription.order.manualOrderMessage')
						});
						this.$emit('modal-close');
						setTimeout(this.$router.go, 3000);
					} else {
						this.$emit('modal-close');
						this.createNotificationError({
							message: response.errorMessage,
							autoClose: false
						});
						setTimeout(this.$router.go, 3000);
					}
				}).catch((errorResponse) => {
					this.$emit('modal-close');
					this.isLoading = false;
					this.createNotificationError({
						message: `${errorResponse.title}: ${errorResponse.message}`,
						autoClose: false
					});
					setTimeout(this.$router.go, 3000);
				});
			}
        },
    }
})
