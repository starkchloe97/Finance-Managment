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
  <div class="contract-show-page">

    <!-- Loading -->
    <div
      v-if="loading"
      class="state-card"
    >
      Loading contract...
    </div>

    <!-- Error -->
    <div
      v-else-if="error"
      class="state-card error"
    >
      {{ error }}
    </div>

    <!-- Contract -->
    <template v-else-if="contract">

      <!-- Header -->
      <header class="page-header no-print">

        <div class="header-left">

          <RouterLink
            class="back-link"
            :to="{ name: 'vehicle-contracts.index' }"
          >
            ← Contracts
          </RouterLink>

          <div>
            <p class="eyebrow">
              Vehicle Contract
            </p>

            <h1>
              {{
                contract.contract_number ||
                `VC-${String(contract.id).padStart(5, '0')}`
              }}
            </h1>

            <p class="subtitle">
              Rental Vehicle Agreement
            </p>
          </div>

        </div>

        <div class="header-actions">

          <RouterLink
            class="btn secondary"
            :to="{
              name: 'vehicle-contracts.edit',
              params: { id: contract.id },
            }"
          >
            Edit
          </RouterLink>

          <button
            class="btn primary"
            type="button"
            @click="printContract"
          >
            Print Agreement
          </button>

        </div>

      </header>


      <!-- Contract Summary -->
      <section class="summary-grid">

        <div class="summary-card">

          <span class="label">
            Customer
          </span>

          <strong>
            {{ contract.customer_name || '—' }}
          </strong>

        </div>

        <div class="summary-card">

          <span class="label">
            Agreement Date
          </span>

          <strong>
            {{ contract.agreement_date || '—' }}
          </strong>

        </div>

        <div class="summary-card">

          <span class="label">
            Total Vehicles
          </span>

          <strong>
            {{ contract.total_vehicles || 0 }}
          </strong>

        </div>

        <div class="summary-card">

          <span class="label">
            Status
          </span>

          <strong
            class="status"
            :class="`status-${contract.status || 'draft'}`"
          >
            {{ contract.status || 'draft' }}
          </strong>

        </div>

      </section>


      <!-- Vehicle Rental -->
      <section class="content-card">

        <div class="section-heading">

          <div>
            <p class="eyebrow">
              Rental Details
            </p>

            <h2>
              Vehicle Rental
            </h2>
          </div>

        </div>

        <div class="details-grid">

          <div class="detail-item">
            <span>Make / Model</span>
            <strong>{{ vehicleName }}</strong>
          </div>

          <div class="detail-item">
            <span>Model Year</span>
            <strong>{{ contract.vehicle_model_year || '—' }}</strong>

          </div>

          <div class="detail-item">
            <span>Vehicle Type</span>
            <strong>
              {{ contract.vehicle_type || '—' }}
            </strong>
          </div>

          <div class="detail-item">
            <span>Total Vehicles</span>
            <strong>
              {{ contract.total_vehicles || 0 }}
            </strong>
          </div>

          <div class="detail-item">
            <span>Rental / Vehicle</span>
            <strong>
              PKR
              {{ formatMoney(contract.monthly_rental_per_vehicle) }}
            </strong>
          </div>

          <div class="detail-item highlight">
            <span>Total Monthly Rental</span>
            <strong>
              PKR
              {{ formatMoney(contract.total_monthly_rental) }}
            </strong>
          </div>

        </div>

      </section>


      <!-- Contract Vehicles -->
      <section class="content-card">

        <div class="section-header">
          <div>
            <span class="eyebrow">Rental Fleet</span>
            <h2>Contract Vehicles</h2>
          </div>

          <span class="count">
            {{ contract.vehicles?.length || 0 }} Vehicles
          </span>
        </div>

        <div
          v-if="contract.vehicles?.length"
          class="vehicle-list"
        >
          <div
            v-for="vehicle in contract.vehicles"
            :key="vehicle.id"
            class="vehicle-row"
          >
            <div>
              <strong>
                {{ vehicle.vehicle_number || 'Unassigned vehicle' }}
              </strong>

              <span>
                {{ vehicle.make || contract.vehicle_make }}
                {{ vehicle.model || contract.vehicle_model }}
              </span>
            </div>

            <div>
              <span>
                PKR
                {{ formatMoney(vehicle.monthly_rental ?? contract.monthly_rental_per_vehicle) }}
              </span>
            </div>

            <RouterLink
              class="btn secondary"
              :to="{
                name: 'vehicle-contracts.vehicle-reports',
                params: {
                  id: contract.id,
                  vehicleId: vehicle.id,
                },
              }"
            >
              Daily Reports
            </RouterLink>
          </div>
        </div>

      </section>


      <!-- Agreement Terms -->
      <section class="content-card">

        <div class="section-heading">

          <div>
            <p class="eyebrow">
              Commercial Terms
            </p>

            <h2>
              Agreement Terms
            </h2>
          </div>

        </div>

        <div class="details-grid">

          <div class="detail-item">
  <span>Driver</span>
  <strong>
    {{ contract.service_type === 'with_driver' ? 'Included' : 'Not Included' }}
  </strong>
