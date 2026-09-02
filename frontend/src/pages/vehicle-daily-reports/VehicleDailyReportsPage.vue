<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'

import { useVehicleDailyReportStore } from '@/stores/vehicleDailyReportStore'
import { useToast } from '@/composables/useToast'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'

const route = useRoute()
const router = useRouter()
const store = useVehicleDailyReportStore()
const { show: showToast } = useToast()

const contractVehicleId = computed(
  () => route.params.id
)

const deletingReportId = ref(null)
const showDeleteDialog = ref(false)
const deleteLoading = ref(false)

const load = async () => {
  const id = contractVehicleId.value

  try {
    await Promise.all([
      store.fetchContractVehicle(id),
      store.fetchReports(id),
    ])
  } catch {
    // errors are stored in store.error
  }
}

const formatMinutes = (minutes) => {
  const total = Number(minutes || 0)

  if (!total) {
    return '0m'
  }

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

const formatMoney = (value) => {
  return Number(value || 0).toLocaleString(
    'en-PK',
    {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    }
  )
}

const createReport = () => {
  router.push({
    name: 'contract-vehicles.daily-reports.create',
    params: { id: contractVehicleId.value },
  })
}

const editReport = (report) => {
  router.push({
    name: 'contract-vehicles.daily-reports.edit',
    params: {
      id: contractVehicleId.value,
      reportId: report.id,
    },
  })
}

const confirmDelete = (report) => {
  deletingReportId.value = report.id
  showDeleteDialog.value = true
}

const deleteReport = async () => {
  deleteLoading.value = true

  try {
    await store.removeReport(
      contractVehicleId.value,
      deletingReportId.value
    )

    showToast('Daily report deleted')
  } catch (err) {
    showToast(
      err.response?.data?.message ||
        'Unable to delete daily report.',
      'error'
    )
  } finally {
    deleteLoading.value = false
    showDeleteDialog.value = false
    deletingReportId.value = null
  }
}

onMounted(load)
</script>

<template>
  <div class="daily-reports-page">

    <header class="page-head">

      <div>
        <p class="eyebrow">
          Operations / Vehicle Reporting
        </p>

        <h1>
          Vehicle Daily Reporting
        </h1>

        <p>
          Track daily working hours, mileage,
          fuel and overtime.
        </p>
      </div>

      <div class="page-actions">

        <RouterLink
          class="btn secondary"
          :to="{
            name: 'vehicle-daily-reports.index'
          }"
        >
          Back
        </RouterLink>

        <button
          class="btn primary"
          type="button"
          @click="createReport"
        >
          + Add Daily Report
        </button>

      </div>

    </header>

    <!-- Error -->

    <div
      v-if="store.error"
      class="form-error"
    >
      {{ store.error }}
    </div>

    <!-- Loading -->

    <div
      v-if="store.loading"
      class="loading-state"
    >
      Loading daily reports...
    </div>

    <template v-else>

      <!-- Vehicle information -->

      <section
        v-if="store.contractVehicle"
        class="vehicle-summary"
      >

        <div class="vehicle-main">

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

        <div class="vehicle-rules">

          <div>
            <span>Normal Duty</span>
            <strong>
              {{ store.contractVehicle.duty_hours_per_day }}h/day
            </strong>
          </div>

          <div>
            <span>Duty Days</span>
            <strong>
              {{ store.contractVehicle.duty_days_per_week }}/week
            </strong>
          </div>

          <div>
            <span>OT Rate</span>
            <strong>
              PKR
              {{ formatMoney(
                store.contractVehicle.overtime_rate
              ) }}
              /hr
            </strong>
          </div>

          <div>
            <span>Mileage Limit</span>
            <strong>
              {{ store.contractVehicle.monthly_mileage_limit }}
              KM/month
            </strong>
          </div>

        </div>

      </section>

      <!-- Reports -->

      <section class="reports-card">

        <div class="reports-card-head">

          <div>
            <h2>Daily Reports</h2>

            <p>
              {{ store.reports.length }}
              reporting entries
            </p>
          </div>

        </div>

        <div
          v-if="!store.reports.length"
          class="empty-state"
        >
          <h3>No daily reports yet</h3>

          <p>
            Start recording the daily activity
            of this rented vehicle.
          </p>

          <button
            class="btn primary"
            type="button"
            @click="createReport"
          >
            + Add Daily Report
          </button>
        </div>

        <div
          v-else
          class="table-wrapper"
        >

          <table class="reports-table">

            <thead>
              <tr>
                <th>Date</th>
                <th>Time In</th>
                <th>Time Out</th>
                <th>Meter In</th>
                <th>Meter Out</th>
                <th>Running</th>
                <th>OT</th>
                <th>OT Amount</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>

            <tbody>

              <tr
                v-for="report in store.reports"
                :key="report.id"
              >

                <td>
                  {{ report.report_date }}
                </td>

                <td>
                  {{ report.time_in || '—' }}
                </td>

                <td>
                  {{ report.time_out || '—' }}
                </td>

                <td>
                  {{ report.meter_in ?? '—' }}
                </td>

                <td>
                  {{ report.meter_out ?? '—' }}
                </td>

                <td>
                  {{ report.total_running || 0 }}
                  KM
                </td>

                <td>
                  {{
                    formatMinutes(
                      report.overtime_minutes
                    )
                  }}
                </td>

                <td>
                  PKR
                  {{
                    formatMoney(
                      report.overtime_amount
                    )
                  }}
                </td>

                <td>
                  <span
                    class="status"
                    :class="`status-${report.status}`"
                  >
                    {{ report.status }}
                  </span>
                </td>

                <td>
                  <div class="row-actions">
                    <button
                      type="button"
                      class="btn secondary"
                      @click="editReport(report)"
                    >
                      Edit
                    </button>

                    <button
                      type="button"
                      class="btn danger"
                      @click="confirmDelete(report)"
                    >
                      Delete
                    </button>
                  </div>
                </td>

              </tr>

            </tbody>

          </table>

        </div>

      </section>

    </template>

    <ConfirmDialog
      :open="showDeleteDialog"
      title="Delete Daily Report"
      message="Are you sure you want to delete this daily report? This action cannot be undone."
      variant="danger"
      confirm-label="Delete"
      :loading="deleteLoading"
      @confirm="deleteReport"
      @cancel="showDeleteDialog = false"
    />

  </div>
