<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import {
  getJob,
  updateJobStatus,
  updateJobNotes,
  getJobActivities,
} from '@/services/transportJobService'
import { money } from '@/utils/money'
import { nextStatuses, statusLabel } from '@/utils/jobStatus'
import {
  createDistribution,
  getJobDistributions,
  createFinancialAdjustment,
} from '@/services/investmentFinanceService'
import JobStatusTimeline from '@/components/jobs/JobStatusTimeline.vue'
import JobFinancialFlow from '@/components/jobs/JobFinancialFlow.vue'
import JobBudget from '@/components/jobs/JobBudget.vue'
import JobExpenses from '@/components/jobs/JobExpenses.vue'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
import InfoTip from '@/components/ui/InfoTip.vue'
import { avatarStyle, initialOf } from '@/utils/avatar'

const route = useRoute()

const job = ref(null)
const loading = ref(true)
const error = ref('')
const actionError = ref('')

// Status move
const nextStatus = ref('')
const statusDialog = ref(false)

// Notes
const notes = ref('')
const notesSaved = ref(false)
let notesTimer = null

// Distributions / adjustments
const distributions = ref([])
const distributing = ref(false)
const adjustmentSaving = ref(false)
const adjustmentError = ref('')
const adjustment = reactive({ field: '', old_value: '', new_value: '', reason: '' })

const activities = ref([])

const isLoss = computed(() => Number(job.value?.final_profit) < 0)
const actualCost = computed(() =>
  job.value ? Number(job.value.cost_price) + Number(job.value.extra_costs) : 0,
)
const canAdvance = computed(() => nextStatuses(job.value?.status || '').length > 0)
const nextStageLabel = computed(() =>
  canAdvance.value ? statusLabel(nextStatuses(job.value.status)[0]) : '',
)

const expensesRef = ref(null)

const fail = (e, fallback) => {
  actionError.value = e?.response?.data?.message || fallback
}

const loadActivities = async () => {
  const { data } = await getJobActivities(job.value.id)
  activities.value = data.data
}

const apply = (updated) => {
  job.value = updated
  notes.value = updated.internal_notes || ''
}

const load = async () => {
  loading.value = true
  error.value = ''
  actionError.value = ''
  try {
    const { data } = await getJob(route.params.id)
    apply(data.data)
    distributions.value = (await getJobDistributions(route.params.id)).data.data
    await loadActivities()
  } catch (e) {
    error.value = e.response?.data?.message || 'Could not load job.'
  } finally {
    loading.value = false
  }
}

const onJobUpdated = (updated) => {
  apply(updated)
  loadActivities()
}

const distribute = async (allocation) => {
  if (distributing.value || job.value.financially_locked_at) return
  distributing.value = true
  actionError.value = ''
  try {
    await createDistribution(job.value.id, { investment_id: allocation.investment_id })
    await load()
  } catch (e) {
    fail(e, 'Could not calculate distribution')
  } finally {
    distributing.value = false
  }
}

const saveAdjustment = async () => {
  if (adjustmentSaving.value) return
  adjustmentSaving.value = true
  adjustmentError.value = ''
  try {
    await createFinancialAdjustment(job.value.id, adjustment)
    Object.assign(adjustment, { field: '', old_value: '', new_value: '', reason: '' })
    await load()
  } catch (e) {
    adjustmentError.value =
      e.response?.data?.errors?.reason?.[0] ||
      e.response?.data?.errors?.field?.[0] ||
      e.response?.data?.message ||
      'Could not record the adjustment.'
  } finally {
    adjustmentSaving.value = false
  }
}

const openStatusDialog = () => {
  nextStatus.value = nextStatuses(job.value.status)[0] || ''
  statusDialog.value = true
}

const moveStatus = async () => {
  if (!nextStatus.value) return
  statusDialog.value = false
  actionError.value = ''
  try {
    const { data } = await updateJobStatus(job.value.id, nextStatus.value)
    apply(data.data)
    nextStatus.value = ''
    await loadActivities()
  } catch (e) {
    fail(e, 'Could not update status')
  }
}

const saveNotes = async () => {
  notesSaved.value = false
  actionError.value = ''
  try {
    const { data } = await updateJobNotes(job.value.id, notes.value)
    apply(data.data)
    notesSaved.value = true
    clearTimeout(notesTimer)
    notesTimer = setTimeout(() => (notesSaved.value = false), 2500)
    await loadActivities()
  } catch (e) {
    fail(e, 'Could not save notes')
  }
}

