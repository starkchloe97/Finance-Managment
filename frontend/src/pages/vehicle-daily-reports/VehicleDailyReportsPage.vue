<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'

import { useVehicleDailyReportStore } from '@/stores/vehicleDailyReportStore'
import { useToast } from '@/composables/useToast'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
import InfoTip from '@/components/ui/InfoTip.vue'

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

/* ---- added: display helpers only (no existing logic changed) ---- */

const vehicle = computed(() => store.contractVehicle)

const vehicleLabel = computed(() =>
  [vehicle.value?.make, vehicle.value?.model].filter(Boolean).join(' ') || '—'
)

const monogram = computed(() =>
  (vehicle.value?.vehicle_number || vehicle.value?.make || '·')
    .trim()
    .slice(0, 2)
    .toUpperCase()
)

const fmtDate = (value) => {
  if (!value) return '—'
  return new Date(value).toLocaleDateString('en-GB', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  })
}

const totals = computed(() => {
  const reports = store.reports || []
  const sum = (pick) =>
    reports.reduce((total, report) => total + Number(pick(report) || 0), 0)

  return {
    reports: reports.length,
    running: sum((r) => r.total_running),
    overtimeMinutes: sum((r) => r.overtime_minutes),
    overtimeAmount: sum((r) => r.overtime_amount),
  }
})

const reportStatusClass = (status) => {
  const key = String(status || '').toLowerCase()
  if (key === 'approved') return 'status-completed'
  if (key === 'rejected') return 'status-danger'
  return 'status-draft'
}

onMounted(load)
</script>

