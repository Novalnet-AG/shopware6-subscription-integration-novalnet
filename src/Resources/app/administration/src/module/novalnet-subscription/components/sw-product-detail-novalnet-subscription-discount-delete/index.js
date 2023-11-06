import template from './sw-product-detail-novalnet-subscription-discount-delete.html.twig';

const { Component, Mixin, Filter, Context } = Shopware;
const Criteria = Shopware.Data.Criteria;
const { currency } = Shopware.Utils.format;

Component.register('sw-product-detail-novalnet-subscription-discount-delete', {
    template,

    props: {
        productId: {
            type: String,
            required: true,
        },
        product: {
            type: Object,
            required: true
        },
        item: {
			type : Object,
			required : true
		}
    },

    inject: ['repositoryFactory','NovalPaymentSubsService', 'acl'],

    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('sw-inline-snippet')
    ],

    
    methods: {

		onDiscountCancel() {
            this.closeModal();
        },

		onDiscountDelete()
        {
			let item	= this.item;

			this.NovalPaymentSubsService.discountDelete(item, this.product.id).then((response) => {
				if(response.success == true){	
					this.createNotificationSuccess({
						message: this.$tc('novalnet-subscription.settingForm.productSettings.discountDeleteSuccess')
					});
				} else {
					this.createNotificationError({
							message: response.errorMessage
						});
				}
			    this.$emit('modal-close');
			    setTimeout(this.$router.go, 3000);
			}).catch((errorResponse) => {
				this.createNotificationError({
					message: `${errorResponse.title}: ${errorResponse.message}`,
					autoClose: false
				});
			});
		},
		
		closeModal() {
            this.$emit('modal-close');
        },

	}
});
