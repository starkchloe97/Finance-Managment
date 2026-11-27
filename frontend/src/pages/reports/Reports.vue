<script setup>
import { computed, onMounted, ref } from 'vue'
import {
  closeAccountingPeriod,
  getAccountingPeriods,
  getFinancialReport,
} from '@/services/reportService'
import { money } from '@/utils/money'

const today = new Date()
const localDate = (date) =>
  `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`
const monthInput = ref(
  localDate(new Date(today.getFullYear(), today.getMonth() - 1, 1)).slice(0, 7),
)
const yearInput = ref(String(today.getFullYear() - 1))
const from = ref(localDate(new Date(today.getFullYear(), today.getMonth(), 1)))
const to = ref(localDate(today))
const report = ref(null)
const closes = ref([])
const loading = ref(false)
const closing = ref(false)
const error = ref('')
const formatMoney = (value) => `PKR ${money(value)}`
const closeHistory = computed(() => closes.value)

const load = async () => {
  loading.value = true
  error.value = ''
  try {
    const [reportResponse, closeResponse] = await Promise.all([
      getFinancialReport({ from: from.value, to: to.value }),
      getAccountingPeriods(),
    ])
    report.value = reportResponse.data.data
    closes.value = closeResponse.data.data
  } catch (requestError) {
    error.value = requestError.response?.data?.message || 'Could not load financial reports.'
  } finally {
    loading.value = false
  }
}

const closePeriod = async (periodType, periodLabel) => {
  const label = periodType === 'month' ? 'monthly' : 'yearly'
  if (
    !window.confirm(
      `Close the ${label} period ${periodLabel}? This saves a permanent snapshot and blocks later-dated edits in that period.`,
    )
  )
    return

  closing.value = true
  error.value = ''
  try {
    await closeAccountingPeriod({ period_type: periodType, period_label: periodLabel })
    await load()
  } catch (requestError) {
    error.value =
      requestError.response?.data?.errors?.period_label?.[0] ||
      requestError.response?.data?.message ||
      `Could not close the ${label} period.`
  } finally {
    closing.value = false
  }
}

const csvCell = (value) => `"${String(value ?? '').replaceAll('"', '""')}"`
const exportCsv = () => {
  if (!report.value) return
  const lines = [['Section', 'Name', 'Amount', 'Details']]
  const addRows = (section, rows, nameField, amountField, detailsField = '') => {
    rows.forEach((row) =>
      lines.push([
        section,
        row[nameField],
        row[amountField],
        detailsField ? row[detailsField] : '',
      ]),
    )
  }
  addRows('Assets', report.value.assets.items, 'name', 'current_value', 'status')
  addRows(
    'Investors',
    report.value.investors.items,
    'name',
    'capital_outstanding',
    'invested_in_period',
  )
  addRows('Customers', report.value.customers.items, 'name', 'revenue', 'profit')
  addRows('Receivables', report.value.receivables.items, 'invoice_no', 'outstanding', 'party_name')
  addRows('Payables', report.value.payables.items, 'invoice_no', 'outstanding', 'party_name')
  addRows(
    'Commission',
    report.value.commission_receivables.items,
    'invoice_no',
    'outstanding',
    'party_name',
  )
  addRows(
    'Operational expenses',
    report.value.operational_expenses.items,
    'title',
    'amount',
    'job_code',
  )
  const csv = lines.map((row) => row.map(csvCell).join(',')).join('\r\n')
  const url = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8' }))
  const link = document.createElement('a')
  link.href = url
  link.download = `financial-report-${from.value}-to-${to.value}.csv`
  link.click()
  window.setTimeout(() => URL.revokeObjectURL(url), 1000)
}

onMounted(load)
</script>

