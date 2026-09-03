<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRoute } from 'vue-router'

import {
  getVehicleContract,
} from '@/services/vehicleContractService'

const route = useRoute()

const contract = ref(null)
const loading = ref(true)
const error = ref('')

const loadContract = async () => {
  loading.value = true
  error.value = ''

  try {
    const response = await getVehicleContract(route.params.id)

    contract.value =
      response.data?.data ??
      response.data
  } catch (err) {
    error.value =
      err.response?.data?.message ||
      'Unable to load the vehicle contract.'
  } finally {
    loading.value = false
  }
}

const formatMoney = (value) => {
  return Number(value || 0).toLocaleString('en-PK', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  })
}

const vehicleName = computed(() => {
  if (!contract.value) return '—'

  return [
    contract.value.vehicle_make,
    contract.value.vehicle_model,
  ]
    .filter(Boolean)
    .join(' ') || '—'
})

const printContract = () => {
  window.print()
}

onMounted(loadContract)
</script>

<template>
  <div class="contract-detail">
    <!-- Error -->
    <div v-if="error" class="card detail-error" role="alert">
      <div>
        <strong>Couldn't load this contract.</strong>
        <p>{{ error }}</p>
      </div>
      <button type="button" class="btn" @click="loadContract">Try again</button>
    </div>

    <!-- Skeleton -->
    <div v-else-if="loading" class="detail-skeleton" aria-hidden="true">
      <div class="sk" style="height: 200px"></div>
      <div class="sk" style="height: 140px"></div>
      <div class="sk" style="height: 340px"></div>
    </div>

    <template v-else-if="contract">
      <!-- ============ HERO ============ -->
      <header class="card hero-card no-print">
        <div class="hero-top">
          <div class="hero-id">
            <span class="hero-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 17h14" /><path d="M6 17V8l2-3h8l2 3v9" /><path d="M4 11h16" /><circle cx="7" cy="17" r="2" /><circle cx="17" cy="17" r="2" />
              </svg>
            </span>
            <div class="hero-copy">
              <span class="section-kicker">Operations / Vehicle contracts</span>
              <div class="hero-title-row">
                <h1>{{ contract.contract_number || `VC-${String(contract.id).padStart(5, '0')}` }}</h1>
                <span
                  class="status capitalize"
                  :class="
                    contract.status === 'active'
                      ? 'status-success'
                      : contract.status === 'draft'
                        ? 'status-draft'
                        : 'status-danger'
                  "
                >
                  {{ contract.status || 'draft' }}
                </span>
              </div>
              <p class="hero-sub">
                <span class="hero-customer">{{ contract.customer_name || '—' }}</span>
                <span class="hero-sep" aria-hidden="true">·</span>
                <span>Rental Vehicle Agreement</span>
                <span class="hero-sep" aria-hidden="true">·</span>
                <span>{{ contract.agreement_date || '—' }}</span>
              </p>
            </div>
          </div>

          <div class="hero-actions">
            <RouterLink
              class="btn-light"
              :to="{ name: 'vehicle-contracts.edit', params: { id: contract.id } }"
            >
              Edit contract
            </RouterLink>
            <button class="btn" type="button" @click="printContract">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" /><path d="M6 9V3a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v6" /><rect width="6" height="5" x="9" y="14" rx="1" />
              </svg>
              Print agreement
            </button>
          </div>
        </div>

        <div class="hero-stats">
          <div class="hero-stat">
            <span>Customer</span>
            <strong class="stat-text">{{ contract.customer_name || '—' }}</strong>
          </div>
          <div class="hero-stat">
            <span>
              Vehicles
              <span class="itip" tabindex="0" data-tip="How many vehicles the customer rents under this contract." aria-label="Vehicles explanation">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <circle cx="12" cy="12" r="10" /><path d="M12 16v-4" /><path d="M12 8h.01" />
                </svg>
              </span>
            </span>
            <strong>{{ contract.total_vehicles || 0 }}</strong>
          </div>
          <div class="hero-stat">
            <span>
              Rental / vehicle
              <span class="itip" tabindex="0" data-tip="Base monthly rate per vehicle — excludes fuel and taxes." aria-label="Rental per vehicle explanation">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <circle cx="12" cy="12" r="10" /><path d="M12 16v-4" /><path d="M12 8h.01" />
                </svg>
              </span>
            </span>
            <strong>PKR {{ formatMoney(contract.monthly_rental_per_vehicle) }}</strong>
          </div>
          <div class="hero-stat">
            <span>
              Total / month
              <span class="itip" tabindex="0" data-tip="Vehicles × rate — the amount billed every month." aria-label="Total monthly rental explanation">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <circle cx="12" cy="12" r="10" /><path d="M12 16v-4" /><path d="M12 8h.01" />
                </svg>
              </span>
            </span>
            <strong class="is-green">PKR {{ formatMoney(contract.total_monthly_rental) }}</strong>
          </div>
        </div>
      </header>

      <!-- ============ Grid ============ -->
      <div class="detail-grid">
        <aside class="detail-side no-print">
          <!-- Contract facts -->
          <section class="card side-card">
            <h2 class="side-title">Contract</h2>
            <ul class="fact-list">
              <li>
                <span class="fact-label">Agreement date</span>
                <span class="fact-value">{{ contract.agreement_date || '—' }}</span>
              </li>
              <li>
                <span class="fact-label">End date</span>
                <span class="fact-value">{{ contract.end_date || '—' }}</span>
              </li>
              <li>
                <span class="fact-label">
                  Advance rental
                  <span class="itip" tabindex="0" data-tip="Months of rent paid upfront when the contract starts." aria-label="Advance rental explanation">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                      <circle cx="12" cy="12" r="10" /><path d="M12 16v-4" /><path d="M12 8h.01" />
                    </svg>
                  </span>
                </span>
                <span class="fact-value">{{ contract.advance_months || 1 }} month{{ contract.advance_months === 1 ? '' : 's' }}</span>
              </li>
            </ul>
          </section>

          <!-- Vendor -->
          <section class="card side-card">
            <h2 class="side-title">Vendor</h2>
            <ul class="fact-list">
              <li>
                <span class="fact-label">Company</span>
                <span class="fact-value">{{ contract.vendor_name || '—' }}</span>
              </li>
              <li v-if="contract.vendor_address">
                <span class="fact-label">Office address</span>
                <span class="fact-value address">{{ contract.vendor_address }}</span>
              </li>
            </ul>
          </section>

          <!-- Terms -->
          <section class="card side-card">
            <h2 class="side-title">
              Terms
              <span class="itip" tabindex="0" data-tip="What the rental includes and how the vehicles may be used." aria-label="Terms explanation">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <circle cx="12" cy="12" r="10" /><path d="M12 16v-4" /><path d="M12 8h.01" />
                </svg>
              </span>
            </h2>
            <ul class="fact-list">
              <li>
                <span class="fact-label">Driver</span>
                <span class="fact-value" :class="contract.service_type === 'with_driver' ? 'is-green' : 'is-muted'">
                  {{ contract.service_type === 'with_driver' ? 'Included' : 'Not included' }}
                </span>
              </li>
              <li>
                <span class="fact-label">Fuel</span>
                <span class="fact-value" :class="contract.fuel_included ? 'is-green' : 'is-muted'">
                  {{ contract.fuel_included ? 'Included' : 'Excluded' }}
                </span>
              </li>
              <li>
                <span class="fact-label">Maintenance</span>
                <span class="fact-value" :class="contract.routine_maintenance_included ? 'is-green' : 'is-muted'">
                  {{ contract.routine_maintenance_included ? 'Included' : 'Excluded' }}
                </span>
              </li>
              <li>
                <span class="fact-label">
                  Duty hours
                  <span class="itip" tabindex="0" data-tip="Hours per day covered by the base rental." aria-label="Duty hours explanation">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                      <circle cx="12" cy="12" r="10" /><path d="M12 16v-4" /><path d="M12 8h.01" />
                    </svg>
                  </span>
                </span>
                <span class="fact-value">{{ contract.duty_hours_per_day || '—' }} hrs / day</span>
              </li>
              <li>
                <span class="fact-label">Duty days</span>
                <span class="fact-value">{{ contract.duty_days_per_week || '—' }} days / week</span>
              </li>
              <li>
                <span class="fact-label">
                  Mileage limit
                  <span class="itip" tabindex="0" data-tip="Kilometres included per vehicle per month. Beyond this, the excess rate applies." aria-label="Mileage limit explanation">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                      <circle cx="12" cy="12" r="10" /><path d="M12 16v-4" /><path d="M12 8h.01" />
                    </svg>
                  </span>
                </span>
                <span class="fact-value">{{ contract.monthly_mileage_limit ? `${formatMoney(contract.monthly_mileage_limit)} KM / month` : '—' }}</span>
              </li>
              <li>
                <span class="fact-label">
                  Excess mileage
                  <span class="itip" tabindex="0" data-tip="Charged per kilometre driven over the monthly limit." aria-label="Excess mileage explanation">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                      <circle cx="12" cy="12" r="10" /><path d="M12 16v-4" /><path d="M12 8h.01" />
                    </svg>
                  </span>
                </span>
                <span class="fact-value">
                  {{ contract.excess_mileage_rate ? `PKR ${formatMoney(contract.excess_mileage_rate)} / KM` : '—' }}
                </span>
              </li>
            </ul>
          </section>
        </aside>

        <div class="detail-main">
          <!-- ===== Rental math ===== -->
          <section class="card block-card no-print">
            <header class="block-head">
              <div class="block-title">
                <span class="block-icon icon-success" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="20" height="12" x="2" y="6" rx="2" /><circle cx="12" cy="12" r="2" /><path d="M6 12h.01M18 12h.01" />
                  </svg>
                </span>
                <div>
                  <h2>Monthly rental math</h2>
                  <p class="block-hint">How the total monthly rental adds up.</p>
                </div>
              </div>
            </header>

            <div class="flow-chain">
              <div class="flow-step">
                <span class="flow-label">Vehicles</span>
                <strong>{{ contract.total_vehicles || 0 }}</strong>
              </div>
              <span class="flow-op" aria-hidden="true">×</span>
              <div class="flow-step">
                <span class="flow-label">
                  Rental / vehicle
                  <span class="itip" tabindex="0" data-tip="Base monthly rate per vehicle — excludes fuel and taxes." aria-label="Rental per vehicle explanation">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                      <circle cx="12" cy="12" r="10" /><path d="M12 16v-4" /><path d="M12 8h.01" />
                    </svg>
                  </span>
                </span>
                <strong>PKR {{ formatMoney(contract.monthly_rental_per_vehicle) }}</strong>
              </div>
              <span class="flow-op" aria-hidden="true">=</span>
              <div class="flow-step flow-final">
                <span class="flow-label">Total / month</span>
                <strong>PKR {{ formatMoney(contract.total_monthly_rental) }}</strong>
              </div>
            </div>
          </section>

          <!-- ===== Fleet ===== -->
          <section class="card block-card no-print">
            <header class="block-head">
              <div class="block-title">
                <span class="block-icon icon-info" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2" /><path d="M15 18H9" /><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.62l-3.48-4.35A1 1 0 0 0 17.52 8H14" /><circle cx="17" cy="18" r="2" /><circle cx="7" cy="18" r="2" />
                  </svg>
                </span>
                <div>
                  <h2>Contract vehicles</h2>
                  <p class="block-hint">
                    The specific vehicles assigned to this contract.
                    <span class="itip" tabindex="0" data-tip="Each vehicle carries the contract rate unless it has its own. Open Daily Reports to see its usage and mileage." aria-label="Contract vehicles explanation">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="10" /><path d="M12 16v-4" /><path d="M12 8h.01" />
                      </svg>
                    </span>
                  </p>
                </div>
              </div>
              <span v-if="contract.vehicles?.length" class="count-badge">
                {{ contract.vehicles.length }}
              </span>
            </header>

            <div v-if="contract.vehicles?.length" class="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th>Vehicle</th>
                    <th>Vehicle detail</th>
                    <th class="right">
                      Monthly rental
                      <span class="itip" tabindex="0" data-tip="Uses this vehicle's own rate if set — otherwise the contract rate." aria-label="Monthly rental explanation">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                          <circle cx="12" cy="12" r="10" /><path d="M12 16v-4" /><path d="M12 8h.01" />
                        </svg>
                      </span>
                    </th>
                    <th class="right"></th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="vehicle in contract.vehicles" :key="vehicle.id">
                    <td>
                      <span class="veh-number">{{ vehicle.vehicle_number || 'Unassigned vehicle' }}</span>
                    </td>
                    <td class="veh-detail">
                      {{ vehicle.make || contract.vehicle_make }} {{ vehicle.model || contract.vehicle_model }}
                    </td>
                    <td class="right row-amount">
                      PKR {{ formatMoney(vehicle.monthly_rental ?? contract.monthly_rental_per_vehicle) }}
                    </td>
                    <td class="right">
                      <RouterLink
                        class="btn-light btn-sm"
                        :to="{ name: 'contract-vehicles.daily-reports', params: { id: vehicle.id } }"
                      >
                        Daily reports
                      </RouterLink>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div v-else class="block-empty">
              <p>No vehicles assigned to this contract yet.</p>
            </div>
          </section>

          <!-- ===== Agreement document (printable) ===== -->
          <section class="card doc-card">
            <header class="block-head no-print">
              <div class="block-title">
                <span class="block-icon icon-neutral" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z" /><path d="M14 2v4a2 2 0 0 0 2 2h4" /><path d="M16 13H8" /><path d="M16 17H8" />
                  </svg>
                </span>
                <div>
                  <h2>Agreement preview</h2>
                  <p class="block-hint">
                    The printable contract document.
                    <span class="itip" tabindex="0" data-tip="Print outputs this document only — the summary cards above stay on screen." aria-label="Print behavior explanation">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="10" /><path d="M12 16v-4" /><path d="M12 8h.01" />
                      </svg>
                    </span>
                  </p>
                </div>
              </div>
              <button class="btn-light btn-sm" type="button" @click="printContract">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" /><path d="M6 9V3a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v6" /><rect width="6" height="5" x="9" y="14" rx="1" />
                </svg>
                Print
              </button>
            </header>

            <article class="paper">
              <header class="doc-header">
                <h1 class="doc-title">Rental Vehicle Agreement</h1>
                <p class="doc-intro">
                  This Rental Vehicle Agreement ("Agreement") is made and entered into on this
                  <strong>{{ contract.agreement_date || '________' }}</strong>.
                </p>
              </header>

              <h3 class="doc-section">Between</h3>

              <p><strong>{{ contract.vendor_name || 'Vendor Name' }}</strong></p>
              <p class="doc-muted">Office Address: {{ contract.vendor_address || 'Vendor Address' }}</p>
              <p class="doc-muted">Hereinafter referred to as the <strong>"Vendor"</strong></p>

              <h3 class="doc-section">And</h3>

              <p><strong>{{ contract.customer_name || 'Customer Name' }}</strong></p>
              <p class="doc-muted">{{ contract.customer_address || 'Customer Address' }}</p>
              <p class="doc-muted">Hereinafter referred to as the <strong>"Customer / User"</strong></p>

              <h3 class="doc-section">1. Nature of Agreement</h3>

              <p>
                This is a
                {{ contract.service_type === 'with_driver' ? 'Vehicle Rental Agreement with Driver' : 'Vehicle Rental Agreement without Driver' }}
                {{ contract.fuel_included ? 'with Fuel' : 'without Fuel' }}.
              </p>

              <h3 class="doc-section">2. Vehicle Rental Details</h3>

              <p>
                The Vendor agrees to provide the following vehicles to the Customer on a rental
                basis:
              </p>

              <ul class="doc-list">
                <li><strong>Total Vehicles:</strong> {{ contract.total_vehicles || '—' }} Units</li>
                <li><strong>Make / Model:</strong> {{ vehicleName }}</li>
                <li><strong>Model Year:</strong> {{ contract.vehicle_model_year || '—' }}</li>
                <li><strong>Vehicle Type:</strong> {{ contract.vehicle_type || '—' }}</li>
              </ul>

              <h4 class="doc-sub">Rental Breakdown</h4>

              <table class="rental-table">
                <thead>
                  <tr>
                    <th>Description</th>
                    <th>Quantity</th>
                    <th>Monthly Rental Per Vehicle</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>{{ vehicleName }} – {{ contract.vehicle_type || 'Vehicle' }}</td>
                    <td>{{ contract.total_vehicles || 0 }} Units</td>
                    <td>PKR {{ formatMoney(contract.monthly_rental_per_vehicle) }}/-</td>
                  </tr>
                </tbody>
              </table>

              <p class="doc-total">
                <strong>
                  Total Monthly Rental: PKR {{ formatMoney(contract.total_monthly_rental) }}/-
                  excluding applicable taxes and fuel.
                </strong>
              </p>

              <h3 class="doc-section">3. Scope of Rental</h3>

              <p>The vehicles shall be provided:</p>

              <ul class="doc-list">
                <li>{{ contract.service_type === 'with_driver' ? 'With driver' : 'Without driver' }}</li>
                <li>{{ contract.routine_maintenance_included ? 'With routine maintenance' : 'Without routine maintenance' }}</li>
                <li>{{ contract.fuel_included ? 'With fuel' : 'Without fuel' }}</li>
              </ul>

              <p class="doc-muted">
                The Customer shall be responsible for fuel consumption and fuel expenses.
              </p>

              <h3 class="doc-section">4. Rental Rate and Duty Hours</h3>

              <p>
                The agreed rental rate is
                <strong>
                  PKR {{ formatMoney(contract.monthly_rental_per_vehicle) }} per vehicle per month
                </strong>,
                excluding fuel and applicable taxes.
              </p>

              <p>The rental includes driver services for:</p>

              <ul class="doc-list">
                <li><strong>{{ contract.duty_hours_per_day }} hours per day</strong></li>
                <li><strong>{{ contract.duty_days_per_week }} days per week</strong></li>
              </ul>

              <h3 class="doc-section">12. Mileage Limit</h3>

              <p>
                The monthly mileage limit shall be:
                <strong>
                  {{ contract.monthly_mileage_limit || '2,500' }} KM per vehicle per month.
                </strong>
              </p>

              <p>
                Any mileage exceeding the monthly limit shall be charged at:
                <strong>
                  PKR {{ formatMoney(contract.excess_mileage_rate || 50) }} per additional KM.
                </strong>
              </p>

              <p>
                The excess mileage shall be calculated based on the vehicle's monthly
                odometer/tracking records.
              </p>

              <div class="document-note no-print">
                The complete agreement content will be rendered here using the same contract data.
                The final print layout will use this document section as the printable source.
              </div>
            </article>
          </section>
        </div>
      </div>

      <div class="detail-footer no-print">
        <RouterLink class="btn-light" :to="{ name: 'vehicle-contracts.index' }">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M19 12H5" /><path d="m12 19-7-7 7-7" />
          </svg>
          Back to contracts
        </RouterLink>
      </div>
    </template>
  </div>
