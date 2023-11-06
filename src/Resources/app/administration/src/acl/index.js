Shopware.Service('privileges')
    .addPrivilegeMappingEntry({
        category: 'permissions',
        parent: 'orders',
        key: 'novalnet_subscription',
        roles: {
            viewer: {
                privileges: [
                    'novalnet_subscription:read',
                    'novalnet_subs_cycle:read',
                ],
                dependencies: [
					'order.viewer',
                ],
            },
            editor: {
                privileges: [
                    'novalnet_subscription:update',
                    'novalnet_subs_cycle:update',
                ],
                dependencies: [
                    'novalnet_subscription_order.viewer',
                    'order.editor',
                ],
            },
            creator: {
                privileges: [
                    'novalnet_subscription:create',
                    'novalnet_subs_cycle:create',
                ],
                dependencies: [
                    'novalnet_subscription_order.viewer',
                    'novalnet_subscription_order.editor',
                    'order.creator',
                ],
            },
            deleter: {
                privileges: [
                    'novalnet_subscription:delete',
                    'novalnet_subs_cycle:delete',
                ],
                dependencies: [
                    'novalnet_subscription_order.viewer',
                ],
            },
        },
    });
    
Shopware.Service('privileges')
    .addPrivilegeMappingEntry({
        category: 'permissions',
        parent: 'catalogues',
        key: 'novalnet_subscription_product',
        roles: {
            viewer: {
                privileges: [
                    'novalnet_product_config:read',
                ],
                dependencies: [
					'product.viewer',
                ],
            },
            editor: {
                privileges: [
					'novalnet_product_config:update',
                ],
                dependencies: [
                    'novalnet_subscription_product.viewer',
                    'product.editor',
                ],
            },
            creator: {
                privileges: [
                    'novalnet_product_config:create',
                ],
                dependencies: [
                    'novalnet_subscription_product.viewer',
                    'novalnet_subscription_product.editor',
                    'product.creator',
                ],
            },
            deleter: {
                privileges: [
                    'novalnet_product_config:delete',
                ],
                dependencies: [
                    'novalnet_subscription_product.viewer',
                ],
            },
        },
    });

