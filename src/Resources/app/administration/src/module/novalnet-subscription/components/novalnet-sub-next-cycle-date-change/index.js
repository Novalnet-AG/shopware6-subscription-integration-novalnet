import template from './novalnet-sub-next-cycle-date-change.html.twig';

const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('novalnet-sub-next-cycle-date-change', {
    template,

    props: {
        abo: {
            type: Object,
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
            reason:'',
            disabled: false,
        }
    },

    created() {
        this.isLoading = false
    },

    methods: {
        closeModal() {
            this.$emit('modal-close');
        },
        
        changeDate() 
        {
			let nextDate	= this.abo.nextDate;
            const reason	= this.reason;
            const aboId	    = this.aboId;
			this.isLoading	= true;

			this.disabled	= true;
			
			this.NovalPaymentSubsService.dateChange(aboId, nextDate, reason, this.abo.subsOrders).then((response) => {
				if(response.success == true)
				{
					this.createNotificationSuccess({
						message: this.$tc('novalnet-subscription.order.cycleDateChange')
					});
					this.$emit('modal-close');
					setTimeout(this.$router.go, 3000);	
				} else {
					this.$emit('modal-close');
					this.createNotificationError({
						message: response.errorMessage,
						autoClose: false
					});
				}
			}).catch((errorResponse) => {
				this.$emit('modal-close');
				this.isLoading = false;
				this.createNotificationError({
					message: `${errorResponse.title}: ${errorResponse.message}`,
					autoClose: false
				});
			});
        },
    }
})
