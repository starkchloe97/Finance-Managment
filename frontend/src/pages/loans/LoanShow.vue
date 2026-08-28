<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useLoanStore } from '@/stores/loanStore'
import { useCompanyCapitalStore } from '@/stores/companyCapitalStore'
import { money } from '@/utils/money'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
import FinanceStatus from '@/components/ui/FinanceStatus.vue'
import InfoTip from '@/components/ui/InfoTip.vue'
import { avatarStyle, initialOf } from '@/utils/avatar'

const route = useRoute()
const loanStore = useLoanStore()
const capitalStore = useCompanyCapitalStore()
const { loan, loading, error } = storeToRefs(loanStore)

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

const canManage = computed(() => ['active', 'overdue'].includes(loan.value?.status))
const canCancel = computed(() => canManage.value && Number(loan.value?.total_repaid || 0) === 0)

const principal = computed(() => Number(loan.value?.amount || 0))
const repaid = computed(() => Number(loan.value?.total_repaid || 0))
const outstanding = computed(() => Number(loan.value?.outstanding_amount || 0))
const isOverdue = computed(() => loan.value?.status === 'overdue')

const repaidPct = computed(() =>
  principal.value > 0 ? Math.min(100, Math.round((repaid.value / principal.value) * 100)) : 0,
)

const repaymentPreview = computed(() => {
  const amount = Number(repaymentForm.amount || 0)
  return { amount, remaining: outstanding.value - amount }
})

