import template from './novalnet-subs-product-change-modal.html.twig';

const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('novalnet-subs-product-change-modal', {
    template,

    props: {
        abo: {
            type: Object,
            required: true
        },
		products: {
            type: Object,
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
            showProductChange: true,
            quantity: 1,
            subscriptionProduct: null
        }
    },
    
    computed: {
		productRepository() {
            return this.repositoryFactory.create('product');
        },
        
		productCriteria() {
            const criteria = new Criteria(1, 200);
            
            criteria.addAssociation('visibilities.salesChannel');
            criteria.addAssociation('configuratorSettings.option');
            criteria.addAssociation('options');
            criteria.addAssociation('options.group');
            criteria.addAssociation('novalnetConfiguration');
			
			return criteria;
        }
	},

    created() {
        this.isLoading = false;
        
        this.createdComponent();
    },

    methods: {
		createdComponent () {
			if (this.abo.quantity) {
				this.quantity = this.abo.quantity;
			} else {
				this.abo.order.lineItems.forEach((lineItem) => {
					if(lineItem.type == "product" && lineItem.id == this.abo.lineItemId)
					{
						this.quantity = lineItem.quantity;
					}
				});
			}
			
			if (this.abo.productId) {
				this.subscriptionProduct = this.abo.productId;
			} else {
				this.abo.order.lineItems.forEach((lineItem) => {
					if(lineItem.type == "product" && lineItem.id == this.abo.lineItemId)
					{
						this.subscriptionProduct = lineItem.productId;
					}
				});
			}
		},
		
		onProductChange(productId) {
			this.subscriptionProduct = productId;
		},
		
        closeModal() {
            this.$emit('modal-close');
        },
        
        updateProduct() {
			
			if (this.quantity == 0)
			{
				this.$emit('modal-close');
				this.createNotificationError({
					message: this.$tc('novalnet-subscription.order.productErrorMessage'),
					autoClose: false
				});
				return;
			}
			
			var productId;
			if (this.abo.productId) {
				productId = this.abo.productId;
			} else {
				this.abo.order.lineItems.forEach((lineItem) => {
					if(lineItem.type == "product" && lineItem.id == this.abo.lineItemId)
					{
						productId = lineItem.productId;
					}
				});
			}
			
			if (this.subscriptionProduct == undefined || (this.subscriptionProduct == productId && this.abo.quantity == this.quantity))
			{
				this.$emit('modal-close');
				return;
			}
			
			this.isLoading	= true;
			this.disabled	= true;
			
			if (this.subscriptionProduct == productId && this.abo.quantity != this.quantity)
			{
				this.NovalPaymentSubsService.updateProductQuantity(this.abo.id, this.quantity).then((response) => {
					if(response.success == true)
					{
						this.createNotificationSuccess({
							message: this.$tc('novalnet-subscription.order.productUpdateMessage')
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
			} else {
				this.NovalPaymentSubsService.updateProduct(this.abo.id, this.subscriptionProduct, this.quantity).then((response) => {
					if(response.success == true)
					{
						this.createNotificationSuccess({
							message: this.$tc('novalnet-subscription.order.productUpdateMessage')
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
			}
        },
        
        updateProductQuantity() {
			if (this.quantity == 0)
			{
				this.$emit('modal-close');
				this.createNotificationError({
					message: this.$tc('novalnet-subscription.order.productErrorMessage'),
					autoClose: false
				});
				return;
			}
			
			if (this.abo.quantity == this.quantity)
			{
				this.$emit('modal-close');
				return;
			}
			
			this.isLoading	= true;
			this.disabled	= true;
			
			this.NovalPaymentSubsService.updateProductQuantity(this.abo.id, this.quantity).then((response) => {
				if(response.success == true)
				{
					this.createNotificationSuccess({
						message: this.$tc('novalnet-subscription.order.productUpdateMessage')
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
