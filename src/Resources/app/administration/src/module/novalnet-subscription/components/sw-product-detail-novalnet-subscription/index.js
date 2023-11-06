import template from './sw-product-detail-novalnet-subscription.html.twig';

const { Component, Mixin, Filter, Context } = Shopware;
const Criteria = Shopware.Data.Criteria;
const { mapPropertyErrors, mapState, mapGetters } = Shopware.Component.getComponentHelper();

Component.register('sw-product-detail-novalnet-subscription', {
    template,
    
    inject: ['repositoryFactory', 'acl'],
    
    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('sw-inline-snippet')
    ],
    
    metaInfo() {
        return {
            title: 'Product Subscription Settings'
        };
    },
    
    props: {
        productId: {
            type: String,
            required: false,
            default: null
        }
    },
    
    data() {
        return {
            subscriptionInformation: this.$tc('novalnet-subscription.subscriptionInfo'),
            currencySymbol: null,
            isPercentageType: true,
            isPredefinedDisplay: true,
            defaultPredefinedScope: 'subscription',
            defaultDiscountScope: 'all',
            defaultDiscountType: 'percentage',
            defaultSubscriptionType: 'opt_abo',
            defaultSubscriptionPeriod: 'days',
            defaultSubscriptionInterval: 1,
            defaultSubscriptionLength: 0,
            maxDiscountValue: 100,
            mutiSelectOptions: null,
            subsOptions: [],
            freeTrialOptions: [],
            isMultipleAllowed: false,
            showDiscount : false,
            discountVisible : false,
            discountDetailsInfo: [],
            discountPeroidDelete: false,
            discountPeroidUpdate: false,
            discountDeleteItem: {},
            isDiscountDetails: false,
            disabled : false,
            discountAction: false,
            parentDetailsProduct : []
        };
    },
    
    computed: {
        ...mapState('swProductDetail', [
            'product',
            'parentProduct',
            'variants',
            'isLoading',
            
        ]),
        
        ...mapGetters('swProductDetail', [
            'isLoading',
        ]),
        
        productCriteria() {
            const criteria = new Criteria(1, 25);

            criteria.getAssociation('media')
                .addSorting(Criteria.sort('position', 'ASC'));

            criteria.getAssociation('properties')
                .addSorting(Criteria.sort('name', 'ASC', true));

            criteria.getAssociation('prices')
                .addSorting(Criteria.sort('quantityStart', 'ASC', true));

            criteria.getAssociation('tags')
                .addSorting(Criteria.sort('name', 'ASC'));

            criteria.getAssociation('seoUrls')
                .addFilter(Criteria.equals('isCanonical', true));

            criteria.getAssociation('crossSellings')
                .addSorting(Criteria.sort('position', 'ASC'))
                .getAssociation('assignedProducts')
                .addSorting(Criteria.sort('position', 'ASC'))
                .addAssociation('product')
                .getAssociation('product')
                .addAssociation('options.group');

            criteria
                .addAssociation('cover')
                .addAssociation('categories')
                .addAssociation('visibilities.salesChannel')
                .addAssociation('options')
                .addAssociation('configuratorSettings.option')
                .addAssociation('unit')
                .addAssociation('productReviews')
                .addAssociation('seoUrls')
                .addAssociation('mainCategories')
                .addAssociation('options.group')
                .addAssociation('customFieldSets')
                .addAssociation('featureSet')
                .addAssociation('cmsPage')
                .addAssociation('featureSet')
                .addAssociation('downloads.media')
                .addAssociation('novalnetConfiguration');

            criteria.getAssociation('manufacturer')
                .addAssociation('media');

            return criteria;
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
        
        discountScopeOptions() {
            return [
				{
                    id: 'first',
                    name: this.$tc('novalnet-subscription.settingForm.productSettings.discountScopeOption1')
                },
                {
                    id: 'all',
                    name: this.$tc('novalnet-subscription.settingForm.productSettings.discountScopeOption2')
                },
                {
                    id: 'last',
                    name: this.$tc('novalnet-subscription.settingForm.productSettings.discountScopeOption3')
                }
            ];
        },
        
        multipleDiscountScopeOptions() {
            return [
				{
                    id: 'first',
                    name: this.$tc('novalnet-subscription.settingForm.productSettings.discountScopeOption1')
                },
                {
                    id: 'all',
                    name: this.$tc('novalnet-subscription.settingForm.productSettings.discountScopeOption2')
                },
                {
                    id: 'last',
                    name: this.$tc('novalnet-subscription.settingForm.productSettings.discountScopeOption3')
                },
                {
                    id: 'cycleduration',
                    name: this.$tc('novalnet-subscription.settingForm.productSettings.discountScopeOption4')
                }
            ];
        },
        
        subsPredefinedOptions() {
            return [
				{
                    id: 'subscription',
                    name: this.$tc('novalnet-subscription.settingForm.productSettings.subsPredefinedOptions1')
                },
                {
                    id: 'standard',
                    name: this.$tc('novalnet-subscription.settingForm.productSettings.subsPredefinedOptions2')
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
        
        subsSelectOptions() {
            return [
                {
                    id: 'opt_abo',
                    name: this.$tc('novalnet-subscription.settingForm.productSettings.productOption1')
                },
                {
                    id: 'only_abo',
                    name: this.$tc('novalnet-subscription.settingForm.productSettings.productOption2')
                }
            ];
        },
        
        periodOptions() {
            return [
                {
                    id: 'days',
                    name: this.$tc('novalnet-subscription.settingForm.productSettings.intervalPeriodDay')
                },
                {
                    id: 'weeks',
                    name: this.$tc('novalnet-subscription.settingForm.productSettings.intervalPeriodWeek')
                },
                {
                    id: 'months',
                    name: this.$tc('novalnet-subscription.settingForm.productSettings.intervalPeriodMonth')
                },
                {
                    id: 'years',
                    name: this.$tc('novalnet-subscription.settingForm.productSettings.intervalPeriodYear')
                }
            ];
        },
        
        freeTrialPeriodOptions() {
            return [
                {
                    id: 'days',
                    name: this.$tc('novalnet-subscription.settingForm.productSettings.daysLabel')
                },
                {
                    id: 'weeks',
                    name: this.$tc('novalnet-subscription.settingForm.productSettings.weeksLabel')
                },
                {
                    id: 'months',
                    name: this.$tc('novalnet-subscription.settingForm.productSettings.monthsLabel')
                }
            ];
        },
        
        intervalOptions() {
            return [
                {
                    id: 1,
                    name: this.$tc('novalnet-subscription.settingForm.productSettings.every')
                },
                {
                    id: 2,
                    name: this.$tc('novalnet-subscription.settingForm.productSettings.every2')
                },
                {
                    id: 3,
                    name: this.$tc('novalnet-subscription.settingForm.productSettings.every3')
                },
                {
                    id: 4,
                    name: this.$tc('novalnet-subscription.settingForm.productSettings.every4')
                },
                {
                    id: 5,
                    name: this.$tc('novalnet-subscription.settingForm.productSettings.every5')
                },
                {
                    id: 6,
                    name: this.$tc('novalnet-subscription.settingForm.productSettings.every6')
                }
            ];
        },
        
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
        
        getDiscountColums() {
            const columnDefinitions = [{
                property: 'period',
                dataIndex: 'period',
                label: this.$tc('novalnet-subscription.settingForm.period'),
                width: '80px'
                
            }, 
            {
                property: 'type',
                dataIndex: 'type',
                label: this.$tc('novalnet-subscription.settingForm.productSettings.discountTypeTitle'),
                width: '80px'
                
            },
            {
                property: 'periodterm',
                dataIndex: 'periodterm',
                visible: false
                
            },
            {
                property: 'value',
                dataIndex: 'value',
                label: this.$tc('novalnet-subscription.settingForm.productSettings.discountAmount'),
                width: '80px'
            }];

            return columnDefinitions;
        },
    },
    
    created() {
        this.createdComponent();
    },
    
    watch: {
        product: {
            handler: function (product) {
				
                if (this.product.extensions !== undefined) {
					
					if (this.product.parentId) this.loadParentProduct();
					
					this.showDiscount = false; this.isDiscountDetails = false; this.isMultipleAllowed = false; this.disabled = false;

                    if (this.product.extensions.novalnetConfiguration == undefined) {
						this.$set(this.product.extensions, 'novalnetConfiguration', this.createAboConfigRepository().create());
					}
					
					['type', 'multipleSubscription', 'multiSubscriptionOptions', 'period', 'interval', 'subscriptionLength', 'freeTrial', 'freeTrialPeriod', 'discountType', 'discountScope', 'detailPageText', 'discountType', 'predefinedSelection', 'discountDetails'].forEach((element) => {
						if (this.product.extensions.novalnetConfiguration[element] == undefined)
						{
							this.product.extensions.novalnetConfiguration[element] = null;
						}
					});
					
					if (this.product.extensions.novalnetConfiguration.multipleSubscription != undefined && this.product.extensions.novalnetConfiguration.multipleSubscription == true) 
					{
						this.isMultipleAllowed = true;
					}
					
					if ((this.product.extensions.novalnetConfiguration.multipleSubscription != undefined && this.product.extensions.novalnetConfiguration.multipleSubscription != true) && (this.product.extensions.novalnetConfiguration.discountScope != undefined && this.product.extensions.novalnetConfiguration.discountScope == 'cycleduration'))
					{
						this.product.extensions.novalnetConfiguration.discountScope = 'all';
					}
					
					if ((this.product.extensions.novalnetConfiguration.discountDetails != undefined && this.product.extensions.novalnetConfiguration.discountDetails != null) && 
						(this.product.extensions.novalnetConfiguration.discountScope != undefined && this.product.extensions.novalnetConfiguration.discountScope == 'cycleduration'))
					{	
						this.isDiscountDetails = true;
					}
					
					if (this.product.extensions.novalnetConfiguration.type != undefined)
					{
						if (this.product.extensions.novalnetConfiguration.type == 'opt_abo' || this.product.extensions.novalnetConfiguration.type == null)
						{
							this.isPredefinedDisplay = true;
						} else {
							this.isPredefinedDisplay = false;
						}
					}
					if(this.product.extensions.novalnetConfiguration.discountScope != undefined && this.product.extensions.novalnetConfiguration.discountScope == 'cycleduration'){
						this.showDiscount = true;
					}
					
					if (this.product.extensions.novalnetConfiguration.discountType != undefined)
					{
						if (this.product.extensions.novalnetConfiguration.discountType == 'percentage')
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
					
					if (this.product.extensions.novalnetConfiguration.multiSubscriptionOptions != undefined && this.product.extensions.novalnetConfiguration.multiSubscriptionOptions != null)
					{
						if (this.product.extensions.novalnetConfiguration['multiSubscriptionOptions'].length != undefined && this.product.extensions.novalnetConfiguration['multiSubscriptionOptions'].length > 0)
						{
							this.mutiSelectOptions = this.product.extensions.novalnetConfiguration.multiSubscriptionOptions;
						} else if (this.product.extensions.novalnetConfiguration['multiSubscriptionOptions'].length == undefined || this.product.extensions.novalnetConfiguration['multiSubscriptionOptions'].length == 0)
						{
							this.mutiSelectOptions = null;
						}
					}
					
					if ((this.product.extensions.novalnetConfiguration.discountScope == null))
					{
						this.disabled = true;
					}
					
					if ((this.product.extensions.novalnetConfiguration.discountDetails == null))
					{
						this.discountAction = true;
					}
					
					this.discountDetailsInfo = [];
					
					if(this.product.extensions.novalnetConfiguration.discountDetails != undefined && this.product.extensions.novalnetConfiguration.discountDetails != null){
						let discountDetails = JSON.parse(this.product.extensions.novalnetConfiguration.discountDetails);
						if(this.product.extensions.novalnetConfiguration.multipleSubscription != undefined && this.product.extensions.novalnetConfiguration.multipleSubscription == true){
							if(discountDetails != ''){
								Object.values(discountDetails).forEach(values => {
									this.discountDetailsInfo.push ({
										'period': this.$tc('novalnet-subscription.settingForm.productSettings.'+ values.period +''),
										'periodterm': values.period,
										'type':  values.type == 'fixed' ? this.$tc('novalnet-subscription.settingForm.productSettings.discountTypeOption2') : this.$tc('novalnet-subscription.settingForm.productSettings.discountTypeOption1') ,
										'value': values.discount
									});
									
								});
							}
						}
					} else  {
						this.productRepository.get(this.product.parentId, Shopware.Context.api, this.productCriteria).then((res) => {
							this.parentDetailsProduct = res;
							if (this.parentDetailsProduct != null && this.parentDetailsProduct.extensions !== undefined && this.parentDetailsProduct.extensions !== null) 
							{
								if (this.parentDetailsProduct.extensions.novalnetConfiguration != undefined && this.parentDetailsProduct.extensions.novalnetConfiguration != null)
								{
									if (this.parentDetailsProduct.extensions.novalnetConfiguration.discountDetails != undefined && this.parentDetailsProduct.extensions.novalnetConfiguration.discountDetails != null) 
									{
										let discountDetails = JSON.parse(this.parentDetailsProduct.extensions.novalnetConfiguration.discountDetails);
										if (this.parentDetailsProduct.extensions.novalnetConfiguration.multipleSubscription != undefined && this.parentDetailsProduct.extensions.novalnetConfiguration.multipleSubscription == true)
										{
											if (discountDetails != '') 
											{
												Object.values(discountDetails).forEach(values => {
													this.discountDetailsInfo.push ({
														'period': this.$tc('novalnet-subscription.settingForm.productSettings.'+ values.period +''),
														'periodterm': values.period,
														'type':  values.type == 'fixed' ? this.$tc('novalnet-subscription.settingForm.productSettings.discountTypeOption2') : this.$tc('novalnet-subscription.settingForm.productSettings.discountTypeOption1') ,
														'value': values.discount
													});
													
												});
											}
										}
									}
								}
							}
						
						}).then(() => {
						});
					}
					
					this.setOptions();
					this.setFreeTrial();
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
            
            // initialize parent product
            if (this.product.parentId) this.loadParentProduct();
            
            this.$root.$on('product-reload', () => {
                if (this.product.parentId) this.loadParentProduct();
            });
        },
        
        loadParentProduct() {
            Shopware.State.commit('swProductDetail/setLoading', ['parentProduct', true]);

            return this.productRepository.get(this.product.parentId, Shopware.Context.api, this.productCriteria)
                .then((res) => {
					this.parentProduct = res;
                    Shopware.State.commit('swProductDetail/setParentProduct', res);
                }).then(() => {
                    Shopware.State.commit('swProductDetail/setLoading', ['parentProduct', false]);
                });
        },
        
        createAboConfigRepository() {
            return this.repositoryFactory.create('novalnet_product_config');
        },
        
        updateMultipleSubscription(value) {
            this.product.extensions.novalnetConfiguration.multipleSubscription = value;
            if (value == true) {
				this.isMultipleAllowed = true;
			} else {
				this.isMultipleAllowed = false;
			}
        },
        
        updateCurrentTypeValue(value) {
            this.product.extensions.novalnetConfiguration.type = value;
            if (value == 'only_abo')
            {
				this.isPredefinedDisplay = false;
			} else {
				this.isPredefinedDisplay = true;
			}
        },
        
        updateMultipleSubsOptions(value) {
            this.product.extensions.novalnetConfiguration.multiSubscriptionOptions = value;
        },
        
        updateDiscount(value) {
            this.product.extensions.novalnetConfiguration.discount = value;
        },
        
        onAddDiscount() {
            this.discountVisible = true;
        },
        
        discountDelete(item) {
			this.discountDeleteItem = item;
            this.discountPeroidDelete = true;
        },
        
        discountUpdate(item) {
			this.peroidItem = item;
            this.discountPeroidUpdate = true;
        },
        
        closeModals() {
			this.discountVisible = false;
			this.discountPeroidDelete = false;
			this.discountPeroidUpdate = false;
		},
		
		reloadProductDetails() {
			this.closeModals();
			window.location.reload();
		},
        
        updateDiscountType(value) {
			this.product.extensions.novalnetConfiguration.discountType = value;
			this.product.extensions.novalnetConfiguration.discount = 0;
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
        
        updatePeriod(value) {
            this.product.extensions.novalnetConfiguration.period = value;
        },
        
        updateInterval(value) {
            this.product.extensions.novalnetConfiguration.interval = value;
        },
        
        getInheritValue(key) {
            const p = this.parentProduct;
           
            if (p.extensions != null && p.extensions.novalnetConfiguration != undefined) {
                if (key == 'multiSubscriptionOptions' && p.extensions.novalnetConfiguration[key] != null && (p.extensions.novalnetConfiguration[key].length == undefined || p.extensions.novalnetConfiguration[key].length == 0))
				{
					return null;
				}

				if (key == 'multipleSubscription' && p.extensions.novalnetConfiguration[key] == true && this.product.extensions.novalnetConfiguration[key] == null)
				{
					this.isMultipleAllowed = true;
				}
				
				if (key == 'discountScope' && p.extensions.novalnetConfiguration[key] == 'cycleduration' && ((this.product.extensions.novalnetConfiguration[key] == null) || (this.product.extensions.novalnetConfiguration[key] == 'cycleduration')))
				{
					this.showDiscount = true;
					if (p.extensions.novalnetConfiguration.discountDetails != null) {
						this.isDiscountDetails = true;
				    }
				}
				
				if (key == 'type' && p.extensions.novalnetConfiguration[key] == 'only_abo' && this.product.extensions.novalnetConfiguration.type == null)
				{
					this.isPredefinedDisplay = false;
				}
				return p.extensions.novalnetConfiguration[key] != undefined ? p.extensions.novalnetConfiguration[key] : null ;
			} else {
				return null;
			}
        },
		
		setOptions() {
			this.subsOptions = [];
			
			if (this.product.parentId)
			{
				this.productRepository.get(this.product.parentId, Shopware.Context.api, this.productCriteria)
				  .then((res) => {
					this.subsOptions = [];
					var period = this.product.extensions.novalnetConfiguration.period != null ? this.product.extensions.novalnetConfiguration.period : (res.extensions.novalnetConfiguration != undefined && res.extensions.novalnetConfiguration.period != null ? res.extensions.novalnetConfiguration.period : 'days');
					var interval = this.product.extensions.novalnetConfiguration.interval != null ? this.product.extensions.novalnetConfiguration.interval : (res.extensions.novalnetConfiguration != undefined && res.extensions.novalnetConfiguration.interval != null ? res.extensions.novalnetConfiguration.interval : 1);
					
					if(period == 'months') {
						var length = 24;
					} else if (period == 'weeks') {
						var length = 54;
					} else if (period == 'years') {
						var length = 12;
					} else {
						var length = 90;
					}
					
					for (var i = 0; i <= length; i++) {
						
						if(i == 0)
						{
							this.subsOptions.push({
								id: i,
								name: this.$tc('novalnet-subscription.settingForm.productSettings.neverExpires')
							});
						} else if(i == 1 && (i % interval === 0)) {
							
							this.subsOptions.push({
								id: i,
								name: i + ' ' + this.$tc('novalnet-subscription.settingForm.productSettings.'+ period + 'SingleLabel')
							});
							
						} else if((i % interval === 0)) {
							this.subsOptions.push({
								id: i,
								name: i + ' ' + this.$tc('novalnet-subscription.settingForm.productSettings.'+ period + 'Label')
							});
						}
					}
				});
			} else {
				
				var period = this.product.extensions.novalnetConfiguration.period ?? 'days';
				var interval = this.product.extensions.novalnetConfiguration.interval ?? 1;
				
				if(period == 'months') {
					var length = 24;
				} else if (period == 'weeks') {
					var length = 54;
				} else if (period == 'years') {
					var length = 12;
				} else {
					var length = 90;
				}
				
				for (var i = 0; i <= length; i++) {
					
					if(i == 0)
					{
						this.subsOptions.push({
							id: i,
							name: this.$tc('novalnet-subscription.settingForm.productSettings.neverExpires')
						});
					} else if(i == 1 && (i % interval === 0)) {
						
						this.subsOptions.push({
							id: i,
							name: i + ' ' + this.$tc('novalnet-subscription.settingForm.productSettings.'+ period + 'SingleLabel')
						});
						
					} else if((i % interval === 0)) {
						this.subsOptions.push({
							id: i,
							name: i + ' ' + this.$tc('novalnet-subscription.settingForm.productSettings.'+ period + 'Label')
						});
					}
				}
			}
		},
		
		setFreeTrial() {
			this.freeTrialOptions = [];
			
			if (this.product.parentId)
			{
				this.productRepository.get(this.product.parentId, Shopware.Context.api, this.productCriteria)
				  .then((res) => {
					this.freeTrialOptions = [];
					var frialTrialPeriod = this.product.extensions.novalnetConfiguration.freeTrialPeriod != null ? this.product.extensions.novalnetConfiguration.freeTrialPeriod : (res.extensions.novalnetConfiguration != undefined && res.extensions.novalnetConfiguration.freeTrialPeriod != null ? res.extensions.novalnetConfiguration.freeTrialPeriod : 'days');
					
					if(frialTrialPeriod == 'months') {
						var length = 24;
					} else if (frialTrialPeriod == 'weeks') {
						var length = 54;
					} else {
						var length = 90;
					}
					
					for (var i = 0; i <= length; i++) {
						
						if(i == 0)
						{
							this.freeTrialOptions.push({
								id: i,
								name: this.$tc('novalnet-subscription.settingForm.productSettings.freeTrialEmpty')
							});
						} else if(i == 1) {
							
							this.freeTrialOptions.push({
								id: i,
								name: i + ' ' + this.$tc('novalnet-subscription.settingForm.productSettings.'+ frialTrialPeriod + 'SingleLabel')
							});
							
						} else {
							this.freeTrialOptions.push({
								id: i,
								name: i + ' ' + this.$tc('novalnet-subscription.settingForm.productSettings.'+ frialTrialPeriod + 'Label')
							});
						}
					}
				});
			} else {
				
				var frialTrialPeriod = this.product.extensions.novalnetConfiguration.freeTrialPeriod ?? 'days';
					
				if(frialTrialPeriod == 'months') {
					var length = 24;
				} else if (frialTrialPeriod == 'weeks') {
					var length = 54;
				} else {
					var length = 90;
				}
				
				for (var i = 0; i <= length; i++) {
					
					if(i == 0)
					{
						this.freeTrialOptions.push({
							id: i,
							name: this.$tc('novalnet-subscription.settingForm.productSettings.freeTrialEmpty')
						});
					} else if(i == 1) {
						
						this.freeTrialOptions.push({
							id: i,
							name: i + ' ' + this.$tc('novalnet-subscription.settingForm.productSettings.'+ frialTrialPeriod + 'SingleLabel')
						});
						
					} else {
						this.freeTrialOptions.push({
							id: i,
							name: i + ' ' + this.$tc('novalnet-subscription.settingForm.productSettings.'+ frialTrialPeriod + 'Label')
						});
					}
				}
			}
		},
    },
    
});