</template>

<style scoped>
.contract-detail {
  display: flex;
  flex-direction: column;
  gap: 20px;
  min-width: 0;
}

/* ---------- Pure-CSS tooltip (no script imports allowed) ---------- */

.itip {
  display: inline-flex;
  position: relative;
}

.itip svg {
  color: var(--text-muted);
  cursor: help;
  height: 13px;
  transition: color 0.15s ease;
  width: 13px;
}

.itip:hover svg,
.itip:focus-visible svg {
  color: var(--accent);
}

.itip::after {
  background: #101828;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgb(16 24 40 / 18%);
  color: #fff;
  content: attr(data-tip);
  font-size: 11.5px;
  font-weight: 400;
  left: 50%;
  line-height: 1.45;
  max-width: 230px;
  opacity: 0;
  padding: 7px 10px;
  pointer-events: none;
  position: absolute;
  text-align: left;
  top: calc(100% + 7px);
  transform: translateX(-50%) translateY(-3px);
  transition: opacity 0.15s ease, transform 0.15s ease;
  white-space: normal;
  width: max-content;
  z-index: 20;
}

.itip:hover::after,
.itip:focus-visible::after {
  opacity: 1;
  transform: translateX(-50%) translateY(0);
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

.hero-icon {
  align-items: center;
  background: var(--accent-soft);
  border-radius: 12px;
  color: var(--accent);
  display: flex;
  flex: 0 0 48px;
  height: 48px;
  justify-content: center;
  width: 48px;
}
.hero-icon svg { height: 22px; width: 22px; }

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
  margin: 6px 0 0;
}
.hero-customer { color: var(--text-secondary); font-weight: 600; }
.hero-sep { color: var(--text-muted); }

