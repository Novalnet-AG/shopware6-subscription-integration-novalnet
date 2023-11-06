import template from './novalnet-subsctiption-overview-detail-transactions.html.twig';

const { Component } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('novalnet-subsctiption-overview-detail-transactions', {
    template,

    inject: [
        'repositoryFactory'
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
            orders: [],
            transactions: null,
            isLoading: true,            
            versionContext: null,
        }
    },

    created() {
        this.createdComponent();
    },

    computed: {
        aboRepository() {
            return this.repositoryFactory.create('novalnet_subscription');
        },

        gridColumns() {
            return this.getGridColumns()
        },

        hasTransactions() {
            return !!this.transactions && this.transactions.length;
        }
    },

    methods: {
        createdComponent() {
            this.versionContext = Shopware.Context.api
            let criteria = new Criteria();
            criteria.addAssociation('subsOrders')
            criteria.addAssociation('subsOrders.order');
            
            this.aboRepository.get(this.aboId, Shopware.Context.api, criteria).then((response) => {
                this.abo = response;
                
                this.abo.subsOrders.sort((a, b) => a.cycles - b.cycles ).forEach((subsOrder) => {
					if(subsOrder.order != null && subsOrder.order != undefined)
					{
						this.orders.push(subsOrder);
					}
				});
				
                this.isLoading = false;
            }).catch((err) => {
                this.isLoading = false;
            });
        },
        getVariantFromOrderState(status) {
            if(status == 'FAILURE') {
				return 'danger';
			}
			else if (status == 'CANCELLED') {
				return 'danger';
			} else if (status == 'PENDING') {
				return 'info';
			} else if (status == 'RETRY') {
				return 'warning';
			} else {
				return 'success';
			}
        },
        getGridColumns() {
            return [{
                property: 'subsOrders.cycles',
                label: 'novalnet-subscription.order.orderType'
            }, {
                property: 'order.orderNumber',
                label: 'novalnet-subscription.order.orderNumber',
            }, {
                property: 'subsOrders.status',
                label: 'novalnet-subscription.order.status',
            }, {
                property: 'subsOrders.cycleDate',
                label: 'novalnet-subscription.order.orderDate'
            }]
        },
    }
})