const goToExpenses = () => {
  expensesRef.value?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

const when = (value) => (value ? new Date(value).toLocaleString() : '—')
const whenDate = (value) => (value ? new Date(value).toLocaleDateString() : '—')

onMounted(load)
</script>

<template>
  <div class="job-detail">
    <!-- Error -->
    <div v-if="error" class="card detail-error" role="alert">
      <div>
        <strong>Couldn't load this job.</strong>
        <p>{{ error }}</p>
      </div>
      <button type="button" class="btn" @click="load">Try again</button>
    </div>

    <!-- Skeleton -->
    <div v-else-if="loading && !job" class="detail-skeleton" aria-hidden="true">
      <div class="sk" style="height: 260px"></div>
      <div class="sk" style="height: 160px"></div>
      <div class="sk" style="height: 360px"></div>
    </div>

    <template v-else-if="job">
      <!-- ============ HERO ============ -->
      <header class="card hero-card">
        <div class="hero-top">
          <div class="hero-id">
            <span class="hero-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2" /><path d="M15 18H9" /><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.62l-3.48-4.35A1 1 0 0 0 17.52 8H14" /><circle cx="17" cy="18" r="2" /><circle cx="7" cy="18" r="2" />
              </svg>
            </span>
            <div class="hero-copy">
              <span class="section-kicker">Operations / Transport jobs</span>
              <div class="hero-title-row">
                <h1>{{ job.code }}</h1>
                <span class="status" :class="`status-${job.status}`">
                  {{ statusLabel(job.status) }}
                </span>
              </div>
              <div v-if="job.customer?.name" class="hero-customer">
                <span class="customer-avatar" :style="avatarStyle(job.customer.name)" aria-hidden="true">
                  {{ initialOf(job.customer.name) }}
                </span>
                <RouterLink class="hero-customer-link" :to="`/customers/${job.customer.id}`">
                  {{ job.customer.name }}
                </RouterLink>
                <span class="hero-sep" aria-hidden="true">·</span>
                <span class="hero-date">{{ whenDate(job.job_date) }}</span>
              </div>
              <p v-else class="hero-date">No customer · {{ whenDate(job.job_date) }}</p>
            </div>
          </div>

          <div class="hero-actions">
            <button v-if="canAdvance" type="button" class="btn" @click="openStatusDialog">
              Advance to {{ nextStageLabel }}
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M5 12h14" /><path d="m12 5 7 7-7 7" />
              </svg>
            </button>
            <span v-else class="hero-done">Completed — no stages left</span>
            <button type="button" class="btn-light" @click="goToExpenses">+ Add Expense</button>
          </div>
        </div>

        <div class="hero-stats">
          <div class="hero-stat">
            <span>Revenue</span>
            <strong>{{ money(job.sell_price) }}</strong>
          </div>
          <div class="hero-stat">
            <span>Planned cost</span>
            <strong>{{ money(job.cost_price) }}</strong>
          </div>
          <div class="hero-stat">
            <span>
              Actual cost
              <InfoTip label="Planned cost plus every unexpected expense recorded on this job." />
            </span>
            <strong>{{ money(actualCost) }}</strong>
          </div>
          <div class="hero-stat">
            <span>Profit</span>
            <strong :class="isLoss ? 'money-loss' : 'money-profit'">
              {{ money(job.final_profit) }}
            </strong>
          </div>
          <div class="hero-stat">
            <span>
              Margin
              <InfoTip label="Final profit as a share of the quoted price." />
            </span>
            <strong>{{ job.margin != null ? `${job.margin}%` : '—' }}</strong>
          </div>
        </div>

        <div class="hero-timeline">
          <JobStatusTimeline :status="job.status" />
        </div>
      </header>

      <p v-if="actionError" class="action-error" role="alert">{{ actionError }}</p>

      <!-- ============ Money story ============ -->
      <JobFinancialFlow
        :sell-price="job.sell_price"
        :cost-price="job.cost_price"
        :extra-costs="job.extra_costs"
        :final-profit="job.final_profit"
      />

      <!-- ============ Blocks ============ -->
      <div class="detail-grid">
        <!-- Sidebar -->
        <aside class="detail-side">
          <section class="card side-card">
            <h2 class="side-title">Job facts</h2>
            <ul class="fact-list">
              <li>
                <span class="fact-label">Customer</span>
                <span class="fact-value">
                  {{ job.customer?.name || '—' }}
                </span>
              </li>
              <li>
                <span class="fact-label">Job date</span>
                <span class="fact-value">{{ whenDate(job.job_date) }}</span>
              </li>
              <li>
                <span class="fact-label">Estimate</span>
                <span class="fact-value">
                  <RouterLink
                    v-if="job.estimate?.id"
                    :to="`/estimates/${job.estimate.id}`"
                  >
                    {{ job.estimate.code || 'View quote' }} →
                  </RouterLink>
                  <template v-else>Not linked</template>
                </span>
              </li>
              <li>
                <span class="fact-label">Finances</span>
                <span class="fact-value">
                  <span v-if="job.financially_locked_at" class="lock-badge locked">🔒 Locked</span>
                  <span v-else class="lock-badge open">Open</span>
                </span>
              </li>
            </ul>
          </section>

          <section class="card side-card">
            <h2 class="side-title">Internal notes</h2>
            <p class="side-hint">Only your team sees this — never the customer.</p>
            <form @submit.prevent="saveNotes">
              <textarea v-model="notes" rows="4" placeholder="Driver says the crane will be an hour late…"></textarea>
              <div class="notes-actions">
                <button type="submit" class="btn btn-sm">Save notes</button>
                <transition name="fade">
                  <span v-if="notesSaved" class="saved-flash">✓ Saved</span>
                </transition>
              </div>
            </form>
          </section>
        </aside>

        <!-- Main column -->
        <div class="detail-main">
          <div ref="expensesRef" class="scroll-anchor">
            <JobExpenses :job="job" @updated="onJobUpdated" />
          </div>

          <JobBudget :job="job" />

          <!-- Funding -->
          <section class="card block-card">
            <header class="block-head">
              <div class="block-title">
                <span class="block-icon icon-success" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="20" height="12" x="2" y="6" rx="2" /><circle cx="12" cy="12" r="2" /><path d="M6 12h.01M18 12h.01" />
                  </svg>
                </span>
                <div>
                  <h2>Investor funding</h2>
                  <p class="block-hint">Money invested into this job and how the profit is shared.</p>
                </div>
              </div>
            </header>

            <div v-if="job.allocations?.length" class="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th>Investor</th>
                    <th class="right">Allocated</th>
                    <th class="right">Action</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="allocation in job.allocations" :key="allocation.id">
                    <td>{{ allocation.investment?.investor?.name || '—' }}</td>
                    <td class="right">{{ money(allocation.amount) }}</td>
                    <td class="right">
                      <button
                        v-if="allocation.status === 'active' && !job.financially_locked_at"
                        class="btn-light btn-sm"
                        type="button"
                        :disabled="distributing"
                        @click="distribute(allocation)"
                      >
                        {{ distributing ? 'Calculating…' : 'Calculate distribution' }}
                      </button>
                      <span v-else-if="job.financially_locked_at" class="hint">Locked</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div v-else class="block-empty">
              <p>No investor funding allocated to this job.</p>
            </div>

            <template v-if="distributions.length">
              <h3 class="sub-head">Profit distributions</h3>
              <div class="table-wrap">
                <table>
                  <thead>
                    <tr>
                      <th>Investor</th>
                      <th>Share</th>
                      <th class="right">Profit</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="distribution in distributions" :key="distribution.id">
                      <td>{{ distribution.investor?.name || '—' }}</td>
                      <td>{{ distribution.profit_share_value }}</td>
                      <td class="right money-profit">{{ money(distribution.profit_amount) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </template>
          </section>

          <!-- Adjustments -->
          <section class="card block-card">
            <header class="block-head">
              <div class="block-title">
                <span class="block-icon icon-warning" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" /><path d="M12 9v4" /><path d="M12 17h.01" />
                  </svg>
                </span>
                <div>
                  <h2>Financial adjustments</h2>
                  <p class="block-hint">A paper trail for corrected numbers after financial review.</p>
                </div>
              </div>
            </header>

            <div v-if="job.financial_adjustments?.length" class="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th>Field</th>
                    <th>Previous</th>
                    <th>Corrected</th>
                    <th>Reason</th>
                    <th>By</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in job.financial_adjustments" :key="item.id">
                    <td>{{ item.field }}</td>
                    <td>{{ item.old_value || '—' }}</td>
                    <td>{{ item.new_value || '—' }}</td>
                    <td>{{ item.reason }}</td>
                    <td>{{ item.author?.name || 'system' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div v-else class="block-empty">
              <p>No adjustments recorded — nothing needed correcting.</p>
            </div>

            <form class="grid adjust-form" @submit.prevent="saveAdjustment">
              <div class="field">
                <label>Field corrected</label>
                <input v-model="adjustment.field" :disabled="adjustmentSaving" placeholder="Unexpected cost" />
              </div>
              <div class="field">
                <label>Previous value</label>
                <input v-model="adjustment.old_value" :disabled="adjustmentSaving" />
              </div>
              <div class="field">
                <label>Corrected value</label>
                <input v-model="adjustment.new_value" :disabled="adjustmentSaving" />
              </div>
              <div class="field">
                <label>Reason</label>
                <input v-model="adjustment.reason" :disabled="adjustmentSaving" required placeholder="Supporting document corrected the amount" />
              </div>
              <div class="field actions" style="align-self: end">
                <button type="submit" :disabled="adjustmentSaving">
                  {{ adjustmentSaving ? 'Recording…' : 'Record adjustment' }}
                </button>
              </div>
            </form>
            <p v-if="adjustmentError" class="error">{{ adjustmentError }}</p>
          </section>

          <!-- Activity -->
          <section class="card block-card">
            <header class="block-head">
              <div class="block-title">
                <span class="block-icon icon-info" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 12h-2.48a2 2 0 0 0-1.93 1.46l-2.35 8.36a.25.25 0 0 1-.48 0L9.24 2.18a.25.25 0 0 0-.48 0l-2.35 8.36A2 2 0 0 1 4.49 12H2" />
                  </svg>
                </span>
                <div>
                  <h2>Activity</h2>
                  <p class="block-hint">What happened to this job, newest first.</p>
                </div>
              </div>
            </header>

            <ul v-if="activities.length" class="activity-timeline">
              <li v-for="item in activities" :key="item.id">
                <span class="timeline-dot" aria-hidden="true"></span>
                <div class="timeline-body">
                  <b>{{ item.description }}</b>
                  <span class="timeline-meta">{{ item.author || 'system' }} · {{ when(item.created_at) }}</span>
                </div>
              </li>
            </ul>
            <div v-else class="block-empty"><p>Nothing recorded yet.</p></div>
          </section>
        </div>
      </div>

      <div class="detail-footer">
        <RouterLink class="btn-light" to="/jobs">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M19 12H5" /><path d="m12 19-7-7 7-7" />
          </svg>
          Back to jobs
        </RouterLink>
      </div>

      <!-- Status advance dialog -->
      <ConfirmDialog
        :open="statusDialog"
        title="Advance job status?"
        :message="`Move ${job.code} to the next stage?`"
        confirm-label="Advance"
        @confirm="moveStatus"
        @cancel="statusDialog = false"
      >
        <template #body>
          <div class="field">
            <label>Next stage</label>
            <select v-model="nextStatus">
              <option value="" disabled>Choose next stage…</option>
              <option v-for="stage in nextStatuses(job.status)" :key="stage" :value="stage">
                {{ statusLabel(stage) }}
              </option>
            </select>
          </div>
        </template>
      </ConfirmDialog>
    </template>
  </div>
</template>

<style scoped>
.job-detail {
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

.hero-id { align-items: flex-start; display: flex; gap: 16px; min-width: 0; }

.hero-icon {
  align-items: center;
  background: var(--accent-soft);
  border-radius: 12px;
  color: var(--accent);
  display: flex;
  flex: 0 0 48px;
  height: 48px;
  justify-content: center;
  width: 48px;
}
.hero-icon svg { height: 22px; width: 22px; }

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
.customer-avatar {
  align-items: center;
  border-radius: 50%;
  color: #fff;
  display: inline-flex;
  flex: 0 0 26px;
  font-size: 11px;
  font-weight: 600;
  height: 26px;
  justify-content: center;
  width: 26px;
}
.hero-customer-link { color: var(--text-secondary); font-weight: 500; }
.hero-customer-link:hover { color: var(--accent); }
.hero-sep { color: var(--text-muted); }
.hero-date { color: var(--text-muted); font-size: 14px; margin-top: 6px; }

.hero-actions {
  align-items: center;
  display: flex;
  flex: 0 0 auto;
  flex-wrap: wrap;
  gap: 10px;
}
.hero-done {
  color: var(--text-muted);
  font-size: 13px;
  padding: 0 4px;
}

.hero-stats {
  border-top: 1px solid var(--border);
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
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
  font-size: 19px;
  font-variant-numeric: tabular-nums;
  font-weight: 700;
  letter-spacing: -0.01em;
}

.hero-timeline {
  border-top: 1px solid var(--border);
  margin-top: 20px;
  padding-top: 18px;
}

.action-error {
  background: var(--danger-soft);
  border: 1px solid var(--danger);
  border-radius: var(--radius-md);
  color: var(--danger);
  font-size: 14px;
  padding: 12px 16px;
}

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

.scroll-anchor { scroll-margin-top: 24px; }

/* ---------- Sidebar ---------- */
.side-card { padding: 20px; }

.side-title {
  color: var(--text-muted);
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.08em;
  margin: 0 0 14px;
  text-transform: uppercase;
}

.side-hint {
  color: var(--text-muted);
  font-size: 12px;
  margin: -8px 0 12px;
}

.fact-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
  list-style: none;
  margin: 0;
  padding: 0;
}

.fact-list li {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.fact-label { color: var(--text-muted); font-size: 11px; font-weight: 500; }
.fact-value { color: var(--text-primary); font-size: 14px; font-weight: 500; }
.fact-value a { color: var(--accent); font-weight: 600; }

.lock-badge {
  border-radius: 999px;
  display: inline-flex;
  font-size: 12px;
  font-weight: 600;
  padding: 3px 10px;
  width: fit-content;
}
.lock-badge.locked { background: var(--warning-soft); color: var(--warning); }
.lock-badge.open { background: var(--success-soft); color: var(--success); }

.notes-actions {
  align-items: center;
  display: flex;
  gap: 10px;
  margin-top: 10px;
}

.saved-flash {
  color: var(--success);
  font-size: 13px;
  font-weight: 600;
}

.fade-enter-active,
.fade-leave-active { transition: opacity 0.3s ease; }
.fade-enter-from,
.fade-leave-to { opacity: 0; }

/* ---------- Blocks ---------- */
.block-card { padding: 20px; }

.block-head {
  align-items: flex-start;
  display: flex;
  gap: 12px;
  justify-content: space-between;
  margin-bottom: 14px;
}

.block-title {
  align-items: flex-start;
  display: flex;
  gap: 12px;
}
.block-title h2 { font-size: 15px; font-weight: 600; margin: 0; }
.block-hint { color: var(--text-muted); font-size: 13px; margin: 2px 0 0; }

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
.icon-success { background: var(--success-soft); color: var(--success); }
.icon-warning { background: var(--warning-soft); color: var(--warning); }
.icon-info { background: var(--info-soft); color: var(--info); }
.icon-violet { background: var(--violet-soft); color: var(--violet); }

.sub-head {
  color: var(--text-muted);
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.08em;
  margin: 18px 0 10px;
  text-transform: uppercase;
}

.block-empty {
  border: 1px dashed var(--border-strong);
  border-radius: var(--radius-md);
  color: var(--text-muted);
  font-size: 13px;
  padding: 18px 16px;
  text-align: center;
}
.block-empty p { margin: 0; }

.adjust-form { margin-top: 16px; }

/* ---------- Activity timeline ---------- */
.activity-timeline { list-style: none; margin: 4px 0 0; padding: 0; }

.activity-timeline li {
  display: grid;
  gap: 14px;
  grid-template-columns: 10px minmax(0, 1fr);
  padding-bottom: 18px;
  position: relative;
}
.activity-timeline li:last-child { padding-bottom: 0; }

.activity-timeline li:not(:last-child)::before {
  background: var(--border);
  bottom: 2px;
  content: '';
  left: 4px;
  position: absolute;
  top: 14px;
  width: 2px;
}

.timeline-dot {
  background: var(--accent);
  border: 3px solid var(--accent-soft);
  border-radius: 50%;
  height: 12px;
  margin-top: 3px;
  width: 12px;
}

.timeline-body { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
.timeline-body b { color: var(--text-primary); font-size: 13.5px; font-weight: 600; }
.timeline-meta { color: var(--text-muted); font-size: 12px; }

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
@media (max-width: 1180px) {
  .hero-stats {
    grid-template-columns: repeat(3, minmax(0, 1fr));
    row-gap: 18px;
  }
  .hero-stat:nth-child(3n + 1) { border-left: 0; padding-left: 0; }
}

@media (max-width: 1024px) {
  .detail-grid { grid-template-columns: 1fr; }
  .detail-side { position: static; }
}

@media (max-width: 700px) {
  .hero-top { flex-direction: column; }
  .hero-actions { width: 100%; }
  .hero-actions .btn,
  .hero-actions .btn-light { flex: 1; justify-content: center; }
  .hero-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .hero-stat:nth-child(odd) { border-left: 0; padding-left: 0; }
  .hero-stat:nth-child(3n + 1) { border-left: 1px solid var(--border); padding-left: 16px; }
  .detail-error { align-items: flex-start; flex-direction: column; }
}
</style>