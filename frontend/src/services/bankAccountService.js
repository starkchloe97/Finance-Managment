import api from '@/api/axios'

export const getBankAccounts = () => api.get('/bank-accounts')
export const createBankAccount = (data) => api.post('/bank-accounts', data)
export const getBankAccountTransactions = (id, params = {}) =>
  api.get(`/bank-accounts/${id}/transactions`, { params })