</div>

<div class="detail-item">
  <span>Fuel</span>
  <strong>
    {{ contract.fuel_included ? 'Included' : 'Excluded' }}
  </strong>
</div>

<div class="detail-item">
  <span>Duty Hours</span>
  <strong>
    {{ contract.duty_hours_per_day || '—' }}
  </strong>
</div>

<div class="detail-item">
  <span>Duty Days</span>
  <strong>
    {{ contract.duty_days_per_week || '—' }}
  </strong>
</div>

<div class="detail-item">
  <span>Mileage Limit</span>
  <strong>
    {{ contract.monthly_mileage_limit || '—' }}
    KM / vehicle / month
  </strong>
</div>



          <div class="detail-item">
            <span>Excess Mileage</span>
            <strong>
              PKR
              {{ formatMoney(contract.excess_mileage_rate) }}
              / KM
            </strong>
          </div>

          <div class="detail-item">
            <span>Advance Rental</span>
            <strong>
              {{ contract.advance_months || 1 }}
              month
            </strong>
          </div>

          <div class="detail-item">
            <span>End Date</span>
            <strong>
              {{ contract.end_date || '—' }}
            </strong>
          </div>

        </div>

      </section>


      <!-- Agreement Preview -->
      <section class="content-card agreement-preview">

        <div class="section-heading no-print">

          <div>
            <p class="eyebrow">
              Document
            </p>

            <h2>
              Agreement Preview
            </h2>
          </div>

        </div>

        <article class="agreement-document">

          <header class="document-header">

            <h1>
              RENTAL VEHICLE AGREEMENT
            </h1>

            <p>
              This Rental Vehicle Agreement ("Agreement") is made
              and entered into on this
              <strong>
                {{ contract.agreement_date || '________' }}
              </strong>.
            </p>

          </header>


          <h3>
            BETWEEN
          </h3>

          <p>
            <strong>
              {{ contract.vendor_name || 'Vendor Name' }}
            </strong>
          </p>

          <p>
            Office Address: {{ contract.vendor_address || 'Vendor Address' }}
          </p>

          <p>
            Hereinafter referred to as the
            <strong>"Vendor"</strong>
          </p>


          <h3>
            AND
          </h3>

          <p>
            <strong>
              {{ contract.customer_name || 'Customer Name' }}
            </strong>
          </p>

          <p>
            {{ contract.customer_address || 'Customer Address' }}
          </p>

          <p>
            Hereinafter referred to as the
            <strong>"Customer / User"</strong>
          </p>


          <h3>
            1. NATURE OF AGREEMENT
          </h3>

          <p>
  This is a {{ contract.service_type === 'with_driver' ? 'Vehicle Rental Agreement with Driver' : 'Vehicle Rental Agreement without Driver' }}
  {{ contract.fuel_included ? 'with Fuel' : 'without Fuel' }}.
