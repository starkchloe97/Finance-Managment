import api from '@/api/axios'

export const getSummary = () => api.get('/reports/summary')
export const getFinancialReport = (params) => api.get('/reports/financial', { params })
export const getAccountingPeriods = () => api.get('/accounting-periods')
export const closeAccountingPeriod = (payload) => api.post('/accounting-periods', payload)
