import { defineStore } from 'pinia'
import { getDashboard } from '@/services/dashboardService'

const emptyDashboard = {
  meta: null,
  kpis: {},
  financial_overview: [],
  job_status: {},
  current_pipeline: {},
  recent_jobs: [],
  pending_estimates: [],
  alerts: [],
}

const localDate = (date) => {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

export const useDashboardStore = defineStore('dashboard', {
  state: () => ({
    dashboard: null,
    period: 'this_month',
    fromDate: localDate(new Date(new Date().getFullYear(), new Date().getMonth(), 1)),
    toDate: localDate(new Date()),
    loading: false,
    refreshing: false,
    error: null,
    statusMessage: '',
  }),
  actions: {
    async fetchDashboard() {
      const hasDashboard = Boolean(this.dashboard)
      this.loading = !hasDashboard
      this.refreshing = hasDashboard
      this.error = null
      this.statusMessage = ''

      const params = { period: this.period }
      if (this.period === 'custom') {
        params.from = this.fromDate
        params.to = this.toDate
      }

      try {
        const response = await getDashboard(params)
        this.dashboard = { ...emptyDashboard, ...response.data }
        this.statusMessage = 'Dashboard refreshed.'
        return this.dashboard
      } catch (error) {
        this.error = error.response?.data?.message || 'Could not load dashboard data.'
        throw error
      } finally {
        this.loading = false
        this.refreshing = false
      }
    },
    async setPeriod(period) {
      this.period = period
      this.error = null
      if (period === 'custom') return
      await this.fetchDashboard()
    },
    async setCustomRange(from, to) {
      if (!from || !to || from > to) {
        this.error = 'Choose a valid date range before applying the custom period.'
        return
      }
      this.fromDate = from
      this.toDate = to
      await this.fetchDashboard()
    },
    async refresh() {
      await this.fetchDashboard()
    },
  },
})
