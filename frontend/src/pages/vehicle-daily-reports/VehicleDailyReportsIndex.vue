<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'

import { getMonthlySummary } from '@/services/vehicleDailyReportService'
import { getVehicleContracts } from '@/services/vehicleContractService'
import InfoTip from '@/components/ui/InfoTip.vue'

const router = useRouter()

const contracts = ref([])
const selectedContractId = ref('')
const selectedMonth = ref(new Date().toISOString().slice(0, 7))
const loading = ref(false)
const error = ref('')
const vehicleData = ref([])

const selectedContract = computed(() => {
  return contracts.value.find(
    (contract) => String(contract.id) === String(selectedContractId.value),
  )
})

const loadContracts = async () => {
  try {
    const response = await getVehicleContracts()
    contracts.value = response.data?.data ?? response.data ?? []
  } catch (err) {
    error.value = err.response?.data?.message || 'Unable to load contracts.'
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
    const vehicles = selectedContract.value.vehicles || []

    const results = await Promise.all(
      vehicles.map(async (vehicle) => {
        const response = await getMonthlySummary(vehicle.id, selectedMonth.value)
        return {
          vehicle,
          summary: response.data?.data ?? response.data ?? {},
        }
      }),
    )

    vehicleData.value = results
  } catch (err) {
    error.value = err.response?.data?.message || 'Unable to load vehicle summaries.'
  } finally {
    loading.value = false
  }
}

/* ---- routes (unchanged) ---- */
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

const formatNumber = (value) => {
  return Number(value || 0).toLocaleString('en-PK')
}

const formatMinutes = (minutes) => {
  const total = Number(minutes || 0)
  const hours = Math.floor(total / 60)
  const remainingMinutes = total % 60

  if (!hours) return `${remainingMinutes}m`
  if (!remainingMinutes) return `${hours}h`
  return `${hours}h ${remainingMinutes}m`
}

/* ---- added: display helpers only (no existing logic changed) ---- */

const monthLabel = computed(() => {
  if (!selectedMonth.value) return ''
  const [y, m] = selectedMonth.value.split('-').map(Number)
  return new Date(y, m - 1, 1).toLocaleDateString('en-GB', {
    month: 'long',
    year: 'numeric',
  })
})

const totals = computed(() => {
  const list = vehicleData.value
  const sum = (pick) =>
    list.reduce((total, item) => total + Number(pick(item.summary) || 0), 0)

  return {
    reports: sum((s) => s.report_count),
    running: sum((s) => s.total_running),
    limit: sum((s) => s.monthly_mileage_limit),
    extra: sum((s) => s.total_overtime_amount) + sum((s) => s.excess_mileage_amount),
  }
})

const vehicleStatusClass = (vehicle) => {
  const status = String(vehicle?.status || 'active').toLowerCase()
  if (status === 'maintenance') return 'status-warning'
  if (status === 'inactive') return 'status-draft'
  return 'status-success'
}

const extraCharges = (summary) =>
  Number(summary?.total_overtime_amount || 0) + Number(summary?.excess_mileage_amount || 0)

const mileagePct = (summary) => {
  const limit = Number(summary?.monthly_mileage_limit || 0)
  if (!limit) return null
  return Math.min(100, Math.round((Number(summary?.total_running || 0) / limit) * 100))
}

const isOverLimit = (summary) =>
  Number(summary?.monthly_mileage_limit || 0) > 0 &&
  Number(summary?.total_running || 0) > Number(summary?.monthly_mileage_limit || 0)

onMounted(async () => {
  await loadContracts()

  if (contracts.value.length) {
    selectedContractId.value = contracts.value[0].id
  }
})

watch([selectedContractId, selectedMonth], () => {
  loadVehicleSummaries()
})
</script>

