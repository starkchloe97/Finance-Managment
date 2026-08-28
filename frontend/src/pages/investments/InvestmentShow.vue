<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useInvestmentStore } from '@/stores/investmentStore'
import { money } from '@/utils/money'
import AllocationForm from '@/components/AllocationForm.vue'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
import FinanceStatus from '@/components/ui/FinanceStatus.vue'
import InfoTip from '@/components/ui/InfoTip.vue'
import { avatarStyle, initialOf } from '@/utils/avatar'

const route = useRoute()
const investmentStore = useInvestmentStore()
const { investment, loading, error } = storeToRefs(investmentStore)

const allocations = ref([])
const distributions = ref([])
const financeLoading = ref(false)
const financeError = ref('')
const actionError = ref('')
const pendingAction = ref(null)
const releasing = ref(null)

// ---- Derived ----
const isPool = computed(() => investment.value?.investment_category === 'pool')

const canMature = computed(() => {
  if (!investment.value?.maturity_date) return false
  const today = new Date().toISOString().slice(0, 10)
  return investment.value.maturity_date <= today
})

const returnTerms = computed(() => {
  if (investment.value?.return_type === 'percentage') {
    return `${investment.value.return_percentage || 0}% of profit`
  }
  return `${money(investment.value?.fixed_return_amount)} fixed`
})

const principal = computed(() => Number(investment.value?.amount || 0))
const expectedReturn = computed(() => Number(investment.value?.calculated_return_amount || 0))
const deduction = computed(() => Number(investment.value?.deduction_amount || 0))
const settlement = computed(() => Number(investment.value?.expected_settlement_amount || 0))

const allocated = computed(() => Number(investment.value?.allocated_amount || 0))
const remaining = computed(() => Number(investment.value?.remaining_capital || 0))
const allocatedShare = computed(() => {
  if (principal.value <= 0) return 0
  return Math.round((allocated.value / principal.value) * 100)
})

const isActive = computed(() => investment.value?.status === 'active')

// Lifecycle stages for the visual tracker
const lifecycleStages = computed(() => {
  const inv = investment.value || {}
  const withdrawnOrCancelled = Boolean(inv.withdrawn_at || inv.cancelled_at)
  return [
    {
      key: 'placed',
      label: 'Placed',
      date: inv.investment_date || '—',
      done: true,
      tip: 'The day the capital was placed with the company.',
    },
    {
      key: 'ended',
      label: inv.withdrawn_at ? 'Withdrawn' : inv.cancelled_at ? 'Cancelled' : 'Matured',
      date: inv.matured_at || inv.withdrawn_at || inv.cancelled_at || null,
      done: Boolean(inv.matured_at || inv.withdrawn_at || inv.cancelled_at),
      tip: inv.withdrawn_at
        ? 'Capital was withdrawn before the term ended.'
        : inv.cancelled_at
          ? 'The investment was cancelled.'
          : 'The agreed term has ended.',
    },
    {
      key: 'settled',
      label: 'Settled',
      date: inv.settled_at || null,
      done: Boolean(inv.settled_at),
      tip: 'Capital and return paid back to the investor. This closes the investment.',
    },
  ]
})

const totalDistributed = computed(() =>
  distributions.value.reduce((sum, d) => sum + Number(d.profit_amount || 0), 0),
)

// ---- Loading ----
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

// ---- Actions ----
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

// Services imported at bottom of script for readability — hoisted anyway.
import {
  getAllocations,
  getInvestmentDistributions,
  releaseAllocation,
} from '@/services/investmentFinanceService'
</script>

