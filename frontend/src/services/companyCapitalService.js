import api from '@/api/axios'

const companyCapitalService = {
  async getCapital() {
    const response = await api.get('/company-capital')
    return response.data
  },

  async initializeCapital(data) {
    const response = await api.post('/company-capital/initialize', data)
    return response.data
  },

  async addCapital(data) {
    const response = await api.post('/company-capital', data)
    return response.data
  },

  async withdrawCapital(data) {
    const response = await api.post('/company-capital/withdraw', data)
    return response.data
  },

  async updateAvailability(transactionId, data) {
    const response = await api.patch(`/company-capital/transactions/${transactionId}`, data)
    return response.data
  },

  async convertDraft(draftId, data) {
    const response = await api.post(`/company-capital/drafts/${draftId}/convert`, data)
    return response.data
  },

  async removeDraft(draftId, data) {
    const response = await api.post(`/company-capital/drafts/${draftId}/remove`, data)
    return response.data
  },
}

export default companyCapitalService