</template>

<style scoped>
.daily-reports-page {
  min-width: 0;
}

.page-head {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
  margin-bottom: 1.25rem;
}

.page-actions {
  display: flex;
  gap: 0.6rem;
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

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 0;
  border-radius: 8px;
  padding: 0.6rem 0.85rem;
  font: inherit;
  font-size: 0.8rem;
  font-weight: 600;
  text-decoration: none;
  cursor: pointer;
}

.btn.primary {
  background: var(--primary, #111827);
  color: white;
}

.btn.secondary {
  background: var(--surface-muted, #f3f4f6);
  color: var(--text, #111827);
}

.btn.danger {
  background: #fee2e2;
  color: #991b1b;
}

.vehicle-summary {
  display: flex;
  justify-content: space-between;
  gap: 1.5rem;
  padding: 1rem;
  margin-bottom: 1rem;
  border: 1px solid var(--border-color, #e5e7eb);
  border-radius: 12px;
  background: #fff;
}

.vehicle-code {
  display: inline-flex;
  margin-bottom: 0.35rem;
  font-size: 0.75rem;
  font-weight: 700;
}

.vehicle-main h2 {
  margin: 0;
  font-size: 1.1rem;
}

.vehicle-main p {
  margin: 0.25rem 0 0;
  color: #6b7280;
}

.vehicle-rules {
  display: grid;
  grid-template-columns: repeat(4, minmax(110px, 1fr));
  gap: 0.75rem;
}

.vehicle-rules div {
  padding: 0.65rem 0.75rem;
  border-radius: 8px;
  background: #f8fafc;
}

.vehicle-rules span {
  display: block;
  margin-bottom: 0.25rem;
  font-size: 0.7rem;
  color: #6b7280;
}

.vehicle-rules strong {
  font-size: 0.82rem;
}

.reports-card {
  overflow: hidden;
  border: 1px solid var(--border-color, #e5e7eb);
  border-radius: 12px;
  background: #fff;
}

.reports-card-head {
  padding: 1rem;
  border-bottom: 1px solid #e5e7eb;
}

.reports-card-head h2 {
  margin: 0;
  font-size: 1rem;
}

.reports-card-head p {
  margin: 0.2rem 0 0;
  font-size: 0.8rem;
  color: #6b7280;
}

.table-wrapper {
  overflow-x: auto;
}

.reports-table {
  width: 100%;
  border-collapse: collapse;
  min-width: 900px;
}

.reports-table th,
.reports-table td {
  padding: 0.7rem 0.8rem;
  text-align: left;
  border-bottom: 1px solid #f0f0f0;
  white-space: nowrap;
  font-size: 0.8rem;
}

.reports-table th {
  font-size: 0.7rem;
  font-weight: 700;
  color: #6b7280;
  background: #fafafa;
}

.row-actions {
  display: flex;
  gap: 0.4rem;
}

.status {
  display: inline-flex;
  padding: 0.25rem 0.5rem;
  border-radius: 999px;
  font-size: 0.68rem;
  font-weight: 700;
  text-transform: capitalize;
}

.status-draft {
  background: #fef3c7;
  color: #92400e;
}

.status-approved {
  background: #dcfce7;
  color: #166534;
}

.status-rejected {
  background: #fee2e2;
  color: #991b1b;
}

.empty-state {
  padding: 3rem 1rem;
  text-align: center;
}

.empty-state h3 {
  margin: 0;
}

.empty-state p {
  margin: 0.4rem 0 1rem;
  color: #6b7280;
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
  color: #6b7280;
}

@media (max-width: 1000px) {
  .vehicle-summary {
    flex-direction: column;
  }

  .vehicle-rules {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 700px) {
  .page-head {
    flex-direction: column;
  }

  .page-actions {
    width: 100%;
  }

  .page-actions .btn {
    flex: 1;
  }

  .vehicle-rules {
    grid-template-columns: 1fr 1fr;
  }
}
</style>
