<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useInvestorStore } from '@/stores/investorStore'
import { useInvestmentStore } from '@/stores/investmentStore'
import { money } from '@/utils/money'
import EntityDetailLayout from '@/components/ui/EntityDetailLayout.vue'

const route = useRoute()
const investorStore = useInvestorStore()
const investmentStore = useInvestmentStore()

const { investor, loading, error } = storeToRefs(investorStore)
const {
  investments,
  investorInvestmentTotals,
  loading: investmentsLoading,
  error: investmentsError,
} = storeToRefs(investmentStore)

const activeTab = ref('overview')
const tabs = [
  { key: 'overview', label: 'Overview' },
  { key: 'investments', label: 'Investments' },
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
  ])
}

const statusClass = (status) => (status === 'active' ? 'status-success' : 'status-warning')

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
@media (max-width: 560px) {
  .mini-stat-grid {
    grid-template-columns: 1fr;
  }
}
</style>
