import template from './novalnet-subsctiption-overview-detail.html.twig';
import '../../components/novalnet-subs-renewal-modal';
import '../../components/novalnet-subs-product-change-modal';
import '../../components/novalnet-subs-manual-execution-modal';

const { Component, Mixin, Filter, Context } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('novalnet-subsctiption-overview-detail', {
    template,

    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('sw-inline-snippet')
    ],

    inject: [
        'repositoryFactory',
        'acl'
    ],

    data() {
        return {
            identifier: '',
            aboId: null,
            unit: null,
            interval: null,
            abo: null,
            products: [],
            isEditing: false,
            isLoading: true,
            actionToConfirm: '',
            isSaveSuccessful: false,
            showProductChangeModal: false
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle()
        };
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
        this.createdComponent();
    },
    
    methods: {
        createdComponent() {
            this.aboId = this.$route.params.id;
            this.loadNovalnetSubscription()
                .finally(() => {
                    this.isLoading = false;
                });
            
            this.getSubscriptionProduct();
        },

        async getSubscriptionProduct() {

            const criteria = new Criteria(1, 200);
			criteria.addAssociation('visibilities.salesChannel');
			criteria.addAssociation('configuratorSettings.option');
			criteria.addAssociation('options');
			criteria.addAssociation('options.group');
			criteria.addAssociation('novalnetConfiguration');

			this.productRepository.search(criteria).then((result) => {

				result.forEach(product => {                    
                    if (product.parentId)
                    {
                        this.productRepository.get(product.parentId, Shopware.Context.api, this.productCriteria).then((parentProduct) => {
                            if (product.active == true || parentProduct.active == true)
                            {
                                if ((product.extensions != null && product.extensions.novalnetConfiguration != undefined && product.extensions.novalnetConfiguration.active == true) || (parentProduct != null && parentProduct.extensions != null && parentProduct.extensions.novalnetConfiguration != undefined && parentProduct.extensions.novalnetConfiguration.active == true))
                                {
                                    var name = '';var group = false;
                                    if (product.name == null && product.translated.name == null)
                                    {
                                        name += parentProduct.translated.name ? parentProduct.translated.name : parentProduct.group.name;
                                    } else {
                                        name += product.translated.name ? product.translated.name : product.group.name;
                                    }

                                    if (product.options != null)
                                    {
                                        name += ' ( ';
                                        product.options.sort((a, b) => a.createdAt - b.createdAt).forEach(option => {
                                            if (option.group != undefined)
                                            {   
												group = true;
                                                name +=  (option.group.name ? option.group.name : option.group.translated.name) + ': ' + (option.name ? option.name : option.translated.name) + ' | ';
                                            }
                                        });
                                        
                                        name = name.slice(0, -3);

                                        if (group)
                                        {
										    name += ' )' ;
										}
                                    }
                                    if (product.extensions != null && product.extensions.novalnetConfiguration != undefined && product.extensions.novalnetConfiguration.displayName != null && product.extensions.novalnetConfiguration.displayName.trim() != '')
                                    {
										product.name = product.translated.name = product.extensions.novalnetConfiguration.displayName;
									} else {
										product.name = product.translated.name = name;
									}
                                    this.products.push(product);
                                }

                            }
                        });
                    } else {
                        if (product.active == true && product.extensions != null && product.extensions.novalnetConfiguration != undefined && product.extensions.novalnetConfiguration.active == true && product.childCount == 0)
                        {
							if (product.extensions != null && product.extensions.novalnetConfiguration != undefined && product.extensions.novalnetConfiguration.displayName != null && product.extensions.novalnetConfiguration.displayName.trim() != '')
							{
								product.name = product.translated.name = product.extensions.novalnetConfiguration.displayName;
							}
							
                            this.products.push(product);
                        }
                    }
				});
			});

        },
        
        onRenewalAbo() {
			this.actionToConfirm = 'renewalAbo';
		},
		
		onManualExecutionAbo() {
			this.actionToConfirm = 'manualExecutionAbo';
		},
		
        novalnetRepository() {
            return this.repositoryFactory.create('novalnet_subscription');
        },
        
        loadNovalnetSubscription() {
			
			let criteria = new Criteria();
            criteria.addAssociation('subsOrders')
            criteria.addAssociation('order')
            criteria.addAssociation('order.salesChannel')
            criteria.addAssociation('order.lineItems')
            criteria.addAssociation('order.currency')
            criteria.addAssociation('order.orderCustomer');
            
            return this.novalnetRepository().get(this.aboId, Shopware.Context.api, criteria)
                .then((abo) => {
                    this.abo = abo;
                    this.unit = abo.unit;
                    this.interval = abo.interval;
                });
        },
        
        confirmAction(action) {
            this.actionToConfirm = '';
        },
        
        closeModal() {
            this.actionToConfirm = '';
        },
        
        onProductModal() {
            this.showProductChangeModal = true;
        },
        
        closeProductChangeModal() {
            this.showProductChangeModal = false;
        }
	}
});
