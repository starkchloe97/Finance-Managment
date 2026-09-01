import api from '@/api/axios'

export const assetService = {
  async getAll(params = {}) {
    const response = await api.get('/assets', {
      params,
    })

    return response.data
  },

  async get(id) {
    const response = await api.get(
      `/assets/${id}`
    )

    return response.data
  },

  async create(payload) {
    const response = await api.post(
      '/assets',
      payload
    )

    return response.data
  },

  async update(id, payload) {
    const response = await api.put(
      `/assets/${id}`,
      payload
    )

    return response.data
  },

  async delete(id) {
    const response = await api.delete(
      `/assets/${id}`
    )

    return response.data
  },

  async getAvailableVehicles() {
    const response = await api.get('/assets/available-vehicles')

    return response.data
  },
}