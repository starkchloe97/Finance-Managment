<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useInvestmentStore } from '@/stores/investmentStore'
import { money } from '@/utils/money'
import AllocationForm from '@/components/AllocationForm.vue'
import { getAllocations, getInvestmentDistributions, releaseAllocation } from '@/services/investmentFinanceService'

const route = useRoute()
const router = useRouter()

const investmentStore = useInvestmentStore()

const { investment, loading, error } = storeToRefs(investmentStore)
const allocations = ref([])
const distributions = ref([])

const canMature = computed(() => {
  if (!investment.value?.maturity_date) {
    return false
  }

  const now = new Date()
  const today = [now.getFullYear(), now.getMonth() + 1, now.getDate()]
    .map((part, index) => (index === 0 ? part : String(part).padStart(2, '0')))
    .join('-')

  return investment.value.maturity_date <= today
})

const formatDateTime = (value) => {
  if (!value) return '-'

  return new Intl.DateTimeFormat(undefined, {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(new Date(value))
}

onMounted(async () => {
  await investmentStore.fetchInvestment(route.params.id)
  await loadFinance()
})

const loadFinance = async () => {
  const [allocationResponse, distributionResponse] = await Promise.all([getAllocations(route.params.id), getInvestmentDistributions(route.params.id)])
  allocations.value = allocationResponse.data.data
  distributions.value = distributionResponse.data.data
}

const release = async (allocation) => {
  if (!confirm('Release this allocation?')) return
  await releaseAllocation(allocation.id)
  await investmentStore.fetchInvestment(route.params.id)
  await loadFinance()
}

const handleMature = async () => {
  if (!investment.value) return

  if (!confirm('Mark this investment as matured?')) {
    return
  }

  try {
    await investmentStore.mature(investment.value.id)
  } catch (error) {
    console.error(error)
  }
}

const handleWithdraw = async () => {
  if (!investment.value) return

  if (!confirm('Withdraw this investment?')) {
    return
  }

  try {
    await investmentStore.withdraw(investment.value.id)
  } catch (error) {
    console.error(error)
  }
}

const handleSettle = async () => {
  if (!investment.value) return

  if (!confirm('Settle this investment?')) {
    return
  }

  try {
    await investmentStore.settle(investment.value.id)
  } catch (error) {
    console.error(error)
  }
}

const handleCancel = async () => {
  if (!investment.value) return

  if (!confirm('Cancel this investment?')) {
    return
  }

  try {
    await investmentStore.cancel(investment.value.id)
  } catch (error) {
    console.error(error)
  }
}

const goBack = () => {
  router.push({
    name: 'investments.index',
  })
}

const editInvestment = () => {
  router.push({
    name: 'investments.edit',
    params: {
      id: investment.value.id,
    },
  })
}
</script>

<style scoped>
.details-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 16px;
}

.page-container {
  max-width: 1180px;
  margin: 0 auto;
}

.page-header,
.section-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
}

.page-header {
  margin-bottom: 22px;
}

.page-header p,
.section-header p {
  margin: 4px 0 0;
  color: var(--muted);
}

