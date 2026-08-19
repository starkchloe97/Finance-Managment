<script setup>
import { computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useInvestmentStore } from '@/stores/investmentStore'
import { money } from '@/utils/money'

const route = useRoute()
const router = useRouter()

const investmentStore = useInvestmentStore()

const { investment, loading, error } = storeToRefs(investmentStore)

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
})

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

.detail-card {
  display: flex;
  flex-direction: column;
  gap: 6px;
  padding: 20px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
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

@media (max-width: 900px) {
  .details-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 600px) {
  .details-grid {
    grid-template-columns: 1fr;
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
          <button type="button" @click="goBack">Back</button>

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

          <strong>
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