.hero-actions {
  align-items: center;
  display: flex;
  flex: 0 0 auto;
  flex-wrap: wrap;
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
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.hero-stat strong.stat-text { font-size: 15px; }
.hero-stat strong.is-green { color: var(--success); }

.capitalize { text-transform: capitalize; }

/* ---------- Grid / sidebar ---------- */

.detail-grid {
  align-items: start;
  display: grid;
  gap: 20px;
  grid-template-columns: 300px minmax(0, 1fr);
}

.detail-side {
  display: flex;
  flex-direction: column;
  gap: 20px;
  position: sticky;
  top: 20px;
}

.detail-main {
  display: flex;
  flex-direction: column;
  gap: 20px;
  min-width: 0;
}

.side-card { padding: 20px; }

.side-title {
  align-items: center;
  color: var(--text-muted);
  display: flex;
  font-size: 11px;
  font-weight: 600;
  gap: 5px;
  letter-spacing: 0.08em;
  margin: 0 0 14px;
  text-transform: uppercase;
}

.fact-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
  list-style: none;
  margin: 0;
  padding: 0;
}
.fact-list li { display: flex; flex-direction: column; gap: 2px; }
.fact-label {
  align-items: center;
  color: var(--text-muted);
  display: flex;
  font-size: 11px;
  font-weight: 500;
  gap: 5px;
}
.fact-value { color: var(--text-primary); font-size: 14px; font-weight: 500; }
.fact-value.address { white-space: pre-line; }
.fact-value.is-green { color: var(--success); font-weight: 600; }
.fact-value.is-muted { color: var(--text-muted); }

