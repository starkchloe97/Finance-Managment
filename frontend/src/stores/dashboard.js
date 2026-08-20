import { defineStore } from 'pinia'
import { getDashboard } from '@/services/dashboardService'

export const useDashboardStore = defineStore('dashboard', {
  state: () => ({ dashboard: null, period: 'this_month', loading: false, error: null }),
  actions: {
    async fetchDashboard() {
      this.loading = true
      this.error = null
      try {
        this.dashboard = (await getDashboard(this.period)).data
      } catch (error) {
        this.error = error
      } finally {
        this.loading = false
      }
    },
    async setPeriod(period) {
      this.period = period
      await this.fetchDashboard()
    },
    async refresh() {
      await this.fetchDashboard()
    },
  },
})
