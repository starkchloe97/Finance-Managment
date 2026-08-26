import { defineStore } from 'pinia'
import investorService from '@/services/investorService'

export const useInvestorStore = defineStore('investor', {
  state: () => ({
    investors: [],
    investor: null,

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
    async fetchInvestors(params = {}) {
      this.loading = true
      this.error = null

      try {
        const response = await investorService.getInvestors(params)

        this.investors = response.data || []

        if (response.meta) {
          this.pagination = {
            current_page: response.meta.current_page,
            last_page: response.meta.last_page,
            per_page: response.meta.per_page,
            total: response.meta.total,
          }
        }

        return response
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to load investors.'

        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchInvestor(id) {
      this.loading = true
      this.error = null

      try {
        const response = await investorService.getInvestor(id)

        this.investor = response.data

        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to load investor.'

        throw error
      } finally {
        this.loading = false
      }
    },

    async createInvestor(data) {
      this.loading = true
      this.error = null

      try {
        const response = await investorService.createInvestor(data)

        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to create investor.'

        throw error
      } finally {
        this.loading = false
      }
    },

    async updateInvestor(id, data) {
      this.loading = true
      this.error = null

      try {
        const response = await investorService.updateInvestor(id, data)

        this.investor = response.data

        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to update investor.'

        throw error
      } finally {
        this.loading = false
      }
    },

    async deleteInvestor(id) {
      this.loading = true
      this.error = null

      try {
        await investorService.deleteInvestor(id)

        this.investors = this.investors.filter((investor) => investor.id !== id)
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to delete investor.'

        throw error
      } finally {
        this.loading = false
      }
    },

    setPage(page) {
      return this.fetchInvestors({ page })
    },

    clearInvestor() {
      this.investor = null
      this.error = null
    },
  },
})
