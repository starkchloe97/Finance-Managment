<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useInvestorStore } from '@/stores/investorStore'
import { useInvestmentStore } from '@/stores/investmentStore'
import { useLoanStore } from '@/stores/loanStore'
import { money } from '@/utils/money'
import EntityDetailLayout from '@/components/ui/EntityDetailLayout.vue'
import Pagination from '@/components/ui/Pagination.vue'

const route = useRoute()
const investorStore = useInvestorStore()
const investmentStore = useInvestmentStore()
const loanStore = useLoanStore()

const { investor, loading, error } = storeToRefs(investorStore)
const {
  investments,
  investorInvestmentTotals,
  loading: investmentsLoading,
  error: investmentsError,
} = storeToRefs(investmentStore)
const {
  investorLoans,
  investorLoanTotals,
  investorPagination,
  investorLoansLoading,
  investorLoansError,
} = storeToRefs(loanStore)

const activeTab = ref('overview')
const tabs = [
  { key: 'overview', label: 'Overview' },
  { key: 'investments', label: 'Investments' },
  { key: 'loans', label: 'Loans' },
]

const stats = computed(() => [
  { label: 'Total invested', value: money(investorInvestmentTotals.value.total), tone: 'revenue' },
  { label: 'Pool capital', value: money(investorInvestmentTotals.value.pool), tone: 'neutral' },
  { label: 'Normal capital', value: money(investorInvestmentTotals.value.normal), tone: 'neutral' },
  { label: 'Investments', value: String(investments.value.length), tone: 'neutral' },
])

const load = async () => {
  await Promise.all([
    investorStore.fetchInvestor(route.params.id),
    investmentStore.fetchInvestorInvestments(route.params.id),
    loanStore.fetchInvestorLoans(route.params.id),
  ])
}

const loadLoanPage = (page) => loanStore.fetchInvestorLoans(route.params.id, { page })
const statusClass = (status) => {
  if (status === 'active' || status === 'paid') return 'status-success'
  if (status === 'overdue' || status === 'cancelled') return 'status-danger'
  return 'status-warning'
}

onMounted(() => {
  load().catch(() => {})
})
</script>

