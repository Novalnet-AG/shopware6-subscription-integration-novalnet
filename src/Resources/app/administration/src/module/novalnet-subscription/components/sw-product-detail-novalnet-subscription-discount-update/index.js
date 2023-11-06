import template from './sw-product-detail-novalnet-subscription-discount-update.html.twig';

const { Component, Mixin, Filter, Context } = Shopware;
const Criteria = Shopware.Data.Criteria;
const { currency } = Shopware.Utils.format;

Component.register('sw-product-detail-novalnet-subscription-discount-update', {
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
        peroidItem: {
			type : Object,
			required : true
		}
    },

    inject: ['repositoryFactory','NovalPaymentSubsService', 'acl'],

    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('sw-inline-snippet')
    ],

    data() {
        return {
            cycle : '',
            discountType : '',
            discountValue : 1,
            isPercentageType: true,
            maxDiscountValue: 100,
            currencySymbol: null
        };
    },
    
    computed: {
		
        discountTypeOptions() {
            return [
				{
                    id: 'percentage',
                    name: this.$tc('novalnet-subscription.settingForm.productSettings.discountTypeOption1')
                },
                {
                    id: 'fixed',
                    name: this.$tc('novalnet-subscription.settingForm.productSettings.discountTypeOption2')
                }
            ];
        },
        
        currencyRepository() {
            return this.repositoryFactory.create('currency');
        },
        
        productRepository() {
            return this.repositoryFactory.create('product');
        },
        
        maxValueSuffix() {
            return this.currencySymbol;
        },
	},
	
	created() {
        this.createdComponent();
    },
	
	watch: {
        product: {
            handler: function (product) {
                if (this.peroidItem !='') {

					this.cycle = this.peroidItem.period;
					this.discountType = this.peroidItem.type == 'Percentage' ? 'percentage' : 'fixed' ;
					this.discountValue = this.peroidItem.value;
					
					if (this.peroidItem.type == 'Percentage') {
							
								this.isPercentageType = true;
								this.maxDiscountValue = 100;
					} else {
						this.isPercentageType = false;
						if (this.product.parentId && this.product.price ==  null)
						{
							this.productRepository.get(this.product.parentId, Shopware.Context.api, this.productCriteria).then((res) => {
								this.maxDiscountValue = res.price[0].gross;
							});
						} else {
							this.maxDiscountValue = this.product.price[0].gross;
						}
					}
				}
				
			},
            deep: true,
            immediate: true
        }
    },
	

    methods: {
		
		createdComponent() {
            this.currencyRepository.search(new Criteria()).then((response) => {
                this.currencies = response;
                this.defaultCurrency = this.currencies.find(currency => currency.isSystemDefault);
                this.currencySymbol = this.defaultCurrency.symbol;
            });
        },
		
		novalnetdiscount()
        {
            let cycle	= this.cycle;
            let discountType	= this.discountType;
            let discountValue	= this.discountValue;
            
            if(cycle !=null && discountType != null && discountValue != null ){
				
				this.NovalPaymentSubsService.discountUpdate(cycle, discountType, discountValue, this.peroidItem.periodterm, this.product.id).then((response) => {
					if(response.success != undefined && response.success != '', response.success == true){	
						this.createNotificationSuccess({
							message: this.$tc('novalnet-subscription.settingForm.productSettings.discountUpdate')
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
			} else {
				this.createNotificationError({
				   message: this.$tc('novalnet-subscription.settingForm.productSettings.emptyError')
				});
				
			}
			

		},
		
		closeModal() {
            this.$emit('modal-close');
        },
        
        updateDiscountType(value) {
			
			this.discountValue = 1;
			if (value == 'percentage')
			{
				this.isPercentageType = true;
				this.maxDiscountValue = 100;
			} else {
				this.isPercentageType = false;
				
				if (this.product.parentId && this.product.price ==  null)
				{
					this.productRepository.get(this.product.parentId, Shopware.Context.api, this.productCriteria).then((res) => {
						this.maxDiscountValue = res.price[0].gross;
					});
				} else {
					this.maxDiscountValue = this.product.price[0].gross;
				}
			}
        },

        
	}
});
