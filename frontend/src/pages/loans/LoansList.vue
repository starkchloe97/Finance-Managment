<script setup>
import { onMounted, reactive, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useLoanStore } from '@/stores/loanStore'
import { useCompanyCapitalStore } from '@/stores/companyCapitalStore'
import { money } from '@/utils/money'
import Pagination from '@/components/ui/Pagination.vue'
import StatePanel from '@/components/ui/StatePanel.vue'

const loanStore = useLoanStore()
const capitalStore = useCompanyCapitalStore()
const { loans, loading, error, pagination } = storeToRefs(loanStore)
const filters = reactive({ status: '', borrower: '', from: '', to: '' })
const opening = reactive({ amount: '', transaction_date: new Date().toISOString().slice(0, 10) })
const openingErrors = ref({})
const initializing = ref(false)

const query = (page = 1) => ({
  page,
  ...Object.fromEntries(Object.entries(filters).filter(([, value]) => value !== '')),
})
const loadLoans = (page = 1) => loanStore.fetchLoans(query(page))
const load = () => Promise.all([loadLoans(), capitalStore.fetchCapital()])
const resetFilters = () => {
  Object.assign(filters, { status: '', borrower: '', from: '', to: '' })
  loadLoans().catch(() => {})
}
const initialize = async () => {
  initializing.value = true
  openingErrors.value = {}
  try {
    await capitalStore.initializeCapital(opening)
  } catch (error) {
    if (error.response?.status === 422) openingErrors.value = error.response.data.errors || {}
  } finally {
    initializing.value = false
  }
}
const statusClass = (status) =>
  ({
    active: 'status-info',
    overdue: 'status-danger',
    paid: 'status-success',
    cancelled: 'status-cancelled',
  })[status] || 'status-draft'
const movementLabel = (type) =>
  ({
    opening_balance: 'Opening balance',
    loan_issued: 'Loan issued',
    loan_repayment: 'Loan repayment',
    loan_cancelled: 'Loan cancellation',
  })[type] || type

onMounted(() => load().catch(() => {}))
</script>

