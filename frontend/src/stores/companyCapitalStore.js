import { defineStore } from 'pinia'
import companyCapitalService from '@/services/companyCapitalService'

const emptyCapital = () => ({
  initialized: false,
  account: null,
  opening_balance: 0,
  available_to_lend: 0,
  lent_out: 0,
  reserved: 0,
  total_capital: 0,
  current_balance: 0,
  transactions: [],
  drafts: [],
  draft_history: [],
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

    async addCapital(data) {
      this.loading = true
      this.error = null

      try {
        const response = await companyCapitalService.addCapital(data)
        Object.assign(this, response.data || emptyCapital())
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to add company capital.'
        throw error
      } finally {
        this.loading = false
      }
    },

    async withdrawCapital(data) {
      this.loading = true
      this.error = null

      try {
        const response = await companyCapitalService.withdrawCapital(data)
        Object.assign(this, response.data || emptyCapital())
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to withdraw company capital.'
        throw error
      } finally {
        this.loading = false
      }
    },

    async updateAvailability(transactionId, data) {
      this.loading = true
      this.error = null

      try {
        const response = await companyCapitalService.updateAvailability(transactionId, data)
        Object.assign(this, response.data || emptyCapital())
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to update capital availability.'
        throw error
      } finally {
        this.loading = false
      }
    },

    async convertDraft(draftId, data) {
      this.loading = true
      this.error = null

      try {
        const response = await companyCapitalService.convertDraft(draftId, data)
        Object.assign(this, response.data || emptyCapital())
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to convert capital draft.'
        throw error
      } finally {
        this.loading = false
      }
    },

    async removeDraft(draftId, data) {
      this.loading = true
      this.error = null

      try {
        const response = await companyCapitalService.removeDraft(draftId, data)
        Object.assign(this, response.data || emptyCapital())
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to remove capital draft.'
        throw error
      } finally {
        this.loading = false
      }
    },
  },
})
