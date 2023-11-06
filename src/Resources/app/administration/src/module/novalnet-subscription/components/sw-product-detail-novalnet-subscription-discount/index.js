import template from './sw-product-detail-novalnet-subscription-discount.html.twig';

const { Component, Mixin, Filter, Context } = Shopware;
const Criteria = Shopware.Data.Criteria;
const { currency } = Shopware.Utils.format;
const {  mapState } = Shopware.Component.getComponentHelper();

Component.register('sw-product-detail-novalnet-subscription-discount', {
    template,

    props: {
        productId: {
            type: String,
            required: true,
        },
        product: {
            type: Object,
            required: true
        }
    },

    inject: ['repositoryFactory','NovalPaymentSubsService', 'acl'],

    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('sw-inline-snippet')
    ],

    data() {
        return {
            cycle : 'dailyDelivery',
            discountType : 'percentage',
            discountValue : 1,
            createNovalnetProduct : false,
            updatedNovalnetProduct : false,
            novalnetProduct : {},
            isPercentageType: true,
            maxDiscountValue: 100,
            currencySymbol: null
        };
    },
    
    computed: {
		
		...mapState('swProductDetail', [
            'product',
            'parentProduct',
            'loading',
        ]),

		
		displayFieldOptions() {
            return [
                {
                    id: 'dailyDelivery',
                    name: this.$tc('novalnet-subscription.settingForm.productSettings.dailyDelivery')
                },
                {
                    id: 'weeklyDelivery',
                    name: this.$tc('novalnet-subscription.settingForm.productSettings.weeklyDelivery')
                },
                {
                    id: 'Every2WeekDelivery',
                    name: this.$tc('novalnet-subscription.settingForm.productSettings.Every2WeekDelivery')
                },
                {
                    id: 'Every3WeekDelivery',
                    name: this.$tc('novalnet-subscription.settingForm.productSettings.Every3WeekDelivery')
                },
                {
                    id: 'monthlyDelivery',
                    name: this.$tc('novalnet-subscription.settingForm.productSettings.monthlyDelivery')
                },
                {
                    id: 'Every6WeekDelivery',
                    name: this.$tc('novalnet-subscription.settingForm.productSettings.Every6WeekDelivery')
                },
                {
                    id: 'Every2MonthDelivery',
                    name: this.$tc('novalnet-subscription.settingForm.productSettings.Every2MonthDelivery')
                },
                {
                    id: 'Every3MonthDelivery',
                    name: this.$tc('novalnet-subscription.settingForm.productSettings.Every3MonthDelivery')
                },
                {
                    id: 'Every4MonthDelivery',
                    name: this.$tc('novalnet-subscription.settingForm.productSettings.Every4MonthDelivery')
                },
                {
                    id: 'halfYearlyDelivery',
                    name: this.$tc('novalnet-subscription.settingForm.productSettings.halfYearlyDelivery')
                },
                {
                    id: 'Every9MonthDelivery',
                    name: this.$tc('novalnet-subscription.settingForm.productSettings.Every9MonthDelivery')
                },
                {
                    id: 'yearlyDelivery',
                    name: this.$tc('novalnet-subscription.settingForm.productSettings.yearlyDelivery')
                }
            ];
        },
        
        
        
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
                if (this.product !='') {

					if (this.product.extensions != undefined && this.product.extensions.novalnetConfiguration != undefined) {
						
						if (this.product.extensions.novalnetConfiguration.discountType != undefined)
						{
							if (this.discountType == 'percentage')
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
						}
						
						if (this.product.extensions.novalnetConfiguration._isNew == true ){
							
							if(this.product.parentId != null){
								const novalnetProductRepository = this.repositoryFactory.create('novalnet_product_config');
								const novalnetProductCriteria = new Criteria(1, 1);
									novalnetProductCriteria.addFilter(Criteria.equals('productId', this.product.parentId));
									novalnetProductRepository.search(novalnetProductCriteria, Context.api).then((searchResult) => {
										this.novalnetProduct = searchResult.first();
									
										if(novalnetProduct !=''){
											this.updatedNovalnetProduct = true;
										}
									}).finally(() => {
										this.updatedNovalnetProduct = false;
								   });
						    }
						   
						    if (this.product.extensions.novalnetConfiguration.type == null)
							{ 
								this.product.extensions.novalnetConfiguration.type = (this.updatedNovalnetProduct == true ? this.novalnetProduct.type :'opt_abo');
							}
							
							if (this.product.extensions.novalnetConfiguration.predefinedSelection == null)
							{
								this.product.extensions.novalnetConfiguration.predefinedSelection = (this.updatedNovalnetProduct == true ? this.novalnetProduct.predefinedSelection : 'subscription');
							}
							
							if (this.product.extensions.novalnetConfiguration.interval == null)
							{
								this.product.extensions.novalnetConfiguration.interval = (this.updatedNovalnetProduct == true ? this.novalnetProduct.interval : 1);
							}
					
							if (this.product.extensions.novalnetConfiguration.period == null)
							{
								this.product.extensions.novalnetConfiguration.period = (this.updatedNovalnetProduct == true ? this.novalnetProduct.period :'days');
							}
							
							if (this.product.extensions.novalnetConfiguration.freeTrial == null)
							{
								this.product.extensions.novalnetConfiguration.freeTrial = (this.updatedNovalnetProduct == true ? this.novalnetProduct.freeTrial :0);
							}
							
							if (this.product.extensions.novalnetConfiguration.freeTrialPeriod == null)
							{
								this.product.extensions.novalnetConfiguration.freeTrialPeriod = (this.updatedNovalnetProduct == true ? this.novalnetProduct.freeTrialPeriod : 'days' );
							}
							
							if (this.product.extensions.novalnetConfiguration.subscriptionLength == null)
							{
								this.product.extensions.novalnetConfiguration.subscriptionLength = (this.updatedNovalnetProduct == true ? this.novalnetProduct.subscriptionLength : 0 );
							}
							
							if (this.product.extensions.novalnetConfiguration.discount == null)
							{
								this.product.extensions.novalnetConfiguration.discount = (this.updatedNovalnetProduct == true ? this.novalnetProduct.discount : 0 );
							}
							
							if (this.product.extensions.novalnetConfiguration.discountType == null)
							{
								this.product.extensions.novalnetConfiguration.discountType = (this.updatedNovalnetProduct == true ? this.novalnetProduct.discountType : 'percentage' );
							}
							
							if (this.product.extensions.novalnetConfiguration.discountScope == null)
							{
								this.product.extensions.novalnetConfiguration.discountScope = (this.updatedNovalnetProduct == true ? this.novalnetProduct.discountScope : 'all');
							}
							
							if (this.product.extensions.novalnetConfiguration.active == undefined)
							{
								this.product.extensions.novalnetConfiguration.active = (this.updatedNovalnetProduct == true ? this.novalnetProduct.active : true);
							}
							
							if (this.product.extensions.novalnetConfiguration.signUpFee == undefined)
							{
								this.product.extensions.novalnetConfiguration.signUpFee = (this.updatedNovalnetProduct == true ? this.novalnetProduct.signUpFee : null );
							}
							
							if (this.product.extensions.novalnetConfiguration.multiSubscriptionOptions == undefined)
							{
								this.product.extensions.novalnetConfiguration.multiSubscriptionOptions = (this.updatedNovalnetProduct == true ? this.novalnetProduct.multiSubscriptionOptions : null);
							}
							
							if (this.product.extensions.novalnetConfiguration.operationalMonth == undefined)
							{
								this.product.extensions.novalnetConfiguration.operationalMonth = (this.updatedNovalnetProduct == true ? this.novalnetProduct.operationalMonth : 0);
							}
							
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
				
				this.NovalPaymentSubsService.novalnetDiscount(cycle, discountType, discountValue, this.product).then((response) => {
					if(response.success != undefined && response.success != '' && response.success == true){	
						this.createNotificationSuccess({
							message: this.$tc('novalnet-subscription.settingForm.productSettings.discountSucess')
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