<template>
  <EntityDetailLayout
    v-model="activeTab"
    :tabs="tabs"
    :loading="loading && !investor"
    :error="error"
    :stats="stats"
    @retry="load"
  >
    <template #title>
      <h1>{{ investor?.name }}</h1>
      <span class="hint">{{ investor?.investor_code }}</span>
      <span v-if="investor?.status" class="status" :class="statusClass(investor.status)">
        {{ investor.status }}
      </span>
    </template>

    <template #actions>
      <RouterLink class="btn-light" :to="`/investors/${investor?.id}/edit`">Edit</RouterLink>
      <RouterLink class="btn-light" :to="`/loans/create?investor_id=${investor?.id}`">
        Add Loan
      </RouterLink>
      <RouterLink class="btn" :to="`/investments/create?investor_id=${investor?.id}`">
        Add Investment
      </RouterLink>
    </template>

    <section v-if="activeTab === 'overview'" class="entity-section">
      <div class="card">
        <div class="section-head">
          <div>
            <h3>Investor profile</h3>
            <p class="hint">Contact and account information for this investor.</p>
          </div>
        </div>
        <div class="grid investor-details">
          <div class="field">
            <label>Investor code</label>
            <p>{{ investor?.investor_code || '—' }}</p>
          </div>
          <div class="field">
            <label>Email</label>
            <p>{{ investor?.email || '—' }}</p>
          </div>
          <div class="field">
            <label>Phone</label>
            <p>{{ investor?.phone || '—' }}</p>
          </div>
          <div class="field">
            <label>Address</label>
            <p>{{ investor?.address || '—' }}</p>
          </div>
        </div>
      </div>

      <div v-if="investor?.notes" class="card">
        <h3>Notes</h3>
        <p>{{ investor.notes }}</p>
      </div>

      <div class="card">
        <div class="section-head">
          <div>
            <h3>Investment mix</h3>
            <p class="hint">Capital grouped by the investment categories already recorded.</p>
          </div>
          <button type="button" class="btn-light btn-sm" @click="activeTab = 'investments'">
            View investments
          </button>
        </div>
        <div class="mini-stat-grid">
          <div>
            <span>Pool investments</span>
            <strong>{{ money(investorInvestmentTotals.pool) }}</strong>
          </div>
          <div>
            <span>Normal investments</span>
            <strong>{{ money(investorInvestmentTotals.normal) }}</strong>
          </div>
        </div>
      </div>
    </section>

    <section v-if="activeTab === 'investments'" class="entity-section">
      <div class="section-head">
        <div>
          <h3>Investments</h3>
          <p class="hint">All capital placements associated with this investor.</p>
        </div>
        <RouterLink class="btn btn-sm" :to="`/investments/create?investor_id=${investor?.id}`">
          Add Investment
        </RouterLink>
      </div>

      <div v-if="investmentsLoading" class="state-panel state-loading">
        <div class="skeleton-block"></div>
      </div>
      <div v-else-if="investmentsError" class="state-panel state-error">
        <p>{{ investmentsError }}</p>
        <button type="button" class="btn" @click="load">Try again</button>
      </div>
      <div v-else-if="!investments.length" class="state-panel state-empty">
        <p>No investments yet. Add an investment to start tracking this investor’s capital.</p>
        <RouterLink class="btn" :to="`/investments/create?investor_id=${investor?.id}`">
          Add Investment
        </RouterLink>
      </div>
      <div v-else class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Code</th>
              <th>Category</th>
              <th class="right">Principal</th>
              <th>Return</th>
              <th>Maturity</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="investment in investments" :key="investment.id">
              <td>
                <RouterLink :to="`/investments/${investment.id}`">
                  {{ investment.investment_code }}
                </RouterLink>
              </td>
              <td class="capitalize">{{ investment.investment_category }}</td>
              <td class="right money">{{ money(investment.amount) }}</td>
              <td>
                {{
                  investment.return_type === 'percentage'
                    ? `${investment.return_percentage}%`
                    : money(investment.fixed_return_amount)
                }}
              </td>
              <td>{{ investment.maturity_date || '—' }}</td>
              <td>
                <span class="status" :class="statusClass(investment.status)">{{
                  investment.status
                }}</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <section v-if="activeTab === 'loans'" class="entity-section">
      <div class="section-head">
        <div>
          <h3>Loans</h3>
          <p class="hint">
            Company-issued loans linked to this investor, separate from investment capital.
          </p>
        </div>
        <RouterLink class="btn btn-sm" :to="`/loans/create?investor_id=${investor?.id}`">
          Add Loan
        </RouterLink>
      </div>

      <div class="loan-summary-grid">
        <div>
          <span>Outstanding</span><strong>{{ money(investorLoanTotals.outstanding) }}</strong>
        </div>
        <div>
          <span>Active</span><strong>{{ investorLoanTotals.active }}</strong>
        </div>
        <div>
          <span>Overdue</span><strong class="loan-overdue">{{ investorLoanTotals.overdue }}</strong>
        </div>
        <div>
          <span>Paid</span><strong>{{ investorLoanTotals.paid }}</strong>
        </div>
      </div>

      <div v-if="investorLoansLoading" class="state-panel state-loading">
        <div class="skeleton-block"></div>
      </div>
      <div v-else-if="investorLoansError" class="state-panel state-error">
        <p>{{ investorLoansError }}</p>
        <button type="button" class="btn" @click="loadLoanPage(investorPagination.current_page)">
          Try again
        </button>
      </div>
      <div v-else-if="!investorLoans.length" class="state-panel state-empty">
        <p>No loans are linked to this investor. Loans remain separate from investment totals.</p>
        <RouterLink class="btn" :to="`/loans/create?investor_id=${investor?.id}`"
          >Add Loan</RouterLink
        >
      </div>
      <template v-else>
        <div class="table-wrap">
          <table class="investor-loan-table">
            <thead>
              <tr>
                <th>Loan</th>
                <th class="right">Principal</th>
                <th class="right">Paid</th>
                <th class="right">Outstanding</th>
                <th>Due date</th>
                <th>Status</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="loan in investorLoans" :key="loan.id">
                <td>
                  <RouterLink :to="`/loans/${loan.id}`">{{ loan.loan_code }}</RouterLink>
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
          :page="investorPagination.current_page"
          :last-page="investorPagination.last_page"
          :total="investorPagination.total"
          :per-page="investorPagination.per_page"
          @update:page="loadLoanPage"
        />
      </template>
    </section>
  </EntityDetailLayout>
</template>

<style scoped>
.investor-details p {
  margin: var(--space-0);
}
.mini-stat-grid {
  display: grid;
  gap: var(--space-3);
  grid-template-columns: repeat(2, minmax(var(--space-0), 1fr));
}
.mini-stat-grid > div {
  background: var(--surface-muted);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  display: flex;
  flex-direction: column;
  gap: var(--space-1);
  padding: var(--space-4);
}
.mini-stat-grid span {
  color: var(--text-muted);
  font-size: var(--text-sm);
}
.mini-stat-grid strong {
  color: var(--text-primary);
  font-variant-numeric: tabular-nums;
}
.capitalize {
  text-transform: capitalize;
}
.loan-summary-grid {
  display: grid;
  gap: var(--space-3);
  grid-template-columns: 2fr repeat(3, 1fr);
}
.loan-summary-grid > div {
  background: var(--surface-muted);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  padding: var(--space-4);
}
.loan-summary-grid span {
  color: var(--text-muted);
  display: block;
  font-size: var(--text-sm);
}
.loan-summary-grid strong {
  display: block;
  font-size: var(--text-xl);
  font-variant-numeric: tabular-nums;
  margin-top: var(--space-1);
}
.loan-overdue {
  color: var(--danger);
}
@media (max-width: 760px) {
  .loan-summary-grid {
    grid-template-columns: 1fr 1fr;
  }
  .investor-loan-table {
    min-width: 760px;
  }
}
@media (max-width: 560px) {
  .mini-stat-grid {
    grid-template-columns: 1fr;
  }
}
</style>