</p>

          <h3>
            2. VEHICLE RENTAL DETAILS
          </h3>

          <p>
            The Vendor agrees to provide the following vehicles
            to the Customer on a rental basis:
          </p>

          <ul>
            <li>
              <strong>Total Vehicles:</strong>
              {{ contract.total_vehicles || '—' }} Units
            </li>

            <li>
              <strong>Make / Model:</strong>
              {{ vehicleName }}
            </li>

            <li>
              <strong>Model Year:</strong>
              {{ contract.vehicle_model_year || '—' }}
            </li>

            <li>
              <strong>Vehicle Type:</strong>
              {{ contract.vehicle_type || '—' }}
            </li>
          </ul>


          <h3>
            Rental Breakdown
          </h3>

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
                <td>
                  {{ vehicleName }}
                  –
                  {{ contract.vehicle_type || 'Vehicle' }}
                </td>

                <td>
                  {{ contract.total_vehicles || 0 }} Units
                </td>

                <td>
                  PKR
                  {{ formatMoney(contract.monthly_rental_per_vehicle) }}
                  /-
                </td>
              </tr>
            </tbody>

          </table>

          <p>
            <strong>
              Total Monthly Rental:
              PKR {{ formatMoney(contract.total_monthly_rental) }}/-
              excluding applicable taxes and fuel.
            </strong>
          </p>


          <h3>
            3. SCOPE OF RENTAL
          </h3>

          <p>
            The vehicles shall be provided:
          </p>

          <ul>
  <li>
    {{ contract.service_type === 'with_driver' ? 'With driver' : 'Without driver' }}
  </li>
  <li>
    {{ contract.routine_maintenance_included ? 'With routine maintenance' : 'Without routine maintenance' }}
  </li>
  <li>
    {{ contract.fuel_included ? 'With fuel' : 'Without fuel' }}
  </li>
</ul>

          <p>
            The Customer shall be responsible for fuel consumption
            and fuel expenses.
          </p>


          <h3>
            4. RENTAL RATE AND DUTY HOURS
          </h3>

          <p>
            The agreed rental rate is
            <strong>
              PKR
              {{ formatMoney(contract.monthly_rental_per_vehicle) }}
              per vehicle per month
            </strong>,
            excluding fuel and applicable taxes.
          </p>

          <p>
            The rental includes driver services for:
          </p>

<ul>
  <li><strong>{{ contract.duty_hours_per_day }} hours per day</strong></li>
  <li><strong>{{ contract.duty_days_per_week }} days per week</strong></li>
</ul>


          <h3>
            12. MILEAGE LIMIT
          </h3>

          <p>
            The monthly mileage limit shall be:
            <strong>
{{ contract.monthly_mileage_limit || '2,500' }}             
 KM per vehicle per month.
            </strong>
          </p>

          <p>
            Any mileage exceeding the monthly limit shall be
            charged at:
            <strong>
              PKR
              {{ formatMoney(contract.excess_mileage_rate || 50) }}
              per additional KM.
            </strong>
          </p>

          <p>
            The excess mileage shall be calculated based on the
            vehicle's monthly odometer/tracking records.
          </p>


          <div class="document-note">
            The complete agreement content will be rendered here
            using the same contract data. The final print layout
            will use this document section as the printable source.
          </div>

        </article>

      </section>

    </template>

  </div>
</template>

<style scoped>
.contract-show-page {
  min-width: 0;
}

.page-header {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1.25rem;
}

.header-left {
  display: flex;
  gap: 1rem;
  align-items: flex-start;
}

.back-link {
  font-size: 0.8rem;
  text-decoration: none;
  margin-top: 0.25rem;
}

