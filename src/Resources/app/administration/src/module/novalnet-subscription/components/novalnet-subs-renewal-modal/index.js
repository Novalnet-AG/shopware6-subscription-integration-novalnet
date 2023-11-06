import template from './novalnet-subs-renewal-modal.html.twig';

const { Component, Mixin, Filter, Context } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('novalnet-subs-renewal-modal', {
    template,

    props: {
        actionToConfirm: {
            type: String,
            required: true
        },
        aboId: {
            type: String,
            required: true
        },
        unit: {
            type: String,
            required: false
        },
        interval: {
            type: String,
            required: false
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
            isLoading: true,
            cancelReason: '',
            disabled: false,
            subsOptions: [],
            subscriptionLength: 0,
            subscriptionInterval: 0
        }
    },

    created() {
		this.createdComponent();
        this.isLoading = false
    },

    methods: {
		createdComponent() {
			this.subscriptionInterval = this.interval;
			this.subsOptions = [];
			if(this.unit == 'm') {
				var length = 24;
				var period = 'months';
			} else if (this.unit == 'w') {
				var length = 54;
				var period = 'weeks';
			} else if (this.unit == 'y') {
				var length = 12;
				var period = 'years';
			} else {
				var length = 90;
				var period = 'days';
			}
			
			for (var i = 0; i <= length; i++) {
				
				if(i == 0)
				{
					this.subsOptions.push({
						id: i,
						name: this.$tc('novalnet-subscription.settingForm.productSettings.neverExpires')
					});
				} else if(i == 1 && (i % this.interval === 0)) {
					
					this.subsOptions.push({
						id: i,
						name: i + ' ' + this.$tc('novalnet-subscription.settingForm.productSettings.'+ period + 'SingleLabel')
					});
					
				} else if((i % this.interval === 0)) {
					this.subsOptions.push({
						id: i,
						name: i + ' ' + this.$tc('novalnet-subscription.settingForm.productSettings.'+ period + 'Label')
					});
				}
			}
        },
        closeModal() {
            this.$emit('modal-close');
        },
        confirm() {
			this.isLoading	= true;
			this.disabled	= true;
			var length	 	= this.subscriptionLength;
			if(this.actionToConfirm == 'renewalAbo')
			{
				if (length != 0)
				{
					length = length / this.subscriptionInterval;
				}
				this.NovalPaymentSubsService.renewalSubscription(this.aboId, length).then((response) => {	
					this.createNotificationSuccess({
						message: this.$tc('novalnet-subscription.order.renewalMessage')
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
        },
        
        changeField(value) {
			this.subscriptionLength = parseInt(value, 10);
            this.$emit('field-changed', parseInt(value, 10));
        },
    }
})
