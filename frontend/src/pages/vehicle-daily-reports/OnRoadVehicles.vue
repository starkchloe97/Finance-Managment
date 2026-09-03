<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'

import { useContractVehicleStore } from '@/stores/contractVehicleStore'
import InfoTip from '@/components/ui/InfoTip.vue'

const store = useContractVehicleStore()

/* ---- added: display helpers only (no existing logic changed) ---- */

const isDuePast = (vehicle) => {
  if (!vehicle?.contract?.end_date) return false
  const status = String(vehicle?.contract?.status || 'active').toLowerCase()
  if (status !== 'active') return false
  return new Date(vehicle.contract.end_date) < new Date()
}

const contractLabel = (vehicle) =>
  vehicle?.contract?.contract_number ||
  `VC-${String(vehicle?.contract?.id).padStart(5, '0')}`

const vehicleStatusClass = (vehicle) => {
  const status = String(vehicle?.status || 'active').toLowerCase()
  if (status === 'maintenance') return 'status-warning'
  if (status === 'inactive') return 'status-draft'
  return 'status-success'
}

const totals = computed(() => {
  const vehicles = store.vehicles || []
  const rental = vehicles.reduce((sum, v) => sum + Number(v.monthly_rental || 0), 0)
  const customers = new Set(vehicles.map((v) => v.contract?.customer_name).filter(Boolean))
  return {
    vehicles: vehicles.length,
    contracts: customers.size,
    monthly: rental,
    duePast: vehicles.filter(isDuePast).length,
  }
})

const search = ref('')
const searchTimer = ref(null)

const filteredVehicles = computed(() => {
  const term = search.value.trim().toLowerCase()
  if (!term) return store.vehicles
  return store.vehicles.filter((v) =>
    [
      v.vehicle_number,
      v.make,
      v.model,
      v.vehicle_type,
      v.contract?.customer_name,
      v.contract?.contract_number,
    ]
      .filter(Boolean)
      .some((field) => String(field).toLowerCase().includes(term)),
  )
})

/* ---- existing helpers (unchanged) ---- */

