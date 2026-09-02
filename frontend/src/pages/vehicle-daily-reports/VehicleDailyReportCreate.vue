<script setup>
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { useVehicleDailyReportStore } from '@/stores/vehicleDailyReportStore'
import { useToast } from '@/composables/useToast'
import VehicleDailyReportForm from '@/components/vehicle-daily-reports/VehicleDailyReportForm.vue'

const route = useRoute()
const router = useRouter()
const store = useVehicleDailyReportStore()
const { show: showToast } = useToast()

const contractVehicleId = route.params.id

const submitting = ref(false)
const errors = ref({})
const generalError = ref('')

const loadVehicle = async () => {
  try {
    await store.fetchContractVehicle(contractVehicleId)
  } catch (err) {
    generalError.value =
      err.response?.data?.message ||
      'Unable to load vehicle details.'
  }
}

const submit = async (payload) => {
  submitting.value = true
  errors.value = {}
  generalError.value = ''

  try {
    await store.addReport(contractVehicleId, payload)

    showToast('Daily report added')

    await router.push({
      name: 'contract-vehicles.daily-reports',
      params: { id: contractVehicleId },
    })
  } catch (err) {
    if (err.response?.status === 422) {
      errors.value = err.response.data.errors || {}
    } else {
      generalError.value =
        err.response?.data?.message ||
        'Unable to save daily report.'
    }
  } finally {
    submitting.value = false
  }
}

const cancel = () => {
  router.push({
    name: 'contract-vehicles.daily-reports',
    params: { id: contractVehicleId },
  })
}

onMounted(loadVehicle)
</script>

<template>
  <div class="daily-report-form-page">
    <header class="page-head">
      <div>
        <p class="eyebrow">
          Operations / Vehicle Reporting
        </p>

        <h1>Add Daily Report</h1>
      </div>
    </header>

    <div
      v-if="generalError"
      class="form-error"
    >
      {{ generalError }}
    </div>

    <section
      v-if="store.contractVehicle"
      class="form-card"
    >
      <div class="vehicle-summary">
        <span class="vehicle-code">
          {{ store.contractVehicle.vehicle_number }}
        </span>

        <h2>
          {{ store.contractVehicle.make }}
          {{ store.contractVehicle.model }}
        </h2>

        <p>
          {{ store.contractVehicle.vehicle_type }}
        </p>
      </div>

      <VehicleDailyReportForm
        :contract-vehicle="store.contractVehicle"
        :errors="errors"
        :submitting="submitting"
        submit-label="Add Report"
        @submit="submit"
        @cancel="cancel"
      />
    </section>

    <div
      v-else-if="!generalError"
      class="loading-state"
    >
      Loading vehicle details…
    </div>
  </div>
</template>

<style scoped>
.daily-report-form-page {
  min-width: 0;
}

.page-head {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
  margin-bottom: 1.25rem;
}

.eyebrow {
  margin: 0 0 0.25rem;
  color: var(--text-muted, #6b7280);
  font-size: 0.68rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

h1 {
  margin: 0;
  font-size: 1.25rem;
}

.form-card {
  border: 1px solid var(--border-color, #e5e7eb);
  border-radius: 12px;
  background: var(--surface-color, #fff);
  padding: 1.25rem;
  max-width: 720px;
}

.vehicle-summary {
  margin-bottom: 1.25rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid var(--border-color, #e5e7eb);
}

.vehicle-code {
  display: inline-flex;
  margin-bottom: 0.35rem;
  font-size: 0.75rem;
  font-weight: 700;
}

.vehicle-summary h2 {
  margin: 0;
  font-size: 1.1rem;
}

.vehicle-summary p {
  margin: 0.25rem 0 0;
  color: var(--text-muted, #6b7280);
  font-size: 0.8rem;
}

.form-error {
  margin-bottom: 1rem;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  background: #fee2e2;
  color: #991b1b;
}

.loading-state {
  padding: 3rem;
  text-align: center;
  color: var(--text-muted, #6b7280);
}
</style>
