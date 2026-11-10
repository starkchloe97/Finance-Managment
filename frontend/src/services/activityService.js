import api from '@/api/axios'

export const getActivities = (params = {}) => api.get('/activities', { params })
