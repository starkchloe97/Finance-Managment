import { defineStore } from 'pinia'
import loanService from '@/services/loanService'

const emptyPagination = () => ({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
})

const emptyLoanTotals = () => ({
  issued: 0,
  repaid: 0,
  outstanding: 0,
  active: 0,
  overdue: 0,
  paid: 0,
  cancelled: 0,
})

export const useLoanStore = defineStore('loan', {
  state: () => ({
    loans: [],
    loan: null,
    borrowers: [],
    investorLoans: [],
    investorLoanTotals: emptyLoanTotals(),
    pagination: emptyPagination(),
    investorPagination: emptyPagination(),
    loading: false,
    borrowerLoading: false,
    investorLoansLoading: false,
    error: null,
    borrowerError: null,
    investorLoansError: null,
  }),

  actions: {
    setPagination(meta, key = 'pagination') {
      if (!meta) return
      this[key] = {
        current_page: meta.current_page ?? 1,
        last_page: meta.last_page ?? 1,
        per_page: meta.per_page ?? 15,
        total: meta.total ?? 0,
      }
    },

    async fetchLoans(params = {}) {
      this.loading = true
      this.error = null
      try {
        const response = await loanService.getLoans(params)
        this.loans = response.data || []
        this.setPagination(response.meta)
        return response
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to load loans.'
        throw error
      } finally {
        this.loading = false
      }
    },

    async fetchLoan(id) {
      this.loading = true
      this.error = null
      try {
        const response = await loanService.getLoan(id)
        this.loan = response.data
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to load loan.'
        throw error
      } finally {
        this.loading = false
      }
    },

    async createLoan(data) {
      this.loading = true
      this.error = null
      try {
        const response = await loanService.createLoan(data)
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to create loan.'
        throw error
      } finally {
        this.loading = false
      }
    },

    async updateLoan(id, data) {
      const response = await loanService.updateLoan(id, data)
      this.loan = response.data
      return response.data
    },

    async recordRepayment(id, data) {
      await loanService.recordRepayment(id, data)
      return this.fetchLoan(id)
    },

    async cancelLoan(id) {
      const response = await loanService.cancelLoan(id)
      this.loan = response.data
      return response.data
    },

    async fetchBorrowers(params = {}) {
      this.borrowerLoading = true
      this.borrowerError = null
      try {
        const response = await loanService.getBorrowers(params)
        this.borrowers = response.data || []
        return response
      } catch (error) {
        this.borrowerError = error.response?.data?.message || 'Failed to load borrowers.'
        throw error
      } finally {
        this.borrowerLoading = false
      }
    },

    async fetchInvestorLoans(investorId, params = {}) {
      this.investorLoansLoading = true
      this.investorLoansError = null
      try {
        const response = await loanService.getInvestorLoans(investorId, params)
        this.investorLoans = response.data || []
        this.investorLoanTotals = response.meta?.loan_totals || emptyLoanTotals()
        this.setPagination(response.meta, 'investorPagination')
        return response
      } catch (error) {
        this.investorLoansError = error.response?.data?.message || 'Failed to load investor loans.'
        throw error
      } finally {
        this.investorLoansLoading = false
      }
    },

    clearLoan() {
      this.loan = null
      this.error = null
    },
  },
})