/* ---------- Blocks ---------- */

.block-card { padding: 20px; }

.block-head {
  align-items: flex-start;
  display: flex;
  gap: 12px;
  justify-content: space-between;
  margin-bottom: 14px;
}

.block-title { align-items: flex-start; display: flex; gap: 12px; }
.block-title h2 { font-size: 15px; font-weight: 600; margin: 0; }
.block-hint {
  align-items: center;
  color: var(--text-muted);
  display: flex;
  flex-wrap: wrap;
  font-size: 13px;
  gap: 4px;
  margin: 2px 0 0;
}

.block-icon {
  align-items: center;
  border-radius: 9px;
  display: flex;
  flex: 0 0 32px;
  height: 32px;
  justify-content: center;
  width: 32px;
}
.block-icon svg { height: 15px; width: 15px; }
.icon-success { background: var(--success-soft); color: var(--success); }
.icon-info { background: var(--info-soft); color: var(--info); }
.icon-neutral { background: var(--surface-2); color: var(--text-secondary); }

.count-badge {
  align-items: center;
  background: var(--surface-2);
  border-radius: 999px;
  color: var(--text-secondary);
  display: inline-flex;
  font-size: 11px;
  font-weight: 600;
  height: 20px;
  justify-content: center;
  min-width: 20px;
  padding: 0 7px;
}

