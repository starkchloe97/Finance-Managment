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
import EntityDetailLayout from '@/components/ui/EntityDetailLayout.vue'
import JobFinancialHeader from '@/components/jobs/JobFinancialHeader.vue'
import JobBudget from '@/components/jobs/JobBudget.vue'
import JobExpenses from '@/components/jobs/JobExpenses.vue'
import JobStatusTimeline from '@/components/jobs/JobStatusTimeline.vue'
import JobFinancialFlow from '@/components/jobs/JobFinancialFlow.vue'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'

const route = useRoute()

const job = ref(null)
const loading = ref(true)
const error = ref('')
const activeTab = ref('overview')

const tabs = [
  { key: 'overview', label: 'Overview' },
  { key: 'budget', label: 'Budget' },
  { key: 'expenses', label: 'Expenses' },
  { key: 'financials', label: 'Financials' },
]

// Status move
const nextStatus = ref('')
const statusDialog = ref(false)

// Notes
const notes = ref('')
const notesSaved = ref(false)

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
  try {
    await createDistribution(job.value.id, { investment_id: allocation.investment_id })
    await load()
  } catch (error) {
    alert(error.response?.data?.message || 'Could not calculate distribution')
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
  } catch (error) {
    adjustmentError.value =
      error.response?.data?.errors?.reason?.[0] ||
      error.response?.data?.errors?.field?.[0] ||
      error.response?.data?.message ||
      'Could not record the adjustment.'
  } finally {
    adjustmentSaving.value = false
  }
}

const openStatusDialog = () => {
  statusDialog.value = true
}

const moveStatus = async () => {
  if (!nextStatus.value) return
  statusDialog.value = false
  try {
    const { data } = await updateJobStatus(job.value.id, nextStatus.value)
    apply(data.data)
    nextStatus.value = ''
    await loadActivities()
  } catch (error) {
    alert(error.response?.data?.message || 'Could not update status')
  }
}

const saveNotes = async () => {
  notesSaved.value = false
  try {
    const { data } = await updateJobNotes(job.value.id, notes.value)
    apply(data.data)
    notesSaved.value = true
    await loadActivities()
  } catch (error) {
    alert(error.response?.data?.message || 'Could not save notes')
  }
}

const when = (value) => new Date(value).toLocaleString()

onMounted(load)

const stats = computed(() => [
  { label: 'Revenue', value: money(job.value?.sell_price), tone: 'revenue' },
  { label: 'Planned cost', value: money(job.value?.cost_price), tone: 'cost' },
  { label: 'Actual cost', value: money(actualCost.value), tone: 'cost' },
  {
    label: 'Profit',
    value: money(job.value?.final_profit),
    tone: isLoss.value ? 'loss' : 'profit',
  },
])
</script>

