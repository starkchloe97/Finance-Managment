import { createRouter, createWebHistory } from "vue-router";
import { useAuthStore } from "@/stores/authStore";

import AppLayout from "@/layouts/AppLayout.vue";
import Login from "@/pages/Login.vue";
import Dashboard from "@/pages/Dashboard.vue";

// Everything except /login lives inside AppLayout, so requiresAuth on the
// parent covers every page below it.
const routes = [
    {
        path: "/login",
        name: "login",
        component: Login,
        meta: { guest: true },
    },
    {
        path: "/",
        component: AppLayout,
        meta: { requiresAuth: true },
        children: [
            { path: "", component: Dashboard },
            {
                path: "customers",
                component: () => import("@/pages/customers/CustomersList.vue"),
            },
            {
                path: "customers/create",
                component: () => import("@/pages/customers/CustomerCreate.vue"),
            },
            {
                path: "customers/:id/edit",
                component: () => import("@/pages/customers/CustomerEdit.vue"),
            },
            {
                path: "estimates",
                component: () => import("@/pages/estimates/EstimatesList.vue"),
            },
            {
                path: "estimates/create",
                component: () => import("@/pages/estimates/EstimateCreate.vue"),
            },
            {
                path: "jobs",
                component: () => import("@/pages/jobs/JobsList.vue"),
            },
            {
                path: "jobs/:id",
                component: () => import("@/pages/jobs/JobDetail.vue"),
            },
            {
                path: '/investors',
                name: 'investors',
                component: () => import('@/pages/Investors/Investors.vue'),
                meta: {
                    requiresAuth: true,
                },
            },
            {
                path: '/investors/create',
                name: 'investor-create',
                component: () => import('@/pages/Investors/InvestorCreate.vue'),
                meta: {
                    requiresAuth: true,
                },
            },
            {
                path: '/investors/:id',
                name: 'investor-show',
                component: () => import('@/pages/Investors/InvestorShow.vue'),
                meta: {
                    requiresAuth: true,
                },
            },
            {
                path: '/investors/:id/edit',
                name: 'investor-edit',
                component: () => import('@/pages/Investors/InvestorEdit.vue'),
                meta: {
                    requiresAuth: true,
                },
            },
            {
    path: '/investments',
    name: 'investments.index',
    component: () => import('@/pages/investments/Investments.vue'),
},

{
    path: '/investments/create',
    name: 'investments.create',
    component: () => import('@/pages/investments/InvestmentCreate.vue'),
},

{
    path: '/investments/:id',
    name: 'investments.show',
    component: () => import('@/pages/investments/InvestmentShow.vue'),
},

{
    path: '/investments/:id/edit',
    name: 'investments.edit',
    component: () => import('@/pages/investments/InvestmentEdit.vue'),
},

        ],
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach(async (to) => {
    const auth = useAuthStore();

    if (auth.token && !auth.user) {
        await auth.getUser();
    }

    if (to.meta.requiresAuth && !auth.isAuthenticated) {
        return "/login";
    }

    if (to.meta.guest && auth.isAuthenticated) {
        return "/";
    }
});

export default router;