<template>
  <div class="reports-detail">
    <!-- Error -->
    <div v-if="store.error && !vehicle" class="card detail-error" role="alert">
      <div>
        <strong>Couldn't load this vehicle's reports.</strong>
        <p>{{ store.error }}</p>
      </div>
      <button type="button" class="btn" @click="load">Try again</button>
    </div>

    <!-- Contextual error (rows already visible) -->
    <div v-else-if="store.error" class="error-banner" role="alert">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 20h16a2 2 0 0 0 1.73-2Z" /><path d="M12 9v4" /><path d="M12 17h.01" />
      </svg>
      <span>{{ store.error }}</span>
      <button type="button" class="banner-retry" @click="load">Retry</button>
    </div>

    <!-- Skeleton -->
    <div v-else-if="store.loading && !vehicle" class="detail-skeleton" aria-hidden="true">
      <div class="sk" style="height: 200px"></div>
      <div class="sk" style="height: 300px"></div>
    </div>

    <template v-else-if="vehicle">
      <!-- ============ HERO: vehicle + contract rules ============ -->
      <header class="card hero-card">
        <div class="hero-top">
          <div class="hero-id">
            <span class="hero-monogram" aria-hidden="true">{{ monogram }}</span>
            <div class="hero-copy">
              <span class="section-kicker">Operations / Vehicle reporting</span>
              <div class="hero-title-row">
                <h1>{{ vehicle.vehicle_number || 'Unassigned vehicle' }}</h1>
                <span class="status status-success">Active</span>
              </div>
              <p class="hero-sub">
                <span>{{ vehicleLabel }}</span>
                <span v-if="vehicle.vehicle_type" class="hero-sep" aria-hidden="true">·</span>
                <span>{{ vehicle.vehicle_type }}</span>
              </p>
            </div>
          </div>

          <div class="hero-actions">
            <RouterLink class="btn-light" :to="{ name: 'vehicle-daily-reports.index' }">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M19 12H5" /><path d="m12 19-7-7 7-7" />
              </svg>
              Back
            </RouterLink>
            <button class="btn" type="button" @click="createReport">
              + Add daily report
            </button>
          </div>
        </div>

        <!-- The rules every day is judged against -->
        <div class="hero-stats">
          <div class="hero-stat">
            <span>
              Normal duty
              <InfoTip label="Hours per day covered by the base rental. Hours beyond this count as overtime." />
            </span>
            <strong>{{ vehicle.duty_hours_per_day ?? '—' }}h / day</strong>
          </div>
          <div class="hero-stat">
            <span>
              Duty days
              <InfoTip label="Working days per week included in the rental." />
            </span>
            <strong>{{ vehicle.duty_days_per_week ?? '—' }} / week</strong>
          </div>
          <div class="hero-stat">
            <span>
              Overtime rate
              <InfoTip label="Charged per driver-hour worked beyond the daily duty hours." />
            </span>
            <strong>PKR {{ formatMoney(vehicle.overtime_rate) }} / hr</strong>
          </div>
          <div class="hero-stat">
            <span>
              Mileage limit
              <InfoTip label="Kilometres included per month. Beyond the limit, the excess rate is charged." />
            </span>
            <strong>{{ vehicle.monthly_mileage_limit ?? '—' }} KM / month</strong>
          </div>
        </div>
      </header>

      <!-- ============ Totals strip ============ -->
      <section v-if="totals.reports" class="card sum-card" aria-label="Totals">
        <div class="sum-stat">
          <span>
            Daily reports
            <InfoTip label="Reporting entries recorded for this vehicle." />
          </span>
          <strong>{{ totals.reports }}</strong>
        </div>
        <div class="sum-stat">
          <span>
            Total mileage
            <InfoTip label="Kilometres driven across all recorded days." />
          </span>
          <strong>{{ totals.running }} KM</strong>
        </div>
        <div class="sum-stat">
          <span>
            Total overtime
            <InfoTip label="Driver hours worked beyond daily duty hours, across all recorded days." />
          </span>
          <strong :class="{ 'is-amber': totals.overtimeMinutes > 0 }">
            {{ formatMinutes(totals.overtimeMinutes) }}
          </strong>
        </div>
        <div class="sum-stat" :class="{ 'is-amber': totals.overtimeAmount > 0 }">
          <span>
            Overtime charges
            <InfoTip label="Overtime hours billed at the contract rate — on top of the monthly rental." />
          </span>
          <strong>PKR {{ formatMoney(totals.overtimeAmount) }}</strong>
        </div>
      </section>

      <!-- ============ Reports table ============ -->
      <section v-if="totals.reports || store.loading" class="card table-card" :aria-busy="store.loading">
        <header class="block-head">
          <div class="block-title">
            <span class="block-icon icon-info" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect width="18" height="18" x="3" y="4" rx="2" /><path d="M16 2v4" /><path d="M8 2v4" /><path d="M3 10h18" />
              </svg>
            </span>
            <div>
              <h2>Daily reports</h2>
              <p class="block-hint">One entry per working day — newest data as loaded.</p>
            </div>
          </div>
        </header>

        <div class="table-wrap">
          <table>
            <caption class="sr-only">Daily reports for {{ vehicle.vehicle_number }}</caption>

            <thead>
              <tr>
                <th>Date</th>
                <th>Time in</th>
                <th>Time out</th>
                <th class="right">
                  Meter in
                  <InfoTip label="Odometer reading at the start of the day." />
                </th>
                <th class="right">
                  Meter out
                  <InfoTip label="Odometer reading at the end of the day." />
                </th>
                <th class="right">
                  Running
                  <InfoTip label="Meter out minus meter in — kilometres driven that day." />
                </th>
                <th class="right">
                  Overtime
                  <InfoTip label="Hours worked beyond the daily duty hours." />
                </th>
                <th class="right">
                  OT amount
                  <InfoTip label="Overtime hours billed at the contract rate." />
                </th>
                <th>Status</th>
                <th class="right"></th>
              </tr>
            </thead>

            <tbody v-if="totals.reports">
              <tr v-for="report in store.reports" :key="report.id">
                <td class="row-date">{{ fmtDate(report.report_date) }}</td>
                <td class="row-time">{{ report.time_in || '—' }}</td>
                <td class="row-time">{{ report.time_out || '—' }}</td>
                <td class="right row-meter">{{ report.meter_in ?? '—' }}</td>
                <td class="right row-meter">{{ report.meter_out ?? '—' }}</td>
                <td class="right row-amount">
                  {{ report.total_running || 0 }}
                  <span class="unit">KM</span>
                </td>
                <td class="right" :class="Number(report.overtime_minutes || 0) > 0 ? 'row-ot' : 'row-muted'">
                  {{ formatMinutes(report.overtime_minutes) }}
                </td>
                <td class="right" :class="Number(report.overtime_amount || 0) > 0 ? 'row-ot-amount' : 'row-muted'">
                  PKR {{ formatMoney(report.overtime_amount) }}
                </td>
                <td>
                  <span class="status-row">
                    <span class="status" :class="reportStatusClass(report.status)">
                      {{ report.status }}
                    </span>
                    <InfoTip
                      :label="
                        String(report.status || '').toLowerCase() === 'approved'
                          ? 'Approved — counted in billing.'
                          : String(report.status || '').toLowerCase() === 'rejected'
                            ? 'Rejected — correct or delete this entry.'
                            : 'Recorded but not yet approved.'
                      "
                    />
                  </span>
                </td>
                <td class="right">
                  <div class="row-actions">
                    <button
                      class="icon-action"
                      type="button"
                      title="Edit daily report"
                      aria-label="Edit daily report"
                      @click="editReport(report)"
                    >
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M17 3a2.83 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z" />
                      </svg>
                    </button>
                    <button
                      class="icon-action danger"
                      type="button"
                      title="Delete daily report"
                      aria-label="Delete daily report"
                      @click="confirmDelete(report)"
                    >
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M3 6h18" /><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" /><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>

            <!-- Skeleton rows -->
            <tbody v-else aria-hidden="true">
              <tr v-for="i in 7" :key="`sk-${i}`">
                <td><span class="skeleton w-60"></span></td>
                <td><span class="skeleton w-40"></span></td>
                <td><span class="skeleton w-40"></span></td>
                <td class="right"><span class="skeleton w-50"></span></td>
                <td class="right"><span class="skeleton w-50"></span></td>
                <td class="right"><span class="skeleton w-40"></span></td>
                <td class="right"><span class="skeleton w-40"></span></td>
                <td class="right"><span class="skeleton w-60"></span></td>
                <td><span class="skeleton skel-badge"></span></td>
                <td><span class="skeleton w-70"></span></td>
              </tr>
            </tbody>

            <tfoot v-if="totals.reports">
              <tr class="grand-total">
                <td colspan="5">Totals</td>
                <td class="right">{{ totals.running }} KM</td>
                <td class="right">{{ formatMinutes(totals.overtimeMinutes) }}</td>
                <td class="right">PKR {{ formatMoney(totals.overtimeAmount) }}</td>
                <td colspan="2"></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </section>

      <!-- Empty -->
      <section v-else class="card empty-state">
        <div class="empty-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <rect width="18" height="18" x="3" y="4" rx="2" /><path d="M16 2v4" /><path d="M8 2v4" /><path d="M3 10h18" /><path d="M9 16h6" />
          </svg>
        </div>
        <h2>No daily reports yet</h2>
        <p>Start recording the daily activity of this rented vehicle.</p>
        <button class="btn" type="button" @click="createReport">+ Add daily report</button>
      </section>
    </template>

    <!-- Delete confirmation (unchanged) -->
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
.reports-detail {
  display: flex;
  flex-direction: column;
  gap: 20px;
  min-width: 0;
}