.subtitle {
  margin: 0.25rem 0 0;
  color: var(--text-muted, #6b7280);
}

.header-actions {
  display: flex;
  gap: 0.5rem;
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

.summary-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 0.75rem;
  margin-bottom: 0.75rem;
}

.summary-card,
.content-card {
  border: 1px solid var(--border-color, #e5e7eb);
  border-radius: 12px;
  background: var(--surface-color, #fff);
}

.summary-card {
  padding: 1rem;
}

.summary-card .label,
.detail-item span {
  display: block;
  margin-bottom: 0.3rem;
  color: var(--text-muted, #6b7280);
  font-size: 0.72rem;
}

.summary-card strong {
  font-size: 0.9rem;
}

.content-card {
  padding: 1.25rem;
  margin-bottom: 0.75rem;
}

.section-heading {
  margin-bottom: 1rem;
}

.section-heading h2 {
  margin: 0;
  font-size: 1rem;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 1rem;
  margin-bottom: 1rem;
}

.section-header h2 {
  margin: 0;
  font-size: 1rem;
}

.count {
  color: var(--text-muted, #6b7280);
  font-size: 0.78rem;
  font-weight: 700;
  white-space: nowrap;
}

.vehicle-list {
  display: grid;
  gap: 0.6rem;
}

.vehicle-row {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto auto;
  align-items: center;
  gap: 1rem;
  padding: 0.8rem;
  border-radius: 8px;
  background: var(--surface-muted, #f9fafb);
}

.vehicle-row strong,
.vehicle-row span {
  display: block;
}

.vehicle-row strong {
  margin-bottom: 0.2rem;
  font-size: 0.82rem;
}

.vehicle-row span {
  color: var(--text-muted, #6b7280);
  font-size: 0.78rem;
}

.vehicle-row .btn {
  white-space: nowrap;
}

.eyebrow {
  margin: 0 0 0.25rem;
  color: var(--text-muted, #6b7280);
  font-size: 0.68rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.details-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.75rem;
}

.detail-item {
  padding: 0.75rem;
  border-radius: 8px;
  background: var(--surface-muted, #f9fafb);
}

.detail-item strong {
  font-size: 0.82rem;
}

.detail-item.highlight {
  border: 1px solid var(--primary, #111827);
}

.status {
  text-transform: capitalize;
}

.status-active {
  color: #15803d;
}

.status-draft {
  color: #b45309;
}

.status-expired,
.status-terminated {
  color: #dc2626;
}

.agreement-document {
  max-width: 850px;
  margin: auto;
  padding: 2.5rem;
  border: 1px solid #ddd;
  background: white;
  color: #111;
  line-height: 1.65;
}

.document-header {
  text-align: center;
  margin-bottom: 2rem;
}

.document-header h1 {
  margin-bottom: 1rem;
  font-size: 1.4rem;
}

.agreement-document h3 {
  margin-top: 1.5rem;
  font-size: 0.95rem;
}

.agreement-document p,
.agreement-document li {
  font-size: 0.84rem;
}

.rental-table {
  width: 100%;
  border-collapse: collapse;
  margin: 1rem 0;
}

.rental-table th,
.rental-table td {
  border: 1px solid #bbb;
  padding: 0.55rem;
  font-size: 0.78rem;
  text-align: left;
}

.rental-table th {
  background: #f5f5f5;
}

.document-note {
  margin-top: 2rem;
  padding: 1rem;
  border: 1px dashed #aaa;
  font-size: 0.78rem;
  color: #666;
}

.state-card {
  padding: 3rem;
  text-align: center;
}

.state-card.error {
  color: #b91c1c;
}

@media (max-width: 1000px) {
  .summary-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .details-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 700px) {
  .page-header {
    flex-direction: column;
  }

  .header-left {
    flex-direction: column;
  }

  .summary-grid,
  .details-grid {
    grid-template-columns: 1fr;
  }

  .section-header {
    align-items: flex-start;
    flex-direction: column;
  }

  .vehicle-row {
    grid-template-columns: 1fr;
  }

  .agreement-document {
    padding: 1rem;
  }
}

@media print {
  .no-print {
    display: none !important;
  }

  .contract-show-page {
    margin: 0;
  }

  .content-card {
    border: 0;
    padding: 0;
  }

  .agreement-preview {
    margin: 0;
  }

  .agreement-document {
    max-width: none;
    border: 0;
    padding: 0;
  }
}
</style>