const fillFullOutstanding = () => {
  repaymentForm.amount = String(outstanding.value)
}

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
  <div class="loan-detail">
    <!-- Error -->
    <div v-if="error" class="card detail-error" role="alert">
      <div>
        <strong>Couldn't load this loan.</strong>
        <p>{{ error }}</p>
      </div>
      <button type="button" class="btn" @click="load">Try again</button>
    </div>

    <!-- Skeleton -->
    <div v-else-if="loading && !loan" class="detail-skeleton" aria-hidden="true">
      <div class="sk" style="height: 200px"></div>
      <div class="sk" style="height: 280px"></div>
    </div>

    <template v-else-if="loan">
      <!-- ============ Hero ============ -->
      <header class="card hero-card">
        <div class="hero-top">
          <div class="hero-id">
            <span class="hero-avatar" :style="avatarStyle(loan.borrower?.name || 'L')" aria-hidden="true">
              {{ initialOf(loan.borrower?.name || 'L') }}
            </span>
            <div class="hero-copy">
              <span class="section-kicker">Capital / Loans</span>
              <div class="hero-title-row">
                <h1>{{ loan.loan_code }}</h1>
                <FinanceStatus :status="loan.status" kind="loan" />
              </div>
              <p class="hero-sub">
                <span v-if="loan.borrower" class="hero-borrower">{{ loan.borrower.name }}</span>
                <span class="hero-sep">·</span>
                <span class="capitalize">{{ loan.borrower_type }}</span>
                <span class="hero-sep">·</span>
                <span>Issued {{ loan.loan_date }}</span>
              </p>
            </div>
          </div>

          <div class="hero-actions">
            <button v-if="canManage" type="button" class="btn" @click="openRepayment">
              Record repayment
            </button>
            <button v-if="canManage" type="button" class="btn-light" @click="openEdit">
              Edit terms
            </button>
            <button v-if="canCancel" type="button" class="btn-light danger-text" @click="cancelOpen = true">
              Cancel loan
            </button>
          </div>
        </div>

        <div class="hero-stats">
          <div class="hero-stat">
            <span>
              Principal
              <InfoTip label="The amount originally issued. Loans are interest-free — only this is owed back." />
            </span>
            <strong>{{ money(principal) }}</strong>
          </div>
          <div class="hero-stat">
            <span>
              Repaid
              <InfoTip label="Total repayments received so far." />
            </span>
            <strong class="is-green">{{ money(repaid) }}</strong>
          </div>
          <div class="hero-stat">
            <span>
              Outstanding
              <InfoTip label="Principal minus repayments — what the borrower still owes." />
            </span>
            <strong :class="outstanding > 0 ? 'is-red' : 'is-green'">{{ money(outstanding) }}</strong>
          </div>
          <div class="hero-stat">
            <span>
              Due date
              <InfoTip label="When the loan should be fully repaid." />
            </span>
            <strong :class="isOverdue ? 'is-red' : ''">{{ loan.due_date || '—' }}</strong>
          </div>
        </div>

        <div class="repay-progress">
          <div class="repay-bar-row">
            <div class="repay-bar" role="progressbar" :aria-valuenow="repaidPct" aria-valuemin="0" aria-valuemax="100" :aria-label="`${repaidPct}% repaid`">
              <span class="repay-fill" :style="{ width: `${repaidPct}%` }"></span>
            </div>
            <span v-if="outstanding === 0" class="repay-done">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M20 6 9 17l-5-5" />
              </svg>
              Fully repaid
            </span>
          </div>
          <p class="repay-caption">
            {{ repaidPct }}% repaid · {{ money(repaid) }} of {{ money(principal) }}
            <template v-if="outstanding > 0">· {{ money(outstanding) }} to go</template>
          </p>
        </div>

        <p v-if="isOverdue" class="overdue-banner" role="status">
          Past due since {{ loan.due_date }} — {{ money(outstanding) }} still outstanding.
        </p>
      </header>

      <!-- ============ Grid ============ -->
      <div class="detail-grid">
        <aside class="detail-side">
          <section class="card side-card">
            <h2 class="side-title">Borrower</h2>
            <ul class="fact-list">
              <li>
                <span class="fact-label">
                  Type
                  <InfoTip label="Investor borrowers have a profile in this system. Outsiders are standalone contacts." />
                </span>
                <span class="fact-value capitalize">{{ loan.borrower_type }}</span>
              </li>
              <li>
                <span class="fact-label">Code</span>
                <span class="fact-value">{{ loan.borrower?.code || '—' }}</span>
              </li>
              <li>
                <span class="fact-label">Contact</span>
                <span class="fact-value">{{ loan.borrower?.phone || loan.borrower?.email || '—' }}</span>
              </li>
            </ul>
          </section>

          <section class="card side-card">
            <h2 class="side-title">
              Lifecycle
              <InfoTip label="Key moments recorded on this loan." />
            </h2>
            <ul class="fact-list">
              <li>
                <span class="fact-label">First overdue</span>
                <span class="fact-value" :class="loan.first_overdue_at ? 'is-red' : ''">
                  {{ formatDateTime(loan.first_overdue_at) }}
                </span>
              </li>
              <li>
                <span class="fact-label">Paid off</span>
                <span class="fact-value" :class="loan.paid_at ? 'is-green' : ''">
                  {{ formatDateTime(loan.paid_at) }}
                </span>
              </li>
              <li>
                <span class="fact-label">Cancelled</span>
                <span class="fact-value">{{ formatDateTime(loan.cancelled_at) }}</span>
              </li>
            </ul>
          </section>

          <section class="card side-card">
            <h2 class="side-title">
              Company capital
              <InfoTip label="Company-wide figure, not specific to this loan. Repayments on this loan add to it." />
            </h2>
            <div class="capital-mini">
              <span class="capital-mini-label">Available to lend</span>
              <strong>{{ money(capitalStore.available_to_lend) }}</strong>
            </div>
          </section>

          <section v-if="loan.notes" class="card side-card">
            <h2 class="side-title">Notes</h2>
            <p class="notes-text">{{ loan.notes }}</p>
          </section>
        </aside>

        <div class="detail-main">
          <!-- Repayments block -->
          <section class="card block-card">
            <header class="block-head">
              <div class="block-title">
                <span class="block-icon icon-success" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="18" height="12" x="3" y="8" rx="2" /><path d="M7 12h.01M11 12h2m4 0h.01" /><circle cx="12" cy="14" r="4" />
                  </svg>
                </span>
                <div>
                  <h2>Repayments</h2>
                  <p class="block-hint">
                    Every payment recorded against this loan.
                    <InfoTip label="Each repayment reduces the outstanding amount and returns money to company capital." />
                  </p>
                </div>
              </div>
              <button v-if="canManage" class="btn-light btn-sm" type="button" @click="openRepayment">
                + Record repayment
              </button>
            </header>

            <div v-if="loan.repayments?.length" class="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th>Payment date</th>
                    <th>
                      Reference
                      <InfoTip label="Optional receipt or transfer identifier." />
                    </th>
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
                    <td class="right money-profit">{{ money(repayment.amount) }}</td>
                  </tr>
                </tbody>
                <tfoot>
                  <tr class="grand-total">
                    <td colspan="4">Total repaid</td>
                    <td class="right">{{ money(repaid) }}</td>
                  </tr>
                </tfoot>
              </table>
            </div>
            <div v-else class="block-empty">
              <p v-if="outstanding > 0">
                No repayments yet — the full {{ money(principal) }} is outstanding.
              </p>
              <p v-else>No repayments were needed.</p>
              <button v-if="canManage && outstanding > 0" class="btn-light btn-sm" type="button" @click="openRepayment">
                Record first repayment →
              </button>
            </div>
          </section>
        </div>
      </div>

      <div class="detail-footer">
        <RouterLink class="btn-light" to="/loans">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M19 12H5" /><path d="m12 19-7-7 7-7" />
          </svg>
          Back to loans
        </RouterLink>
      </div>

      <!-- ============ Dialogs ============ -->

      <ConfirmDialog
        :open="editOpen"
        title="Edit loan terms"
        confirm-label="Save changes"
        :loading="saving"
        @confirm="saveEdit"
        @cancel="editOpen = false"
      >
        <template #body>
          <form @submit.prevent="saveEdit">
            <div class="field">
              <label for="edit-due-date">Due date</label>
              <input id="edit-due-date" v-model="editForm.due_date" type="date" :aria-invalid="Boolean(fieldError('due_date'))" />
              <span v-if="fieldError('due_date')" class="error">{{ fieldError('due_date') }}</span>
            </div>
            <div class="field">
              <label for="edit-notes">Notes</label>
              <textarea id="edit-notes" v-model="editForm.notes" rows="3"></textarea>
            </div>
          </form>
        </template>
      </ConfirmDialog>

      <ConfirmDialog
        :open="repaymentOpen"
        title="Record repayment"
        confirm-label="Record repayment"
        wide
        :loading="saving"
        @confirm="saveRepayment"
        @cancel="repaymentOpen = false"
      >
        <template #body>
          <div class="mini-preview">
            <div class="preview-row">
              <span>Outstanding before payment</span>
              <strong>{{ money(outstanding) }}</strong>
            </div>
            <div v-if="Number(repaymentForm.amount) > 0" class="preview-row preview-total">
              <span>Remaining after</span>
              <strong :class="repaymentPreview.remaining < 0 ? 'money-loss' : 'money-profit'">
                {{ money(repaymentPreview.remaining) }}
              </strong>
            </div>
          </div>
          <form @submit.prevent="saveRepayment">
            <div class="field">
              <label for="repayment-amount">Amount</label>
              <div class="amount-row">
                <input
                  id="repayment-amount"
                  v-model="repaymentForm.amount"
                  inputmode="decimal"
                  placeholder="0.00"
                  :aria-invalid="Boolean(fieldError('amount'))"
                />
                <button v-if="outstanding > 0" type="button" class="btn-light btn-sm" @click="fillFullOutstanding">
                  Full amount
                </button>
              </div>
              <span v-if="fieldError('amount')" class="error">{{ fieldError('amount') }}</span>
            </div>
            <div class="dialog-grid">
              <div class="field">
                <label for="payment-date">Payment date</label>
                <input id="payment-date" v-model="repaymentForm.payment_date" type="date" :aria-invalid="Boolean(fieldError('payment_date'))" />
                <span v-if="fieldError('payment_date')" class="error">{{ fieldError('payment_date') }}</span>
              </div>
              <div class="field">
                <label for="payment-reference">
                  Reference
                  <InfoTip label="Optional — receipt number, bank transfer ID, anything identifying the payment." />
                </label>
                <input id="payment-reference" v-model="repaymentForm.reference" />
              </div>
            </div>
            <div class="field">
              <label for="payment-notes">Notes</label>
              <textarea id="payment-notes" v-model="repaymentForm.notes" rows="2"></textarea>
            </div>
          </form>
        </template>
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
    </template>
  </div>