.page-header > div:last-child,
.action-buttons {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.detail-card {
  display: flex;
  flex-direction: column;
  gap: 6px;
  padding: 20px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
}

.detail-card span {
  font-size: 13px;
}

.detail-card strong {
  font-size: 18px;
}

.detail-card small {
  font-size: 13px;
}

.detail-section {
  margin-top: 30px;
}

.detail-section > h2,
.section-header h2 {
  margin-bottom: 14px;
}

.capital-section {
  padding: 22px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
}

.capital-section .section-header {
  margin-bottom: 20px;
}

.capital-section .section-header h2 {
  margin-bottom: 0;
}

.capital-grid {
  gap: 20px;
}

.capital-card {
  min-height: 112px;
  justify-content: center;
}

.capital-card strong {
  color: var(--accent-hover);
  font-size: 22px;
}

.capital-form {
  margin-top: 26px;
  padding-top: 24px;
  border-top: 1px solid var(--border);
}

.table-wrap {
  overflow-x: auto;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
}

.table-wrap table {
  min-width: 560px;
}

.status-label {
  color: green;
  font-weight: 600;
  text-transform: capitalize;
}

.investment-financial-summary,
.investment-actions {
  margin-top: 30px;
  padding: 22px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
}

.investment-financial-summary .details-grid {
  margin-top: 18px;
}

.investment-actions p {
  margin: 0;
  color: var(--muted);
  font-size: 13px;
}

@media (max-width: 900px) {
  .details-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 600px) {
  .page-header,
  .section-header {
    flex-direction: column;
  }

  .page-header > div:last-child {
    width: 100%;
  }

  .details-grid {
    grid-template-columns: 1fr;
  }

  .investment-financial-summary,
  .investment-actions,
  .capital-section {
    padding: 18px;
  }
}
</style>

<template>
  <div class="page-container">
    <div v-if="loading" class="loading-state">Loading investment...</div>

    <div v-else-if="error" class="error-state">
      {{ error }}
    </div>

    <template v-else-if="investment">
      <div class="page-header">
        <div>
          <h1>
            {{ investment.investment_code }}
          </h1>

          <p>Investment details</p>
        </div>

        <div>
          <button class="btn-light" type="button" @click="goBack">Back</button>

          <button v-if="investment.status === 'active'" type="button" @click="editInvestment">
            Edit
          </button>
        </div>
      </div>

      <div class="details-grid">
        <div class="detail-card">
          <span>Investor</span>

          <strong>
            {{ investment.investor?.name || '-' }}
          </strong>

          <small>
            {{ investment.investor?.investor_code || '' }}
          </small>
        </div>

        <div class="detail-card">
          <span>Amount</span>

          <strong>
            {{ money(investment.amount) }}
          </strong>
        </div>

        <div class="detail-card">
          <span>Investment Date</span>

          <strong>
            {{ investment.investment_date }}
          </strong>
        </div>

        <div class="detail-card">
          <span>Status</span>

          <strong class="status-label">
            {{ investment.status }}
          </strong>
        </div>

        <div class="detail-card">
          <span>Period</span>

          <strong>
            {{ investment.period_months ? `${investment.period_months} months` : '-' }}
          </strong>
        </div>

        <div class="detail-card">
          <span>Return Policy</span>

          <strong>
            {{ investment.return_policy_days ? `${investment.return_policy_days} days` : '-' }}
          </strong>
        </div>

        <div class="detail-card">
          <span>Minimum Return</span>

          <strong>
            {{ investment.min_return_percent !== null ? `${investment.min_return_percent}%` : '-' }}
          </strong>
        </div>

        <div class="detail-card">
          <span>Maximum Return</span>

          <strong>
            {{ investment.max_return_percent !== null ? `${investment.max_return_percent}%` : '-' }}
          </strong>
        </div>

        <div class="detail-card">
          <span>Deduction</span>

          <strong>
            {{ money(investment.deduction_amount) }}
          </strong>
        </div>

        <div v-if="investment.matured_at" class="detail-card">
          <span>Matured At</span>

          <strong>
            {{ formatDateTime(investment.matured_at) }}
          </strong>
        </div>

        <div v-if="investment.settled_at" class="detail-card">
          <span>Settled At</span>
          <strong>{{ formatDateTime(investment.settled_at) }}</strong>
        </div>

        <div v-if="investment.cancelled_at" class="detail-card">
          <span>Cancelled At</span>
          <strong>{{ formatDateTime(investment.cancelled_at) }}</strong>
        </div>

        <div v-if="investment.withdrawn_at" class="detail-card">
          <span>Withdrawn At</span>

          <strong>
            {{ formatDateTime(investment.withdrawn_at) }}
          </strong>
        </div>
      </div>

      <section class="detail-section capital-section">
        <div class="section-header">
          <div>
            <h2>Capital</h2>
            <p>Track how much of this investment is committed to jobs.</p>
          </div>
        </div>

        <div class="details-grid capital-grid">
          <div class="detail-card capital-card"><span>Total capital</span><strong>{{ money(investment.amount) }}</strong></div>
          <div class="detail-card capital-card"><span>Allocated capital</span><strong>{{ money(investment.allocated_amount) }}</strong></div>
          <div class="detail-card capital-card"><span>Available capital</span><strong>{{ money(investment.remaining_capital) }}</strong></div>
        </div>

        <div v-if="investment.status === 'active'" class="capital-form">
          <AllocationForm :investment="investment" @created="async () => { await investmentStore.fetchInvestment(route.params.id); await loadFinance() }" />
        </div>
      </section>

      <section class="detail-section">
        <h2>Allocations</h2>
        <p v-if="!allocations.length">No allocations yet.</p>
        <div v-else class="table-wrap"><table><thead><tr><th>Job</th><th>Amount</th><th>Status</th><th></th></tr></thead><tbody><tr v-for="allocation in allocations" :key="allocation.id"><td>{{ allocation.job?.code }}</td><td>{{ money(allocation.amount) }}</td><td><span class="status-label">{{ allocation.status }}</span></td><td><button v-if="allocation.status === 'active'" class="btn-sm" @click="release(allocation)">Release</button></td></tr></tbody></table></div>
      </section>

      <section class="detail-section">
        <h2>Profit Distributions</h2>
        <p v-if="!distributions.length">No distributions yet.</p>
        <div v-else class="table-wrap"><table><thead><tr><th>Job</th><th>Basis</th><th>Profit</th></tr></thead><tbody><tr v-for="distribution in distributions" :key="distribution.id"><td>{{ distribution.transport_job_id }}</td><td>{{ money(distribution.profit_basis) }}</td><td>{{ money(distribution.profit_amount) }}</td></tr></tbody></table></div>
      </section>

      <div v-if="investment.notes" class="detail-section">
        <h2>Notes</h2>

        <p>
          {{ investment.notes }}
        </p>
      </div>
    </template>

    <section v-if="investment" class="investment-financial-summary">
      <div class="section-header">
        <div>
          <h2>Financial Summary</h2>

          <p>Current investment and expected settlement values.</p>
        </div>
      </div>

      <div class="details-grid">
        <div class="detail-card">
          <span>Principal</span>

          <strong>
            {{ money(investment.amount) }}
          </strong>
        </div>

        <div class="detail-card">
          <span>Maturity Date</span>

          <strong>
            {{ investment.maturity_date || '-' }}
          </strong>
        </div>

        <div class="detail-card">
          <span>Minimum Return</span>

          <strong>
            {{ money(investment.minimum_return_amount) }}
          </strong>
        </div>

        <div class="detail-card">
          <span>Maximum Return</span>

          <strong>
            {{ money(investment.maximum_return_amount) }}
          </strong>
        </div>

        <div class="detail-card">
          <span>Deduction</span>

          <strong>
            {{ money(investment.deduction_amount) }}
          </strong>
        </div>

        <div class="detail-card">
          <span>Expected Settlement</span>

          <strong>
            {{ money(investment.minimum_settlement_amount) }}
            -
            {{ money(investment.maximum_settlement_amount) }}
          </strong>
        </div>
      </div>
    </section>

    <section v-if="investment" class="investment-actions">
      <div class="section-header">
        <div>
          <h2>Investment Actions</h2>
          <p>Manage the current investment lifecycle.</p>
        </div>
      </div>

      <div class="action-buttons">
        <button
          v-if="investment?.status === 'active' && canMature"
          type="button"
          @click="handleMature"
          :disabled="investmentStore.loading"
        >
          Mature
        </button>

        <p v-if="investment?.status === 'active' && !canMature">
          This investment can mature on {{ investment.maturity_date || 'its maturity date' }}.
        </p>

        <button
          v-if="investment?.status === 'active'"
          type="button"
          @click="handleWithdraw"
          :disabled="investmentStore.loading"
        >
          Withdraw
        </button>

        <button
          v-if="investment?.status === 'active'"
          type="button"
          @click="handleCancel"
          :disabled="investmentStore.loading"
        >
          Cancel
        </button>

        <button
          v-if="investment?.status === 'matured' || investment?.status === 'withdrawn'"
          type="button"
          @click="handleSettle"
          :disabled="investmentStore.loading"
        >
          Settle
        </button>
      </div>
    </section>
  </div>
</template>
