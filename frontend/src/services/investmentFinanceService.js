import api from '@/api/axios'

export const getAllocations = (investmentId) => api.get(`/investments/${investmentId}/allocations`)
export const createAllocation = (investmentId, payload) =>
  api.post(`/investments/${investmentId}/allocations`, payload)
export const releaseAllocation = (allocationId) =>
  api.delete(`/investment-allocations/${allocationId}`)
export const getInvestmentDistributions = (investmentId) =>
  api.get(`/investments/${investmentId}/profit-distributions`)
export const getJobDistributions = (jobId) => api.get(`/jobs/${jobId}/profit-distributions`)
export const createDistribution = (jobId, payload) =>
  api.post(`/jobs/${jobId}/profit-distributions`, payload)
export const getFinancialAdjustments = (jobId) => api.get(`/jobs/${jobId}/financial-adjustments`)
export const createFinancialAdjustment = (jobId, payload) =>
  api.post(`/jobs/${jobId}/financial-adjustments`, payload)
