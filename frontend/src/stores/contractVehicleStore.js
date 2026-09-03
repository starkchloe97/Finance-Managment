import { defineStore } from 'pinia'
import { ref } from 'vue'

import {
  getContractVehicles,
} from '@/services/vehicleDailyReportService'

export const useContractVehicleStore = defineStore(
  'contractVehicle',
  () => {
    const vehicles = ref([])
    const pagination = ref(null)
    const loading = ref(false)
    const error = ref('')

    const fetchVehicles = async (params = {}) => {
      loading.value = true
      error.value = ''

      try {
        const response = await getContractVehicles(params)

        const responseData = response.data?.data ?? []

        vehicles.value = Array.isArray(responseData)
          ? responseData
          : responseData.data ?? []

        pagination.value = response.data?.meta ?? null

        return vehicles.value
      } catch (err) {
        error.value =
          err.response?.data?.message ||
          'Unable to load vehicles.'
        throw err
      } finally {
        loading.value = false
      }
    }

    return {
      vehicles,
      pagination,
      loading,
      error,
      fetchVehicles,
    }
  }
)