<template>
  <div class="reports-page">
    <!-- Header -->
    <header class="page-head">
      <div>
        <span class="section-kicker">Operations / Vehicle reporting</span>
        <h1>Vehicle daily reporting</h1>
        <p class="page-sub">
          Monthly usage, overtime, and mileage for each vehicle on the contract.
        </p>
      </div>
    </header>

    <!-- Filters -->
    <section class="card toolbar-card">
      <div class="filter-field grow">
        <label for="contract">
          Contract
          <InfoTip
            label="Daily reporting is organized per contract — pick the customer whose vehicles you want to review."
          />
        </label>
        <select id="contract" v-model="selectedContractId">
          <option value="">Select contract</option>
          <option v-for="contract in contracts" :key="contract.id" :value="contract.id">
            {{ contract.customer_name }}{{ contract.contract_number ? ` — ${contract.contract_number}` : '' }}
          </option>
        </select>
      </div>

      <div class="filter-field">
        <label for="month">Month</label>
        <input id="month" v-model="selectedMonth" type="month" />
      </div>

      <div v-if="selectedContract" class="ctx">
        <span class="ctx-month">{{ monthLabel }}</span>
        <span class="ctx-meta">
          {{ selectedContract.total_vehicles || 0 }}
          {{ selectedContract.total_vehicles === 1 ? 'vehicle' : 'vehicles' }} ·
          PKR {{ formatNumber(selectedContract.total_monthly_rental) }} / month
        </span>
      </div>
    </section>

    <!-- Contextual error (reload while rows visible) -->
    <div v-if="error && vehicleData.length" class="error-banner" role="alert">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 20h16a2 2 0 0 0 1.73-2Z" /><path d="M12 9v4" /><path d="M12 17h.01" />
      </svg>
      <span>{{ error }}</span>
      <button type="button" class="banner-retry" @click="loadVehicleSummaries">Retry</button>
    </div>

    <!-- Month summary strip -->
    <section
      v-if="vehicleData.length && !loading"
      class="card sum-card"
      aria-label="Month summary"
    >
      <div class="sum-stat">
        <span>
          Vehicles
          <InfoTip label="Vehicles on the selected contract." />
        </span>
        <strong>{{ vehicleData.length }}</strong>
      </div>

      <div class="sum-stat">
        <span>
          Daily reports
          <InfoTip label="Reports logged across all vehicles this month — the source of every figure below." />
        </span>
        <strong>{{ totals.reports }}</strong>
      </div>

      <div class="sum-stat">
        <span>
          Mileage used
          <InfoTip label="Total kilometres driven this month, across all vehicles." />
        </span>
        <strong>{{ formatNumber(totals.running) }} KM</strong>
        <em v-if="totals.limit">
          {{ Math.min(100, Math.round((totals.running / totals.limit) * 100)) }}% of limit
        </em>
      </div>

      <div class="sum-stat" :class="{ 'is-amber': totals.extra > 0 }">
        <span>
          Extra charges
          <InfoTip label="Overtime pay plus excess-mileage charges this month — billed on top of the monthly rental." />
        </span>
        <strong>PKR {{ formatNumber(totals.extra) }}</strong>
        <em>Overtime + excess mileage</em>
      </div>
    </section>

    <!-- Vehicle list -->
    <section v-if="vehicleData.length || loading" class="card list-card" :aria-busy="loading">
      <div v-if="loading && vehicleData.length" class="progress-line" aria-hidden="true">
        <span></span>
      </div>

      <!-- Data rows -->
      <template v-if="vehicleData.length">
        <article
          v-for="item in vehicleData"
          :key="item.vehicle.id"
          class="v-row"
          :class="{ 'is-refreshing': loading }"
        >
          <!-- Identity -->
          <div class="v-id">
            <span class="v-monogram" aria-hidden="true">
              {{
                (item.vehicle.vehicle_number || item.vehicle.make || '·')
                  .trim()
                  .slice(0, 2)
                  .toUpperCase()
              }}
            </span>
            <div class="v-copy">
              <div class="v-title-row">
                <h2 class="v-number">{{ item.vehicle.vehicle_number || 'Unassigned vehicle' }}</h2>
                <span class="status" :class="vehicleStatusClass(item.vehicle)">
                  {{ item.vehicle.status || 'Active' }}
                </span>
              </div>
              <p class="v-sub" :title="item.vehicle.vehicle_type">
                {{
                  [item.vehicle.make, item.vehicle.model, item.vehicle.vehicle_type]
                    .filter(Boolean)
                    .join(' · ') || '—'
                }}
              </p>
            </div>
          </div>

          <!-- Mileage -->
          <div class="v-mileage">
            <template v-if="item.summary.monthly_mileage_limit">
              <div class="m-top">
                <span class="m-label">
                  Mileage
                  <InfoTip
                    label="Kilometres driven this month vs the contract's monthly allowance. Beyond the limit, the excess rate is charged."
                  />
                </span>
                <span class="m-nums">
                  <strong :class="{ 'is-over': isOverLimit(item.summary) }">
                    {{ formatNumber(item.summary.total_running) }}
                  </strong>
                  <span class="m-limit">
                    / {{ formatNumber(item.summary.monthly_mileage_limit) }} KM
                  </span>
                  <span v-if="isOverLimit(item.summary)" class="m-over">
                    +{{ formatNumber(item.summary.excess_mileage) }}
                  </span>
                </span>
              </div>
              <div
                class="m-bar"
                role="progressbar"
                :aria-valuenow="mileagePct(item.summary) ?? 0"
                aria-valuemin="0"
                aria-valuemax="100"
                :aria-label="`Mileage used for ${item.vehicle.vehicle_number || 'vehicle'}`"
              >
                <span
                  class="m-fill"
                  :class="{ 'is-over': isOverLimit(item.summary) }"
                  :style="{ width: `${mileagePct(item.summary) ?? 0}%` }"
                ></span>
              </div>
              <p class="m-caption">
                <template v-if="isOverLimit(item.summary)">Over the monthly limit</template>
                <template v-else>{{ mileagePct(item.summary) }}% of the monthly limit</template>
              </p>
            </template>

            <template v-else>
              <div class="m-top">
                <span class="m-label">
                  Mileage
                  <InfoTip label="This contract has no monthly mileage allowance — every kilometre is within terms." />
                </span>
                <span class="m-nums">
                  <strong>{{ formatNumber(item.summary.total_running) }}</strong>
                  <span class="m-limit">KM</span>
                </span>
              </div>
              <p class="m-caption">No monthly limit on this contract</p>
            </template>
          </div>

          <!-- Stats -->
          <div class="v-stats">
            <div class="v-stat">
              <span>
                Reports
                <InfoTip label="Daily reports logged this month." />
              </span>
              <strong>{{ item.summary.report_count ?? 0 }}</strong>
            </div>

            <div class="v-stat">
              <span>
                Overtime
                <InfoTip label="Driver hours beyond the contracted daily duty hours this month." />
              </span>
              <strong
                :class="{ 'is-muted': !Number(item.summary.total_overtime_minutes || 0) }"
              >
                {{ formatMinutes(item.summary.total_overtime_minutes) }}
              </strong>
            </div>

            <div class="v-stat" :class="{ 'is-amber': extraCharges(item.summary) > 0 }">
              <span>
                Extra charges
                <InfoTip label="Overtime pay + excess mileage charges — billed on top of the monthly rental." />
              </span>
              <strong v-if="extraCharges(item.summary) > 0" class="v-extra">
                PKR {{ formatNumber(extraCharges(item.summary)) }}
              </strong>
              <strong v-else class="is-muted">—</strong>
            </div>
          </div>

          <!-- Actions → routes preserved -->
          <div class="v-actions">
            <button
              type="button"
              class="btn-light btn-sm"
              @click="openReports(item.vehicle)"
            >
              View reports
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M5 12h14" /><path d="m12 5 7 7-7 7" />
              </svg>
            </button>

            <button
              type="button"
              class="btn btn-sm"
              @click="addReport(item.vehicle)"
            >
              + Add report
            </button>
          </div>
        </article>
      </template>

      <!-- Skeleton rows (first load) -->
      <template v-else>
        <div v-for="i in 6" :key="`sk-${i}`" class="v-row" aria-hidden="true">
          <div class="v-id">
            <div class="sk sk-mono"></div>
            <div class="sk sk-line" style="width: 65%"></div>
          </div>
          <div class="v-mileage">
            <div class="sk sk-line" style="width: 45%"></div>
            <div class="sk sk-bar"></div>
          </div>
          <div class="v-stats">
            <div class="sk sk-line" style="width: 100%"></div>
            <div class="sk sk-line" style="width: 100%"></div>
            <div class="sk sk-line" style="width: 100%"></div>
          </div>
          <div class="v-actions">
            <div class="sk sk-btn"></div>
            <div class="sk sk-btn"></div>
          </div>
        </div>
      </template>
    </section>

    <!-- Full error -->
    <div v-else-if="error" class="card detail-error" role="alert">
      <div>
        <strong>Couldn't load vehicle summaries.</strong>
        <p>{{ error }}</p>
      </div>
      <button type="button" class="btn" @click="loadVehicleSummaries">Try again</button>
    </div>

    <!-- Empty: no contracts -->
    <section v-else-if="!contracts.length" class="card empty-state">
      <h2>No vehicle contracts yet</h2>
      <p>Daily reporting is organized by contract — create one first.</p>
      <RouterLink class="btn" :to="{ name: 'vehicle-contracts.create' }">
        Create contract
      </RouterLink>
    </section>

    <!-- Empty: no vehicles on this contract -->
    <section v-else-if="selectedContract" class="card empty-state">
      <h2>No vehicles on this contract</h2>
      <p>This contract has no vehicles assigned yet — reporting needs at least one.</p>
    </section>

    <!-- Empty: nothing selected -->
    <section v-else class="card empty-state">
      <h2>Select a contract</h2>
      <p>Choose a contract above to see its vehicles and monthly summaries.</p>
    </section>
  </div>
