import api from '@/api/axios'

const investorService = {
  async getInvestors(params = {}) {
    const response = await api.get('/investors', {
      params,
    })

    return response.data
  },

  async getInvestor(id) {
    const response = await api.get(`/investors/${id}`)

    return response.data
  },

  async createInvestor(data) {
    const response = await api.post('/investors', data)

    return response.data
  },

  async updateInvestor(id, data) {
    const response = await api.put(`/investors/${id}`, data)

    return response.data
  },

  async deleteInvestor(id) {
    const response = await api.delete(`/investors/${id}`)

    return response.data
  },
}

export default investorService