/* Flex gap owns spacing — neutralize global .card margin */
.reports-detail > .card {
  margin-bottom: 0;
}

/* ---------- Hero ---------- */

.hero-card { padding: 24px; }

.hero-top {
  align-items: flex-start;
  display: flex;
  gap: 16px;
  justify-content: space-between;
}

.hero-id { align-items: center; display: flex; gap: 16px; min-width: 0; }

.hero-monogram {
  align-items: center;
  background: var(--accent-soft);
  border-radius: 12px;
  color: var(--accent);
  display: inline-flex;
  flex: 0 0 48px;
  font-size: 16px;
  font-weight: 700;
  height: 48px;
  justify-content: center;
  letter-spacing: 0.02em;
  width: 48px;
}

.hero-copy { min-width: 0; }
.hero-copy .section-kicker { margin-bottom: 4px; }

.hero-title-row {
  align-items: center;
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}
.hero-title-row h1 {
  font-size: 22px;
  font-weight: 700;
  letter-spacing: -0.02em;
  margin: 0;
}

.hero-sub {
  align-items: center;
  color: var(--text-secondary);
  display: flex;
  flex-wrap: wrap;
  font-size: 14px;
  gap: 8px;
  margin: 5px 0 0;
}
.hero-sep { color: var(--text-muted); }

