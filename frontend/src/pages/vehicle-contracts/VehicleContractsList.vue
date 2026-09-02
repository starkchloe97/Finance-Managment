<script setup>
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'

import {
  getVehicleContracts,
  deleteVehicleContract,
} from '@/services/vehicleContractService'

const contracts = ref([])
const loading = ref(true)
const error = ref('')

const loadContracts = async () => {
  loading.value = true
  error.value = ''

  try {
    const response = await getVehicleContracts()

    contracts.value =
      response.data?.data ??
      response.data ??
      []
  } catch (err) {
    error.value =
      err.response?.data?.message ||
      'Unable to load vehicle contracts.'
  } finally {
    loading.value = false
  }
}

const removeContract = async (contract) => {
  const confirmed = window.confirm(
    `Delete contract ${contract.contract_number || ''}?`
  )

  if (!confirmed) return

  try {
    await deleteVehicleContract(contract.id)

    contracts.value = contracts.value.filter(
      item => item.id !== contract.id
    )
  } catch (err) {
    error.value =
      err.response?.data?.message ||
      'Unable to delete the contract.'
  }
}

const formatMoney = (value) => {
  return Number(value || 0).toLocaleString('en-PK', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  })
}

const statusClass = (status) => {
  return `status-${String(status || 'draft').toLowerCase()}`
}

onMounted(loadContracts)
</script>

<template>
  <div class="contracts-page">

    <header class="page-head">
      <div>
        <p class="eyebrow">
          Operations / Vehicle Contracts
        </p>

        <h1>Vehicle Contracts</h1>

        <p>
          Manage rental vehicle agreements and contract documents.
        </p>
      </div>

      <RouterLink
        class="btn primary"
        :to="{ name: 'vehicle-contracts.create' }"
      >
        + New Contract
      </RouterLink>
    </header>

    <div
      v-if="error"
      class="page-error"
    >
      {{ error }}
    </div>

    <section class="contracts-card">

      <div
        v-if="loading"
        class="empty-state"
      >
        Loading contracts...
      </div>

      <div
        v-else-if="!contracts.length"
        class="empty-state"
      >
        <h3>No vehicle contracts yet</h3>

        <p>
          Create your first rental vehicle agreement.
        </p>

        <RouterLink
          class="btn primary"
          :to="{ name: 'vehicle-contracts.create' }"
        >
          Create Contract
        </RouterLink>
      </div>

      <div
        v-else
        class="table-wrapper"
      >
        <table>
          <thead>
            <tr>
              <th>Contract</th>
              <th>Customer</th>
              <th>Vehicle</th>
              <th>Vehicles</th>
              <th>Monthly Rental</th>
              <th>Validity</th>
              <th>Status</th>
              <th class="actions-column"></th>
            </tr>
          </thead>

          <tbody>
            <tr
              v-for="contract in contracts"
              :key="contract.id"
            >
              <td>
                <RouterLink
                  class="contract-number"
                  :to="{
                    name: 'vehicle-contracts.show',
                    params: { id: contract.id },
                  }"
                >
                  {{ contract.contract_number || `VC-${String(contract.id).padStart(5, '0')}` }}
                </RouterLink>

                <span class="muted">
                  {{ contract.agreement_date || '—' }}
                </span>
              </td>

              <td>
                <strong>
                  {{ contract.customer_name || '—' }}
                </strong>
              </td>

              <td>
                <strong>
                  {{
                    [
                      contract.vehicle_make,
                      contract.vehicle_model,
                    ]
                      .filter(Boolean)
                      .join(' ') || '—'
                  }}
                </strong>

                <span class="muted">
                  {{ contract.vehicle_type || '' }}
                </span>
              </td>

              <td>
                {{ contract.total_vehicles || 0 }}
              </td>

              <td>
                <strong>
                  PKR
                  {{
                    formatMoney(
                      contract.total_monthly_rental
                    )
                  }}
                </strong>
              </td>

              <td>
                <span class="muted">
                  {{ contract.end_date || '—' }}
                </span>
              </td>

              <td>
                <span
                  class="status-badge"
                  :class="statusClass(contract.status)"
                >
                  {{ contract.status || 'draft' }}
                </span>
              </td>

              <td>
                <div class="row-actions">

                  <RouterLink
                    class="icon-action"
                    :to="{
                      name: 'vehicle-contracts.show',
                      params: { id: contract.id },
                    }"
                    title="View contract"
                    aria-label="View contract"
                  >
                    View
                  </RouterLink>

                  <RouterLink
                    class="icon-action"
                    :to="{
                      name: 'vehicle-contracts.edit',
                      params: { id: contract.id },
                    }"
                    title="Edit contract"
                    aria-label="Edit contract"
                  >
                    Edit
                  </RouterLink>

                  <button
                    class="icon-action danger"
                    type="button"
                    title="Delete contract"
                    aria-label="Delete contract"
                    @click="removeContract(contract)"
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
  </div>
</template>

<style scoped>
.contracts-page {
  min-width: 0;
}

.page-head {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
  margin-bottom: 1.25rem;
}

.page-error {
  margin-bottom: 1rem;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  background: #fee2e2;
  color: #991b1b;
}

.contracts-card {
  border: 1px solid var(--border-color, #e5e7eb);
  border-radius: 12px;
  background: var(--surface-color, #fff);
  overflow: hidden;
}

.table-wrapper {
  width: 100%;
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
}

th,
td {
  padding: 0.8rem 1rem;
  border-bottom: 1px solid var(--border-color, #e5e7eb);
  text-align: left;
  vertical-align: middle;
  white-space: nowrap;
}

th {
  font-size: 0.72rem;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--text-muted, #6b7280);
  background: var(--surface-muted, #f9fafb);
}

td {
  font-size: 0.82rem;
}

tbody tr:last-child td {
  border-bottom: 0;
}

.contract-number {
  display: block;
  font-weight: 700;
  text-decoration: none;
}

.muted {
  display: block;
  margin-top: 0.15rem;
  font-size: 0.72rem;
  color: var(--text-muted, #6b7280);
}

.status-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.25rem 0.55rem;
  border-radius: 999px;
  font-size: 0.7rem;
  font-weight: 700;
  text-transform: capitalize;
}

.status-draft {
  background: #fef3c7;
  color: #92400e;
}

.status-active {
  background: #dcfce7;
  color: #166534;
}

.status-expired {
  background: #e5e7eb;
  color: #374151;
}

.status-terminated {
  background: #fee2e2;
  color: #991b1b;
}

.row-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.35rem;
}

.icon-action {
  border: 0;
  background: transparent;
  padding: 0.3rem;
  cursor: pointer;
  font: inherit;
  font-size: 0.75rem;
  text-decoration: none;
}

.icon-action.danger {
  color: #dc2626;
}

.empty-state {
  padding: 4rem 2rem;
  text-align: center;
}

.empty-state h3 {
  margin: 0 0 0.4rem;
}

.empty-state p {
  margin: 0 0 1rem;
  color: var(--text-muted, #6b7280);
}

@media (max-width: 900px) {
  .page-head {
    flex-direction: column;
  }
}
</style>