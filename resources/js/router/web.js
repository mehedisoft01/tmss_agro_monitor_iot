export const routes = [
    {
        path: '/',
        name: 'App',
        component: () => import('@/web/layout/WebLayout.vue'),
        children: [
            {
                path: 'dashboard',
                name: 'dashboard',
                component: () => import('@/web/Dashboard.vue'),
                meta: { dataUrl: 'web/api/dashboard' }
            },
            {
                path: 'profile',
                name: 'profile',
                component: () => import('@/web/profile.vue'),
                meta: { dataUrl: 'web/api/profile' }
            },
            {
                path: 'order',
                name: 'order',
                component: () => import('@/web/order.vue'),
                meta: { dataUrl: 'web/api/order' }
            },
            {
                path: 'order-history',
                name: 'order-history',
                component: () => import('@/web/orderHistory.vue'),
                meta: { dataUrl: 'web/api/order-history' }
            },
            {
                path: 'return-request',
                name: 'return-request',
                component: () => import('@/web/returnRequest.vue'),
                meta: { dataUrl: 'web/api/return-request'}
            },

            {
                path: 'return-history',
                name: 'return-history',
                component: () => import('@/web/returnHistory.vue'),
                meta: { dataUrl: 'web/api/return-history'}
            },
        ]
    }
];