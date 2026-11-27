import api from '@/api/axios'

export const getReceipts = (params = {}) => api.get('/receipts', { params })
export const getReceipt = (id) => api.get(`/receipts/${id}`)
