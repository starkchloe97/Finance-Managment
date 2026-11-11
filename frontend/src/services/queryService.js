import api from '@/api/axios'

export const getQueries = (params = {}) => api.get('/queries', { params })

export const createQuery = (data) => api.post('/queries', data)

export const getQuery = (id) => api.get(`/queries/${id}`)

export const updateQuery = (id, data) => api.put(`/queries/${id}`, data)

export const deleteQuery = (id) => api.delete(`/queries/${id}`)
