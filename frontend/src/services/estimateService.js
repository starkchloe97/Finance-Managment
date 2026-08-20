import api from '@/api/axios'

export const getEstimates = (params = {}) => api.get('/estimates', { params })

export const getEstimate = (id) => api.get(`/estimates/${id}`)

export const createEstimate = (data) => api.post('/estimates', data)

export const updateEstimate = (id, data) => api.put(`/estimates/${id}`, data)