<template>
  <section class="reports">
    <header class="page-header">
      <div>
        <h1>Reports &amp; period close</h1>
        <p class="muted">
          Review the company's financial position, then lock completed months or years.
        </p>
      </div>
      <button class="button secondary" :disabled="!report || loading" @click="exportCsv">
        Export CSV
      </button>
    </header>

    <p v-if="error" class="error" role="alert">{{ error }}</p>
    <p v-if="loading && !report" class="muted" role="status">Loading financial reports…</p>

    <section class="panel filters report-filters" aria-label="Report date range">
      <label>From <input v-model="from" type="date" /></label>
      <label>To <input v-model="to" type="date" /></label>
      <button class="button" :disabled="loading || !from || !to || from > to" @click="load">
        {{ loading ? 'Loading…' : 'Run report' }}
      </button>
    </section>

    <template v-if="report">
      <section class="panel">
        <h2>Financial position</h2>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th scope="col">Measure</th>
                <th scope="col">Amount</th>
                <th scope="col">Context</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <th scope="row">Asset value</th>
                <td>{{ formatMoney(report.assets.totals.current_value) }}</td>
                <td>{{ report.assets.totals.count }} registered assets</td>
              </tr>
              <tr>
                <th scope="row">Investor capital outstanding</th>
                <td>{{ formatMoney(report.investors.totals.capital_outstanding) }}</td>
                <td>
                  {{ formatMoney(report.investors.totals.invested_in_period) }} received in selected
                  period
                </td>
              </tr>
              <tr>
                <th scope="row">Customer revenue</th>
                <td>{{ formatMoney(report.customers.totals.revenue) }}</td>
                <td>{{ formatMoney(report.customers.totals.profit) }} job profit</td>
              </tr>
              <tr>
                <th scope="row">Operational expenses</th>
                <td>{{ formatMoney(report.operational_expenses.total) }}</td>
                <td>{{ report.operational_expenses.items.length }} job expense entries</td>
              </tr>
              <tr>
                <th scope="row">Company capital available</th>
                <td>{{ formatMoney(report.company_capital.current_balance) }}</td>
                <td>
                  {{ formatMoney(report.company_capital.total_capital) }} total ·
                  {{ formatMoney(report.company_capital.lent_out) }} lent
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section class="panel">
        <h2>Invoice balances for the selected period</h2>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th scope="col">Type</th>
                <th scope="col">Invoices</th>
                <th scope="col">Total</th>
                <th scope="col">Paid</th>
                <th scope="col">Outstanding</th>
                <th scope="col">Overdue</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <th scope="row">Receivables</th>
                <td>{{ report.receivables.count }}</td>
                <td>{{ formatMoney(report.receivables.total) }}</td>
                <td>{{ formatMoney(report.receivables.paid) }}</td>
                <td>{{ formatMoney(report.receivables.outstanding) }}</td>
                <td>{{ formatMoney(report.receivables.overdue) }}</td>
              </tr>
              <tr>
                <th scope="row">Payables</th>
                <td>{{ report.payables.count }}</td>
                <td>{{ formatMoney(report.payables.total) }}</td>
                <td>{{ formatMoney(report.payables.paid) }}</td>
                <td>{{ formatMoney(report.payables.outstanding) }}</td>
                <td>{{ formatMoney(report.payables.overdue) }}</td>
              </tr>
              <tr>
                <th scope="row">Administrative payables</th>
                <td>{{ report.payables.administrative.count }}</td>
                <td>{{ formatMoney(report.payables.administrative.total) }}</td>
                <td>{{ formatMoney(report.payables.administrative.paid) }}</td>
                <td>{{ formatMoney(report.payables.administrative.outstanding) }}</td>
                <td>{{ formatMoney(report.payables.administrative.overdue) }}</td>
              </tr>
              <tr>
                <th scope="row">Commission receivables</th>
                <td>{{ report.commission_receivables.count }}</td>
                <td>{{ formatMoney(report.commission_receivables.total) }}</td>
                <td>{{ formatMoney(report.commission_receivables.paid) }}</td>
                <td>{{ formatMoney(report.commission_receivables.outstanding) }}</td>
                <td>{{ formatMoney(report.commission_receivables.overdue) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <div class="report-grid">
        <article class="panel">
          <h2>Assets</h2>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th scope="col">Asset</th>
                  <th scope="col">Status</th>
                  <th scope="col">Purchase</th>
                  <th scope="col">Value</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="asset in report.assets.items" :key="asset.asset_code">
                  <td>{{ asset.asset_code }} · {{ asset.name }}</td>
                  <td>{{ asset.status }}</td>
                  <td>{{ formatMoney(asset.purchase_price) }}</td>
                  <td>{{ formatMoney(asset.current_value) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </article>
        <article class="panel">
          <h2>Investor capital</h2>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th scope="col">Investor</th>
                  <th scope="col">Period investment</th>
                  <th scope="col">Outstanding</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="investor in report.investors.items" :key="investor.investor_code">
                  <td>{{ investor.name }}</td>
                  <td>{{ formatMoney(investor.invested_in_period) }}</td>
                  <td>{{ formatMoney(investor.capital_outstanding) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </article>
        <article class="panel">
          <h2>Customer / job performance</h2>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th scope="col">Customer</th>
                  <th scope="col">Jobs</th>
                  <th scope="col">Revenue</th>
                  <th scope="col">Cost</th>
                  <th scope="col">Profit</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="customer in report.customers.items" :key="customer.customer_code">
                  <td>{{ customer.name }}</td>
                  <td>{{ customer.jobs }}</td>
                  <td>{{ formatMoney(customer.revenue) }}</td>
                  <td>{{ formatMoney(customer.cost) }}</td>
                  <td>{{ formatMoney(customer.profit) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </article>
        <article class="panel">
          <h2>Operational expenses</h2>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th scope="col">Date</th>
                  <th scope="col">Job</th>
                  <th scope="col">Expense</th>
                  <th scope="col">Category</th>
                  <th scope="col">Amount</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="expense in report.operational_expenses.items" :key="expense.id">
                  <td>{{ expense.expense_date }}</td>
                  <td>{{ expense.job_code }}</td>
                  <td>{{ expense.title }}</td>
                  <td>{{ expense.category }}</td>
                  <td>{{ formatMoney(expense.amount) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </article>
      </div>

      <section class="panel close-panel">
        <div>
          <h2>Close an accounting period</h2>
          <p class="muted">
            A close stores a report snapshot. Later changes dated within the closed month or year
            are rejected.
          </p>
        </div>
        <form @submit.prevent="closePeriod('month', monthInput)">
          <label>Month <input v-model="monthInput" type="month" required /></label>
          <button class="button" :disabled="closing">Close month</button>
        </form>
        <form @submit.prevent="closePeriod('year', yearInput)">
          <label
            >Year <input v-model="yearInput" type="number" min="2000" max="9999" required
          /></label>
          <button class="button" :disabled="closing">Close year</button>
        </form>
      </section>

      <section class="panel">
        <h2>Closed periods</h2>
        <p v-if="!closeHistory.length" class="muted">No accounting periods have been closed.</p>
        <div v-else class="table-wrap">
          <table>
            <thead>
              <tr>
                <th scope="col">Period</th>
                <th scope="col">Dates</th>
                <th scope="col">Closed by</th>
                <th scope="col">Closed at</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="period in closeHistory" :key="period.id">
                <td>{{ period.period_type }} · {{ period.period_label }}</td>
                <td>{{ period.period_start }} – {{ period.period_end }}</td>
                <td>{{ period.closer?.name || 'Unknown' }}</td>
                <td>{{ new Date(period.closed_at).toLocaleString() }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </template>
  </section>
</template>

<style scoped>
.reports {
  display: grid;
  gap: var(--space-5);
}
.page-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
}
h1,
h2,
p {
  margin: 0;
}
h1 {
  font-size: var(--text-3xl);
  line-height: var(--line-height-tight);
}
h2 {
  font-size: var(--text-lg);
  margin-bottom: var(--space-3);
}
.muted,
small {
  color: var(--text-muted);
}
.panel {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: var(--space-4);
}
.filters,
.close-panel {
  display: flex;
  flex-wrap: wrap;
  align-items: end;
  gap: 1rem;
}
.report-filters label{
  margin-bottom: 0;
}
.filters label,
.close-panel label {
  display: grid;
  gap: 0.35rem;
  font-size: 0.85rem;
  font-weight: 600;
}
input {
  border: 1px solid var(--border-strong);
  border-radius: var(--radius-sm);
  padding: 0.55rem;
  font: inherit;
  color: var(--text-primary);
}
.button {
  border: 1px solid #00000038;
  border-radius: var(--radius-sm);
  background: var(--accent);
  color: var(--text-inverse);
  padding: 0.65rem 0.9rem;
  font: inherit;
  font-weight: var(--font-weight-semibold);
  cursor: pointer;
}
.button:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}
.button:hover:not(:disabled) {
  background: var(--accent-hover);
  color: var(--text-inverse);
  border:0;
}
.button:focus-visible,
input:focus-visible {
  outline: 3px solid var(--accent);
  outline-offset: 2px;
}
.button.secondary {
  background: var(--surface-hover);
  color: var(--text-primary);
}
.report-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.8rem;
}
.close-panel > div {
  flex: 1 1 100%;
}
.close-panel form {
  display: flex;
  align-items: end;
  gap: 0.75rem;
}
.close-panel form label {
  margin-bottom: 0;
}
.table-wrap {
  overflow-x: auto;
}
table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
}
th,
td {
  padding: 0.65rem;
  border-bottom: 1px solid var(--border);
  white-space: nowrap;
}
th {
  color: var(--text-secondary);
  font-weight: var(--font-weight-semibold);
}
.error {
  color: var(--danger);
}
@media (max-width: 760px) {
  .report-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}
@media (max-width: 500px) {
  .report-grid {
    grid-template-columns: 1fr;
  }
  .page-header {
    flex-direction: column;
  }
}
</style>
