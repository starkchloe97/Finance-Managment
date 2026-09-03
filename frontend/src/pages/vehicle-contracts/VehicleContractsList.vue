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

    <!-- Header -->
    <header class="page-head">
      <div>
        <p class="eyebrow">
          Operations / Vehicle Contracts
        </p>

        <h1>
          Vehicle Contracts

          <span
            v-if="!loading && contracts.length"
            class="count-chip"
          >
            {{ contracts.length }}
          </span>
        </h1>

        <p class="page-sub">
          Manage rental vehicle agreements and contract documents.
        </p>
      </div>

      <RouterLink
        class="btn primary btn-new"
        :to="{ name: 'vehicle-contracts.create' }"
      >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true">
          <path d="M12 5v14M5 12h14" />
        </svg>
        New Contract
      </RouterLink>
    </header>


    <!-- Contextual error banner (e.g. a delete failed while rows are visible) -->
    <div
      v-if="error && contracts.length"
      class="error-banner"
      role="alert"
    >
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 20h16a2 2 0 0 0 1.73-2Z" />
        <path d="M12 9v4" />
        <path d="M12 17h.01" />
      </svg>

      <span>{{ error }}</span>

      <button
        type="button"
        class="banner-retry"
        @click="loadContracts"
      >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M21 12a9 9 0 1 1-2.64-6.36L21 8" />
          <path d="M21 3v5h-5" />
        </svg>
        Reload
      </button>
    </div>


    <section class="contracts-card">

      <!-- Thin progress bar while refreshing existing rows -->
      <div
        v-if="loading && contracts.length"
        class="progress-line"
        aria-hidden="true"
      >
        <span></span>
      </div>


      <!-- Load failure -->
      <div
        v-if="error && !contracts.length"
        class="state-panel"
        role="alert"
      >
        <div class="state-icon state-icon-danger">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 20h16a2 2 0 0 0 1.73-2Z" />
            <path d="M12 9v4" />
            <path d="M12 17h.01" />
          </svg>
        </div>

        <h2>Couldn't load contracts</h2>

        <p>{{ error }}</p>

        <button
          type="button"
          class="retry-btn"
          @click="loadContracts"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M21 12a9 9 0 1 1-2.64-6.36L21 8" />
            <path d="M21 3v5h-5" />
          </svg>
          Try again
        </button>
      </div>


      <!-- Empty -->
      <div
        v-else-if="!loading && !contracts.length"
        class="state-panel"
      >
        <div class="state-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z" />
            <path d="M14 2v6h6" />
            <path d="M16 13H8" />
            <path d="M16 17H8" />
            <path d="M10 9H8" />
          </svg>
        </div>

        <h2>No vehicle contracts yet</h2>

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


      <!-- Table / skeleton -->
      <div
        v-else
        class="table-wrapper"
        :aria-busy="loading"
      >
        <span v-if="loading" class="sr-only">
          Loading contracts…
        </span>

        <table>
          <caption class="sr-only">
            Vehicle rental contracts
          </caption>

          <thead>
            <tr>
              <th class="col-contract">Contract</th>
              <th class="col-customer">Customer</th>
              <th class="col-vehicle">Vehicle</th>
              <th class="col-vehicles">Vehicles</th>
              <th class="col-money">Monthly rental</th>
              <th class="col-validity">Valid until</th>
              <th class="col-status">Status</th>
              <th class="actions-column">
                <span class="sr-only">Actions</span>
              </th>
            </tr>
          </thead>


          <!-- Data rows -->
          <tbody
            v-if="contracts.length"
            :class="{ 'is-refreshing': loading }"
          >
            <tr
              v-for="contract in contracts"
              :key="contract.id"
            >
              <td class="col-contract">
                <RouterLink
                  class="contract-link"
                  :to="{
                    name: 'vehicle-contracts.show',
                    params: { id: contract.id },
                  }"
                >
                  {{ contract.contract_number || `VC-${String(contract.id).padStart(5, '0')}` }}
                </RouterLink>

                <span class="muted">
                  {{
                    contract.agreement_date
                      ? new Date(contract.agreement_date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
                      : '—'
                  }}
                </span>
              </td>


              <td class="col-customer">
                <div class="customer-cell">
                  <span class="monogram" aria-hidden="true">
                    {{ (contract.customer_name || '·').trim().split(/\s+/).slice(0, 2).map(w => w[0]).join('').toUpperCase() }}
                  </span>

                  <strong>
                    {{ contract.customer_name || '—' }}
                  </strong>
                </div>
              </td>


              <td class="col-vehicle">
                <strong class="vehicle-name">
                  {{
                    [contract.vehicle_make, contract.vehicle_model]
                      .filter(Boolean)
                      .join(' ') || '—'
                  }}
                </strong>

                <span class="muted">
                  {{ contract.vehicle_type || '' }}
                </span>
              </td>


              <td class="col-vehicles">
                <span class="count-pill">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M5 18H3c-.6 0-1-.4-1-1V7c0-.6.4-1 1-1h10c.6 0 1 .4 1 1v11" />
                    <path d="M14 9h4l4 4v4c0 .6-.4 1-1 1h-2" />
                    <circle cx="7.5" cy="17.5" r="2.5" />
                    <circle cx="17.5" cy="17.5" r="2.5" />
                  </svg>

                  {{ contract.total_vehicles || 0 }}
                </span>
              </td>


              <td class="col-money cell-money">
                <span class="money-currency">PKR</span>
                <strong>
                  {{ formatMoney(contract.total_monthly_rental) }}
                </strong>
              </td>


              <td class="col-validity">
                <span class="muted">
                  {{
                    contract.end_date
                      ? new Date(contract.end_date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
                      : '—'
                  }}
                </span>
              </td>


              <td class="col-status">
                <span
                  class="status-badge"
                  :class="statusClass(contract.status)"
                >
                  {{ contract.status || 'draft' }}
                </span>
              </td>


              <td class="actions-cell">
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
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                      <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" />
                      <circle cx="12" cy="12" r="3" />
                    </svg>
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
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                      <path d="M17 3a2.83 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z" />
                    </svg>
                  </RouterLink>

                  <button
                    class="icon-action danger"
                    type="button"
                    title="Delete contract"
                    aria-label="Delete contract"
                    @click="removeContract(contract)"
                  >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                      <path d="M3 6h18" />
                      <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" />
                      <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                      <path d="M10 11v6M14 11v6" />
                    </svg>
                  </button>

                </div>
              </td>

            </tr>
          </tbody>


          <!-- Skeleton rows (first load) -->
          <tbody
            v-else
            class="skeleton-body"
            aria-hidden="true"
          >
            <tr v-for="i in 7" :key="`sk-${i}`">
              <td><span class="skeleton w-70"></span></td>

              <td>
                <div class="customer-cell">
                  <span class="skeleton skel-av"></span>
                  <span class="skeleton w-60"></span>
                </div>
              </td>

              <td><span class="skeleton w-70"></span></td>

              <td class="col-vehicles">
                <span class="skeleton w-30"></span>
              </td>

              <td class="col-money">
                <span class="skeleton w-60"></span>
              </td>

              <td class="col-validity">
                <span class="skeleton w-55"></span>
              </td>

              <td><span class="skeleton skel-badge"></span></td>

              <td><span class="skeleton skel-actions"></span></td>
            </tr>
          </tbody>

        </table>


        <!-- Active commitments summary -->
        <div
          v-if="!loading && contracts.length"
          class="table-footer"
        >
          <span class="footer-stats" title="Counts active contracts only">
            <strong>
              {{ contracts.filter(c => (c.status || '').toLowerCase() === 'active').length }}
            </strong>
            active

            <span class="dot-sep" aria-hidden="true">·</span>

            <strong>
              {{ contracts.filter(c => (c.status || '').toLowerCase() === 'active').reduce((s, c) => s + Number(c.total_vehicles || 0), 0) }}
            </strong>
            vehicles
          </span>

          <span class="footer-total">
            <span class="footer-label">Monthly commitment</span>

            <strong>
              <span class="money-currency">PKR</span>
              {{
                formatMoney(
                  contracts
                    .filter(c => (c.status || '').toLowerCase() === 'active')
                    .reduce((s, c) => s + Number(c.total_monthly_rental || 0), 0)
                )
              }}
            </strong>
          </span>
        </div>

      </div>

    </section>

  </div>
</template>

<style scoped>
.contracts-page {
  display: flex;
  flex-direction: column;
  gap: 18px;
  min-width: 0;
}

/* ---------- header ---------- */

.page-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
}

.page-sub {
  margin: 6px 0 0;
  color: var(--text-muted, #6b7280);
  font-size: 14px;
}

.count-chip {
  display: inline-flex;
  align-items: center;
  vertical-align: middle;
  margin-left: 10px;
  padding: 3px 10px;
  border: 1px solid var(--border-color, #e5e7eb);
  border-radius: 999px;
  background: var(--surface-muted, #f9fafb);
  font-size: 12px;
  font-weight: 600;
  color: var(--text-muted, #6b7280);
}

.btn-new {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  text-decoration: none;
}

.btn-new svg {
  width: 14px;
  height: 14px;
}

/* ---------- error banner ---------- */

.error-banner {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 14px;
  border: 1px solid var(--danger, #fecaca);
  border-radius: 10px;
  background: var(--danger-soft, #fee2e2);
  color: var(--danger, #991b1b);
  font-size: 13px;
  font-weight: 500;
}

.error-banner svg {
  width: 16px;
  height: 16px;
  flex: 0 0 auto;
}

.error-banner span {
  min-width: 0;
}

.banner-retry {
  margin-left: auto;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  border: 1px solid currentColor;
  border-radius: 7px;
  background: transparent;
  padding: 4px 10px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: opacity .15s ease;
}

.banner-retry svg {
  width: 12px;
  height: 12px;
}

.banner-retry:hover {
  opacity: .8;
}

/* ---------- card ---------- */

.contracts-card {
  position: relative;
  border: 1px solid var(--border-color, #e5e7eb);
  border-radius: 12px;
  background: var(--surface, #fff);
  overflow: hidden;
}

.progress-line {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 2px;
  overflow: hidden;
  z-index: 3;
}

.progress-line span {
  position: absolute;
  top: 0;
  left: 0;
  width: 40%;
  height: 100%;
  border-radius: 2px;
  background: var(--accent, #4f46e5);
  animation: progress-slide 1.1s ease-in-out infinite;
}

/* ---------- state panels (error / empty) ---------- */

.state-panel {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  padding: 56px 24px;
}

.state-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 64px;
  height: 64px;
  margin-bottom: 14px;
  border: 1px solid var(--border-color, #e5e7eb);
  border-radius: 18px;
  background: var(--surface-muted, #f9fafb);
  color: var(--text-muted, #6b7280);
}

.state-icon svg {
  width: 28px;
  height: 28px;
}

.state-icon-danger {
  border-color: transparent;
  background: var(--danger-soft, #fee2e2);
  color: var(--danger, #991b1b);
}

.state-panel h2 {
  margin: 0 0 8px;
  font-size: 17px;
}

.state-panel p {
  margin: 0 0 20px;
  max-width: 380px;
  color: var(--text-muted, #6b7280);
  font-size: 13.5px;
}

.retry-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  border: 1px solid var(--border-color, #e5e7eb);
  border-radius: 9px;
  background: var(--surface, #fff);
  padding: 8px 14px;
  font-size: 13px;
  font-weight: 600;
  color: inherit;
  cursor: pointer;
  transition: background .15s ease;
}

.retry-btn svg {
  width: 13px;
  height: 13px;
}

.retry-btn:hover {
  background: var(--surface-muted, #f9fafb);
}

.retry-btn:focus-visible {
  outline: 2px solid currentColor;
  outline-offset: 2px;
}

/* ---------- table ---------- */

.table-wrapper {
  width: 100%;
  overflow-x: auto;
}

table {
  width: 100%;
  min-width: 920px;
  border-collapse: collapse;
}

th,
td {
  padding: 12px 16px;
  border-bottom: 1px solid var(--border-color, #e5e7eb);
  text-align: left;
  vertical-align: middle;
  white-space: nowrap;
}

th {
  position: sticky;
  top: 0;
  z-index: 2;
  background: var(--surface, #fff);
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .06em;
  color: var(--text-muted, #6b7280);
}

td {
  font-size: 13px;
}

tbody tr:last-child td {
  border-bottom: 0;
}

tbody tr {
  transition: background .12s ease;
}

tbody tr:hover {
  background: var(--surface-muted, #f9fafb);
}

tbody.is-refreshing {
  opacity: .55;
  pointer-events: none;
  transition: opacity .15s ease;
}

/* contract cell */

.col-contract {
  min-width: 150px;
}

.contract-link {
  display: block;
  font-weight: 700;
  color: var(--text-primary, #111827);
  text-decoration: none;
  transition: color .15s ease;
}

.contract-link:hover {
  color: var(--accent, #4f46e5);
  text-decoration: underline;
}

.contract-link:focus-visible {
  outline: 2px solid currentColor;
  outline-offset: 2px;
  border-radius: 4px;
}

.muted {
  display: block;
  margin-top: 2px;
  font-size: 11.5px;
  color: var(--text-muted, #6b7280);
}

/* customer cell */

.col-customer {
  min-width: 180px;
}

.customer-cell {
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 0;
}

.monogram {
  flex: 0 0 auto;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 34px;
  height: 34px;
  border: 1px solid var(--border-color, #e5e7eb);
  border-radius: 9px;
  background: var(--surface-muted, #f9fafb);
  color: var(--text-muted, #6b7280);
  font-size: 12px;
  font-weight: 700;
  letter-spacing: .02em;
}

.customer-cell strong {
  font-size: 13px;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* vehicle cell */

.col-vehicle {
  min-width: 170px;
}

.vehicle-name {
  font-size: 13px;
  font-weight: 600;
}

/* vehicles count */

.col-vehicles {
  min-width: 90px;
}

.count-pill {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 3px 10px;
  border: 1px solid var(--border-color, #e5e7eb);
  border-radius: 999px;
  background: var(--surface-muted, #f9fafb);
  color: var(--text-muted, #6b7280);
  font-size: 12px;
  font-weight: 650;
  font-variant-numeric: tabular-nums;
}

.count-pill svg {
  width: 13px;
  height: 13px;
}

/* money */

.col-money {
  text-align: right;
  min-width: 125px;
}

.money-currency {
  font-size: 10px;
  font-weight: 600;
  letter-spacing: .03em;
  color: var(--text-muted, #6b7280);
  margin-right: 5px;
}

.cell-money strong {
  font-variant-numeric: tabular-nums;
}

/* validity */

.col-validity {
  min-width: 110px;
}

/* status badges */

.col-status {
  min-width: 105px;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  border-radius: 999px;
  padding: 4px 10px 4px 8px;
  font-size: 11px;
  font-weight: 700;
  text-transform: capitalize;
  background: var(--surface-muted, #f1f2f4);
  color: var(--text-muted, #6b7280);
}

.status-badge::before {
  content: '';
  flex: 0 0 auto;
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: currentColor;
}

.status-draft {
  background: var(--warning-soft, #fef3c7);
  color: var(--warning, #92400e);
}

.status-active {
  background: var(--success-soft, #dcfce7);
  color: var(--success, #166534);
}

.status-expired {
  background: var(--surface-muted, #f1f2f4);
  color: var(--text-muted, #6b7280);
}

.status-terminated {
  background: var(--danger-soft, #fee2e2);
  color: var(--danger, #991b1b);
}

/* actions */

.actions-column,
.actions-cell {
  text-align: right;
}

.row-actions {
  display: inline-flex;
  gap: 4px;
}

.icon-action {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 30px;
  height: 30px;
  border: 1px solid transparent;
  border-radius: 8px;
  background: transparent;
  color: var(--text-muted, #6b7280);
  text-decoration: none;
  cursor: pointer;
  transition: background .15s ease, color .15s ease, border-color .15s ease;
}

.icon-action svg {
  width: 15px;
  height: 15px;
}

.icon-action:hover {
  background: var(--surface-muted, #f9fafb);
  border-color: var(--border-color, #e5e7eb);
  color: var(--text-primary, #111827);
}

.icon-action.danger:hover {
  background: var(--danger-soft, #fee2e2);
  border-color: transparent;
  color: var(--danger, #991b1b);
}

.icon-action:focus-visible {
  outline: 2px solid currentColor;
  outline-offset: 1px;
}

/* ---------- footer summary ---------- */

.table-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 10px;
  padding: 12px 16px;
  border-top: 1px solid var(--border-color, #e5e7eb);
  font-size: 12.5px;
  color: var(--text-muted, #6b7280);
}

.footer-stats strong {
  color: var(--text-primary, #111827);
  font-weight: 650;
  font-variant-numeric: tabular-nums;
}

.dot-sep {
  margin: 0 3px;
  opacity: .5;
}

.footer-total {
  display: inline-flex;
  align-items: baseline;
  gap: 8px;
}

.footer-label {
  font-size: 11.5px;
}

.footer-total strong {
  color: var(--text-primary, #111827);
  font-size: 14px;
  font-weight: 700;
  font-variant-numeric: tabular-nums;
}

/* ---------- skeleton ---------- */

.skeleton {
  display: block;
  height: 11px;
  border-radius: 6px;
  background: var(--surface-muted, #f1f2f4);
  position: relative;
  overflow: hidden;
}

.skeleton::after {
  content: '';
  position: absolute;
  inset: 0;
  transform: translateX(-100%);
  background: linear-gradient(90deg, transparent, rgba(128, 128, 128, .14), transparent);
  animation: shimmer 1.4s infinite;
}

.skel-av {
  width: 34px;
  height: 34px;
  border-radius: 9px;
  flex: 0 0 auto;
}

.skel-badge {
  width: 78px;
  height: 20px;
  border-radius: 999px;
}

.skel-actions {
  width: 100px;
  height: 20px;
}

.w-30 { width: 30%; }
.w-55 { width: 55%; }
.w-60 { width: 60%; }
.w-70 { width: 70%; }

/* ---------- utils ---------- */

.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0 0 0 0);
  white-space: nowrap;
  border: 0;
}

@keyframes shimmer {
  to {
    transform: translateX(100%);
  }
}

@keyframes progress-slide {
  0% {
    transform: translateX(-100%);
  }
  100% {
    transform: translateX(350%);
  }
}

/* ---------- responsive ---------- */

@media (max-width: 900px) {
  .page-head {
    flex-direction: column;
  }
}

@media (max-width: 760px) {
  .col-vehicles,
  .col-validity {
    display: none;
  }

  table {
    min-width: 700px;
  }

  .error-banner {
    flex-wrap: wrap;
  }

  .banner-retry {
    margin-left: 0;
  }

  .table-footer {
    flex-direction: column;
    align-items: flex-start;
    gap: 6px;
  }
}
</style>