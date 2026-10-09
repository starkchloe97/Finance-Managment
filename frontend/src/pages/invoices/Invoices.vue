<script setup>
import { computed, onMounted, reactive, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useInvoiceStore } from '@/stores/invoiceStore'
import StatePanel from '@/components/ui/StatePanel.vue'
import FinanceStatus from '@/components/ui/FinanceStatus.vue'
import { money } from '@/utils/money'

const route = useRoute()
const store = useInvoiceStore()
const filters = reactive({ search: '', party: '', status: '', category: '', from: '', to: '' })
const direction = computed(() =>
  route.path === '/payables'
    ? 'payable'
    : route.path === '/receivables' || route.path === '/commission-receivables'
      ? 'receivable'
      : '',
)
const commission = computed(() => route.path === '/commission-receivables')
const heading = computed(() =>
  commission.value
    ? 'Commission receivable'
    : direction.value === 'payable'
      ? 'Payables'
      : direction.value === 'receivable'
        ? 'Receivables'
        : 'Invoices',
)
const load = () =>
  store.fetchInvoices({ ...filters, direction: direction.value, commission: commission.value })
watch(() => route.path, load)
onMounted(load)
</script>

<template>
  <div class="invoice-page">
    <header class="page-heading">
      <div>
        <span class="section-kicker">Finance / Accounting</span>
        <h1>{{ heading }}</h1>
      </div>
      <RouterLink v-if="!commission" class="btn" to="/invoices/create">Create invoice</RouterLink>
    </header>
    <section class="card filters">
      <input v-model="filters.search" placeholder="Search invoice or party" @input="load" />
      <input v-model="filters.party" placeholder="Filter party" @input="load" />
      <select v-model="filters.status" @change="load">
        <option value="">All statuses</option>
        <option value="unpaid">Unpaid</option>
        <option value="partially_paid">Partially paid</option>
        <option value="overdue">Overdue</option>
        <option value="paid">Paid</option>
      </select>
      <select v-model="filters.category" @change="load">
        <option value="">All categories</option>
        <option value="job">Transport job</option>
        <option value="vehicle_rental">Vehicle rental</option>
        <option value="sales">Sales</option>
        <option value="commission">Commission</option>
        <option value="contractor">Contractor</option>
        <option value="supplier">Supplier</option>
        <option value="administrative">Administrative</option>
        <option value="marketing">Marketing</option>
        <option value="other">Other</option>
      </select>
      <label class="date-filter">
        <input v-model="filters.from" type="date" aria-label="Date from" required @change="load" />
        <span v-if="!filters.from" class="date-placeholder">Date from</span>
      </label>
      <label class="date-filter">
        <input v-model="filters.to" type="date" aria-label="Date to" required @change="load" />
        <span v-if="!filters.to" class="date-placeholder">Date to</span>
      </label>
    </section>
    <StatePanel
      :loading="store.loading"
      :error="store.error"
      :empty="!store.loading && !store.error && !store.invoices.length"
      empty-title="No invoices match these filters."
    >
      <template #error-action><button class="btn" @click="load">Try again</button></template>
      <div class="card table-wrap">
        <table>
          <thead>
            <tr>
              <th>Invoice</th>
              <th>Party</th>
              <th>Source</th>
              <th>Date</th>
              <th>Due</th>
              <th>Total</th>
              <th>{{ direction === 'payable' ? 'Paid' : 'Received' }}</th>
              <th>Outstanding</th>
              <th>Status</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="invoice in store.invoices" :key="invoice.id">
              <td>
                <RouterLink :to="`/invoices/${invoice.id}`">{{ invoice.invoice_no }}</RouterLink>
              </td>
              <td>{{ invoice.party_company || invoice.party_name }}</td>
              <td>
                <RouterLink
                  v-if="invoice.source?.type === 'transport_job'"
                  :to="`/jobs/${invoice.source.id}`"
                >{{ invoice.source.code }}</RouterLink>
                <RouterLink
                  v-else-if="invoice.source?.type === 'job_expense' && invoice.source.job_id"
                  :to="`/jobs/${invoice.source.job_id}`"
                >{{ invoice.source.job_code }} · {{ invoice.source.title }}</RouterLink>
                <RouterLink
                  v-else-if="invoice.source?.type === 'vehicle_contract'"
                  :to="`/vehicle-contracts/${invoice.source.id}`"
                >{{ invoice.source.contract_number }} · {{ invoice.source.billing_period }}</RouterLink>
                <span v-else>—</span>
              </td>
              <td>{{ invoice.invoice_date }}</td>
              <td>{{ invoice.due_date || '—' }}</td>
              <td>{{ money(invoice.total) }}</td>
              <td>{{ money(invoice.paid_amount) }}</td>
              <td>{{ money(invoice.outstanding_amount) }}</td>
              <td><FinanceStatus :status="invoice.status" /></td>
              <td><RouterLink :to="`/invoices/${invoice.id}`">View</RouterLink></td>
            </tr>
          </tbody>
        </table>
      </div>
    </StatePanel>
  </div>
</template>

<style scoped>
.invoice-page {
  display: grid;
  gap: var(--space-4);
}
.page-heading {
  display: flex;
  align-items: end;
  justify-content: space-between;
  gap: var(--space-4);
}
.section-kicker {
  color: var(--text-muted);
  display: block;
  font-size: var(--text-xs);
  text-transform: uppercase;
}
.filters {
  display: grid;
  grid-template-columns: 2fr 1.2fr 1fr 1fr 1fr 1fr;
  gap: var(--space-3);
  align-items: center;
  padding: var(--space-2) var(--space-4);
}
.filters input,
.filters select {
  border: 1px solid var(--border-strong);
  border-radius: var(--radius-sm);
  padding: 10px;
  height: 38px;
  min-width: 0;
  box-sizing: border-box;
}
.date-filter {
  position: relative;
  display: block;
  min-width: 0;
}
.date-filter input {
  width: 100%;
}
.date-filter input:required:invalid {
  color: transparent;
}
.date-filter input:required:invalid::-webkit-datetime-edit {
  color: transparent;
}
.date-placeholder {
  position: absolute;
  left: 11px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-muted);
  font-size: var(--text-sm);
  pointer-events: none;
}
.table-wrap {
  overflow-x: auto;
  padding: 0;
}
table {
  width: 100%;
  min-width: 950px;
  font-size: var(--text-sm);
}
th,
td {
  border-bottom: 1px solid var(--border);
  padding: 12px;
  text-align: left;
  white-space: nowrap;
}
th {
  color: var(--text-muted);
  font-weight: 600;
}
@media (max-width: 1100px) {
  .filters {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}
@media (max-width: 700px) {
  .filters {
    grid-template-columns: 1fr;
  }
}
</style>
