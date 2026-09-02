import { defineStore } from 'pinia'
import { ref } from 'vue'

import {
  getContractVehicle,
  getDailyReports,
  getDailyReport,
  createDailyReport,
  updateDailyReport,
  deleteDailyReport,
} from '@/services/vehicleDailyReportService'

export const useVehicleDailyReportStore = defineStore(
  'vehicleDailyReport',
  () => {
    const contractVehicle = ref(null)
    const reports = ref([])
    const pagination = ref(null)
    const loading = ref(false)
    const error = ref('')

    const reset = () => {
      contractVehicle.value = null
      reports.value = []
      pagination.value = null
      error.value = ''
    }

    const fetchContractVehicle = async (contractVehicleId) => {
      const response = await getContractVehicle(contractVehicleId)
      contractVehicle.value = response.data?.data ?? response.data
      return contractVehicle.value
    }

    const fetchReports = async (contractVehicleId, params = {}) => {
      loading.value = true
      error.value = ''

      try {
        const response = await getDailyReports(
          contractVehicleId,
          params
        )

        const responseData = response.data?.data ?? []

        reports.value = Array.isArray(responseData)
          ? responseData
          : responseData.data ?? []

        pagination.value = response.data?.meta ?? null

        return reports.value
      } catch (err) {
        error.value =
          err.response?.data?.message ||
          'Unable to load daily reports.'
        throw err
      } finally {
        loading.value = false
      }
    }

    const fetchReport = async (contractVehicleId, reportId) => {
      const response = await getDailyReport(
        contractVehicleId,
        reportId
      )
      return response.data?.data ?? response.data
    }

    const addReport = async (contractVehicleId, payload) => {
      const response = await createDailyReport(
        contractVehicleId,
        payload
      )
      return response.data?.data ?? response.data
    }

    const editReport = async (
      contractVehicleId,
      reportId,
      payload
    ) => {
      const response = await updateDailyReport(
        contractVehicleId,
        reportId,
        payload
      )
      return response.data?.data ?? response.data
    }

    const removeReport = async (contractVehicleId, reportId) => {
      await deleteDailyReport(contractVehicleId, reportId)
      reports.value = reports.value.filter(
        report => String(report.id) !== String(reportId)
      )
    }

    return {
      contractVehicle,
      reports,
      pagination,
      loading,
      error,
      reset,
      fetchContractVehicle,
      fetchReports,
      fetchReport,
      addReport,
      editReport,
      removeReport,
    }
  }
)
