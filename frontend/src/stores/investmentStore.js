import { defineStore } from 'pinia'
import investmentService from '@/services/investmentService'

const lifecycleActions = {
  mature: 'matureInvestment',
  withdraw: 'withdrawInvestment',
  settle: 'settleInvestment',
  cancel: 'cancelInvestment',
}

export const useInvestmentStore = defineStore('investment', {
  state: () => ({
    investments: [],
    investment: null,
    investorInvestmentTotals: {
      pool: 0,
      normal: 0,
      total: 0,
    },

    loading: false,
    error: null,

    pagination: {
      current_page: 1,
      last_page: 1,
      per_page: 15,
      total: 0,
    },
  }),

  actions: {
    async fetchInvestments(params = {}) {
      this.loading = true
      this.error = null

      try {
        const response = await investmentService.getInvestments(params)

        this.investments = response.data || []
        this.investorInvestmentTotals = response.meta?.investment_totals || {
          pool: 0,
          normal: 0,
          total: 0,
        }

        this.setPagination(response.meta)

        return response
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to load investments.'

        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchInvestment(id) {
      this.loading = true
      this.error = null

      try {
        const response = await investmentService.getInvestment(id)

        this.investment = response.data

        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to load investment.'

        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchInvestorInvestments(investorId, params = {}) {
      this.loading = true
      this.error = null

      try {
        const response = await investmentService.getInvestorInvestments(investorId, params)

        this.investments = response.data || []
        this.investorInvestmentTotals = response.meta?.investment_totals || {
          pool: 0,
          normal: 0,
          total: 0,
        }

        this.setPagination(response.meta)

        return response
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to load investor investments.'

        throw error
      } finally {
        this.loading = false
      }
    },

    async createInvestment(data) {
      this.loading = true
      this.error = null

      try {
        const response = await investmentService.createInvestment(data)

        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to create investment.'

        throw error
      } finally {
        this.loading = false
      }
    },

    async updateInvestment(id, data) {
      this.loading = true
      this.error = null

      try {
        const response = await investmentService.updateInvestment(id, data)

        this.investment = response.data

        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to update investment.'

        throw error
      } finally {
        this.loading = false
      }
    },

    async transitionInvestment(action, id) {
      this.loading = true
      this.error = null

      try {
        const response = await investmentService[lifecycleActions[action]](id)

        this.investment = response.data

        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || `Failed to ${action} investment.`

        throw error
      } finally {
        this.loading = false
      }
    },

    async mature(id) {
      return this.transitionInvestment('mature', id)
    },

    async withdraw(id) {
      return this.transitionInvestment('withdraw', id)
    },

    async settle(id) {
      return this.transitionInvestment('settle', id)
    },

    async cancel(id) {
      return this.transitionInvestment('cancel', id)
    },

    async deleteInvestment(id) {
      this.loading = true
      this.error = null

      try {
        await investmentService.deleteInvestment(id)

        this.investments = this.investments.filter((investment) => investment.id !== id)
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to delete investment.'

        throw error
      } finally {
        this.loading = false
      }
    },

    setPagination(meta) {
      if (!meta) {
        return
      }

      this.pagination = {
        current_page: meta.current_page ?? 1,

        last_page: meta.last_page ?? 1,

        per_page: meta.per_page ?? 15,

        total: meta.total ?? 0,
      }
    },

    clearInvestment() {
      this.investment = null
      this.error = null
    },
  },
})
