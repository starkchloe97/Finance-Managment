import api from '@/api/axios'

export const getVehicleContracts = (params = {}) =>
  api.get('/vehicle-contracts', { params })

export const getVehicleContract = (id) =>
  api.get(`/vehicle-contracts/${id}`)

export const createVehicleContract = (data) =>
  api.post('/vehicle-contracts', data)

export const updateVehicleContract = (id, data) =>
  api.put(`/vehicle-contracts/${id}`, data)

export const deleteVehicleContract = (id) =>
  api.delete(`/vehicle-contracts/${id}`)