const formatDate = (value) => {
  if (!value) return '—'

  return new Date(value).toLocaleDateString('en-GB', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

const formatMoney = (value) =>
  Number(value || 0).toLocaleString('en-PK', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  })

onMounted(() => {
  store.fetchVehicles().catch(() => {
    // error handled in store
  })
})
</script>

<template>
  <div class="on-road-page">
    <!-- Header -->
    <header class="page-head">
      <div>
        <span class="section-kicker">Operations / Vehicle reporting</span>
        <h1>On-road vehicles</h1>
        <p class="page-sub">
          Every vehicle currently out on a rental contract — across all customers.
        </p>
      </div>
    </header>

    <!-- Contextual error (rows visible) -->
    <div v-if="store.error && store.vehicles.length" class="error-banner" role="alert">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 20h16a2 2 0 0 0 1.73-2Z" /><path d="M12 9v4" /><path d="M12 17h.01" />
      </svg>
      <span>{{ store.error }}</span>
      <button type="button" class="banner-retry" @click="store.fetchVehicles()">Retry</button>
    </div>

    <!-- Full error -->
    <div v-else-if="store.error" class="card detail-error" role="alert">
      <div>
        <strong>Couldn't load on-road vehicles.</strong>
        <p>{{ store.error }}</p>
      </div>
      <button type="button" class="btn" @click="store.fetchVehicles()">Try again</button>
    </div>

    <template v-else>
      <!-- Summary strip -->
      <section
        v-if="!store.loading && store.vehicles.length"
        class="card sum-card"
        aria-label="Fleet summary"
      >
        <div class="sum-stat">
          <span>
            On road
            <InfoTip label="Vehicles currently rented out across all contracts." />
          </span>
          <strong>{{ totals.vehicles }}</strong>
        </div>

        <div class="sum-stat">
          <span>
            Customers
            <InfoTip label="Distinct customers with vehicles on the road right now." />
          </span>
          <strong>{{ totals.contracts }}</strong>
        </div>

        <div class="sum-stat">
          <span>
            Monthly rental
            <InfoTip label="Combined monthly rental across all on-road vehicles." />
          </span>
          <strong>PKR {{ formatMoney(totals.monthly) }}</strong>
        </div>

        <div class="sum-stat" :class="{ 'is-red': totals.duePast > 0 }">
          <span>
            Past due
            <InfoTip label="Active contracts whose end date has already passed — renew or close them." />
          </span>
          <strong>{{ totals.duePast }}</strong>
        </div>
      </section>

      <!-- Search + table -->
      <section v-if="store.vehicles.length || store.loading" class="card table-card" :aria-busy="store.loading">
        <!-- Search -->
        <div class="toolbar">
          <div class="search-field">
            <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
              <circle cx="11" cy="11" r="7" />
              <path d="m20 20-4-4" />
            </svg>
            <input
              v-model="search"
              type="search"
              placeholder="Search by vehicle, customer, or contract…"
              aria-label="Search on-road vehicles"
            />
            <button
              v-if="search"
              type="button"
              class="search-clear"
              aria-label="Clear search"
              @click="search = ''"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                <path d="M18 6 6 18M6 6l12 12" />
              </svg>
            </button>
          </div>
          <span v-if="!store.loading" class="result-count">
            {{ filteredVehicles.length }}
            {{ filteredVehicles.length === 1 ? 'vehicle' : 'vehicles' }}
          </span>
        </div>

        <div v-if="store.loading" class="progress-line" aria-hidden="true">
          <span></span>
        </div>

        <div class="table-wrap">
          <table>
            <caption class="sr-only">On-road rental vehicles across all contracts</caption>

            <thead>
              <tr>
                <th>Vehicle</th>
                <th>Contract</th>
                <th>Customer</th>
                <th class="right">
                  Rental
                  <InfoTip label="This vehicle's monthly rental — the contract rate unless the vehicle has its own." />
                </th>
                <th>
                  Ends
                  <InfoTip label="The contract's end date. Red means the date has passed while the contract is still active." />
                </th>
                <th>Status</th>
                <th class="right"></th>
              </tr>
            </thead>

            <!-- Rows -->
            <tbody v-if="filteredVehicles.length" :class="{ 'is-refreshing': store.loading }">
              <tr v-for="vehicle in filteredVehicles" :key="vehicle.id">
                <!-- Vehicle -->
                <td>
                  <div class="veh-cell">
                    <span class="veh-monogram" aria-hidden="true">
                      {{ (vehicle.vehicle_number || vehicle.make || '·').trim().slice(0, 2).toUpperCase() }}
                    </span>
                    <div class="veh-id">
                      <strong class="veh-number">{{ vehicle.vehicle_number || '—' }}</strong>
                      <span class="veh-sub" :title="vehicle.vehicle_type">
                        {{ [vehicle.make, vehicle.model].filter(Boolean).join(' ') || '—' }}
                        <template v-if="vehicle.model_year"> · {{ vehicle.model_year }}</template>
                      </span>
                    </div>
                  </div>
                </td>

                <!-- Contract chip -->
                <td>
                  <RouterLink
                    v-if="vehicle.contract"
                    class="contract-chip"
                    :to="{ name: 'vehicle-contracts.show', params: { id: vehicle.contract.id } }"
                    :title="`Open contract ${contractLabel(vehicle)}`"
                  >
                    {{ contractLabel(vehicle) }}
                  </RouterLink>
                  <span v-else class="veh-sub">No contract</span>
                </td>

                <!-- Customer -->
                <td>
                  <span class="customer-name">{{ vehicle.contract?.customer_name || '—' }}</span>
                </td>

                <!-- Rental -->
                <td class="right row-amount">
                  <template v-if="vehicle.monthly_rental">
                    PKR {{ formatMoney(vehicle.monthly_rental) }}
                  </template>
                  <span v-else class="veh-sub">—</span>
                </td>

                <!-- End date -->
                <td :class="{ 'due-past': isDuePast(vehicle) }">
                  {{ formatDate(vehicle.contract?.end_date) }}
                </td>

                <!-- Status -->
                <td>
                  <span class="status-row">
                    <span class="status" :class="vehicleStatusClass(vehicle)">
                      {{ vehicle.status || 'Active' }}
                    </span>
                    <InfoTip
                      :label="
                        String(vehicle.status || 'active').toLowerCase() === 'maintenance'
                          ? 'Under repair — temporarily off duty.'
                          : 'On the road and earning.'
                      "
                    />
                  </span>
                </td>

                <!-- Action → daily reports (route preserved) -->
                <td class="right">
                  <RouterLink
                    class="btn-light btn-sm"
                    :to="{ name: 'contract-vehicles.daily-reports', params: { id: vehicle.id } }"
                  >
                    Daily reports
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                      <path d="M5 12h14" /><path d="m12 5 7 7-7 7" />
                    </svg>
                  </RouterLink>
                </td>
              </tr>
            </tbody>

            <!-- Search empty -->
            <tbody v-else>
              <tr>
                <td colspan="7" class="no-results">
                  <p>No vehicles match "{{ search }}".</p>
                  <button type="button" class="btn-light btn-sm" @click="search = ''">
                    Clear search
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <!-- Skeleton (first load) -->
      <div v-else-if="store.loading" class="sk" style="height: 320px" aria-hidden="true"></div>

      <!-- Empty -->
      <section v-else class="card empty-state">
        <div class="empty-icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
            <path d="M5 18H3c-.6 0-1-.4-1-1V7c0-.6.4-1 1-1h10c.6 0 1 .4 1 1v11" /><path d="M14 9h4l4 4v4c0 .6-.4 1-1 1h-2" /><circle cx="7.5" cy="17.5" r="2.5" /><circle cx="17.5" cy="17.5" r="2.5" />
          </svg>
        </div>
        <h2>No vehicles on the road</h2>
        <p>
          Nothing is out on a rental contract right now — on-road vehicles appear here as soon as
          contracts get vehicles assigned.
        </p>
      </section>
    </template>
  </div>
</template>

<style scoped>
.on-road-page {
  display: flex;
  flex-direction: column;
  gap: 18px;
  min-width: 0;
}

/* Flex gap owns spacing — neutralize global .card margin */
.on-road-page > .card {
  margin-bottom: 0;
}

.page-sub {
  color: var(--text-secondary);
  font-size: 14px;
  margin-top: var(--space-2);
}

/* ---------- Summary strip ---------- */

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
.sum-stat.is-red strong { color: var(--danger); }

/* ---------- Toolbar ---------- */

.table-card {
  overflow: hidden;
  padding: 0;
  position: relative;
}

.toolbar {
  align-items: center;
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  padding: 12px 16px;
}

.search-field {
  flex: 1 1 260px;
  min-width: 0;
  position: relative;
}

.search-field .search-icon {
  color: var(--text-muted);
  height: 15px;
  left: 12px;
  pointer-events: none;
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  width: 15px;
}

.search-field input {
  border-radius: 10px;
  padding-left: 36px;
  padding-right: 38px;
}

.search-field input[type='search']::-webkit-search-cancel-button {
  -webkit-appearance: none;
  appearance: none;
  display: none;
}

.search-clear {
  align-items: center;
  background: transparent;
  border: 0;
  border-radius: 7px;
  color: var(--text-muted);
  cursor: pointer;
  display: flex;
  height: 26px;
  justify-content: center;
  position: absolute;
  right: 8px;
  top: 50%;
  transform: translateY(-50%);
  width: 26px;
}
.search-clear svg { height: 13px; width: 13px; }
.search-clear:hover { background: var(--surface-2); color: var(--text-primary); }

.result-count {
  color: var(--text-muted);
  flex: 0 0 auto;
  font-size: 12.5px;
  white-space: nowrap;
}

/* ---------- Progress line ---------- */

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

/* ---------- Table ---------- */

.table-card .table-wrap { padding: 0 0 4px; }
.table-card table { min-width: 860px; }
.table-card th,
.table-card td { white-space: nowrap; }

tbody.is-refreshing {
  opacity: 0.55;
  pointer-events: none;
  transition: opacity 0.15s ease;
}

/* Vehicle cell */
.veh-cell {
  align-items: center;
  display: flex;
  gap: 11px;
}

.veh-monogram {
  align-items: center;
  background: var(--accent-soft);
  border-radius: 9px;
  color: var(--accent);
  display: inline-flex;
  flex: 0 0 34px;
  font-size: 12px;
  font-weight: 700;
  height: 34px;
  justify-content: center;
  letter-spacing: 0.02em;
  width: 34px;
}

.veh-id {
  display: flex;
  flex-direction: column;
  gap: 1px;
  min-width: 0;
}

.veh-number { color: var(--text-primary); font-weight: 700; }

.veh-sub {
  color: var(--text-muted);
  display: block;
  font-size: 12px;
  max-width: 260px;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Contract chip */
.contract-chip {
  align-items: center;
  background: var(--violet-soft);
  border-radius: 999px;
  color: var(--violet);
  display: inline-flex;
  font-size: 11.5px;
  font-weight: 700;
  padding: 4px 11px;
  text-decoration: none;
  transition: background 0.15s ease, color 0.15s ease;
}
.contract-chip:hover { background: var(--violet); color: #fff; }

.customer-name { color: var(--text-primary); }

.row-amount {
  color: var(--text-primary);
  font-variant-numeric: tabular-nums;
  font-weight: 700;
}

.due-past { color: var(--danger); font-weight: 700; }

.status-row {
  align-items: center;
  display: inline-flex;
  gap: 5px;
}

.status { text-transform: capitalize; }

/* Search empty row */
.no-results { padding: 32px 16px; text-align: center; }
.no-results p {
  color: var(--text-muted);
  font-size: 13px;
  margin: 0 0 10px;
}
.no-results .btn-light { justify-content: center; }

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

.detail-error {
  align-items: center;
  border-color: var(--danger);
  color: var(--danger);
  display: flex;
  gap: 16px;
  justify-content: space-between;
}
.detail-error p { color: var(--text-secondary); margin: 4px 0 0; }

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
  margin: 0;
  max-width: 420px;
}

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

@keyframes progress-slide {
  0% { transform: translateX(-100%); }
  100% { transform: translateX(350%); }
}

/* ---------- Responsive ---------- */

@media (max-width: 900px) {
  .sum-card { grid-template-columns: repeat(2, minmax(0, 1fr)); row-gap: 16px; }
  .sum-stat:nth-child(odd) { border-left: 0; padding-left: 0; }
}

@media (max-width: 760px) {
  .table-card table { min-width: 640px; }
  .col-hidden-sm { display: none; }
}

@media (max-width: 640px) {
  .detail-error { align-items: flex-start; flex-direction: column; }
}
</style>