</template>

<style scoped>
.loan-detail {
  display: flex;
  flex-direction: column;
  gap: 20px;
  min-width: 0;
}

/* ---------- Hero ---------- */
.hero-card { padding: 20px 24px; }

.hero-top {
  align-items: flex-start;
  display: flex;
  gap: 16px;
  justify-content: space-between;
}
.hero-id { align-items: center; display: flex; gap: 14px; min-width: 0; }

.hero-avatar {
  align-items: center;
  border-radius: 50%;
  box-shadow: 0 4px 12px rgb(16 24 40 / 14%);
  color: #fff;
  display: inline-flex;
  flex: 0 0 48px;
  font-size: 18px;
  font-weight: 700;
  height: 48px;
  justify-content: center;
  width: 48px;
}

.hero-copy { min-width: 0; }
.hero-copy .section-kicker { margin-bottom: 3px; }

.hero-title-row { align-items: center; display: flex; flex-wrap: wrap; gap: 12px; }
.hero-title-row h1 {
  font-size: 21px;
  font-weight: 700;
  letter-spacing: -0.02em;
  margin: 0;
}

.hero-sub {
  align-items: center;
  color: var(--text-muted);
  display: flex;
  flex-wrap: wrap;
  font-size: 13px;
  gap: 7px;
  margin: 5px 0 0;
}
.hero-borrower { color: var(--text-secondary); font-weight: 600; }
.hero-sep { color: var(--text-muted); }

