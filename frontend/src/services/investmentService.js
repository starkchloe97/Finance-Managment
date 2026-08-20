import api from '@/api/axios'
const investmentService = {
  async getInvestments(params = {}) {
    const response = await api.get('/investments', {
      params,
    })

    return response.data
  },

  async getInvestment(id) {
    const response = await api.get(`/investments/${id}`)

    return response.data
  },

  async getInvestorInvestments(investorId, params = {}) {
    const response = await api.get(`/investors/${investorId}/investments`, {
      params,
    })

    return response.data
  },

  async createInvestment(data) {
    const response = await api.post('/investments', data)

    return response.data
  },

  async updateInvestment(id, data) {
    const response = await api.put(`/investments/${id}`, data)

    return response.data
  },

  async matureInvestment(id) {
    const response = await api.post(`/investments/${id}/mature`)

    return response.data
  },

  async withdrawInvestment(id) {
    const response = await api.post(`/investments/${id}/withdraw`)

    return response.data
  },

  async settleInvestment(id) {
    const response = await api.post(`/investments/${id}/settle`)

    return response.data
  },

  async cancelInvestment(id) {
    const response = await api.post(`/investments/${id}/cancel`)

    return response.data
  },

  async deleteInvestment(id) {
    const response = await api.delete(`/investments/${id}`)

    return response.data
  },
}

export default investmentService
