import api from '@/api/axios'

export const getDailyReports = (contractVehicleId, params = {}) => {
  return api.get(
    `/contract-vehicles/${contractVehicleId}/daily-reports`,
    { params }
  )
}

export const getDailyReport = (
  contractVehicleId,
  reportId
) => {
  return api.get(
    `/contract-vehicles/${contractVehicleId}/daily-reports/${reportId}`
  )
}

export const createDailyReport = (
  contractVehicleId,
  payload
) => {
  return api.post(
    `/contract-vehicles/${contractVehicleId}/daily-reports`,
    payload
  )
}

export const updateDailyReport = (
  contractVehicleId,
  reportId,
  payload
) => {
  return api.put(
    `/contract-vehicles/${contractVehicleId}/daily-reports/${reportId}`,
    payload
  )
}

export const deleteDailyReport = (
  contractVehicleId,
  reportId
) => {
  return api.delete(
    `/contract-vehicles/${contractVehicleId}/daily-reports/${reportId}`
  )
}