import { createRouter, createWebHistory } from "vue-router";
import { useAuthStore } from "@/stores/authStore";

import Login from "@/pages/Login.vue";
import Dashboard from "@/pages/Dashboard.vue";
import Home from "@/pages/Home.vue";

const routes = [
    {
        path: "/login",
        name: "login",
        component: Login,
        meta: { guest: true },
    },
    {
        path: "/",
        component: Home,
        meta: { requiresAuth: true },
        children: [
            {
                path: "",
                component: Dashboard,
            },
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
                path: "estimates/:id/edit",
                component: () => import("@/pages/estimates/EstimateEdit.vue"),
            },
            {
                path: "jobs",
                component: () => import("@/pages/jobs/JobsList.vue"),
            },
            {
                path: "jobs/:id",
                component: () => import("@/pages/jobs/JobDetail.vue"),
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