<template>
  <div>
    <!-- Loading / error ladder -->
    <div v-if="error" class="state-panel state-error">
      <p>{{ error }}</p>
      <button type="button" class="btn" @click="load">Try again</button>
    </div>

    <div v-else-if="loading && !job" class="state-panel state-loading">
      <div class="skeleton-block"></div>
    </div>

    <template v-else-if="job">
      <div class="page-head">
        <div class="head-title">
          <h1>{{ job.code }}</h1>
          <span class="status" :class="`status-${job.status}`">{{ statusLabel(job.status) }}</span>
          <span class="hint">{{ job.customer?.name || '—' }}</span>
        </div>
        <div class="actions">
          <button
            v-if="nextStatuses(job.status).length"
            type="button"
            class="btn"
            @click="openStatusDialog"
          >
            Advance to {{ statusLabel(nextStatuses(job.status)[0]) }}
          </button>
          <span v-else class="hint">Completed — no stages left.</span>
          <button type="button" class="btn-light" @click="activeTab = 'expenses'">
            + Add Expense
          </button>
        </div>
      </div>

      <!-- Status timeline replaces the bare badge on this page -->
      <div class="card">
        <JobStatusTimeline :status="job.status" />
      </div>

      <!-- The financial chain, standalone -->
      <JobFinancialFlow
        :sell-price="job.sell_price"
        :cost-price="job.cost_price"
        :extra-costs="job.extra_costs"
        :final-profit="job.final_profit"
      />

      <EntityDetailLayout
        :tabs="tabs"
        v-model="activeTab"
        :loading="false"
        :error="''"
        :stats="stats"
        :show-header="false"
      >
        <!-- Overview -->
        <section v-if="activeTab === 'overview'" class="entity-section">
          <JobFinancialHeader :job="job" />

          <div class="card">
            <h3>Internal Notes</h3>
            <p class="hint">
              For whoever is running the job. Never shown to the customer and not part of the quote.
            </p>
            <form @submit.prevent="saveNotes">
              <textarea
                v-model="notes"
                placeholder="Driver says the crane will be an hour late…"
              ></textarea>
              <div class="actions" style="margin-top: 12px">
                <button type="submit">Save Notes</button>
                <span v-if="notesSaved" class="hint">Saved.</span>
              </div>
            </form>
          </div>

          <div class="card">
            <h3>Activity Timeline</h3>
            <p class="hint">A record of what happened to this job, newest first.</p>
            <ul class="timeline">
              <li v-for="item in activities" :key="item.id">
                <div class="timeline-when">{{ when(item.created_at) }}</div>
                <div class="timeline-what">
                  <b>{{ item.description }}</b>
                  <span class="hint">{{ item.author || 'system' }}</span>
                </div>
              </li>
            </ul>
            <p v-if="!activities.length" class="empty">Nothing recorded yet.</p>
          </div>
        </section>

        <!-- Budget -->
        <section v-if="activeTab === 'budget'" class="entity-section">
          <JobBudget :job="job" />
        </section>

        <!-- Expenses -->
        <section v-if="activeTab === 'expenses'" class="entity-section">
          <JobExpenses :job="job" @updated="onJobUpdated" />
        </section>

        <!-- Financials -->
        <section v-if="activeTab === 'financials'" class="entity-section">
          <JobFinancialHeader :job="job" />

          <div class="card">
            <h3>Funding</h3>
            <p v-if="!job.allocations?.length" class="empty">No investor funding allocated.</p>
            <table v-else>
              <thead>
                <tr>
                  <th>Investor</th>
                  <th>Allocation</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="allocation in job.allocations" :key="allocation.id">
                  <td>{{ allocation.investment?.investor?.name || '-' }}</td>
                  <td class="right">{{ money(allocation.amount) }}</td>
                  <td>
                    <button
                      v-if="allocation.status === 'active' && !job.financially_locked_at"
                      :disabled="distributing"
                      @click="distribute(allocation)"
                    >
                      Calculate distribution
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
            <p v-if="job.financially_locked_at" class="hint">Financially locked.</p>
          </div>

          <div class="card">
            <h3>Profit Distributions</h3>
            <p v-if="!distributions.length" class="empty">No distributions calculated.</p>
            <table v-else>
              <thead>
                <tr>
                  <th>Investor</th>
                  <th>Share</th>
                  <th class="right">Profit</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="distribution in distributions" :key="distribution.id">
                  <td>{{ distribution.investor?.name }}</td>
                  <td>{{ distribution.profit_share_value }}</td>
                  <td class="right">{{ money(distribution.profit_amount) }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="card">
            <h3>Financial Adjustments</h3>
            <p class="hint">
              Keep a reasoned audit record for any correction made after financial review.
            </p>
            <p v-if="!job.financial_adjustments?.length" class="empty">
              No financial adjustments recorded.
            </p>
            <table v-else>
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
                  <td>{{ item.old_value || '-' }}</td>
                  <td>{{ item.new_value || '-' }}</td>
                  <td>{{ item.reason }}</td>
                  <td>{{ item.author?.name || 'system' }}</td>
                </tr>
              </tbody>
            </table>

            <form class="grid" style="margin-top: 14px" @submit.prevent="saveAdjustment">
              <div class="field">
                <label>Field corrected</label
                ><input
                  v-model="adjustment.field"
                  :disabled="adjustmentSaving"
                  placeholder="Unexpected cost"
                />
              </div>
              <div class="field">
                <label>Previous value</label
                ><input v-model="adjustment.old_value" :disabled="adjustmentSaving" />
              </div>
              <div class="field">
                <label>Corrected value</label
                ><input v-model="adjustment.new_value" :disabled="adjustmentSaving" />
              </div>
              <div class="field">
                <label>Reason</label
                ><input
                  v-model="adjustment.reason"
                  :disabled="adjustmentSaving"
                  required
                  placeholder="Supporting document corrected the amount"
                />
              </div>
              <div class="field actions" style="align-self: end">
                <button type="submit" :disabled="adjustmentSaving">
                  {{ adjustmentSaving ? 'Recording…' : 'Record Adjustment' }}
                </button>
              </div>
            </form>
            <p v-if="adjustmentError" class="error">{{ adjustmentError }}</p>
          </div>
        </section>
      </EntityDetailLayout>

      <div class="actions">
        <RouterLink class="btn btn-light" to="/jobs">Back to jobs</RouterLink>
      </div>

      <!-- Status advance dialog -->
      <ConfirmDialog
        :open="statusDialog"
        title="Advance job status?"
        :message="`Move ${job.code} to ${statusLabel(nextStatus)}?`"
        confirm-label="Advance"
        @confirm="moveStatus"
        @cancel="statusDialog = false"
      >
        <template #body>
          <div class="field">
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
