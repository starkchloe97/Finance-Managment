import api from '@/api/axios'

export const getInvoices = (params = {}) => api.get('/invoices', { params })
export const getInvoiceCompanyDetails = () => api.get('/invoices/company-details')
export const getReceivables = (params = {}) => api.get('/receivables', { params })
export const getPayables = (params = {}) => api.get('/payables', { params })
export const getCommissionReceivables = (params = {}) =>
  api.get('/commission/receivables', { params })
export const getInvoice = (id) => api.get(`/invoices/${id}`)
export const createInvoice = (data) => api.post('/invoices', data)
export const updateInvoice = (id, data) => api.put(`/invoices/${id}`, data)
export const createJobInvoice = (jobId) => api.post(`/jobs/${jobId}/invoice`)
export const getPayments = () => api.get('/payments')
export const createPayment = (data) => api.post('/payments', data)