/* Rental math flow */
.flow-chain {
  align-items: stretch;
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.flow-step {
  background: var(--surface-2);
  border-radius: var(--radius-md);
  flex: 1 1 130px;
  padding: 14px 16px;
}

.flow-label {
  align-items: center;
  color: var(--text-muted);
  display: flex;
  font-size: 12px;
  font-weight: 500;
  gap: 6px;
  margin-bottom: 5px;
}

.flow-step strong {
  color: var(--text-primary);
  font-size: 20px;
  font-variant-numeric: tabular-nums;
  font-weight: 700;
  letter-spacing: -0.01em;
}

.flow-op {
  align-items: center;
  color: var(--text-muted);
  display: flex;
  font-size: 18px;
  font-weight: 600;
  padding: 0 2px;
}

.flow-final { background: var(--success-soft); flex: 1.4 1 150px; }
.flow-final strong { color: var(--success); }

/* Fleet table (inherits global .table-wrap styles) */
.veh-number { color: var(--text-primary); font-weight: 600; }
.veh-detail { color: var(--text-secondary); }
.row-amount { color: var(--text-primary); font-weight: 600; font-variant-numeric: tabular-nums; }

.block-empty {
  border: 1px dashed var(--border-strong);
  border-radius: var(--radius-md);
  color: var(--text-muted);
  font-size: 13px;
  padding: 18px 16px;
  text-align: center;
}
.block-empty p { margin: 0; }

/* ---------- Agreement document ---------- */

.doc-card { padding: 20px; }

.paper {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-sm);
  color: var(--text-primary);
  line-height: 1.7;
  margin: 0 auto;
  max-width: 850px;
  padding: 48px 56px;
}

