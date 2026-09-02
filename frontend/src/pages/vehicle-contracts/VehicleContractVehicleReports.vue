<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRoute } from 'vue-router'

import { getVehicleContract } from '@/services/vehicleContractService'

const route = useRoute()

const contract = ref(null)
const loading = ref(true)
const error = ref('')

const loadContract = async () => {
  loading.value = true
  error.value = ''

  try {
    const response = await getVehicleContract(route.params.id)
    contract.value = response.data?.data ?? response.data
  } catch (err) {
    error.value =
      err.response?.data?.message ||
      'Unable to load the vehicle contract.'
  } finally {
    loading.value = false
  }
}

const vehicle = computed(() => {
  if (!contract.value?.vehicles) return null

  return contract.value.vehicles.find(
    (v) => String(v.id) === String(route.params.vehicleId)
  )
})

const formatMoney = (value) => {
  return Number(value || 0).toLocaleString('en-PK', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  })
}

onMounted(loadContract)
</script>

<template>
  <div class="vehicle-reports-page">
    <div
      v-if="loading"
      class="state-card"
    >
      Loading...
    </div>

    <div
      v-else-if="error"
      class="state-card error"
    >
      {{ error }}
    </div>

    <template v-else-if="contract">
      <header class="page-header">
        <div class="header-left">
          <RouterLink
            class="back-link"
            :to="{ name: 'vehicle-contracts.show', params: { id: contract.id } }"
          >
            ← Contract
          </RouterLink>

          <div>
            <p class="eyebrow">
              {{ contract.contract_number || `VC-${String(contract.id).padStart(5, '0')}` }}
            </p>

            <h1>
              Daily Reports
            </h1>
          </div>
        </div>
      </header>

      <section
        v-if="vehicle"
        class="content-card"
      >
        <div class="section-heading">
          <div>
            <p class="eyebrow">Vehicle</p>
            <h2>{{ vehicle.vehicle_number || 'Unassigned vehicle' }}</h2>
          </div>
        </div>

        <div class="details-grid">
          <div class="detail-item">
            <span>Make / Model</span>
            <strong>{{ vehicle.make || contract.vehicle_make }} {{ vehicle.model || contract.vehicle_model }}</strong>
          </div>

          <div class="detail-item">
            <span>Monthly Rental</span>
            <strong>PKR {{ formatMoney(vehicle.monthly_rental ?? contract.monthly_rental_per_vehicle) }}</strong>
          </div>

          <div class="detail-item">
            <span>Status</span>
            <strong class="status">{{ vehicle.status || 'active' }}</strong>
          </div>
        </div>
      </section>

      <section
        v-else
        class="state-card error"
      >
        Vehicle not found in this contract.
      </section>

      <section class="content-card">
        <div class="section-heading">
          <div>
            <p class="eyebrow">Reports</p>
            <h2>Daily Reports</h2>
          </div>
        </div>

        <p class="empty-state">
          Daily reports for this vehicle will be listed here.
        </p>
      </section>
    </template>
  </div>
</template>

<style scoped>
.vehicle-reports-page {
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

.content-card {
  border: 1px solid var(--border-color, #e5e7eb);
  border-radius: 12px;
  background: var(--surface-color, #fff);
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

.detail-item span {
  display: block;
  margin-bottom: 0.3rem;
  color: var(--text-muted, #6b7280);
  font-size: 0.72rem;
}

.detail-item strong {
  font-size: 0.82rem;
}

.status {
  text-transform: capitalize;
}

.state-card {
  padding: 3rem;
  text-align: center;
}

.state-card.error {
  color: #b91c1c;
}

.empty-state {
  color: var(--text-muted, #6b7280);
  text-align: center;
  padding: 2rem 0;
  margin: 0;
}

@media (max-width: 700px) {
  .page-header {
    flex-direction: column;
  }

  .header-left {
    flex-direction: column;
  }

  .details-grid {
    grid-template-columns: 1fr;
  }
}
</style>