.hero-actions {
  align-items: center;
  display: flex;
  flex: 0 0 auto;
  flex-wrap: wrap;
  gap: 8px;
}

.danger-text { color: var(--danger); }
.danger-text:hover { border-color: var(--danger); }

.hero-stats {
  border-top: 1px solid var(--border);
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  margin-top: 16px;
  padding-top: 16px;
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
  font-size: 18px;
  font-variant-numeric: tabular-nums;
  font-weight: 700;
  letter-spacing: -0.01em;
}
.hero-stat strong.is-green { color: var(--success); }
.hero-stat strong.is-red { color: var(--danger); }

/* ---------- Progress ---------- */
.repay-progress {
  border-top: 1px solid var(--border);
  margin-top: 16px;
  padding-top: 14px;
}
.repay-bar-row { align-items: center; display: flex; gap: 14px; }
.repay-bar {
  background: var(--surface-2);
  border-radius: 999px;
  flex: 1;
  height: 10px;
  overflow: hidden;
}
.repay-fill {
  background: var(--success);
  display: block;
  height: 100%;
  transition: width 0.4s ease;
}
.repay-done {
  align-items: center;
  background: var(--success-soft);
  border-radius: 999px;
  color: var(--success);
  display: inline-flex;
  flex: 0 0 auto;
  font-size: 12px;
  font-weight: 600;
  gap: 5px;
  padding: 4px 12px;
}
.repay-done svg { height: 13px; width: 13px; }
.repay-caption { color: var(--text-muted); font-size: 12px; margin: 8px 0 0; }

