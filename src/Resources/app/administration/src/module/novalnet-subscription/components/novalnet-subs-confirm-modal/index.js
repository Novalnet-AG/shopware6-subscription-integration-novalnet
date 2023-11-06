import template from './novalnet-subs-confirm-modal.html.twig';

const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('novalnet-subs-confirm-modal', {
    template,

    props: {
        actionToConfirm: {
            type: String,
            required: true
        },
        customerId: {
            type: String,
            required: true
        },
        aboId: {
            type: String,
            required: true
        },
        salesChannelId: {
            type: String,
            required: true
        },
        confirmButtonText: {
            type: String,
            default: function () {
                return this.$tc('novalnet-subscription.detail.confirmButton')
            }
        },
        cancelReaonLabel: {
            type: String,
            default: function () {
                return $tc('novalnet-subscription.list.cancelReaon')
            }
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
            disabled: false
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
			
			if(this.actionToConfirm == 'activeAbo')
			{
				this.NovalPaymentSubsService.active(this.aboId).then((response) => {	
					
					this.createNotificationSuccess({
						message: this.$tc('novalnet-subscription.order.activeMessage')
					});
					this.$emit('modal-close');
					setTimeout(this.$router.go, 3000);
						
				}).catch((errorResponse) => {
					this.isLoading = false;
					this.createNotificationError({
						message: `${errorResponse.title}: ${errorResponse.message}`,
						autoClose: false
					});
				});
			} else if(this.actionToConfirm == 'cancelAbo')
			{
				this.NovalPaymentSubsService.cancel(this.aboId, this.cancelReason, this.customerId).then((response) => {	
					
					this.createNotificationSuccess({
						message: this.$tc('novalnet-subscription.order.cancelledMessage')
					});
					this.$emit('modal-close');
					setTimeout(this.$router.go, 3000);
						
				}).catch((errorResponse) => {
					this.isLoading = false;
					this.createNotificationError({
						message: `${errorResponse.title}: ${errorResponse.message}`,
						autoClose: false
					});
				});
			} else if(this.actionToConfirm == 'pendingAbo')
			{
				this.NovalPaymentSubsService.pauseSubscription(this.aboId, this.cancelReason, this.customerId).then((response) => {	
					
					this.createNotificationSuccess({
						message: this.$tc('novalnet-subscription.order.pendingMessage')
					});
					this.$emit('modal-close');
					setTimeout(this.$router.go, 3000);
						
				}).catch((errorResponse) => {
					this.isLoading = false;
					this.createNotificationError({
						message: `${errorResponse.title}: ${errorResponse.message}`,
						autoClose: false
					});
				});
			} else if(this.actionToConfirm == 'pauseAbo')
			{
				this.NovalPaymentSubsService.pauseSubscription(this.aboId, this.cancelReason, this.customerId).then((response) => {	
					
					this.createNotificationSuccess({
						message: this.$tc('novalnet-subscription.order.suspendedMessage')
					});
					
					this.$emit('modal-close');
					setTimeout(this.$router.go, 3000);
				}).catch((errorResponse) => {
					this.isLoading = false;
					this.createNotificationError({
						message: `${errorResponse.title}: ${errorResponse.message}`,
						autoClose: false
					});
				});
			}
        }
    }
})
