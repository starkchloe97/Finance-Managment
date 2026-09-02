<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'

import {
  getMonthlySummary,
} from '@/services/vehicleDailyReportService'

import {
  getVehicleContracts,
} from '@/services/vehicleContractService'

const router = useRouter()

const contracts = ref([])

const selectedContractId = ref('')
const selectedMonth = ref(
  new Date().toISOString().slice(0, 7)
)

const loading = ref(false)
const error = ref('')

const vehicleData = ref([])

const selectedContract = computed(() => {
  return contracts.value.find(
    contract =>
      String(contract.id) ===
      String(selectedContractId.value)
  )
})

const loadContracts = async () => {
  try {
    const response = await getVehicleContracts()

    contracts.value =
      response.data?.data ??
      response.data ??
      []

  } catch (err) {
    error.value =
      err.response?.data?.message ||
      'Unable to load contracts.'
  }
}

const loadVehicleSummaries = async () => {
  if (!selectedContract.value) {
    vehicleData.value = []
    return
  }

  loading.value = true
  error.value = ''

  try {
    const vehicles =
      selectedContract.value.vehicles || []

    const results = await Promise.all(
      vehicles.map(async vehicle => {
        const response =
          await getMonthlySummary(
            vehicle.id,
            selectedMonth.value
          )

        return {
          vehicle,
          summary:
            response.data?.data ??
            response.data ??
            {},
        }
      })
    )

    vehicleData.value = results

  } catch (err) {
    error.value =
      err.response?.data?.message ||
      'Unable to load vehicle summaries.'
  } finally {
    loading.value = false
  }
}

const openReports = (vehicle) => {
  router.push({
    name: 'contract-vehicles.daily-reports',
    params: { id: vehicle.id },
  })
}

const addReport = (vehicle) => {
  router.push({
    name: 'contract-vehicles.daily-reports.create',
    params: { id: vehicle.id },
  })
}

const formatNumber = value => {
  return Number(value || 0).toLocaleString(
    'en-PK'
  )
}

const formatMinutes = minutes => {
  const total = Number(minutes || 0)

  const hours = Math.floor(total / 60)
  const remainingMinutes = total % 60

  if (!hours) {
    return `${remainingMinutes}m`
  }

  if (!remainingMinutes) {
    return `${hours}h`
  }

  return `${hours}h ${remainingMinutes}m`
}

onMounted(async () => {
  await loadContracts()

  if (contracts.value.length) {
    selectedContractId.value =
      contracts.value[0].id
  }
})

watch(
  [selectedContractId, selectedMonth],
  () => {
    loadVehicleSummaries()
  }
)
</script>

<template>
  <div class="vehicle-daily-reports-page">

    <header class="page-head">
      <div>
        <p class="eyebrow">
          Operations / Vehicle Reporting
        </p>

        <h1>Vehicle Daily Reporting</h1>

        <p>
          Track daily usage, overtime and mileage
          for rented vehicles.
        </p>
      </div>
    </header>

    <section class="report-filters">

      <div class="filter-field">
        <label for="contract">
          Contract
        </label>

        <select
          id="contract"
          v-model="selectedContractId"
        >
          <option value="">
            Select contract
          </option>

          <option
            v-for="contract in contracts"
            :key="contract.id"
            :value="contract.id"
          >
            {{ contract.customer_name }}
          </option>
        </select>
      </div>

      <div class="filter-field">
        <label for="month">
          Reporting Month
        </label>

        <input
          id="month"
          v-model="selectedMonth"
          type="month"
        />
      </div>

    </section>

    <div
      v-if="error"
      class="form-error"
    >
      {{ error }}
    </div>

    <div
      v-if="loading"
      class="loading-state"
    >
      Loading vehicle reports...
    </div>

    <section
      v-else-if="vehicleData.length"
      class="vehicle-grid"
    >

      <article
        v-for="item in vehicleData"
        :key="item.vehicle.id"
        class="vehicle-card"
      >

        <div class="vehicle-card-head">
          <div>
            <h2>
              {{ item.vehicle.vehicle_number }}
            </h2>

            <p>
              {{ item.vehicle.make }}
              {{ item.vehicle.model }}
            </p>
          </div>

          <span class="status-badge">
            {{ item.vehicle.status || 'Active' }}
          </span>
        </div>

        <div class="vehicle-type">
          {{ item.vehicle.vehicle_type }}
        </div>

        <div class="summary-grid">

          <div>
            <span>Reports</span>
            <strong>
              {{ item.summary.report_count ?? 0 }}
            </strong>
          </div>

          <div>
            <span>Running</span>
            <strong>
              {{ formatNumber(
                item.summary.total_running
              ) }}
              KM
            </strong>
          </div>

          <div>
            <span>Mileage Limit</span>
            <strong>
              {{ formatNumber(
                item.summary.monthly_mileage_limit
              ) }}
              KM
            </strong>
          </div>

          <div>
            <span>Excess</span>
            <strong>
              {{ formatNumber(
                item.summary.excess_mileage
              ) }}
              KM
            </strong>
          </div>

          <div>
            <span>Overtime</span>
            <strong>
              {{ formatMinutes(
                item.summary.total_overtime_minutes
              ) }}
            </strong>
          </div>

          <div>
            <span>OT Amount</span>
            <strong>
              PKR
              {{ formatNumber(
                item.summary.total_overtime_amount
              ) }}
            </strong>
          </div>

          <div>
            <span>Excess KM Amount</span>
            <strong>
              PKR
              {{ formatNumber(
                item.summary.excess_mileage_amount
              ) }}
            </strong>
          </div>

        </div>

        <div class="vehicle-actions">

          <button
            type="button"
            class="btn secondary"
            @click="openReports(item.vehicle)"
          >
            View Daily Reports
          </button>

          <button
            type="button"
            class="btn primary"
            @click="addReport(item.vehicle)"
          >
            + Add Report
          </button>

        </div>

      </article>

    </section>

    <div
      v-else-if="selectedContract"
      class="empty-state"
    >
      No contract vehicles found.
    </div>

  </div>
