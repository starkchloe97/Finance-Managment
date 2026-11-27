import { defineStore } from 'pinia'
import { getReceipt, getReceipts } from '@/services/receiptService'

export const useReceiptStore = defineStore('receipts', {
  state: () => ({
    receipts: [],
    receipt: null,
    pagination: null,
    loading: false,
    error: null,
  }),
  actions: {
    async fetchReceipts(params = {}) {
      this.loading = true
      this.error = null
      try {
        const { data } = await getReceipts(params)
        this.receipts = data.data || []
        this.pagination = data.meta || null
      } catch (error) {
        this.error = error.response?.data?.message || 'Could not load receipts.'
        throw error
      } finally {
        this.loading = false
      }
    },
    async fetchReceipt(id) {
      this.loading = true
      this.error = null
      try {
        const { data } = await getReceipt(id)
        this.receipt = data.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Could not load this receipt.'
        throw error
      } finally {
        this.loading = false
      }
    },
  },
})