</template>

<style scoped>
.reports-page {
  display: flex;
  flex-direction: column;
  gap: 18px;
  min-width: 0;
}

/* Flex gap owns vertical rhythm — neutralize global .card margin */
.reports-page > .card {
  margin-bottom: 0;
}

.page-sub {
  color: var(--text-secondary);
  font-size: 14px;
  margin-top: var(--space-2);
}

/* ---------- Toolbar ---------- */

.toolbar-card {
  align-items: flex-end;
  display: flex;
  flex-wrap: wrap;
  gap: 14px;
  padding: 14px 16px;
}

.filter-field {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.filter-field.grow {
  flex: 1 1 280px;
  min-width: 240px;
}

.filter-field:not(.grow) {
  width: 170px;
}

.filter-field label {
  align-items: center;
  color: var(--text-secondary);
  display: flex;
  font-size: 12px;
  font-weight: var(--font-weight-medium);
  gap: 5px;
}

.filter-field select,
.filter-field input {
  border-radius: 10px;
  min-height: 38px;
  width: 100%;
}

.ctx {
  display: flex;
  flex-direction: column;
  gap: 2px;
  margin-left: auto;
  padding-bottom: 2px;
  text-align: right;
}
.ctx-month {
  color: var(--text-primary);
  font-size: 14px;
  font-weight: 700;
}
.ctx-meta {
  color: var(--text-muted);
  font-size: 12px;
}

/* ---------- Month summary strip ---------- */

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
.sum-stat:first-child {
  border-left: 0;
  padding-left: 0;
}

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

.sum-stat em {
  color: var(--text-muted);
  font-style: normal;
  font-size: 11px;
}

.sum-stat.is-amber strong {
  color: var(--warning);
}

/* ---------- Vehicle list rows ---------- */

.list-card {
  overflow: hidden;
  padding: 0;
  position: relative;
}

.progress-line {
  height: 2px;
  left: 0;
  overflow: hidden;
  position: absolute;
  right: 0;
  top: 0;
  z-index: 3;
}

.progress-line span {
  animation: progress-slide 1.1s ease-in-out infinite;
  background: var(--accent);
  border-radius: 2px;
  height: 100%;
  left: 0;
  position: absolute;
  top: 0;
  width: 40%;
}

.v-row {
  align-items: center;
  border-bottom: 1px solid var(--border);
  display: grid;
  gap: 16px;
  grid-template-columns:
    minmax(190px, 1.05fr) minmax(190px, 1.25fr) 64px 96px 145px max-content;
  padding: 16px 20px;
  transition: background 0.12s ease;
}
.v-row:last-child { border-bottom: 0; }
.v-row:hover { background: var(--surface-2); }

.v-row.is-refreshing {
  opacity: 0.55;
  pointer-events: none;
  transition: opacity 0.15s ease;
}

/* Identity */
.v-id {
  align-items: center;
  display: flex;
  gap: 12px;
  min-width: 0;
}

.v-monogram {
  align-items: center;
  background: var(--accent-soft);
  border-radius: 10px;
  color: var(--accent);
  display: inline-flex;
  flex: 0 0 36px;
  font-size: 12px;
  font-weight: 700;
  height: 36px;
  justify-content: center;
  letter-spacing: 0.02em;
  width: 36px;
}

.v-copy {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.v-title-row {
  align-items: center;
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.v-number {
  font-size: 15px;
  font-weight: 700;
  margin: 0;
}

.v-sub {
  color: var(--text-muted);
  font-size: 12px;
  margin: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* Mileage */
.v-mileage { min-width: 0; }

.m-top {
  align-items: baseline;
  display: flex;
  gap: 8px;
  justify-content: space-between;
  margin-bottom: 6px;
}

.m-label {
  align-items: center;
  color: var(--text-muted);
  display: inline-flex;
  font-size: 12px;
  font-weight: 500;
  gap: 5px;
}

.m-nums {
  align-items: baseline;
  display: inline-flex;
  gap: 5px;
}

.m-nums strong {
  color: var(--text-primary);
  font-size: 14px;
  font-variant-numeric: tabular-nums;
  font-weight: 700;
}
.m-nums strong.is-over { color: var(--danger); }

.m-limit {
  color: var(--text-muted);
  font-size: 11.5px;
  white-space: nowrap;
}

.m-over {
  background: var(--danger-soft);
  border-radius: 999px;
  color: var(--danger);
  font-size: 10.5px;
  font-weight: 700;
  padding: 1px 7px;
  white-space: nowrap;
}

.m-bar {
  background: var(--surface-2);
  border-radius: 999px;
  height: 7px;
  overflow: hidden;
}

.m-fill {
  background: var(--accent);
  border-radius: 999px;
  display: block;
  height: 100%;
  transition: width 0.4s ease;
}
.m-fill.is-over { background: var(--danger); }

.m-caption {
  color: var(--text-muted);
  font-size: 11px;
  margin: 6px 0 0;
}

/* Stats — display:contents lets them ride the row grid on desktop */
.v-stats { display: contents; }

.v-stat {
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.v-stat > span {
  align-items: center;
  color: var(--text-muted);
  display: flex;
  font-size: 11px;
  font-weight: 500;
  gap: 4px;
}

.v-stat strong {
  color: var(--text-primary);
  font-size: 14px;
  font-variant-numeric: tabular-nums;
  font-weight: 700;
}
.v-stat strong.is-muted {
  color: var(--text-muted);
  font-weight: 500;
}
.v-stat strong.v-extra {
  font-size: 13px;
  white-space: nowrap;
}
.v-stat.is-amber strong { color: var(--warning); }

/* Actions */
.v-actions {
  display: flex;
  gap: 8px;
  justify-content: flex-end;
}

/* ---------- Error states ---------- */

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
.error-banner svg {
  flex: 0 0 16px;
  height: 16px;
  width: 16px;
}

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

.detail-error {
  align-items: center;
  border-color: var(--danger);
  color: var(--danger);
  display: flex;
  gap: 16px;
  justify-content: space-between;
}
.detail-error p {
  color: var(--text-secondary);
  margin: 4px 0 0;
}

/* ---------- Empty states ---------- */

.empty-state {
  align-items: center;
  display: flex;
  flex-direction: column;
  padding: 48px 24px;
  text-align: center;
}
.empty-state h2 {
  font-size: 16px;
  margin: 0 0 6px;
}
.empty-state p {
  color: var(--text-muted);
  font-size: 13.5px;
  margin: 0 0 18px;
  max-width: 380px;
}

/* ---------- Skeletons ---------- */

.sk {
  background: var(--surface-2);
  border-radius: 6px;
  overflow: hidden;
  position: relative;
}
.sk::after {
  animation: shimmer 1.4s infinite;
  background: linear-gradient(90deg, transparent, rgb(255 255 255 / 70%), transparent);
  content: '';
  inset: 0;
  position: absolute;
  transform: translateX(-100%);
}

.sk-mono {
  border-radius: 10px;
  flex: 0 0 36px;
  height: 36px;
  width: 36px;
}
.sk-line {
  height: 12px;
  width: 70%;
}
.sk-bar {
  border-radius: 999px;
  height: 7px;
  margin-top: 8px;
  width: 90%;
}
.sk-btn {
  border-radius: 8px;
  height: 32px;
  width: 110px;
}

@keyframes shimmer {
  to { transform: translateX(100%); }
}

@keyframes progress-slide {
  0% { transform: translateX(-100%); }
  100% { transform: translateX(350%); }
}

/* ---------- Responsive ---------- */

@media (max-width: 1200px) {
  /* Two-line rows: identity + actions, then mileage, then stats */
  .v-row {
    grid-template-columns: minmax(0, 1fr) max-content;
    grid-template-areas:
      'id actions'
      'mileage mileage'
      'stats stats';
    row-gap: 14px;
  }

  .v-id { grid-area: id; }
  .v-mileage { grid-area: mileage; }

  .v-stats {
    display: flex;
    flex-wrap: wrap;
    gap: 14px 32px;
    grid-area: stats;
  }

  .v-actions { grid-area: actions; }
}

@media (max-width: 900px) {
  .toolbar-card {
    align-items: stretch;
    flex-direction: column;
  }
  .filter-field:not(.grow) { width: 100%; }
  .ctx {
    margin-left: 0;
    text-align: left;
  }

  .sum-card {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    row-gap: 16px;
  }
  .sum-stat:nth-child(odd) {
    border-left: 0;
    padding-left: 0;
  }
}

@media (max-width: 640px) {
  .v-row {
    grid-template-columns: 1fr;
    grid-template-areas:
      'id'
      'mileage'
      'stats'
      'actions';
  }

  .v-actions {
    justify-content: stretch;
  }
  .v-actions .btn,
  .v-actions .btn-light {
    flex: 1;
    justify-content: center;
  }

  .v-stats { gap: 14px 20px; }
}
</style>