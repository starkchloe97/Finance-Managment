<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useInvestmentStore } from '@/stores/investmentStore'
import { money } from '@/utils/money'
import AllocationForm from '@/components/AllocationForm.vue'
import EntityDetailLayout from '@/components/ui/EntityDetailLayout.vue'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
import {
  getAllocations,
  getInvestmentDistributions,
  releaseAllocation,
} from '@/services/investmentFinanceService'

const route = useRoute()
const investmentStore = useInvestmentStore()
const { investment, loading, error } = storeToRefs(investmentStore)

const activeTab = ref('overview')
const allocations = ref([])
const distributions = ref([])
const financeLoading = ref(false)
const financeError = ref('')
const actionError = ref('')
const pendingAction = ref(null)
const releasing = ref(null)

const tabs = computed(() => {
  const standard = [{ key: 'overview', label: 'Overview' }]
  if (investment.value?.investment_category === 'normal') {
    standard.push({ key: 'capital', label: 'Capital' })
  }
  standard.push({ key: 'returns', label: 'Returns' }, { key: 'lifecycle', label: 'Lifecycle' })
  return standard
})

const stats = computed(() => [
  { label: 'Principal', value: money(investment.value?.amount), tone: 'revenue' },
  {
    label: 'Expected return',
    value: money(investment.value?.calculated_return_amount),
    tone: 'profit',
  },
  { label: 'Deduction', value: money(investment.value?.deduction_amount), tone: 'cost' },
  {
    label: 'Expected settlement',
    value: money(investment.value?.expected_settlement_amount),
    tone: 'neutral',
  },
])

const canMature = computed(() => {
  if (!investment.value?.maturity_date) return false
  const today = new Date().toISOString().slice(0, 10)
  return investment.value.maturity_date <= today
})

const returnTerms = computed(() => {
  if (investment.value?.return_type === 'percentage') {
    return `${investment.value.return_percentage || 0}% percentage return`
  }
  return `${money(investment.value?.fixed_return_amount)} fixed return`
})

const statusClass = computed(() => {
  const status = investment.value?.status
  if (status === 'active') return 'status-success'
  if (status === 'cancelled') return 'status-danger'
  if (status === 'matured' || status === 'withdrawn') return 'status-warning'
  return 'status-info'
})

const formatDateTime = (value) => {
  if (!value) return '—'
  return new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'short' }).format(
    new Date(value),
  )
}

const loadFinance = async () => {
  financeLoading.value = true
  financeError.value = ''
  try {
    const [allocationResponse, distributionResponse] = await Promise.all([
      getAllocations(route.params.id),
      getInvestmentDistributions(route.params.id),
    ])
    allocations.value = allocationResponse.data.data
    distributions.value = distributionResponse.data.data
  } catch (requestError) {
    financeError.value =
      requestError.response?.data?.message || 'Could not load allocation and return records.'
  } finally {
    financeLoading.value = false
  }
}

const load = async () => {
  await investmentStore.fetchInvestment(route.params.id)
  await loadFinance()
}

const requestAction = (action) => {
  actionError.value = ''
  pendingAction.value = action
}

const performAction = async () => {
  if (!pendingAction.value || !investment.value) return
  const action = pendingAction.value
  try {
    await investmentStore[action](investment.value.id)
    pendingAction.value = null
    await loadFinance()
  } catch (requestError) {
    actionError.value =
      requestError.response?.data?.message || `Could not ${action} this investment.`
    pendingAction.value = null
  }
}

const release = async (allocation) => {
  if (releasing.value) return
  releasing.value = allocation.id
  financeError.value = ''
  try {
    await releaseAllocation(allocation.id)
    await investmentStore.fetchInvestment(route.params.id)
    await loadFinance()
  } catch (requestError) {
    financeError.value =
      requestError.response?.data?.message || 'Could not release this allocation.'
  } finally {
    releasing.value = null
  }
}

const allocationCreated = async () => {
  await investmentStore.fetchInvestment(route.params.id)
  await loadFinance()
}

onMounted(() => {
  load().catch(() => {})
})
</script>