.hero-actions {
  align-items: center;
  display: flex;
  flex: 0 0 auto;
  gap: 10px;
}

.hero-stats {
  border-top: 1px solid var(--border);
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  margin-top: 20px;
  padding-top: 20px;
}

.hero-stat {
  border-left: 1px solid var(--border);
  display: flex;
  flex-direction: column;
  gap: 3px;
  min-width: 0;
  padding: 0 16px;
}
.hero-stat:first-child { border-left: 0; padding-left: 0; }
.hero-stat > span {
  align-items: center;
  color: var(--text-muted);
  display: flex;
  font-size: 12px;
  font-weight: 500;
  gap: 5px;
}
.hero-stat strong {
  color: var(--text-primary);
  font-size: 18px;
  font-variant-numeric: tabular-nums;
  font-weight: 700;
  letter-spacing: -0.01em;
  white-space: nowrap;
}

/* ---------- Totals strip ---------- */

.sum-card {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  padding: 16px 20px;
}

.sum-stat {
  border-left: 1px solid var(--border);
  display: flex;
  flex-direction: column;
  gap: 3px;
  min-width: 0;
  padding: 0 20px;
}
.sum-stat:first-child { border-left: 0; padding-left: 0; }

.sum-stat > span {
  align-items: center;
  color: var(--text-muted);
  display: flex;
  font-size: 12px;
  font-weight: 500;
  gap: 5px;
}

.sum-stat strong {
  color: var(--text-primary);
  font-size: 19px;
  font-variant-numeric: tabular-nums;
  font-weight: 700;
}
.sum-stat strong.is-amber,
.sum-stat.is-amber strong { color: var(--warning); }

/* ---------- Reports table ---------- */

.table-card {
  overflow: hidden;
  padding: 0;
}

.block-head {
  align-items: flex-start;
  border-bottom: 1px solid var(--border);
  display: flex;
  gap: 12px;
  justify-content: space-between;
  padding: 16px 20px;
}

.block-title { align-items: flex-start; display: flex; gap: 12px; }
.block-title h2 { font-size: 15px; font-weight: 600; margin: 0; }
.block-hint { color: var(--text-muted); font-size: 13px; margin: 2px 0 0; }

.block-icon {
  align-items: center;
  background: var(--info-soft);
  border-radius: 9px;
  color: var(--info);
  display: flex;
  flex: 0 0 32px;
  height: 32px;
  justify-content: center;
  width: 32px;
}
.block-icon svg { height: 15px; width: 15px; }

.table-card .table-wrap { padding: 0 0 4px; }
.table-card table { min-width: 980px; }
.table-card th,
.table-card td { white-space: nowrap; }

.row-date { color: var(--text-primary); font-weight: 600; }
.row-time { color: var(--text-secondary); }
.row-meter {
  color: var(--text-secondary);
  font-family: ui-monospace, SFMono-Regular, 'SF Mono', Menlo, Consolas, monospace;
  font-size: 13px;
}
.row-amount { color: var(--text-primary); font-weight: 600; }
.row-ot { color: var(--text-primary); font-weight: 600; }
.row-ot-amount { color: var(--warning); font-weight: 700; }
.row-muted { color: var(--text-muted); }

.unit {
  color: var(--text-muted);
  font-size: 10px;
  font-weight: 600;
  margin-left: 3px;
}

.status-row {
  align-items: center;
  display: inline-flex;
  gap: 5px;
}