.doc-header {
  border-bottom: 2px solid var(--text-primary);
  margin-bottom: 28px;
  padding-bottom: 20px;
  text-align: center;
}

.doc-title {
  font-size: 18px;
  font-weight: 700;
  letter-spacing: 0.14em;
  margin: 0 0 12px;
  text-transform: uppercase;
}

.doc-intro {
  color: var(--text-secondary);
  font-size: 13px;
  margin: 0;
}

.doc-section {
  color: var(--text-primary);
  font-size: 11.5px;
  font-weight: 700;
  letter-spacing: 0.08em;
  margin: 24px 0 8px;
  text-transform: uppercase;
}

.doc-sub {
  border-top: 1px solid var(--border);
  color: var(--text-secondary);
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.06em;
  margin: 20px 0 10px;
  padding-top: 12px;
  text-transform: uppercase;
}

.paper p {
  font-size: 13px;
  margin: 8px 0;
  text-align: justify;
}

.paper p.doc-muted { color: var(--text-secondary); }

.doc-list {
  margin: 10px 0;
  padding-left: 20px;
}
.doc-list li {
  font-size: 13px;
  margin: 4px 0;
}

.doc-total { margin-top: 12px; }

.rental-table {
  border-collapse: collapse;
  margin: 14px 0;
  width: 100%;
}

