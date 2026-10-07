import { defineStore } from 'pinia'
import {
  createInvoice,
  createJobInvoice,
  createPayment,
  getCommissionReceivables,
  getInvoice,
  getInvoices,
  getPayments,
  getPayables,
  getReceivables,
} from '@/services/invoiceService'

export const useInvoiceStore = defineStore('invoices', {
  state: () => ({
    invoices: [],
    invoice: null,
    payments: [],
    pagination: null,
    loading: false,
    error: null,
  }),
  actions: {
    async fetchInvoices({ direction, commission, ...params } = {}) {
      this.loading = true
      this.error = null
      try {
        const fetch = commission
          ? getCommissionReceivables
          : direction === 'receivable'
            ? getReceivables
            : direction === 'payable'
              ? getPayables
              : getInvoices
        const { data } = await fetch(params)
        this.invoices = data.data || []
        this.pagination = data.meta || null
        return this.invoices
      } catch (error) {
        this.error = error.response?.data?.message || 'Could not load invoices.'
        throw error
      } finally {
        this.loading = false
      }
    },
    async fetchInvoice(id) {
      this.loading = true
      this.error = null
      try {
        const { data } = await getInvoice(id)
        this.invoice = data.data
        return this.invoice
      } catch (error) {
        this.error = error.response?.data?.message || 'Could not load invoice.'
        throw error
      } finally {
        this.loading = false
      }
    },
    async saveInvoice(payload) {
      const { data } = await createInvoice(payload)
      return data.data
    },
    async generateJobInvoice(jobId) {
      const { data } = await createJobInvoice(jobId)
      return data.data
    },
    async savePayment(payload) {
      const { data } = await createPayment(payload)
      return data.data
    },
    async fetchPayments() {
      this.loading = true
      this.error = null
      try {
        const { data } = await getPayments()
        this.payments = data.data || []
        this.pagination = data.meta || null
        return this.payments
      } catch (error) {
        this.error = error.response?.data?.message || 'Could not load payments.'
        throw error
      } finally {
        this.loading = false
      }
    },
  },
})
