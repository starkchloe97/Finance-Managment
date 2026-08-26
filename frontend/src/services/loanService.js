import api from '@/api/axios'

const loanService = {
  async getLoans(params = {}) {
    const response = await api.get('/loans', { params })
    return response.data
  },

  async getLoan(id) {
    const response = await api.get(`/loans/${id}`)
    return response.data
  },

  async createLoan(data) {
    const response = await api.post('/loans', data)
    return response.data
  },

  async updateLoan(id, data) {
    const response = await api.put(`/loans/${id}`, data)
    return response.data
  },

  async recordRepayment(id, data) {
    const response = await api.post(`/loans/${id}/repayments`, data)
    return response.data
  },

  async cancelLoan(id) {
    const response = await api.post(`/loans/${id}/cancel`)
    return response.data
  },

  async getBorrowers(params = {}) {
    const response = await api.get('/loan-borrowers', { params })
    return response.data
  },

  async getInvestorLoans(investorId, params = {}) {
    const response = await api.get(`/investors/${investorId}/loans`, { params })
    return response.data
  },
}

export default loanService
