import api from '@/api/axios'

export const getDashboard = (params = {}) => api.get('/dashboard', { params })
