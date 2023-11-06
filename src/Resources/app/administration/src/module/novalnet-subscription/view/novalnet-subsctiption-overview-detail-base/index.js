import template from './novalnet-subsctiption-overview-detail-base.html.twig';
import './novalnet-subsctiption-overview-detail-base.scss';
import '../../components/novalnet-subs-confirm-modal';
import '../../components/novalnet-sub-next-cycle-date-change';

const { Component, Mixin, Filter, Context } = Shopware;
const { Criteria } = Shopware.Data;
const { currency } = Shopware.Utils.format;

Component.register('novalnet-subsctiption-overview-detail-base', {
    template,

    inject: [
        'repositoryFactory',
        'orderService',
        'userService',
        'NovalPaymentSubsService',
        'acl'
    ],
    
    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('sw-inline-snippet')
    ],

    props: {
        aboId: {
            type: String,
            required: true,
        },
    },

    data() {
        return {
            abo: null,
            subsCycle: false,
            ProductExits: false,
            subOrderStatus : '',
            paymentName : '',
            onHold: false,
            order: null,
            versionContext: null,
            lineItems: [],
            productInfo: [],
            isLoading: true,
            selectedActionName: '',
            salesChannelId: '',
            actionToConfirm: '',
            confirmButtonText: '',
            cancelReaonLabel: '',
            defaultStatus: ['active', 'pending', 'pending_cancel', 'on_hold', 'expired', 'suspended', 'cancelled', 'failed'],
            transitionOptions: [],
            disabled: false,
            customerId: '',
            changeCycleDate : false,
            showCycleDate : false
        }
    },
    
    created() {
        this.createdComponent();
    },
    
    computed: {
		productRepository() {
            return this.repositoryFactory.create('product');
        },
        
		productCriteria() {
            const criteria = new Criteria();
            criteria.addAssociation('options');
            criteria.addAssociation('options.group');
			
			return criteria;
        },
        
        aboRepository() {
            return this.repositoryFactory.create('novalnet_subscription');
        },
        
        getColumns() {
            return [{
                property: 'quantity',
                dataIndex: 'quantity',
                label: 'sw-order.detailBase.columnQuantity',
                primary: true,
                align: 'right',
                width: '90px'
            }, {
                property: 'name',
                dataIndex: 'name',
                label: 'sw-order.detailBase.columnProductName',
                allowResize: false,
                primary: true,
                inlineEdit: true,
                multiLine: true,
            }, {
                property: 'productNumber',
                dataIndex: 'productNumber',
                label: 'sw-order.detailBase.columnProductNumber',
                allowResize: false,
                align: 'left',
            }, {
                property: 'unitPrice',
                dataIndex: 'unitPrice',
                label: 'sw-order.detailBase.columnPriceGross',
                allowResize: false,
                align: 'right',
                inlineEdit: true,
                width: '120px',
            }, {
                property: 'totalPrice',
                dataIndex: 'totalPrice',
                label: 'sw-order.detailBase.columnTotalPriceGross',
                allowResize: false,
                align: 'right',
                width: '120px',
            }];
        },
    },
    
    methods: {
        createdComponent() {
            this.versionContext = Shopware.Context.api;
            let criteria = new Criteria();
            criteria.addAssociation('subsOrders')
            criteria.addAssociation('product')
            criteria.addAssociation('product.options')
            criteria.addAssociation('product.options.group')
            criteria.addAssociation('payment_method')
            criteria.addAssociation('order')
            criteria.addAssociation('order.salesChannel')
            criteria.addAssociation('order.lineItems')
            criteria.addAssociation('order.currency')
            criteria.addAssociation('order.orderCustomer');

            this.aboRepository.get(this.aboId, Shopware.Context.api, criteria).then((response) => {
                this.abo = response;
                
                const product = response.product;
                
                if (product)
                {
					this.ProductExits = true;
					
					if (product.name == null && product.translated.name == null && product.parentId)
					{
						var name = '';
						this.productRepository.get(
							product.parentId,
							Shopware.Context.api,
							this.productCriteria,
						).then((parentProduct) => {
							name += parentProduct.translated.name ? parentProduct.translated.name : parentProduct.group.name;
							
							if (product.options != null)
							{
								name += ' ( ';
								product.options.forEach(option => {
									name +=  (option.group.translated.name ? option.group.translated.name : option.group.name) + ': ' + (option.translated.name ? option.translated.name : option.name) + ' | ';
								});
								name = name.slice(0, -3);
								name += ' )';
							}
							
							this.productInfo.push ({
								'quantity': this.abo.quantity + ' x',
								'name': name,
								'productNumber': product.productNumber,
								'unitPrice': product.price != null ? currency (product.price[0].gross, response.order.currency.shortName) : currency (parentProduct.price[0].gross, response.order.currency.shortName),
								'totalPrice': product.price != null ? currency ((product.price[0].gross * this.abo.quantity), response.order.currency.shortName) : currency ((parentProduct.price[0].gross * this.abo.quantity), response.order.currency.shortName)
							});
						});
					} else {
						this.productInfo.push ({
							'quantity': this.abo.quantity + ' x',
							'name': product.translated.name,
							'productNumber': product.productNumber,
							'unitPrice': currency (product.price[0].gross, response.order.currency.shortName),
							'totalPrice': currency ((product.price[0].gross * this.abo.quantity), response.order.currency.shortName)
						});
					}
					
				}
				
				if(this.abo.payment_method.customFields != null && this.abo.payment_method.customFields.novalnet_payment_method_name != undefined && this.abo.payment_method.customFields.novalnet_payment_method_name == "novalnetpay"){
					
					this.NovalPaymentSubsService.getNovalnetOrderPaymentName(this.abo.order.orderNumber).then((result) => {
						if(result.paymentName != '' && result.paymentName != undefined)
						{
							this.paymentName = result.paymentName ;
						}
						else {
							this.paymentName = this.abo.payment_method.translated.name ?? this.abo.payment_method.name
						}
					}).catch((errorResponse) => {
						this.createNotificationError({
							message: `${errorResponse.title}: ${errorResponse.message}`
						});
					});
					
				}
				else {
					this.paymentName = this.abo.payment_method.translated.name ?? this.abo.payment_method.name
				}
                
                if (this.abo.status == 'ACTIVE' ){
					this.showCycleDate = true;
				}
              
                response.subsOrders.forEach((subsOrder) => {
                    if((subsOrder.status == "RETRY" || subsOrder.status == "FAILURE" || subsOrder.status == "CANCELLED") && this.abo.status != 'ACTIVE')
                    {
                        this.subsCycle = true;
                        
                        if (this.abo.status == 'SUSPENDED'){
							this.subOrderStatus = this.$tc('novalnet-subscription.status.suspended');
						} else if(this.abo.status == 'CANCELLED'){
							this.subOrderStatus = this.$tc('novalnet-subscription.status.cancelled');
						} else{products: []
							this.subOrderStatus = this.abo.status;
						}
                    }
                });
                
                if(this.abo.status == 'ON_HOLD')
                {
                    this.onHold = true;
                }
                this.selectedActionName = this.abo.status.toLowerCase();
                this.getSubscriptionStatus();
                this.abo.order.lineItems.forEach((lineItem) => {
                    if(lineItem.type == "product" && lineItem.id == this.abo.lineItemId)
                    {
                        this.lineItems.push(lineItem);
                    }
                });
                
                this.order = this.abo.order;
                this.order.lineItems = this.lineItems;
                
                this.salesChannelId = this.abo.order.salesChannelId;
                this.isLoading = false;
            }).catch((err) => {
                this.isLoading = false;
            });
            
            this.userService.getUser().then((response) => {
                this.customerId = response.data.id;
            });
        },
        
        selectStyle() {
            return `sw-order-state-select__field--rounded`;
        },
        
        backgroundStyle() {
            return `sw-order-state__danger-select`;
        },
        
        showChangeNextCyle() {
            this.changeCycleDate = true;
        },
        
        getSubscriptionStatus() {
            const options = [];
            this.defaultStatus.forEach((entry) => {
                options.push({
                    id: entry,
                    name: this.$tc('novalnet-subscription.status.' + entry.toLowerCase()),
                    disabled: true
                });
            });
            
            options.forEach((option) => {
                if(this.possibleStatus() != undefined && this.possibleStatus().includes(option.id))
                {
                    option.disabled = false;
                }
            });
            
            this.transitionOptions = options;
        },
        
        getVariantFromOrderState(status) {
            if(status == 'CANCELLED' || status == 'EXPIRED') {
                return 'danger';
            } else if (status == 'PENDING' || status == 'ON_HOLD') {
                return 'info';
            } else if (status == 'SUSPENDED' || status == 'PENDING_CANCEL') {
                return 'warning';
            } else {
                return 'success';
            }
        },
        
        formatNNDate(date) {
            if (date) {
                return `${date.getFullYear()}`;
            }
        },
        
        onStateChangeClicked(value) {
            if(this.selectedActionName == 'pending' || this.selectedActionName == 'pending_cancel')
            {
                this.actionToConfirm = 'pendingAbo';
                this.confirmButtonText = this.$tc('novalnet-subscription.detail.suspendLabel');
                this.cancelReaonLabel = this.$tc('novalnet-subscription.detail.pendingReaon');
            } else if (this.selectedActionName == 'suspended') {
                this.actionToConfirm = 'pauseAbo';
                this.confirmButtonText = this.$tc('novalnet-subscription.detail.suspendLabel');
                this.cancelReaonLabel = this.$tc('novalnet-subscription.detail.suspendReaon');
            } else if (this.selectedActionName == 'cancelled') {
                this.actionToConfirm = 'cancelAbo';
                this.confirmButtonText = this.$tc('novalnet-subscription.detail.cancelLabel');
                this.cancelReaonLabel = this.$tc('novalnet-subscription.detail.cancelReaon');
            } else if (this.selectedActionName == 'active') {
                this.actionToConfirm = 'activeAbo';
                this.confirmButtonText = this.$tc('novalnet-subscription.detail.activeLabel');
            }
        },
        
        possibleStatus() {
            if(this.selectedActionName == 'active') {
                return ['active', 'suspended','on_hold', 'pending_cancel', 'cancelled', 'failed'];
            } else if(this.selectedActionName == 'on_hold') {
                return ['on_hold', 'active', 'suspended', 'cancelled', 'failed'];
            } else if(this.selectedActionName == 'cancelled') {
                return ['active', 'cancelled'];
            }
            else if(this.selectedActionName == 'failed') {
                return ['failed'];
            } else if(this.selectedActionName == 'pending') {
                return ['pending', 'active', 'pending_cancel', 'cancelled', 'failed'];
            } else if(this.selectedActionName == 'suspended') {
                return ['suspended', 'active', 'pending_cancel', 'cancelled'];
            } else if(this.selectedActionName == 'pending_cancel') {
                return ['pending_cancel','active', 'cancelled'];
            } else if(this.selectedActionName == 'expired') {
                return ['expired'];
            }
        },

        closeModal() {
            this.actionToConfirm = '';
            this.changeCycleDate = false;
        },
    }
})
