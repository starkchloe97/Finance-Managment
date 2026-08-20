import api from '@/api/axios'

// All three are nested under the job: changing a cost changes the job's
// totals, and each of these answers with the recalculated job.
export const addExpense = (jobId, data) => api.post(`/jobs/${jobId}/expenses`, data)

export const updateExpense = (jobId, id, data) => api.patch(`/jobs/${jobId}/expenses/${id}`, data)

export const deleteExpense = (jobId, id) => api.delete(`/jobs/${jobId}/expenses/${id}`)