<template>
  <EntityDetailLayout
    v-model="activeTab"
    :tabs="tabs"
    :loading="loading && !investment"
    :error="error"
    :stats="stats"
    @retry="load"
  >
    <template #title>
      <h1>{{ investment?.investment_code }}</h1>
      <span v-if="investment?.status" class="status" :class="statusClass">
        {{ investment.status }}
      </span>
      <span class="hint">{{ investment?.investor?.name || 'Investment details' }}</span>
    </template>

    <template #actions>
      <RouterLink class="btn-light" to="/investments">Back</RouterLink>
      <RouterLink
        v-if="investment?.status === 'active'"
        class="btn"
        :to="`/investments/${investment.id}/edit`"
      >
        Edit
      </RouterLink>
    </template>

    <section v-if="investment && activeTab === 'overview'" class="entity-section">
      <div class="card">
        <div class="section-head">
          <div>
            <h3>Investment overview</h3>
            <p class="hint">Terms and status for this capital placement.</p>
          </div>
          <RouterLink class="btn-light btn-sm" :to="`/investors/${investment.investor_id}`">
            View investor
          </RouterLink>
        </div>
        <div class="grid overview-grid">
          <div class="field">
            <label>Investor</label>
            <p>{{ investment.investor?.name || '—' }}</p>
          </div>
          <div class="field">
            <label>Category</label>
            <p class="capitalize">{{ investment.investment_category }}</p>
          </div>
          <div class="field">
            <label>Investment date</label>
            <p>{{ investment.investment_date || '—' }}</p>
          </div>
          <div class="field">
            <label>Investment period</label>
            <p>{{ investment.period_months ? `${investment.period_months} months` : '—' }}</p>
          </div>
          <div class="field">
            <label>Return policy</label>
            <p>
              {{ investment.return_policy_days ? `${investment.return_policy_days} days` : '—' }}
            </p>
          </div>
          <div class="field">
            <label>Maturity date</label>
            <p>{{ investment.maturity_date || '—' }}</p>
          </div>
          <div class="field">
            <label>Return terms</label>
            <p>{{ returnTerms }}</p>
          </div>
          <div class="field">
            <label>Status</label>
            <p>
              <span class="status" :class="statusClass">{{ investment.status }}</span>
            </p>
          </div>
        </div>
      </div>
      <div v-if="investment.notes" class="card">
        <h3>Notes</h3>
        <p>{{ investment.notes }}</p>
      </div>
    </section>

    <section v-if="investment && activeTab === 'capital'" class="entity-section">
      <div class="card">
        <div class="section-head">
          <div>
            <h3>Capital allocation</h3>
            <p class="hint">Normal investment capital committed to transport jobs.</p>
          </div>
        </div>
        <div class="mini-stat-grid">
          <div>
            <span>Total capital</span><strong>{{ money(investment.amount) }}</strong>
          </div>
          <div>
            <span>Allocated capital</span><strong>{{ money(investment.allocated_amount) }}</strong>
          </div>
          <div>
            <span>Available capital</span><strong>{{ money(investment.remaining_capital) }}</strong>
          </div>
        </div>
        <div v-if="investment.status === 'active'" class="allocation-form-wrap">
          <h4>Allocate available capital</h4>
          <AllocationForm :investment="investment" @created="allocationCreated" />
        </div>
      </div>

      <div v-if="financeLoading" class="state-panel state-loading">
        <div class="skeleton-block"></div>
      </div>
      <div v-else-if="financeError" class="state-panel state-error">
        <p>{{ financeError }}</p>
        <button class="btn" type="button" @click="loadFinance">Try again</button>
      </div>
      <div v-else-if="!allocations.length" class="state-panel state-empty">
        <p>No job allocations have been recorded for this investment.</p>
      </div>
      <div v-else class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Job</th>
              <th>Status</th>
              <th class="right">Amount</th>
              <th>Allocated</th>
              <th class="right">Action</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="allocation in allocations" :key="allocation.id">
              <td>
                <RouterLink v-if="allocation.job" :to="`/jobs/${allocation.job.id}`">{{
                  allocation.job.code
                }}</RouterLink
                ><span v-else>—</span>
              </td>
              <td>
                <span
                  class="status"
                  :class="allocation.status === 'active' ? 'status-success' : 'status-info'"
                  >{{ allocation.status }}</span
                >
              </td>
              <td class="right money">{{ money(allocation.amount) }}</td>
              <td>{{ formatDateTime(allocation.allocated_at) }}</td>
              <td class="right">
                <button
                  v-if="allocation.status === 'active'"
                  type="button"
                  class="btn-light btn-sm"
                  :disabled="releasing === allocation.id"
                  @click="release(allocation)"
                >
                  {{ releasing === allocation.id ? 'Releasing…' : 'Release' }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <section v-if="investment && activeTab === 'returns'" class="entity-section">
      <div class="card">
        <h3>Settlement breakdown</h3>
        <div class="mini-stat-grid settlement-grid">
          <div>
            <span>Principal</span><strong>{{ money(investment.amount) }}</strong>
          </div>
          <div>
            <span>Calculated return</span
            ><strong>{{ money(investment.calculated_return_amount) }}</strong>
          </div>
          <div>
            <span>Deduction</span><strong>{{ money(investment.deduction_amount) }}</strong>
          </div>
          <div>
            <span>Expected settlement</span
            ><strong>{{ money(investment.expected_settlement_amount) }}</strong>
          </div>
        </div>
      </div>
      <div v-if="financeLoading" class="state-panel state-loading">
        <div class="skeleton-block"></div>
      </div>
      <div v-else-if="financeError" class="state-panel state-error">
        <p>{{ financeError }}</p>
        <button class="btn" type="button" @click="loadFinance">Try again</button>
      </div>
      <div v-else-if="!distributions.length" class="state-panel state-empty">
        <p>No profit distributions have been calculated for this investment.</p>
      </div>
      <div v-else class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Job</th>
              <th class="right">Profit basis</th>
              <th class="right">Distribution</th>
              <th>Status</th>
              <th>Recorded</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="distribution in distributions" :key="distribution.id">
              <td>{{ distribution.transport_job_id }}</td>
              <td class="right money">{{ money(distribution.profit_basis) }}</td>
              <td class="right money-profit">{{ money(distribution.profit_amount) }}</td>
              <td>
                <span class="status status-info">{{ distribution.status }}</span>
              </td>
              <td>{{ formatDateTime(distribution.distributed_at) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <section v-if="investment && activeTab === 'lifecycle'" class="entity-section">
      <div class="card lifecycle-card">
        <div>
          <h3>Lifecycle</h3>
          <p class="hint">
            The backend controls lifecycle transitions; unavailable actions are intentionally
            hidden.
          </p>
        </div>
        <ol class="lifecycle-list">
          <li :class="{ complete: true }">
            <strong>Created</strong><span>{{ investment.investment_date || '—' }}</span>
          </li>
          <li :class="{ complete: investment.matured_at }">
            <strong>Matured</strong><span>{{ formatDateTime(investment.matured_at) }}</span>
          </li>
          <li :class="{ complete: investment.settled_at }">
            <strong>Settled</strong><span>{{ formatDateTime(investment.settled_at) }}</span>
          </li>
          <li v-if="investment.withdrawn_at" class="complete">
            <strong>Withdrawn</strong><span>{{ formatDateTime(investment.withdrawn_at) }}</span>
          </li>
          <li v-if="investment.cancelled_at" class="complete">
            <strong>Cancelled</strong><span>{{ formatDateTime(investment.cancelled_at) }}</span>
          </li>
        </ol>
      </div>
      <div class="card">
        <div class="section-head">
          <div>
            <h3>Actions</h3>
            <p class="hint">Actions change the recorded lifecycle state.</p>
          </div>
        </div>
        <p v-if="actionError" class="error" role="alert">{{ actionError }}</p>
        <div class="actions">
          <button
            v-if="investment.status === 'active' && canMature"
            type="button"
            @click="requestAction('mature')"
          >
            Mark matured
          </button>
          <span v-else-if="investment.status === 'active'" class="hint"
            >Eligible to mature on {{ investment.maturity_date || 'its maturity date' }}.</span
          >
          <button
            v-if="investment.status === 'active'"
            type="button"
            class="btn-light"
            @click="requestAction('withdraw')"
          >
            Withdraw
          </button>
          <button
            v-if="investment.status === 'active'"
            type="button"
            class="btn-danger"
            @click="requestAction('cancel')"
          >
            Cancel
          </button>
          <button
            v-if="investment.status === 'matured' || investment.status === 'withdrawn'"
            type="button"
            @click="requestAction('settle')"
          >
            Settle investment
          </button>
        </div>
      </div>
    </section>
  </EntityDetailLayout>

  <ConfirmDialog
    :open="Boolean(pendingAction)"
    :title="`${pendingAction ? pendingAction.charAt(0).toUpperCase() + pendingAction.slice(1) : ''} investment?`"
    :message="`This will update the lifecycle status of ${investment?.investment_code || 'this investment'}.`"
    :confirm-label="
      pendingAction ? pendingAction.charAt(0).toUpperCase() + pendingAction.slice(1) : 'Confirm'
    "
    :variant="pendingAction === 'cancel' ? 'danger' : 'primary'"
    :loading="loading"
    @confirm="performAction"
    @cancel="pendingAction = null"
  />
</template>

<style scoped>
.overview-grid p {
  margin: var(--space-0);
}
.capitalize {
  text-transform: capitalize;
}
.mini-stat-grid {
  display: grid;
  gap: var(--space-3);
  grid-template-columns: repeat(3, minmax(var(--space-0), 1fr));
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
  font-variant-numeric: tabular-nums;
}
.allocation-form-wrap {
  border-top: 1px solid var(--border);
  margin-top: var(--space-5);
  padding-top: var(--space-5);
}
.allocation-form-wrap h4 {
  margin-bottom: var(--space-3);
}
.settlement-grid {
  grid-template-columns: repeat(4, minmax(var(--space-0), 1fr));
}
.lifecycle-card {
  display: grid;
  gap: var(--space-5);
  grid-template-columns: minmax(190px, 1fr) 2fr;
}
.lifecycle-list {
  display: grid;
  gap: var(--space-2);
  list-style: none;
  margin: var(--space-0);
  padding: var(--space-0);
}
.lifecycle-list li {
  align-items: center;
  border-left: 2px solid var(--border-strong);
  display: flex;
  gap: var(--space-3);
  justify-content: space-between;
  padding: var(--space-2) var(--space-3);
}
.lifecycle-list li.complete {
  border-left-color: var(--success);
}
.lifecycle-list span {
  color: var(--text-muted);
  font-size: var(--text-sm);
}
@media (max-width: 900px) {
  .settlement-grid {
    grid-template-columns: repeat(2, minmax(var(--space-0), 1fr));
  }
  .lifecycle-card {
    grid-template-columns: 1fr;
  }
}
@media (max-width: 560px) {
  .mini-stat-grid,
  .settlement-grid {
    grid-template-columns: 1fr;
  }
  .lifecycle-list li {
    align-items: flex-start;
    flex-direction: column;
    gap: var(--space-1);
  }
}
</style>
