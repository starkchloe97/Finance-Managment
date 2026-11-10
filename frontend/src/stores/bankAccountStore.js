import { defineStore } from 'pinia'
import {
  createBankAccount,
  getBankAccountTransactions,
  getBankAccounts,
} from '@/services/bankAccountService'

export const useBankAccountStore = defineStore('bankAccounts', {
  state: () => ({
    accounts: [],
    transactions: [],
    transactionPagination: null,
    loading: false,
    error: null,
  }),
  actions: {
    async fetchAccounts() {
      this.loading = true
      this.error = null
      try {
        const { data } = await getBankAccounts()
        this.accounts = data.data || []
        return this.accounts
      } catch (error) {
        this.error = error.response?.data?.message || 'Could not load bank accounts.'
        throw error
      } finally {
        this.loading = false
      }
    },
    async addAccount(payload) {
      const { data } = await createBankAccount(payload)
      this.accounts.push(data.data)
      this.accounts.sort((a, b) => a.name.localeCompare(b.name))
      return data.data
    },
    async fetchTransactions(accountId, page = 1, append = false) {
      this.error = null
      try {
        const { data } = await getBankAccountTransactions(accountId, { page })
        this.transactions = append ? [...this.transactions, ...(data.data || [])] : data.data || []
        this.transactionPagination = data.meta || null
      } catch (error) {
        this.error = error.response?.data?.message || 'Could not load bank transactions.'
        throw error
      }
      return this.transactions
    },
    async fetchMoreTransactions(accountId) {
      const nextPage = Number(this.transactionPagination?.current_page || 1) + 1
      if (nextPage > Number(this.transactionPagination?.last_page || 1)) return this.transactions
      return this.fetchTransactions(accountId, nextPage, true)
    },
  },
})