.row-actions { display: inline-flex; gap: 4px; justify-content: flex-end; }

.icon-action {
  align-items: center;
  background: transparent;
  border: 1px solid transparent;
  border-radius: 8px;
  color: var(--text-muted);
  cursor: pointer;
  display: inline-flex;
  height: 32px;
  justify-content: center;
  transition: background 0.15s ease, color 0.15s ease;
  width: 32px;
}
.icon-action svg { height: 15px; width: 15px; }
.icon-action:hover { background: var(--accent-soft); color: var(--accent); }
.icon-action.danger:hover { background: var(--danger-soft); color: var(--danger); }

/* ---------- Error states ---------- */

.detail-error {
  align-items: center;
  border-color: var(--danger);
  color: var(--danger);
  display: flex;
  gap: 16px;
  justify-content: space-between;
}
.detail-error p { color: var(--text-secondary); margin: 4px 0 0; }

.error-banner {
  align-items: center;
  background: var(--danger-soft);
  border: 1px solid var(--danger);
  border-radius: var(--radius-md);
  color: var(--danger);
  display: flex;
  font-size: 13px;
  font-weight: 500;
  gap: 10px;
  padding: 10px 14px;
}
.error-banner svg { flex: 0 0 16px; height: 16px; width: 16px; }

.banner-retry {
  background: transparent;
  border: 1px solid currentColor;
  border-radius: 7px;
  color: inherit;
  cursor: pointer;
  font-size: 12px;
  font-weight: 600;
  margin-left: auto;
  padding: 4px 10px;
}
.banner-retry:hover { opacity: 0.8; }

/* ---------- Empty state ---------- */

.empty-state {
  align-items: center;
  display: flex;
  flex-direction: column;
  padding: 56px 24px;
  text-align: center;
}

.empty-icon {
  align-items: center;
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: 18px;
  color: var(--text-muted);
  display: flex;
  height: 64px;
  justify-content: center;
  margin-bottom: 14px;
  width: 64px;
}
.empty-icon svg { height: 28px; width: 28px; }

.empty-state h2 { font-size: 17px; margin: 0 0 8px; }
.empty-state p {
  color: var(--text-muted);
  font-size: 13.5px;
  margin: 0 0 20px;
  max-width: 380px;
}

/* ---------- Skeletons ---------- */

.detail-skeleton { display: flex; flex-direction: column; gap: 20px; }

.sk {
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  overflow: hidden;
  position: relative;
}
.sk::after {
  animation: shimmer 1.6s infinite;
  background: linear-gradient(90deg, transparent, rgb(255 255 255 / 70%), transparent);
  content: '';
  inset: 0;
  position: absolute;
  transform: translateX(-100%);
}
@keyframes shimmer { to { transform: translateX(100%); } }

.skeleton {
  background: var(--surface-2);
  border-radius: 6px;
  display: block;
  height: 11px;
  overflow: hidden;
  position: relative;
}
.skeleton::after {
  animation: shimmer 1.4s infinite;
  background: linear-gradient(90deg, transparent, rgb(255 255 255 / 70%), transparent);
  content: '';
  inset: 0;
  position: absolute;
  transform: translateX(-100%);
}
.skel-badge { border-radius: 999px; height: 20px; width: 78px; }
.w-40 { width: 40%; }
.w-50 { width: 50%; }
.w-60 { width: 60%; }
.w-70 { width: 70%; }

/* ---------- Responsive ---------- */

@media (max-width: 1024px) {
  .hero-stats,
  .sum-card { grid-template-columns: repeat(2, minmax(0, 1fr)); row-gap: 16px; }
  .hero-stat:nth-child(odd),
  .sum-stat:nth-child(odd) { border-left: 0; padding-left: 0; }
}

@media (max-width: 700px) {
  .hero-top { flex-direction: column; }
  .hero-actions { width: 100%; }
  .hero-actions .btn,
  .hero-actions .btn-light { flex: 1; justify-content: center; }
  .detail-error { align-items: flex-start; flex-direction: column; }
}
</style>