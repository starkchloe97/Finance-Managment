import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'
import AppLayout from '@/layouts/AppLayout.vue'
import Login from '@/pages/Login.vue'
import Dashboard from '@/pages/Dashboard.vue'

const secure = (breadcrumb) => ({ requiresAuth: true, breadcrumb })

const routes = [
  { path: '/login', name: 'login', component: Login, meta: { guest: true } },
  {
    path: '/',
    component: AppLayout,
    meta: secure('Dashboard'),
    children: [
      { path: '', name: 'dashboard', component: Dashboard, meta: secure('Dashboard') },
      {
        path: 'customers',
        component: () => import('@/pages/customers/CustomersList.vue'),
        meta: secure('Operations / Customers'),
      },
      {
        path: 'customers/create',
        component: () => import('@/pages/customers/CustomerCreate.vue'),
        meta: secure('Operations / Customers / New customer'),
      },
      {
        path: 'customers/:id',
        component: () => import('@/pages/customers/CustomerDetail.vue'),
        meta: secure('Operations / Customers / Customer details'),
      },
      {
        path: 'customers/:id/edit',
        component: () => import('@/pages/customers/CustomerEdit.vue'),
        meta: secure('Operations / Customers / Edit customer'),
      },
      {
        path: 'estimates',
        component: () => import('@/pages/estimates/EstimatesList.vue'),
        meta: secure('Operations / Estimates'),
      },
      {
        path: 'estimates/create',
        component: () => import('@/pages/estimates/EstimateCreate.vue'),
        meta: secure('Operations / Estimates / New estimate'),
      },
      {
        path: 'estimates/:id',
        component: () => import('@/pages/estimates/EstimateDetail.vue'),
        meta: secure('Operations / Estimates / Estimate details'),
      },
      {
        path: 'estimates/:id/edit',
        component: () => import('@/pages/estimates/EstimateEdit.vue'),
        meta: secure('Operations / Estimates / Edit estimate'),
      },
      {
        path: 'jobs',
        component: () => import('@/pages/jobs/JobsList.vue'),
        meta: secure('Operations / Transport Jobs'),
      },
      {
        path: 'jobs/:id',
        component: () => import('@/pages/jobs/JobDetail.vue'),
        meta: secure('Operations / Transport Jobs / Job details'),
      },
      {
        path: 'investors',
        name: 'investors',
        component: () => import('@/pages/Investors/Investors.vue'),
        meta: secure('Finance / Investors'),
      },
      {
        path: 'investors/create',
        name: 'investor-create',
        component: () => import('@/pages/Investors/InvestorCreate.vue'),
        meta: secure('Finance / Investors / New investor'),
      },
      {
        path: 'investors/:id',
        name: 'investor-show',
        component: () => import('@/pages/Investors/InvestorShow.vue'),
        meta: secure('Finance / Investors / Investor details'),
      },
      {
        path: 'investors/:id/edit',
        name: 'investor-edit',
        component: () => import('@/pages/Investors/InvestorEdit.vue'),
        meta: secure('Finance / Investors / Edit investor'),
      },
      {
        path: 'loans',
        name: 'loans.index',
        component: () => import('@/pages/loans/LoansList.vue'),
        meta: secure('Finance / Loans'),
      },
      {
        path: 'loans/create',
        name: 'loans.create',
        component: () => import('@/pages/loans/LoanCreate.vue'),
        meta: secure('Finance / Loans / Issue loan'),
      },
      {
        path: 'loans/:id',
        name: 'loans.show',
        component: () => import('@/pages/loans/LoanShow.vue'),
        meta: secure('Finance / Loans / Loan details'),
      },
      {
        path: 'investments',
        name: 'investments.index',
        component: () => import('@/pages/investments/Investments.vue'),
        meta: secure('Finance / Investments'),
      },
      {
        path: 'investments/create',
        name: 'investments.create',
        component: () => import('@/pages/investments/InvestmentCreate.vue'),
        meta: secure('Finance / Investments / New investment'),
      },
      {
        path: 'investments/:id',
        name: 'investments.show',
        component: () => import('@/pages/investments/InvestmentShow.vue'),
        meta: secure('Finance / Investments / Investment details'),
      },
      {
        path: 'investments/:id/edit',
        name: 'investments.edit',
        component: () => import('@/pages/investments/InvestmentEdit.vue'),
        meta: secure('Finance / Investments / Edit investment'),
      },
      {
        path: 'assets',
        name: 'assets.index',
        component: () =>
          import('@/pages/assets/Assets.vue'),
        meta: secure('Operations / Assets'),
      },

      {
        path: 'assets/create',
        name: 'assets.create',
        component: () =>
          import('@/pages/assets/AssetCreate.vue'),
        meta: secure(
          'Operations / Assets / New vehicle'
        ),
      },

      {
        path: 'assets/:id',
        name: 'assets.show',
        component: () =>
          import('@/pages/assets/AssetShow.vue'),
        meta: secure(
          'Operations / Assets / Vehicle details'
        ),
      },

      {
        path: 'assets/:id/edit',
        name: 'assets.edit',
        component: () =>
          import('@/pages/assets/AssetEdit.vue'),
        meta: secure(
          'Operations / Assets / Edit vehicle'
        ),
      },
      {
        path: 'vehicle-contracts',
        name: 'vehicle-contracts.index',
        component: () =>
          import('@/pages/vehicle-contracts/VehicleContractsList.vue'),
        meta: secure('Operations / Vehicle Contracts'),
      },

      {
        path: 'vehicle-contracts/create',
        name: 'vehicle-contracts.create',
        component: () =>
          import('@/pages/vehicle-contracts/VehicleContractCreate.vue'),
        meta: secure(
          'Operations / Vehicle Contracts / New contract'
        ),
      },

      {
        path: 'vehicle-contracts/:id',
        name: 'vehicle-contracts.show',
        component: () =>
          import('@/pages/vehicle-contracts/VehicleContractShow.vue'),
        meta: secure(
          'Operations / Vehicle Contracts / Contract details'
        ),
      },

      {
        path: 'vehicle-contracts/:id/edit',
        name: 'vehicle-contracts.edit',
        component: () =>
          import('@/pages/vehicle-contracts/VehicleContractEdit.vue'),
        meta: secure(
          'Operations / Vehicle Contracts / Edit contract'
        ),
      },

      {
        path: 'vehicle-contracts/:id/vehicles/:vehicleId/reports',
        name: 'vehicle-contracts.vehicle-reports',
        component: () =>
          import('@/pages/vehicle-contracts/VehicleContractVehicleReports.vue'),
        meta: secure(
          'Operations / Vehicle Contracts / Daily reports'
        ),
      },
    ],
  },
]

const router = createRouter({ history: createWebHistory(), routes })

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  if (auth.token && !auth.user) await auth.getUser()
  if (to.meta.requiresAuth && !auth.isAuthenticated) return '/login'
  if (to.meta.guest && auth.isAuthenticated) return '/'
})

export default router