<template>
  <div class="entity-list-page loan-page">
    <div class="page-head">
      <div>
        <span class="section-kicker">Capital</span>
        <h1>Loans</h1>
        <p class="page-subtitle">
          Track company-issued principal, repayments, due dates, and available capital.
        </p>
      </div>
      <RouterLink class="btn" to="/loans/create">Issue loan</RouterLink>
    </div>

    <div v-if="capitalStore.error" class="page-error" role="alert">
      <p>{{ capitalStore.error }}</p>
      <button type="button" class="btn-light" @click="capitalStore.fetchCapital()">
        Retry capital
      </button>
    </div>

    <section
      v-if="capitalStore.loading && !capitalStore.account"
      class="card state-panel state-loading"
    >
      <div class="skeleton-block"></div>
    </section>

    <section v-else-if="!capitalStore.initialized" class="card capital-setup">
      <div>
        <span class="section-kicker">Required setup</span>
        <h2>Set opening company capital</h2>
        <p class="hint">
          This one-time balance controls how much company-owned money can be issued as loans.
        </p>
      </div>
      <form class="capital-setup-form" @submit.prevent="initialize">
        <div class="field">
          <label for="opening-amount">Opening amount</label>
          <input
            id="opening-amount"
            v-model="opening.amount"
            inputmode="decimal"
            :aria-invalid="Boolean(openingErrors.amount)"
            aria-describedby="opening-amount-error"
          />
          <span v-if="openingErrors.amount" id="opening-amount-error" class="error">{{
            openingErrors.amount[0]
          }}</span>
        </div>
        <div class="field">
          <label for="opening-date">Transaction date</label>
          <input
            id="opening-date"
            v-model="opening.transaction_date"
            type="date"
            :aria-invalid="Boolean(openingErrors.transaction_date)"
          />
          <span v-if="openingErrors.transaction_date" class="error">{{
            openingErrors.transaction_date[0]
          }}</span>
        </div>
        <button type="submit" :disabled="initializing">
          {{ initializing ? 'Saving capital…' : 'Set opening capital' }}
        </button>
      </form>
    </section>

    <template v-else>
      <section class="capital-strip" aria-label="Company capital summary">
        <div>
          <span>Available capital</span>
          <strong>{{ money(capitalStore.current_balance) }}</strong>
        </div>
        <div>
          <span>Opening capital</span>
          <strong>{{ money(capitalStore.opening_balance) }}</strong>
        </div>
        <div>
          <span>Recent movements</span>
          <strong>{{ capitalStore.transactions.length }}</strong>
        </div>
      </section>

      <details v-if="capitalStore.transactions.length" class="card movement-disclosure">
        <summary>Recent capital movements</summary>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Date</th>
                <th>Movement</th>
                <th>Reference</th>
                <th class="right">Amount</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="transaction in capitalStore.transactions" :key="transaction.id">
                <td>{{ transaction.transaction_date }}</td>
                <td>{{ movementLabel(transaction.type) }}</td>
                <td>{{ transaction.description || transaction.transaction_code }}</td>
                <td
                  class="right money"
                  :class="Number(transaction.amount) < 0 ? 'money-loss' : 'money-profit'"
                >
                  {{ Number(transaction.amount) > 0 ? '+' : '' }}{{ money(transaction.amount) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </details>
    </template>

    <section class="card list-card">
      <div class="list-card-header">
        <div>
          <h2>Loan register</h2>
          <p class="hint">Open a loan to record repayments or revise its due date.</p>
        </div>
        <span v-if="pagination.total" class="result-count">{{ pagination.total }} total</span>
      </div>

      <form class="loan-filters" @submit.prevent="loadLoans()">
        <div class="field filter-search">
          <label for="borrower-filter">Borrower</label
          ><input
            id="borrower-filter"
            v-model="filters.borrower"
            type="search"
            placeholder="Name"
          />
        </div>
        <div class="field">
          <label for="status-filter">Status</label
          ><select id="status-filter" v-model="filters.status">
            <option value="">All statuses</option>
            <option value="active">Active</option>
            <option value="overdue">Overdue</option>
            <option value="paid">Paid</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>
        <div class="field">
          <label for="from-filter">Loan date from</label
          ><input id="from-filter" v-model="filters.from" type="date" />
        </div>
        <div class="field">
          <label for="to-filter">Loan date to</label
          ><input id="to-filter" v-model="filters.to" type="date" />
        </div>
        <div class="filter-actions">
          <button type="submit">Apply filters</button
          ><button type="button" class="btn-light" @click="resetFilters">Reset</button>
        </div>
      </form>

      <div v-if="error" class="page-error" role="alert">
        <p>{{ error }}</p>
        <button type="button" class="btn-light" @click="loadLoans()">Try again</button>
      </div>
      <StatePanel
        :loading="loading && !loans.length"
        :empty="!loading && !loans.length"
        empty-title="No loans match this view. Issue a loan or reset the filters."
        empty-action="Issue loan"
        empty-to="/loans/create"
      >
        <div class="table-wrap">
          <table class="loan-table">
            <thead>
              <tr>
                <th>Loan</th>
                <th>Borrower</th>
                <th class="right">Principal</th>
                <th class="right">Paid</th>
                <th class="right">Outstanding</th>
                <th>Due date</th>
                <th>Status</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="loan in loans" :key="loan.id">
                <td>
                  <RouterLink class="record-link" :to="`/loans/${loan.id}`">{{
                    loan.loan_code
                  }}</RouterLink
                  ><span class="record-code">{{ loan.loan_date }}</span>
                </td>
                <td>
                  <strong>{{ loan.borrower?.name || 'Unknown borrower' }}</strong
                  ><span class="record-code capitalize">{{ loan.borrower_type }}</span>
                </td>
                <td class="right money">{{ money(loan.amount) }}</td>
                <td class="right money">{{ money(loan.total_repaid) }}</td>
                <td class="right money">
                  <strong>{{ money(loan.outstanding_amount) }}</strong>
                </td>
                <td>{{ loan.due_date }}</td>
                <td>
                  <span class="status capitalize" :class="statusClass(loan.status)">{{
                    loan.status
                  }}</span>
                </td>
                <td class="right">
                  <RouterLink class="btn-light btn-sm" :to="`/loans/${loan.id}`">Open</RouterLink>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <Pagination
          :page="pagination.current_page"
          :last-page="pagination.last_page"
          :total="pagination.total"
          :per-page="pagination.per_page"
          @update:page="loadLoans"
        />
      </StatePanel>
    </section>
  </div>
</template>

<style scoped>
.loan-page,
.list-card {
  min-width: 0;
}
.page-subtitle {
  color: var(--text-secondary);
  margin-top: var(--space-2);
}
.page-error {
  align-items: center;
  background: var(--danger-soft);
  border: 1px solid var(--danger);
  border-radius: var(--radius-md);
  display: flex;
  gap: var(--space-3);
  justify-content: space-between;
  margin-bottom: var(--space-4);
  padding: var(--space-3) var(--space-4);
}
.page-error p {
  color: var(--danger);
}
.capital-setup {
  align-items: start;
  display: grid;
  gap: var(--space-6);
  grid-template-columns: minmax(0, 1fr) minmax(320px, 1fr);
}
.capital-setup .hint {
  display: block;
  margin: var(--space-2) 0 0;
}
.capital-setup-form {
  display: grid;
  gap: var(--space-3);
  grid-template-columns: 1fr 1fr;
}
.capital-setup-form button {
  grid-column: 1/-1;
}
.capital-strip {
  display: grid;
  gap: var(--space-3);
  grid-template-columns: 2fr 1fr 1fr;
  margin-bottom: var(--space-5);
}
.capital-strip > div {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: var(--space-4);
}
.capital-strip span {
  color: var(--text-muted);
  display: block;
  font-size: var(--text-sm);
}
.capital-strip strong {
  display: block;
  font-size: var(--text-2xl);
  font-variant-numeric: tabular-nums;
  margin-top: var(--space-1);
}
.capital-strip > div:first-child strong {
  color: var(--accent);
}
.movement-disclosure summary {
  cursor: pointer;
  font-weight: var(--font-weight-semibold);
}
.movement-disclosure[open] summary {
  margin-bottom: var(--space-4);
}
.list-card-header {
  align-items: flex-start;
  display: flex;
  justify-content: space-between;
  margin-bottom: var(--space-4);
}
.result-count,
.record-code {
  color: var(--text-muted);
  font-size: var(--text-xs);
}
.record-link,
.record-code {
  display: block;
}
.record-link {
  font-weight: var(--font-weight-semibold);
}
.record-code {
  margin-top: var(--space-1);
}
.loan-filters {
  align-items: end;
  background: var(--surface-muted);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  display: grid;
  gap: var(--space-3);
  grid-template-columns: 1.5fr repeat(3, 1fr);
  margin-bottom: var(--space-5);
  padding: var(--space-4);
}
.loan-filters .field {
  margin: 0;
}
.filter-actions {
  display: flex;
  gap: var(--space-2);
  grid-column: 1/-1;
  justify-content: flex-end;
}
.capitalize {
  text-transform: capitalize;
}
@media (max-width: 900px) {
  .capital-setup,
  .loan-filters {
    grid-template-columns: 1fr 1fr;
  }
  .capital-strip {
    grid-template-columns: 1fr;
  }
  .filter-actions {
    grid-column: 1/-1;
  }
  .loan-table {
    min-width: 900px;
  }
}
@media (max-width: 560px) {
  .capital-setup,
  .capital-setup-form,
  .loan-filters {
    grid-template-columns: 1fr;
  }
  .page-error,
  .list-card-header {
    align-items: stretch;
    flex-direction: column;
  }
  .filter-actions {
    justify-content: stretch;
  }
  .filter-actions > * {
    flex: 1;
  }
}
</style>