.overdue-banner {
  background: var(--danger-soft);
  border-radius: var(--radius-md);
  color: var(--danger);
  font-size: 13px;
  margin: 14px 0 0;
  padding: 10px 14px;
}

/* ---------- Grid / sidebar / blocks ---------- */
.detail-grid {
  align-items: start;
  display: grid;
  gap: 20px;
  grid-template-columns: 280px minmax(0, 1fr);
}
.detail-side {
  display: flex;
  flex-direction: column;
  gap: 20px;
  position: sticky;
  top: 20px;
}
.detail-main { display: flex; flex-direction: column; gap: 20px; min-width: 0; }

.side-card { padding: 18px 20px; }
.side-title {
  align-items: center;
  color: var(--text-muted);
  display: flex;
  font-size: 11px;
  font-weight: 600;
  gap: 5px;
  letter-spacing: 0.08em;
  margin: 0 0 12px;
  text-transform: uppercase;
}

.fact-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
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
.fact-value.is-red { color: var(--danger); }
.fact-value.is-green { color: var(--success); font-weight: 600; }

.capital-mini-label { color: var(--text-muted); font-size: 12px; }
.capital-mini strong {
  color: var(--accent);
  display: block;
  font-size: 20px;
  font-variant-numeric: tabular-nums;
  font-weight: 700;
}

.notes-text {
  color: var(--text-secondary);
  font-size: 14px;
  line-height: 1.6;
  margin: 0;
  white-space: pre-line;
}

.block-card { padding: 20px; }
.block-head {
  align-items: flex-start;
  display: flex;
  gap: 12px;
  justify-content: space-between;
  margin-bottom: 12px;
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
  background: var(--success-soft);
  border-radius: 9px;
  color: var(--success);
  display: flex;
  flex: 0 0 32px;
  height: 32px;
  justify-content: center;
  width: 32px;
}
.block-icon svg { height: 15px; width: 15px; }

.block-empty {
  border: 1px dashed var(--border-strong);
  border-radius: var(--radius-md);
  color: var(--text-muted);
  display: flex;
  flex-direction: column;
  font-size: 13px;
  gap: 8px;
  padding: 18px 16px;
  text-align: center;
}
.block-empty p { margin: 0; }

/* ---------- Dialog helpers ---------- */
.mini-preview {
  background: var(--surface-2);
  border-radius: var(--radius-md);
  margin-bottom: var(--space-4);
  padding: var(--space-3) var(--space-4);
}
.preview-row {
  display: flex;
  font-size: var(--text-sm);
  justify-content: space-between;
  padding: var(--space-1) 0;
}
.preview-row span { color: var(--text-muted); }
.preview-row strong { font-variant-numeric: tabular-nums; }
.preview-total {
  border-top: 1px solid var(--border);
  font-weight: var(--font-weight-semibold);
  margin-top: var(--space-1);
  padding-top: var(--space-1);
}

.amount-row { display: flex; gap: 8px; }
.amount-row input { flex: 1; }
.amount-row .btn-light { flex: 0 0 auto; }

.dialog-grid {
  display: grid;
  gap: var(--space-3);
  grid-template-columns: 1fr 1fr;
}

.capitalize { text-transform: capitalize; }

/* ---------- Error / skeleton ---------- */
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
  .hero-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); row-gap: 16px; }
  .hero-stat:nth-child(odd) { border-left: 0; padding-left: 0; }
}

@media (max-width: 700px) {
  .hero-top { flex-direction: column; }
  .hero-actions { width: 100%; }
  .hero-actions .btn,
  .hero-actions .btn-light { flex: 1; justify-content: center; }
  .detail-error { align-items: flex-start; flex-direction: column; }
  .dialog-grid { grid-template-columns: 1fr; }
}
</style>