<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useLoanStore } from '@/stores/loanStore'
import { useCompanyCapitalStore } from '@/stores/companyCapitalStore'
import { money } from '@/utils/money'
import EntityDetailLayout from '@/components/ui/EntityDetailLayout.vue'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'

const route = useRoute()
const loanStore = useLoanStore()
const capitalStore = useCompanyCapitalStore()
const { loan, loading, error } = storeToRefs(loanStore)
const activeTab = ref('overview')
const editOpen = ref(false)
const repaymentOpen = ref(false)
const cancelOpen = ref(false)
const saving = ref(false)
const errors = ref({})
const editForm = reactive({ due_date: '', notes: '' })
const repaymentForm = reactive({
  amount: '',
  payment_date: new Date().toISOString().slice(0, 10),
  reference: '',
  notes: '',
})
const tabs = [
  { key: 'overview', label: 'Overview' },
  { key: 'repayments', label: 'Repayments' },
]
const canManage = computed(() => ['active', 'overdue'].includes(loan.value?.status))
const canCancel = computed(() => canManage.value && Number(loan.value?.total_repaid || 0) === 0)
const stats = computed(() =>
  loan.value
    ? [
        { label: 'Original loan', value: money(loan.value.amount), tone: 'neutral' },
        { label: 'Total paid', value: money(loan.value.total_repaid), tone: 'profit' },
        {
          label: 'Outstanding',
          value: money(loan.value.outstanding_amount),
          tone: Number(loan.value.outstanding_amount) > 0 ? 'cost' : 'neutral',
        },
      ]
    : [],
)
const statusClass = (status) =>
  ({
    active: 'status-info',
    overdue: 'status-danger',
    paid: 'status-success',
    cancelled: 'status-cancelled',
  })[status] || 'status-draft'
const load = () => Promise.all([loanStore.fetchLoan(route.params.id), capitalStore.fetchCapital()])
const openEdit = () => {
  Object.assign(editForm, { due_date: loan.value.due_date, notes: loan.value.notes || '' })
  errors.value = {}
  editOpen.value = true
}
const openRepayment = () => {
  Object.assign(repaymentForm, {
    amount: '',
    payment_date: new Date().toISOString().slice(0, 10),
    reference: '',
    notes: '',
  })
  errors.value = {}
  repaymentOpen.value = true
}
const saveEdit = async () => {
  saving.value = true
  errors.value = {}
  try {
    await loanStore.updateLoan(loan.value.id, editForm)
    editOpen.value = false
  } catch (error) {
    if (error.response?.status === 422) errors.value = error.response.data.errors || {}
  } finally {
    saving.value = false
  }
}
const saveRepayment = async () => {
  saving.value = true
  errors.value = {}
  try {
    await loanStore.recordRepayment(loan.value.id, {
      amount: repaymentForm.amount,
      payment_date: repaymentForm.payment_date,
      reference: repaymentForm.reference || null,
      notes: repaymentForm.notes || null,
    })
    await capitalStore.fetchCapital()
    repaymentOpen.value = false
    activeTab.value = 'repayments'
  } catch (error) {
    if (error.response?.status === 422) errors.value = error.response.data.errors || {}
  } finally {
    saving.value = false
  }
}
const cancelLoan = async () => {
  saving.value = true
  try {
    await loanStore.cancelLoan(loan.value.id)
    await capitalStore.fetchCapital()
    cancelOpen.value = false
  } finally {
    saving.value = false
  }
}
const fieldError = (field) => errors.value[field]?.[0]
const formatDateTime = (value) => (value ? new Date(value).toLocaleString() : '—')

onMounted(() => load().catch(() => {}))
</script>

