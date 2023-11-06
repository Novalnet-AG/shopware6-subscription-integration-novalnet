import './components/sw-product-detail-novalnet-subscription';
import './components/sw-product-detail-novalnet-subscription-discount';
import './components/sw-product-detail-novalnet-subscription-discount-delete';
import './components/sw-product-detail-novalnet-subscription-discount-update';
import './page/novalnet-subsctiption-overview';
import './page/novalnet-subsctiption-overview-detail';
import './view/novalnet-subsctiption-overview-detail-base';
import './view/novalnet-subsctiption-overview-detail-transactions';
import './extension/sw-order/view/sw-order-detail-details';
import './decorator/novalnet-subsctiption-search-service-decorator';
import './extension/sw-product-detail';
import './extension/sw-order-detail-base';
import './extension/sw-search-bar-item';

import deDE from './snippet/de_DE.json';
import enGB from './snippet/en_GB.json';

const { Module } = Shopware;
const { mapPropertyErrors, mapState, mapGetters } = Shopware.Component.getComponentHelper();

Module.register('novalnet-subsctiption', {
    type: 'plugin',
    name: 'NovalnetSubscription',
    title: 'novalnet-subscription.label',
    description: 'novalnet-subscription.title',
    version: '2.2.2',
    targetVersion: '2.2.2',
    icon: 'regular-shopping-bag-product',
    color: '#0082C8',
    entity: 'novalnet_subscription',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },
    
    routeMiddleware(next, currentRoute) {
        if (currentRoute.name === 'sw.product.detail') {
            currentRoute.children.push({
                name: 'sw.product.detail.novalnet.subscription',
                path: '/sw/product/detail/:id/novalnet-subscription',
                component: 'sw-product-detail-novalnet-subscription',
                meta: {
                    parentPath: "sw.product.index",
                    privilege: 'novalnet_subscription_product.viewer'
                }
            });
        }
        next(currentRoute);
    },
    
    routes: {
		list: {
			component: 'novalnet-subsctiption-overview',
			path: 'list',
            meta: {
                privilege: 'novalnet_subscription.viewer',
                appSystem: {
                    view: 'list'
                }
            }
		},
        detail: {
            component: 'novalnet-subsctiption-overview-detail',
            path: 'detail/:id?',
            props: {
                default: (route) => ({aboId: route.params.id})
            },
            meta: {
                privilege: 'novalnet_subscription.viewer'
            },
            redirect: {
                name: 'novalnet.subsctiption.detail.base'
            },
            children: {
                base: {
                    component: 'novalnet-subsctiption-overview-detail-base',
                    path: 'base',
                    meta: {
                        parentPath: 'novalnet.subsctiption.list'
                        
                    }
                }, 
                transactions: {
                    component: 'novalnet-subsctiption-overview-detail-transactions',
                    path: 'transactions',
                    meta: {
                        parentPath: 'novalnet.subsctiption.list'
                        
                    }
                }
            }
        }
    },
    
    navigation: [{
		label: 'novalnet-subscription.label',
		color: '#0082C8',
		path: 'novalnet.subsctiption.list',
		parent: 'sw-order',
		icon: 'regular-shopping-bag-product',
		position: 100,
		privilege: 'novalnet_subscription.viewer'
	}]
});

Shopware.Component.override('sw-product-detail', {
    computed: {
		
		...mapState('swProductDetail', [
			'product',
            'parentProduct',
            'variants',
        ]),

        productCriteria() {
            const criteria = this.$super('productCriteria');

            criteria.addAssociation('novalnetConfiguration');

            return criteria;
        }
    }
});
