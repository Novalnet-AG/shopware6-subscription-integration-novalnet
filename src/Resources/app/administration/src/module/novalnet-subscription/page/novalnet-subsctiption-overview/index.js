import template from './novalnet-subsctiption-overview.html.twig';
import './novalnet-subsctiption-overview.scss';

const { Component, Mixin, Defaults } = Shopware;
const { Criteria } = Shopware.Data;

Component.register('novalnet-subsctiption-overview', {
    template,
    
    inject: ['repositoryFactory', 'numberRangeService', 'acl', 'filterFactory'],
    
    data() {
		return {
			subscriptions: [],
            sortBy: 'subsNumber',
            sortDirection: 'DESC',
            naturalSorting: true,
            isLoading: false,
            isBulkLoading: false,
            showDeleteModal: false,
            total: 0,
            page: 1,
            filterLoading: false,
            availableStatus: [],
            statusFilter: [],
		}
	},
	
	mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('listing'),
        Mixin.getByName('placeholder')
    ],
	
	metaInfo() {
        return {
            title: this.$createTitle()
        };
    },
    
    created() {
        this.loadFilterValues();
        this.getList();
    },
    
    computed: {
        subscriptionRepository() {
            return this.repositoryFactory.create('novalnet_subscription');
        },
        
        subscriptionColumns() {
            return this.getSubscriptionColumns();
        },

        filterSelectCriteria() {
            const criteria = new Criteria(1, 1);

            criteria.addFilter(Criteria.not(
                'AND',
                [Criteria.equals('status', null)]
            ));
            criteria.addAggregation(Criteria.terms('status', 'status'));

            return criteria;
        },
    },
    
    methods: {
        getList() {
            this.isLoading = true;
			
            const subscriptionCriteria = new Criteria(this.page, this.limit);
            
            subscriptionCriteria.setTerm(this.term);
            subscriptionCriteria.addAssociation('order');
            subscriptionCriteria.addAssociation('order.salesChannel');
            subscriptionCriteria.addAssociation('order.orderCustomer');
            subscriptionCriteria.addAssociation('order.currency');
            subscriptionCriteria.addAssociation('order.lineItems');
            subscriptionCriteria.getAssociation('order.transactions').addSorting(Criteria.sort('createdAt'));
			
            if (this.statusFilter.length > 0) {
                subscriptionCriteria.addFilter(Criteria.equalsAny('status', this.statusFilter));
            }
			
            subscriptionCriteria.addSorting(Criteria.sort(this.sortBy, this.sortDirection));
			
            return Promise.all([
                this.subscriptionRepository.search(subscriptionCriteria, Shopware.Context.api),
            ]).then((result) => {
				this.subscriptions = [];
				this.total = result[0].total;
				result[0].forEach((row, index) => {
					row.length = row.length * row.interval;
                    this.subscriptions.push(row);
                });
                this.isLoading = false;
                this.selection = {};
            }).catch(() => {
                this.isLoading = false;
            });
        },
        
        disableDeletion(order) {
            if (!this.acl.can('order.deleter')) {
                return true;
            }

            return order.documents.length > 0;
        },
        
        onCloseDeleteModal() {
            this.showDeleteModal = false;
        },
        
        loadFilterValues() {
            this.filterLoading = true;

            return this.subscriptionRepository.search(this.filterSelectCriteria, Shopware.Context.api)
                .then(({ aggregations }) => {
                    aggregations.status.buckets.forEach((row, index) => {
                        aggregations.status.buckets[index]['translated'] = this.$tc('novalnet-subscription.status.' + row.key.toLowerCase());
                    });
                    this.availableStatus = aggregations.status.buckets;
                    this.filterLoading = false;
                    return aggregations;
                }).catch(() => {
                    this.filterLoading = false;
                });
        },
        
        onDelete(id) {
            this.showDeleteModal = id;
        },
        
        getSubscriptionColumns() {
            return [{
                property: 'subsNumber',
                dataIndex: 'subsNumber',
                label: 'novalnet-subscription.list.subscriptionOrderNumber',
                allowResize: true,
                routerLink: 'novalnet.subsctiption.detail.base',
                primary: true
            }, {
                property: 'order.salesChannel.name',
                dataIndex: 'order.salesChannel.name',
                label: 'novalnet-subscription.list.saleschannelName',
                allowResize: true,
            }, {
                property: 'order.orderCustomer.firstName',
                dataIndex: 'order.orderCustomer.firstName,order.orderCustomer.lastName',
                label: 'novalnet-subscription.list.customerName',
                allowResize: true
            }, {
                property: 'amount',
                label: 'novalnet-subscription.list.amount',
                allowResize: true,
                align: 'right'
            }, {
                property: 'status',
                dataIndex: 'status',
                label: 'novalnet-subscription.list.status',
                allowResize: true,
                align: 'center'
            }, {
                property: 'interval',
                dataIndex: 'interval',
                label: 'novalnet-subscription.list.intervalType',
                allowResize: true
            }, {
                property: 'length',
                dataIndex: 'length',
                label: 'novalnet-subscription.list.subscriptionLength',
                allowResize: true
            }, {
                property: 'countItems',
                dataIndex: 'order.lineItems',
                label: 'novalnet-subscription.list.countItems',
                allowResize: true,
                visible: false
            }, {
                property: 'createdAt',
                label: 'novalnet-subscription.list.createdAt',
                allowResize: true
            }, {
                property: 'endingAt',
                label: 'novalnet-subscription.detail.endingDate',
                allowResize: true
            }];
        },
        getVariantFromOrderState(status) {
            if(status == 'CANCELLED' || status == 'EXPIRED' || status == 'FAILED' ) {
				return 'danger';
			} else if (status == 'PENDING' || status == 'ON_HOLD') {
				return 'info';
			} else if (status == 'SUSPENDED' || status == 'PENDING_CANCEL') {
				return 'warning';
			} else {
				return 'success';
			}
        },
        
        updateTotal({ total }) {
            this.total = total;
        },
        
        onConfirmDelete(id) {
            this.showDeleteModal = false;

            return this.subscriptionRepository.delete(id).then(() => {
                this.getList();
            });
        },
        
        onChangeStatusFilter(value) {
			this.page = 1;
            this.statusFilter = value;
            this.getList();
        }
	}
});
