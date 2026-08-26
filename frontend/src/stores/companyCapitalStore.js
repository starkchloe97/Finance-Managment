import { defineStore } from 'pinia'
import companyCapitalService from '@/services/companyCapitalService'

const emptyCapital = () => ({
  initialized: false,
  account: null,
  opening_balance: 0,
  current_balance: 0,
  transactions: [],
})

export const useCompanyCapitalStore = defineStore('companyCapital', {
  state: () => ({
    ...emptyCapital(),
    loading: false,
    error: null,
  }),

  actions: {
    async fetchCapital() {
      this.loading = true
      this.error = null

      try {
        const response = await companyCapitalService.getCapital()
        Object.assign(this, response.data || emptyCapital())
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to load company capital.'
        throw error
      } finally {
        this.loading = false
      }
    },

    async initializeCapital(data) {
      this.loading = true
      this.error = null

      try {
        const response = await companyCapitalService.initializeCapital(data)
        Object.assign(this, response.data || emptyCapital())
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to initialize company capital.'
        throw error
      } finally {
        this.loading = false
      }
    },
  },
})
