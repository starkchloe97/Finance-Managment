import api from '@/api/axios'

export const getDashboard = (period) => api.get('/dashboard', { params: { period } })