<template>
  <div class="loan-detail-page">
    <EntityDetailLayout
      v-model="activeTab"
      :tabs="tabs"
      :loading="loading && !loan"
      :error="error"
      :stats="stats"
      @retry="load"
    >
      <template #title>
        <div class="entity-title-block">
          <span class="section-kicker">Loan</span>
          <h1>{{ loan?.loan_code }}</h1>
          <span v-if="loan?.borrower" class="hint">{{ loan.borrower.name }}</span
          ><span v-if="loan?.status" class="status capitalize" :class="statusClass(loan.status)">{{
            loan.status
          }}</span>
        </div>
      </template>
      <template #actions>
        <button v-if="canManage" type="button" class="btn-light" @click="openEdit">
          Edit terms
        </button>
        <button v-if="canManage" type="button" @click="openRepayment">Record repayment</button>
        <button v-if="canCancel" type="button" class="btn-danger" @click="cancelOpen = true">
          Cancel loan
        </button>
        <RouterLink class="btn-light" to="/loans">All loans</RouterLink>
      </template>

      <section v-if="activeTab === 'overview' && loan" class="entity-section">
        <div class="detail-columns">
          <div class="card">
            <div class="section-head">
              <div>
                <h3>Loan information</h3>
                <p class="hint">Borrower identity and current terms.</p>
              </div>
            </div>
            <dl class="detail-list">
              <div>
                <dt>Borrower</dt>
                <dd>{{ loan.borrower?.name || 'Unknown borrower' }}</dd>
              </div>
              <div>
                <dt>Borrower type</dt>
                <dd class="capitalize">{{ loan.borrower_type }}</dd>
              </div>
              <div>
                <dt>Borrower code</dt>
                <dd>{{ loan.borrower?.code || '—' }}</dd>
              </div>
              <div>
                <dt>Contact</dt>
                <dd>{{ loan.borrower?.phone || loan.borrower?.email || '—' }}</dd>
              </div>
              <div>
                <dt>Loan date</dt>
                <dd>{{ loan.loan_date }}</dd>
              </div>
              <div>
                <dt>Due date</dt>
                <dd>{{ loan.due_date }}</dd>
              </div>
            </dl>
          </div>
          <div class="card">
            <div class="section-head">
              <div>
                <h3>Lifecycle</h3>
                <p class="hint">Status history retained by the loan record.</p>
              </div>
            </div>
            <dl class="detail-list">
              <div>
                <dt>Current status</dt>
                <dd>
                  <span class="status capitalize" :class="statusClass(loan.status)">{{
                    loan.status
                  }}</span>
                </dd>
              </div>
              <div>
                <dt>First overdue</dt>
                <dd>{{ formatDateTime(loan.first_overdue_at) }}</dd>
              </div>
              <div>
                <dt>Paid</dt>
                <dd>{{ formatDateTime(loan.paid_at) }}</dd>
              </div>
              <div>
                <dt>Cancelled</dt>
                <dd>{{ formatDateTime(loan.cancelled_at) }}</dd>
              </div>
              <div>
                <dt>Available company capital</dt>
                <dd class="money">
                  <strong>{{ money(capitalStore.current_balance) }}</strong>
                </dd>
              </div>
            </dl>
          </div>
        </div>
        <div v-if="loan.notes" class="card">
          <h3>Notes</h3>
          <p class="loan-notes">{{ loan.notes }}</p>
        </div>
      </section>

      <section v-if="activeTab === 'repayments' && loan" class="entity-section">
        <div class="section-head">
          <div>
            <h3>Repayment history</h3>
            <p class="hint">Every payment remains linked to this loan and its capital movement.</p>
          </div>
          <button v-if="canManage" type="button" class="btn btn-sm" @click="openRepayment">
            Record repayment
          </button>
        </div>
        <div v-if="!loan.repayments?.length" class="state-panel state-empty">
          <p>No repayments have been recorded. The full principal remains outstanding.</p>
          <button v-if="canManage" type="button" @click="openRepayment">
            Record first repayment
          </button>
        </div>
        <div v-else class="card table-wrap">
          <table>
            <thead>
              <tr>
                <th>Payment date</th>
                <th>Reference</th>
                <th>Notes</th>
                <th>Recorded by</th>
                <th class="right">Amount</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="repayment in loan.repayments" :key="repayment.id">
                <td>{{ repayment.payment_date }}</td>
                <td>{{ repayment.reference || '—' }}</td>
                <td>{{ repayment.notes || '—' }}</td>
                <td>{{ repayment.created_by?.name || '—' }}</td>
                <td class="right money money-profit">{{ money(repayment.amount) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </EntityDetailLayout>

    <ConfirmDialog
      :open="editOpen"
      title="Edit loan terms"
      confirm-label="Save changes"
      :loading="saving"
      @confirm="saveEdit"
      @cancel="editOpen = false"
    >
      <template #body
        ><form @submit.prevent="saveEdit">
          <div class="field">
            <label for="edit-due-date">Due date</label
            ><input
              id="edit-due-date"
              v-model="editForm.due_date"
              type="date"
              :aria-invalid="Boolean(fieldError('due_date'))"
            /><span v-if="fieldError('due_date')" class="error">{{ fieldError('due_date') }}</span>
          </div>
          <div class="field">
            <label for="edit-notes">Notes</label
            ><textarea id="edit-notes" v-model="editForm.notes" rows="4"></textarea
            ><span v-if="fieldError('notes')" class="error">{{ fieldError('notes') }}</span>
          </div>
        </form></template
      >
    </ConfirmDialog>

    <ConfirmDialog
      :open="repaymentOpen"
      title="Record repayment"
      confirm-label="Record repayment"
      :loading="saving"
      @confirm="saveRepayment"
      @cancel="repaymentOpen = false"
    >
      <template #body
        ><p class="repayment-context">
          Outstanding before payment: <strong>{{ money(loan?.outstanding_amount) }}</strong>
        </p>
        <form @submit.prevent="saveRepayment">
          <div class="field">
            <label for="repayment-amount">Amount</label
            ><input
              id="repayment-amount"
              v-model="repaymentForm.amount"
              inputmode="decimal"
              :aria-invalid="Boolean(fieldError('amount'))"
            /><span v-if="fieldError('amount')" class="error">{{ fieldError('amount') }}</span>
          </div>
          <div class="field">
            <label for="payment-date">Payment date</label
            ><input
              id="payment-date"
              v-model="repaymentForm.payment_date"
              type="date"
              :aria-invalid="Boolean(fieldError('payment_date'))"
            /><span v-if="fieldError('payment_date')" class="error">{{
              fieldError('payment_date')
            }}</span>
          </div>
          <div class="field">
            <label for="payment-reference">Reference</label
            ><input id="payment-reference" v-model="repaymentForm.reference" />
          </div>
          <div class="field">
            <label for="payment-notes">Notes</label
            ><textarea id="payment-notes" v-model="repaymentForm.notes" rows="3"></textarea>
          </div></form
      ></template>
    </ConfirmDialog>

    <ConfirmDialog
      :open="cancelOpen"
      title="Cancel this loan?"
      message="Cancellation restores the full principal to company capital. It is only allowed before any repayment."
      confirm-label="Cancel loan"
      variant="danger"
      :loading="saving"
      @confirm="cancelLoan"
      @cancel="cancelOpen = false"
    />
  </div>
</template>

<style scoped>
.loan-detail-page {
  min-width: 0;
}
.entity-title-block .hint {
  display: inline-block;
  margin-left: var(--space-2);
}
.entity-title-block .status {
  margin-left: var(--space-3);
}
.detail-columns {
  display: grid;
  gap: var(--space-4);
  grid-template-columns: 1fr 1fr;
}
.detail-columns .card {
  margin: 0;
}
.detail-list div {
  align-items: start;
  border-bottom: 1px solid var(--border);
  display: grid;
  gap: var(--space-4);
  grid-template-columns: 140px 1fr;
  padding: var(--space-3) 0;
}
.detail-list div:last-child {
  border-bottom: 0;
}
.detail-list dt {
  color: var(--text-muted);
  font-size: var(--text-sm);
}
.detail-list dd {
  font-size: var(--text-sm);
}
.loan-notes {
  margin-top: var(--space-3);
  white-space: pre-wrap;
}
.repayment-context {
  background: var(--accent-soft);
  border-radius: var(--radius-md);
  color: var(--text-secondary);
  margin-bottom: var(--space-4);
  padding: var(--space-3);
}
.capitalize {
  text-transform: capitalize;
}
@media (max-width: 760px) {
  .detail-columns {
    grid-template-columns: 1fr;
  }
}
@media (max-width: 560px) {
  .detail-list div {
    grid-template-columns: 1fr;
    gap: var(--space-1);
  }
}
</style>