<template>
  <div class="investment-detail">
    <!-- Error -->
    <div v-if="error" class="card detail-error" role="alert">
      <div>
        <strong>Couldn't load this investment.</strong>
        <p>{{ error }}</p>
      </div>
      <button type="button" class="btn" @click="load">Try again</button>
    </div>

    <!-- Skeleton -->
    <div v-else-if="loading && !investment" class="detail-skeleton" aria-hidden="true">
      <div class="sk" style="height: 220px"></div>
      <div class="sk" style="height: 160px"></div>
      <div class="sk" style="height: 380px"></div>
    </div>

    <template v-else-if="investment">
      <!-- ============ HERO ============ -->
      <header class="card hero-card">
        <div class="hero-top">
          <div class="hero-id">
            <span class="hero-avatar" :style="avatarStyle(investment.investor?.name || 'I')" aria-hidden="true">
              {{ initialOf(investment.investor?.name || 'I') }}
            </span>
            <div class="hero-copy">
              <span class="section-kicker">Capital / Investments</span>
              <div class="hero-title-row">
                <h1>{{ investment.investment_code }}</h1>
                <FinanceStatus :status="investment.status" kind="investment" />
              </div>
              <div v-if="investment.investor" class="hero-customer">
                <RouterLink
                  class="hero-customer-link"
                  :to="`/investors/${investment.investor.id}`"
                >
                  {{ investment.investor.name }}
                </RouterLink>
                <span class="hero-sep" aria-hidden="true">·</span>
                <span class="hero-category">{{ investment.investment_category }}</span>
                <span class="hero-sep" aria-hidden="true">·</span>
                <span class="hero-date">{{ investment.investment_date || '—' }}</span>
              </div>
              <p v-else class="hero-date">{{ investment.investment_date || '—' }}</p>
            </div>
          </div>

          <div class="hero-actions">
            <RouterLink
              v-if="isActive"
              class="btn"
              :to="`/investments/${investment.id}/edit`"
            >
              Edit investment
            </RouterLink>
            <RouterLink
              v-if="canMature && isActive"
              class="btn"
              type="button"
              @click="requestAction('mature')"
            >
              Mark matured
            </RouterLink>
          </div>
        </div>

        <div class="hero-stats">
          <div class="hero-stat">
            <span>
              Principal
              <InfoTip label="The capital the investor placed with the company." />
            </span>
            <strong>{{ money(principal) }}</strong>
          </div>
          <div class="hero-stat">
            <span>
              Expected return
              <InfoTip :label="`How the investment earns: ${returnTerms}.`" />
            </span>
            <strong>{{ money(expectedReturn) }}</strong>
          </div>
          <div class="hero-stat">
            <span>
              Deduction
              <InfoTip label="An agreed amount held back from the payout — for example to cover fees or risk share." />
            </span>
            <strong>{{ money(deduction) }}</strong>
          </div>
          <div class="hero-stat">
            <span>
              Expected settlement
              <InfoTip label="What the investor should receive at the end: principal + return − deduction." />
            </span>
            <strong class="money-profit">{{ money(settlement) }}</strong>
          </div>
        </div>
      </header>

      <!-- ============ Money story ============ -->
      <section class="card flow-card">
        <header class="flow-head">
          <div>
            <h2>How the payout works</h2>
            <p class="hint">Follow the math from the capital placed to what comes back.</p>
          </div>
        </header>

        <div class="flow-chain">
          <div class="flow-step">
            <span class="flow-label">
              Principal
              <InfoTip label="The capital the investor placed." />
            </span>
            <strong>{{ money(principal) }}</strong>
          </div>
          <span class="flow-op" aria-hidden="true">+</span>
          <div class="flow-step">
            <span class="flow-label">
              Return
              <InfoTip :label="`Earned as ${returnTerms}.`" />
            </span>
            <strong>{{ money(expectedReturn) }}</strong>
          </div>
          <span class="flow-op" aria-hidden="true">−</span>
          <div class="flow-step">
            <span class="flow-label">
              Deduction
              <InfoTip label="Held back from the payout, as agreed when the investment was made." />
            </span>
            <strong>{{ money(deduction) }}</strong>
          </div>
          <span class="flow-op" aria-hidden="true">=</span>
          <div class="flow-step flow-final">
            <span class="flow-label">
              Settlement
              <InfoTip label="What the investor receives when the investment is settled." />
            </span>
            <strong>{{ money(settlement) }}</strong>
          </div>
        </div>
      </section>

      <p v-if="actionError" class="action-error" role="alert">{{ actionError }}</p>

      <!-- ============ Grid ============ -->
      <div class="detail-grid">
        <aside class="detail-side">
          <section class="card side-card">
            <h2 class="side-title">Terms</h2>
            <ul class="fact-list">
              <li>
                <span class="fact-label">
                  Category
                  <InfoTip :label="isPool
                    ? 'Pool capital is spread automatically across transport jobs.'
                    : 'Direct capital — you choose which jobs it is allocated to.'" />
                </span>
                <span class="fact-value fact-cap">{{ investment.investment_category || '—' }}</span>
              </li>
              <li>
                <span class="fact-label">
                  Return terms
                  <InfoTip label="Percentage returns share in job profit. Fixed returns pay a set amount." />
                </span>
                <span class="fact-value">{{ returnTerms }}</span>
              </li>
              <li>
                <span class="fact-label">Investment period</span>
                <span class="fact-value">
                  {{ investment.period_months ? `${investment.period_months} months` : '—' }}
                </span>
              </li>
              <li>
                <span class="fact-label">
                  Return policy
                  <InfoTip label="How soon after the term ends the return is paid out." />
                </span>
                <span class="fact-value">
                  {{ investment.return_policy_days ? `${investment.return_policy_days} days` : '—' }}
                </span>
              </li>
              <li>
                <span class="fact-label">
                  Maturity date
                  <InfoTip label="The term ends on this date — the investment can then be matured and settled." />
                </span>
                <span class="fact-value" :class="{ 'fact-warn': canMature && isActive }">
                  {{ investment.maturity_date || '—' }}
                </span>
              </li>
            </ul>
          </section>

          <section v-if="investment.notes" class="card side-card">
            <h2 class="side-title">Notes</h2>
            <p class="notes-text">{{ investment.notes }}</p>
          </section>

          <!-- Lifecycle: compact vertical tracker in the sidebar -->
          <section class="card side-card">
            <h2 class="side-title">
              Lifecycle
              <InfoTip label="Every investment moves from placed → ended → settled. Actions below move it forward." />
            </h2>
            <ol class="lifecycle-list">
              <li
                v-for="stage in lifecycleStages"
                :key="stage.key"
                :class="{ done: stage.done }"
                :title="stage.tip"
              >
                <span class="lifecycle-dot" aria-hidden="true">
                  <svg v-if="stage.done" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 6 9 17l-5-5" />
                  </svg>
                </span>
                <div class="lifecycle-body">
                  <b>{{ stage.label }}</b>
                  <span>{{ stage.done ? (stage.date || '—') : 'Pending' }}</span>
                </div>
              </li>
            </ol>

            <div v-if="isActive" class="lifecycle-actions">
              <button
                v-if="canMature"
                class="btn btn-sm"
                type="button"
                @click="requestAction('mature')"
              >
                Mark matured
              </button>
              <p v-else class="lifecycle-hint">
                Can mature on {{ investment.maturity_date || 'its maturity date' }}.
              </p>
              <button class="btn-light btn-sm" type="button" @click="requestAction('withdraw')">
                Withdraw
              </button>
              <button class="btn-danger btn-sm" type="button" @click="requestAction('cancel')">
                Cancel
              </button>
            </div>
            <div v-else-if="investment.status === 'matured' || investment.status === 'withdrawn'" class="lifecycle-actions">
              <button class="btn btn-sm" type="button" @click="requestAction('settle')">
                Settle — pay out {{ money(settlement) }}
              </button>
            </div>
          </section>
        </aside>

        <div class="detail-main">
          <!-- ===== Capital block (normal investments only) ===== -->
          <section v-if="!isPool" class="card block-card">
            <header class="block-head">
              <div class="block-title">
                <span class="block-icon icon-violet" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2" /><path d="M15 18H9" /><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.62l-3.48-4.35A1 1 0 0 0 17.52 8H14" /><circle cx="17" cy="18" r="2" /><circle cx="7" cy="18" r="2" />
                  </svg>
                </span>
                <div>
                  <h2>Capital on jobs</h2>
                  <p class="block-hint">
                    Where this capital is committed.
                    <InfoTip label="Direct investments fund specific transport jobs. Released capital becomes available again." />
                  </p>
                </div>
              </div>
              <span v-if="allocations.length" class="count-badge">{{ allocations.length }}</span>
            </header>

            <div class="alloc-progress">
              <div class="alloc-bar" role="img" :aria-label="`${allocatedShare}% allocated to jobs`">
                <span class="alloc-fill" :style="{ width: `${allocatedShare}%` }"></span>
              </div>
              <div class="alloc-legend">
                <span>
                  <b class="alloc-dot filled" aria-hidden="true"></b>
                  Allocated to jobs <strong>{{ money(allocated) }}</strong>
                </span>
                <span>
                  <b class="alloc-dot" aria-hidden="true"></b>
                  Available <strong>{{ money(remaining) }}</strong>
                </span>
              </div>
            </div>

            <div v-if="financeLoading" class="block-loading"><div class="sk" style="height: 120px"></div></div>
            <div v-else-if="financeError" class="block-error">
              <p>{{ financeError }}</p>
              <button class="btn-light btn-sm" type="button" @click="loadFinance">Try again</button>
            </div>
            <template v-else>
              <div v-if="allocations.length" class="table-wrap">
                <table>
                  <thead>
                    <tr>
                      <th>Job</th>
                      <th>Status</th>
                      <th class="right">
                        Amount
                        <InfoTip label="How much of this investment is committed to that job." />
                      </th>
                      <th>Allocated</th>
                      <th class="right">Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="allocation in allocations" :key="allocation.id">
                      <td>
                        <RouterLink v-if="allocation.job" class="row-code" :to="`/jobs/${allocation.job.id}`">
                          {{ allocation.job.code }}
                        </RouterLink>
                        <span v-else>—</span>
                      </td>
                      <td>
                        <FinanceStatus :status="allocation.status" kind="allocation" />
                      </td>
                      <td class="right row-amount">{{ money(allocation.amount) }}</td>
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
              <div v-else class="block-empty">
                <p>No jobs funded from this investment yet.</p>
              </div>

              <div v-if="isActive" class="allocation-form-wrap">
                <h3 class="sub-head">
                  Allocate available capital
                  <InfoTip label="Commit some of the available capital to a transport job. It will earn from that job's profit." />
                </h3>
                <AllocationForm :investment="investment" @created="allocationCreated" />
              </div>
            </template>
          </section>

          <!-- ===== Returns block ===== -->
          <section class="card block-card">
            <header class="block-head">
              <div class="block-title">
                <span class="block-icon icon-success" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="22 7 13.5 15.5 8.5 10.5 2 17" /><polyline points="16 7 22 7 22 13" />
                  </svg>
                </span>
                <div>
                  <h2>Profit distributions</h2>
                  <p class="block-hint">
                    Profit already paid out from jobs funded by this investment.
                  </p>
                </div>
              </div>
              <span v-if="totalDistributed > 0" class="count-badge money-profit">
                {{ money(totalDistributed) }}
              </span>
            </header>

            <div v-if="financeLoading" class="block-loading"><div class="sk" style="height: 120px"></div></div>
            <div v-else-if="financeError" class="block-error">
              <p>{{ financeError }}</p>
              <button class="btn-light btn-sm" type="button" @click="loadFinance">Try again</button>
            </div>
            <div v-else-if="!distributions.length" class="block-empty">
              <p>No profit has been distributed yet.
                <template v-if="isPool">Pool distributions are calculated automatically as jobs complete.</template>
                <template v-else>Run "Calculate distribution" on a funded job to pay out profit.</template>
              </p>
            </div>
            <div v-else class="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th>Job</th>
                    <th class="right">
                      Profit basis
                      <InfoTip label="The job profit the distribution was calculated from." />
                    </th>
                    <th class="right">
                      Distribution
                      <InfoTip label="This investment's share of that profit." />
                    </th>
                    <th>Status</th>
                    <th>Recorded</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="distribution in distributions" :key="distribution.id">
                    <td>
                      <RouterLink
                        v-if="distribution.transport_job?.id"
                        class="row-code"
                        :to="`/jobs/${distribution.transport_job.id}`"
                      >
                        {{ distribution.transport_job.code || `Job #${distribution.transport_job_id}` }}
                      </RouterLink>
                      <span v-else>Job #{{ distribution.transport_job_id }}</span>
                    </td>
                    <td class="right">{{ money(distribution.profit_basis) }}</td>
                    <td class="right money-profit">{{ money(distribution.profit_amount) }}</td>
                    <td>
                      <span class="status status-info capitalize">{{ distribution.status }}</span>
                    </td>
                    <td>{{ formatDateTime(distribution.distributed_at) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </section>
        </div>
      </div>

      <div class="detail-footer">
        <RouterLink class="btn-light" to="/investments">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M19 12H5" /><path d="m12 19-7-7 7-7" />
          </svg>
          Back to investments
        </RouterLink>
      </div>

      <ConfirmDialog
        :open="Boolean(pendingAction)"
        :title="`${pendingAction ? pendingAction.charAt(0).toUpperCase() + pendingAction.slice(1) : ''} investment?`"
        :message="`This will update the lifecycle status of ${investment.investment_code || 'this investment'}.`"
        :confirm-label="
          pendingAction ? pendingAction.charAt(0).toUpperCase() + pendingAction.slice(1) : 'Confirm'
        "
        :variant="pendingAction === 'cancel' ? 'danger' : 'primary'"
        @confirm="performAction"
        @cancel="pendingAction = null"
      />
    </template>
  </div>
</template>

<style scoped>
.investment-detail {
  display: flex;
  flex-direction: column;
  gap: 20px;
  min-width: 0;
}

/* ---------- Hero ---------- */
.hero-card { padding: 24px; }

.hero-top {
  align-items: flex-start;
  display: flex;
  gap: 16px;
  justify-content: space-between;
}

.hero-id { align-items: center; display: flex; gap: 16px; min-width: 0; }

.hero-avatar {
  align-items: center;
  border-radius: 50%;
  box-shadow: 0 4px 12px rgb(16 24 40 / 14%);
  color: #fff;
  display: inline-flex;
  flex: 0 0 56px;
  font-size: 21px;
  font-weight: 700;
  height: 56px;
  justify-content: center;
  width: 56px;
}

.hero-copy { min-width: 0; }
.hero-copy .section-kicker { margin-bottom: 4px; }

.hero-title-row {
  align-items: center;
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}
.hero-title-row h1 {
  font-size: 22px;
  font-weight: 700;
  letter-spacing: -0.02em;
  margin: 0;
}

.hero-customer {
  align-items: center;
  color: var(--text-secondary);
  display: flex;
  font-size: 14px;
  gap: 8px;
  margin-top: 6px;
}
.hero-customer-link { color: var(--text-secondary); font-weight: 500; }
.hero-customer-link:hover { color: var(--accent); }
.hero-sep { color: var(--text-muted); }
.hero-category {
  background: var(--surface-2);
  border-radius: 999px;
  color: var(--text-secondary);
  font-size: 11px;
  font-weight: 600;
  padding: 2px 9px;
  text-transform: capitalize;
}
.hero-date { color: var(--text-muted); font-size: 14px; margin-top: 6px; }

.hero-actions {
  align-items: center;
  display: flex;
  flex: 0 0 auto;
  flex-wrap: wrap;
  gap: 10px;
}

.hero-stats {
  border-top: 1px solid var(--border);
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  margin-top: 20px;
  padding-top: 20px;
}

.hero-stat {
  border-left: 1px solid var(--border);
  display: flex;
  flex-direction: column;
  gap: 3px;
  min-width: 0;
  padding: 0 16px;
}
.hero-stat:first-child { border-left: 0; padding-left: 0; }
.hero-stat > span {
  align-items: center;
  color: var(--text-muted);
  display: flex;
  font-size: 12px;
  font-weight: 500;
  gap: 5px;
}
.hero-stat strong {
  color: var(--text-primary);
  font-size: 19px;
  font-variant-numeric: tabular-nums;
  font-weight: 700;
  letter-spacing: -0.01em;
}

.action-error {
  background: var(--danger-soft);
  border: 1px solid var(--danger);
  border-radius: var(--radius-md);
  color: var(--danger);
  font-size: 14px;
  padding: 12px 16px;
}

/* ---------- Money flow ---------- */
.flow-card { min-width: 0; }

.flow-head { margin-bottom: 18px; }
.flow-head h2 { font-size: 15px; font-weight: 600; }

.flow-chain {
  align-items: stretch;
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.flow-step {
  background: var(--surface-2);
  border-radius: var(--radius-md);
  flex: 1 1 130px;
  min-width: 130px;
  padding: 14px 16px;
}

.flow-label {
  align-items: center;
  color: var(--text-muted);
  display: flex;
  font-size: 12px;
  font-weight: 500;
  gap: 6px;
  margin-bottom: 5px;
}

.flow-step strong {
  color: var(--text-primary);
  font-size: 20px;
  font-variant-numeric: tabular-nums;
  font-weight: 700;
  letter-spacing: -0.01em;
}

.flow-op {
  align-items: center;
  color: var(--text-muted);
  display: flex;
  font-size: 18px;
  font-weight: 600;
  padding: 0 2px;
}

.flow-final { background: var(--success-soft); flex: 1.4 1 150px; }
.flow-final strong { color: var(--success); }

/* ---------- Grid ---------- */
.detail-grid {
  align-items: start;
  display: grid;
  gap: 20px;
  grid-template-columns: 300px minmax(0, 1fr);
}

.detail-side {
  display: flex;
  flex-direction: column;
  gap: 20px;
  position: sticky;
  top: 20px;
}

.detail-main {
  display: flex;
  flex-direction: column;
  gap: 20px;
  min-width: 0;
}

/* ---------- Sidebar ---------- */
.side-card { padding: 20px; }

.side-title {
  align-items: center;
  color: var(--text-muted);
  display: flex;
  font-size: 11px;
  font-weight: 600;
  gap: 5px;
  letter-spacing: 0.08em;
  margin: 0 0 14px;
  text-transform: uppercase;
}

.fact-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
  list-style: none;
  margin: 0;
  padding: 0;
}
.fact-list li { display: flex; flex-direction: column; gap: 2px; }
.fact-label {
  align-items: center;
  color: var(--text-muted);
  display: flex;
  font-size: 11px;
  font-weight: 500;
  gap: 5px;
}
.fact-value { color: var(--text-primary); font-size: 14px; font-weight: 500; }
.fact-cap { text-transform: capitalize; }
.fact-warn { color: var(--warning); font-weight: 600; }

.notes-text {
  color: var(--text-secondary);
  font-size: 14px;
  line-height: 1.6;
  margin: 0;
  white-space: pre-line;
}

/* Lifecycle tracker */
.lifecycle-list {
  display: flex;
  flex-direction: column;
  gap: 0;
  list-style: none;
  margin: 0;
  padding: 0;
}

.lifecycle-list li {
  display: flex;
  gap: 12px;
  padding-bottom: 16px;
  position: relative;
}
.lifecycle-list li:last-child { padding-bottom: 0; }

.lifecycle-list li:not(:last-child)::before {
  background: var(--border);
  bottom: 2px;
  content: '';
  left: 9px;
  position: absolute;
  top: 22px;
  width: 2px;
}
.lifecycle-list li.done:not(:last-child)::before { background: var(--success); }

.lifecycle-dot {
  align-items: center;
  background: var(--surface);
  border: 2px solid var(--border-strong);
  border-radius: 50%;
  color: #fff;
  display: flex;
  flex: 0 0 20px;
  height: 20px;
  justify-content: center;
  width: 20px;
}
.lifecycle-dot svg { height: 10px; width: 10px; }
.lifecycle-list li.done .lifecycle-dot {
  background: var(--success);
  border-color: var(--success);
}

.lifecycle-body { display: flex; flex-direction: column; gap: 1px; min-width: 0; }
.lifecycle-body b { color: var(--text-primary); font-size: 13px; font-weight: 600; }
.lifecycle-body span { color: var(--text-muted); font-size: 12px; }

.lifecycle-actions {
  border-top: 1px solid var(--border);
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 16px;
  padding-top: 14px;
}
.lifecycle-actions .btn-sm { flex: 1 1 auto; justify-content: center; }

.lifecycle-hint {
  color: var(--text-muted);
  flex-basis: 100%;
  font-size: 12px;
  margin: 0;
}

/* ---------- Blocks ---------- */
.block-card { padding: 20px; }

.block-head {
  align-items: flex-start;
  display: flex;
  gap: 12px;
  justify-content: space-between;
  margin-bottom: 14px;
}

.block-title { align-items: flex-start; display: flex; gap: 12px; }
.block-title h2 { font-size: 15px; font-weight: 600; margin: 0; }
.block-hint {
  align-items: center;
  color: var(--text-muted);
  display: flex;
  flex-wrap: wrap;
  font-size: 13px;
  gap: 4px;
  margin: 2px 0 0;
}

.block-icon {
  align-items: center;
  border-radius: 9px;
  display: flex;
  flex: 0 0 32px;
  height: 32px;
  justify-content: center;
  width: 32px;
}
.block-icon svg { height: 15px; width: 15px; }
.icon-violet { background: var(--violet-soft); color: var(--violet); }
.icon-success { background: var(--success-soft); color: var(--success); }

.count-badge {
  align-items: center;
  background: var(--surface-2);
  border-radius: 999px;
  color: var(--text-secondary);
  display: inline-flex;
  font-size: 11px;
  font-weight: 600;
  height: 20px;
  justify-content: center;
  min-width: 20px;
  padding: 0 7px;
  white-space: nowrap;
}
.count-badge.money-profit {
  background: var(--success-soft);
  color: var(--success);
  font-size: 12px;
  height: 24px;
  padding: 0 10px;
}

.row-code { color: var(--text-primary); font-weight: 600; text-decoration: none; }
.row-code:hover { color: var(--accent); }
.row-amount { color: var(--text-primary); font-weight: 600; }

/* Allocation progress */
.alloc-progress { margin-bottom: 16px; }

.alloc-bar {
  background: var(--surface-2);
  border-radius: 999px;
  height: 10px;
  overflow: hidden;
}
.alloc-fill {
  background: var(--violet);
  display: block;
  height: 100%;
  transition: width 0.4s ease;
}

.alloc-legend {
  display: flex;
  flex-wrap: wrap;
  gap: 6px 18px;
  margin-top: 10px;
}
.alloc-legend > span {
  align-items: center;
  color: var(--text-secondary);
  display: inline-flex;
  font-size: 12px;
  gap: 7px;
}
.alloc-legend strong {
  color: var(--text-primary);
  font-variant-numeric: tabular-nums;
  font-weight: 600;
}
.alloc-dot {
  background: var(--surface-2);
  border: 1px solid var(--border-strong);
  border-radius: 50%;
  display: inline-block;
  height: 9px;
  width: 9px;
}
.alloc-dot.filled { background: var(--violet); border-color: var(--violet); }

.sub-head {
  align-items: center;
  color: var(--text-muted);
  display: flex;
  font-size: 11px;
  font-weight: 600;
  gap: 5px;
  letter-spacing: 0.08em;
  margin: 18px 0 10px;
  text-transform: uppercase;
}

.allocation-form-wrap {
  border-top: 1px solid var(--border);
  margin-top: 18px;
  padding-top: 4px;
}

.block-error {
  align-items: center;
  background: var(--danger-soft);
  border-radius: var(--radius-md);
  color: var(--danger);
  display: flex;
  font-size: 13px;
  gap: 12px;
  justify-content: space-between;
  padding: 12px 14px;
}
.block-error p { margin: 0; }

.block-empty {
  border: 1px dashed var(--border-strong);
  border-radius: var(--radius-md);
  color: var(--text-muted);
  font-size: 13px;
  padding: 18px 16px;
  text-align: center;
}
.block-empty p { margin: 0; }

/* ---------- Error / skeleton / footer ---------- */
.detail-error {
  align-items: center;
  border-color: var(--danger);
  color: var(--danger);
  display: flex;
  gap: 16px;
  justify-content: space-between;
}
.detail-error p { color: var(--text-secondary); margin: 4px 0 0; }

.detail-skeleton { display: flex; flex-direction: column; gap: 20px; }

.sk {
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  overflow: hidden;
  position: relative;
}
.sk::after {
  animation: shimmer 1.6s infinite;
  background: linear-gradient(90deg, transparent, rgb(255 255 255 / 70%), transparent);
  content: '';
  inset: 0;
  position: absolute;
  transform: translateX(-100%);
}
@keyframes shimmer { 100% { transform: translateX(100%); } }

.detail-footer { display: flex; }

/* ---------- Responsive ---------- */
@media (max-width: 1024px) {
  .detail-grid { grid-template-columns: 1fr; }
  .detail-side { position: static; }
  .hero-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); row-gap: 18px; }
  .hero-stat:nth-child(odd) { border-left: 0; padding-left: 0; }
}

@media (max-width: 700px) {
  .hero-top { flex-direction: column; }
  .hero-actions { width: 100%; }
  .hero-actions .btn { flex: 1; justify-content: center; }
  .detail-error { align-items: flex-start; flex-direction: column; }
  .block-error { flex-direction: column; align-items: flex-start; }
  .flow-op { display: none; }
  .flow-step { flex-basis: calc(50% - 5px); }
  .flow-final { flex-basis: 100%; }
}
</style>