.rental-table th,
.rental-table td {
  border: 1px solid var(--border-strong);
  font-size: 12.5px;
  padding: 8px 10px;
  text-align: left;
}

.rental-table th {
  background: var(--surface-2);
  color: var(--text-secondary);
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.document-note {
  border: 1px dashed var(--border-strong);
  border-radius: var(--radius-md);
  color: var(--text-muted);
  font-size: 12px;
  margin-top: 24px;
  padding: 12px 14px;
}

/* ---------- Error / skeleton / footer ---------- */

.detail-error {
  align-items: center;
  border-color: var(--danger);
  color: var(--danger);
  display: flex;
  gap: 16px;
  justify-content: space-between;
}
.detail-error p { color: var(--text-secondary); margin: 4px 0 0; }

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
@keyframes shimmer { 100% { transform: translateX(100%); } }

.detail-footer { display: flex; }

/* ---------- Responsive ---------- */

@media (max-width: 1024px) {
  .detail-grid { grid-template-columns: 1fr; }
  .detail-side { position: static; }
  .hero-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); row-gap: 18px; }
  .hero-stat:nth-child(odd) { border-left: 0; padding-left: 0; }
}

@media (max-width: 700px) {
  .hero-top { flex-direction: column; }
  .hero-actions { width: 100%; }
  .hero-actions .btn,
  .hero-actions .btn-light { flex: 1; justify-content: center; }
  .detail-error { align-items: flex-start; flex-direction: column; }
  .flow-op { display: none; }
  .flow-step { flex-basis: calc(50% - 5px); }
  .flow-final { flex-basis: 100%; }
  .paper { padding: 20px; }
}

/* ---------- Print: the document only ---------- */

@media print {
  .no-print { display: none !important; }
  .hero-card { display: none !important; }

  .contract-detail {
    display: block;
  }

  .detail-grid,
  .detail-main {
    display: block;
  }

  .doc-card {
    border: 0;
    box-shadow: none;
    padding: 0;
  }

  .paper {
    border: 0;
    border-radius: 0;
    box-shadow: none;
    max-width: none;
    padding: 0;
  }

  .paper p,
  .doc-list li {
    font-size: 10.5pt;
  }

  .doc-title { font-size: 14pt; }
  .doc-section { font-size: 9.5pt; }
}
</style>