</template>

<style scoped>
.vehicle-daily-reports-page {
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

.report-filters {
  display: flex;
  gap: 1rem;
  margin-bottom: 1rem;
}

.filter-field {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  min-width: 220px;
}

.filter-field label {
  font-size: 0.72rem;
  font-weight: 600;
  color: var(--text-muted, #6b7280);
}

.filter-field select,
.filter-field input {
  padding: 0.55rem 0.7rem;
  border: 1px solid var(--border-color, #e5e7eb);
  border-radius: 8px;
  font: inherit;
  font-size: 0.85rem;
  background: var(--surface-color, #fff);
}

.vehicle-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 1rem;
}

.vehicle-card {
  border: 1px solid var(--border-color, #e5e7eb);
  border-radius: 12px;
  background: var(--surface-color, #fff);
  padding: 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.8rem;
}

.vehicle-card-head {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 0.75rem;
}

.vehicle-card-head h2 {
  margin: 0;
  font-size: 1rem;
}

.vehicle-card-head p {
  margin: 0.2rem 0 0;
  color: var(--text-muted, #6b7280);
  font-size: 0.78rem;
}

.status-badge {
  display: inline-flex;
  padding: 0.25rem 0.5rem;
  border-radius: 999px;
  background: var(--surface-muted, #f3f4f6);
  font-size: 0.68rem;
  font-weight: 700;
  text-transform: capitalize;
}

.vehicle-type {
  color: var(--text-muted, #6b7280);
  font-size: 0.78rem;
}

.summary-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.6rem;
}

.summary-grid div {
  padding: 0.6rem;
  border-radius: 8px;
  background: var(--surface-muted, #f9fafb);
}

.summary-grid span {
  display: block;
  margin-bottom: 0.2rem;
  color: var(--text-muted, #6b7280);
  font-size: 0.68rem;
}

.summary-grid strong {
  font-size: 0.82rem;
}

.vehicle-actions {
  display: flex;
  gap: 0.5rem;
  margin-top: auto;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 0;
  border-radius: 8px;
  padding: 0.55rem 0.75rem;
  font: inherit;
  font-size: 0.78rem;
  font-weight: 600;
  text-decoration: none;
  cursor: pointer;
  flex: 1;
}

.btn.primary {
  background: var(--primary, #111827);
  color: white;
}

.btn.secondary {
  background: var(--surface-muted, #f3f4f6);
  color: var(--text, #111827);
}

.form-error {
  margin-bottom: 1rem;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  background: #fee2e2;
  color: #991b1b;
}

.loading-state,
.empty-state {
  padding: 3rem;
  text-align: center;
  color: var(--text-muted, #6b7280);
}

@media (max-width: 700px) {
  .report-filters {
    flex-direction: column;
  }

  .vehicle-actions {
    flex-direction: column;
  }
}
</style>
