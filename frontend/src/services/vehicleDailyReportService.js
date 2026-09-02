import api from '@/api/axios'

export const getContractVehicle = (contractVehicleId) =>
  api.get(`/contract-vehicles/${contractVehicleId}`)

export const getDailyReports = (contractVehicleId, params = {}) =>
  api.get(
    `/contract-vehicles/${contractVehicleId}/daily-reports`,
    { params }
  )

export const getDailyReport = (contractVehicleId, reportId) =>
  api.get(
    `/contract-vehicles/${contractVehicleId}/daily-reports/${reportId}`
  )

export const createDailyReport = (contractVehicleId, payload) =>
  api.post(
    `/contract-vehicles/${contractVehicleId}/daily-reports`,
    payload
  )

export const updateDailyReport = (contractVehicleId, reportId, payload) =>
  api.put(
    `/contract-vehicles/${contractVehicleId}/daily-reports/${reportId}`,
    payload
  )

export const deleteDailyReport = (contractVehicleId, reportId) =>
  api.delete(
    `/contract-vehicles/${contractVehicleId}/daily-reports/${reportId}`
  )

export const getMonthlySummary = (contractVehicleId, month) =>
  api.get(
    `/contract-vehicles/${contractVehicleId}/daily-reports/monthly-summary`,
    {
      params: { month },
    }
  )
