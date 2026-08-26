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
}

export default companyCapitalService
