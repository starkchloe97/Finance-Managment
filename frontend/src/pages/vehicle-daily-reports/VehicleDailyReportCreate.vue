<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'

import { useVehicleDailyReportStore } from '@/stores/vehicleDailyReportStore'
import { useToast } from '@/composables/useToast'
import VehicleDailyReportForm from '@/components/vehicle-daily-reports/VehicleDailyReportForm.vue'
import InfoTip from '@/components/ui/InfoTip.vue'

const route = useRoute()
const router = useRouter()
const store = useVehicleDailyReportStore()
const { show: showToast } = useToast()

const contractVehicleId = route.params.id

const submitting = ref(false)
const errors = ref({})
const generalError = ref('')

const loadVehicle = async () => {
  generalError.value = ''
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

const rules = computed(() => {
  const v = vehicle.value || {}
  return {
    dutyHours: v.duty_hours_per_day ?? null,
    otRate: v.overtime_rate ?? null,
    mileageLimit: v.monthly_mileage_limit ?? null,
    excessRate: v.excess_mileage_rate ?? null,
    holidayRate: v.public_holiday_rate ?? null,
  }
})

const fmtNum = (value) => Number(value || 0).toLocaleString('en-PK')

onMounted(loadVehicle)
</script>

<template>
  <div class="report-create-page">
    <!-- Header -->
    <header class="page-head">
      <div>
        <span class="section-kicker">Operations / Vehicle reporting</span>
        <h1>Add daily report</h1>
        <p class="page-sub">
          Record one working day — hours, meter readings, and any overtime.
        </p>
      </div>

      <RouterLink
        class="btn-light"
        :to="{ name: 'contract-vehicles.daily-reports', params: { id: contractVehicleId } }"
      >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M19 12H5" /><path d="m12 19-7-7 7-7" />
        </svg>
        View reports
      </RouterLink>
    </header>

    <!-- Load error -->
    <div v-if="generalError && !vehicle" class="card detail-error" role="alert">
      <div>
        <strong>Couldn't load this vehicle.</strong>
        <p>{{ generalError }}</p>
      </div>
      <button type="button" class="btn" @click="loadVehicle">Try again</button>
    </div>

    <!-- Skeleton (matches the two-column layout) -->
    <div v-else-if="!vehicle" class="create-layout" aria-hidden="true">
      <div class="sk" style="height: 460px"></div>
      <div class="aside-col">
        <div class="sk" style="height: 240px"></div>
        <div class="sk" style="height: 200px"></div>
      </div>
    </div>

    <!-- ============ Two-column layout ============ -->
    <div v-else class="create-layout">
      <!-- ===== Form column ===== -->
      <section class="card form-card">
        <!-- Vehicle context banner -->
        <div class="vehicle-banner">
          <span class="v-monogram" aria-hidden="true">{{ monogram }}</span>
          <div class="v-copy">
            <h2 class="v-number">{{ vehicle.vehicle_number || 'Unassigned vehicle' }}</h2>
            <p class="v-sub">
              {{ vehicleLabel }}
              <template v-if="vehicle.vehicle_type"> · {{ vehicle.vehicle_type }}</template>
            </p>
          </div>

          <div class="v-rules">
            <span class="rule-chip" title="Hours per day covered by the base rental — beyond this is overtime.">
              <b>{{ rules.dutyHours ?? '—' }}h</b> duty / day
            </span>
            <span class="rule-chip" title="Overtime is billed per hour at this rate.">
              <b>PKR {{ fmtNum(rules.otRate) }}</b> OT / hr
            </span>
            <span
              v-if="rules.mileageLimit"
              class="rule-chip"
              title="Kilometres included per month — beyond this, the excess rate applies."
            >
              <b>{{ fmtNum(rules.mileageLimit) }} KM</b> / month
            </span>
          </div>
        </div>

        <VehicleDailyReportForm
          :contract-vehicle="vehicle"
          :errors="errors"
          :submitting="submitting"
          submit-label="Add Report"
          @submit="submit"
          @cancel="cancel"
        />

        <!-- Submit error (non-422) surfaced next to the form actions -->
        <div v-if="generalError" class="page-error" role="alert">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="10" /><path d="M12 8v4" /><path d="M12 16h.01" />
          </svg>
          {{ generalError }}
        </div>
      </section>

      <!-- ===== Sticky context sidebar ===== -->
      <aside class="create-aside">
        <!-- Day math -->
        <section class="card panel-card">
          <h2 class="panel-title">
            How the day is calculated
            <InfoTip label="Every number on this report comes from these contract rules." />
          </h2>

          <ul class="math-list">
            <li>
              <span class="math-label">
                Hours worked
                <InfoTip label="From the Time in and Time out you enter below." />
              </span>
              <span class="math-value">Time out − Time in</span>
            </li>

            <li>
              <span class="math-label">
                Overtime
                <InfoTip label="Only hours worked beyond the daily duty limit count as overtime." />
              </span>
              <span class="math-value">
                Hours beyond <b>{{ rules.dutyHours ?? '—' }}h</b> duty
              </span>
            </li>

            <li>
              <span class="math-label">
                Overtime billing
                <InfoTip label="Each overtime hour is charged at the contract rate, on top of the monthly rental." />
              </span>
              <span class="math-value">
                <b>PKR {{ fmtNum(rules.otRate) }}</b> / overtime hour
              </span>
            </li>

            <li>
              <span class="math-label">
                Running KM
                <InfoTip label="The odometer readings you enter — the day's distance." />
              </span>
              <span class="math-value">Meter out − Meter in</span>
            </li>

            <li v-if="rules.mileageLimit">
              <span class="math-label">
                Mileage allowance
                <InfoTip label="This month's kilometres are added up across all reports. Beyond the limit, the excess rate is charged." />
              </span>
              <span class="math-value">
                <b>{{ fmtNum(rules.mileageLimit) }} KM</b> / month
                <template v-if="rules.excessRate">
                  · <b>PKR {{ fmtNum(rules.excessRate) }}</b> / KM over
                </template>
              </span>
            </li>

            <li v-if="rules.holidayRate">
              <span class="math-label">
                Public holiday
                <InfoTip label="Tick Public Holiday on the report when the vehicle worked a holiday — the day bills at this rate." />
              </span>
              <span class="math-value">
                <b>PKR {{ fmtNum(rules.holidayRate) }}</b> for the day
              </span>
            </li>
          </ul>
        </section>

        <!-- Pre-save checklist -->
        <section class="card panel-card">
          <h2 class="panel-title">
            Before you save
            <InfoTip label="A quick check keeps the month's billing accurate." />
          </h2>

          <ul class="check-list">
            <li>
              <span class="check-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M20 6 9 17l-5-5" />
                </svg>
              </span>
              Time out is later than time in.
            </li>
            <li>
              <span class="check-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M20 6 9 17l-5-5" />
                </svg>
              </span>
              Meter out is higher than meter in.
            </li>
            <li>
              <span class="check-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M20 6 9 17l-5-5" />
                </svg>
              </span>
              Tick Public Holiday or Weekly Off so the day bills correctly.
            </li>
            <li>
              <span class="check-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M20 6 9 17l-5-5" />
                </svg>
              </span>
              Use remarks for anything unusual — delays, breakdowns, route changes.
            </li>
          </ul>
        </section>
      </aside>
    </div>
  </div>
</template>

<style scoped>
.report-create-page {
  display: flex;
  flex-direction: column;
  gap: 20px;
  min-width: 0;
}

.page-sub {
  color: var(--text-secondary);
  font-size: 14px;
  margin-top: var(--space-2);
}

/* ---------- Two-column layout (fixes the empty right half) ---------- */

.create-layout {
  align-items: start;
  display: grid;
  gap: 20px;
  grid-template-columns: minmax(0, 1fr) 340px;
}

.aside-col {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.create-aside {
  display: flex;
  flex-direction: column;
  gap: 20px;
  position: sticky;
  top: 20px;
}

/* ---------- Form column ---------- */

.form-card { padding: 20px; }

.vehicle-banner {
  align-items: center;
  background: var(--accent-soft);
  border-radius: var(--radius-md);
  display: flex;
  flex-wrap: wrap;
  gap: 12px 16px;
  margin-bottom: 18px;
  padding: 14px 16px;
}

.v-monogram {
  align-items: center;
  background: var(--accent);
  border-radius: 10px;
  color: #fff;
  display: inline-flex;
  flex: 0 0 40px;
  font-size: 13px;
  font-weight: 700;
  height: 40px;
  justify-content: center;
  letter-spacing: 0.02em;
  width: 40px;
}

.v-copy {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.v-number {
  color: var(--text-primary);
  font-size: 15px;
  font-weight: 700;
  margin: 0;
}

.v-sub {
  color: var(--text-secondary);
  font-size: 12.5px;
  margin: 0;
}

.v-rules {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-left: auto;
}

.rule-chip {
  align-items: center;
  background: rgb(255 255 255 / 75%);
  border-radius: 999px;
  color: var(--text-secondary);
  cursor: help;
  display: inline-flex;
  font-size: 11.5px;
  gap: 3px;
  padding: 4px 11px;
}

.rule-chip b {
  color: var(--accent);
  font-variant-numeric: tabular-nums;
  font-weight: 700;
}

/* ---------- Errors ---------- */

.detail-error {
  align-items: center;
  border-color: var(--danger);
  color: var(--danger);
  display: flex;
  gap: 16px;
  justify-content: space-between;
}
.detail-error p { color: var(--text-secondary); margin: 4px 0 0; }

.page-error {
  align-items: center;
  background: var(--danger-soft);
  border: 1px solid var(--danger);
  border-radius: var(--radius-md);
  color: var(--danger);
  display: flex;
  font-size: 13px;
  gap: 9px;
  margin-top: 16px;
  padding: 11px 14px;
}
.page-error svg { flex: 0 0 16px; height: 16px; width: 16px; }

/* ---------- Sidebar panels ---------- */

.panel-card { padding: 18px; }

.panel-title {
  align-items: center;
  display: flex;
  font-size: 15px;
  font-weight: 600;
  gap: 5px;
  margin: 0 0 12px;
}

/* Day math rows */
.math-list {
  display: flex;
  flex-direction: column;
  list-style: none;
  margin: 0;
  padding: 0;
}

.math-list li {
  border-bottom: 1px solid var(--border);
  display: flex;
  flex-direction: column;
  gap: 3px;
  padding: 10px 0;
}
.math-list li:last-child { border-bottom: 0; padding-bottom: 0; }
.math-list li:first-child { padding-top: 2px; }

.math-label {
  align-items: center;
  color: var(--text-muted);
  display: flex;
  font-size: 11.5px;
  font-weight: 600;
  gap: 5px;
}

.math-value {
  color: var(--text-secondary);
  font-family: ui-monospace, SFMono-Regular, 'SF Mono', Menlo, Consolas, monospace;
  font-size: 12.5px;
}

.math-value b {
  color: var(--text-primary);
  font-variant-numeric: tabular-nums;
  font-weight: 700;
}

/* Checklist */
.check-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
  list-style: none;
  margin: 0;
  padding: 0;
}

.check-list li {
  align-items: flex-start;
  color: var(--text-secondary);
  display: flex;
  font-size: 13px;
  gap: 10px;
  line-height: 1.45;
}

.check-icon {
  align-items: center;
  background: var(--success-soft);
  border-radius: 50%;
  color: var(--success);
  display: inline-flex;
  flex: 0 0 20px;
  height: 20px;
  justify-content: center;
  margin-top: 1px;
  width: 20px;
}
.check-icon svg { height: 11px; width: 11px; }

/* ---------- Skeleton ---------- */

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

/* ---------- Responsive ---------- */

@media (max-width: 1100px) {
  .create-layout { grid-template-columns: 1fr; }

  /* Sidebar stacks below the form — the day math stays
     available while reviewing what was entered */
  .create-aside { position: static; }
}

@media (max-width: 640px) {
  .vehicle-banner { align-items: flex-start; }
  .v-rules { margin-left: 0; }

  .page-head { flex-direction: column; }
}
</style>