<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useLoanStore } from '@/stores/loanStore'
import { useCompanyCapitalStore } from '@/stores/companyCapitalStore'
import { money } from '@/utils/money'
import Pagination from '@/components/ui/Pagination.vue'
import StatePanel from '@/components/ui/StatePanel.vue'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
import FinanceStatus from '@/components/ui/FinanceStatus.vue'
import InfoTip from '@/components/ui/InfoTip.vue'
import { avatarStyle, initialOf } from '@/utils/avatar'

const loanStore = useLoanStore()
const capitalStore = useCompanyCapitalStore()
const { loans, loading, error, pagination } = storeToRefs(loanStore)

const filters = reactive({ status: '', borrower: '', from: '', to: '' })
const hasFilters = computed(() => Object.values(filters).some(Boolean))

const opening = reactive({ amount: '', transaction_date: new Date().toISOString().slice(0, 10) })
const openingErrors = ref({})
const initializing = ref(false)

const addCapitalOpen = ref(false)
const addCapitalForm = reactive({
  amount: '',
  transaction_date: new Date().toISOString().slice(0, 10),
  status: 'available',
  notes: '',
})
const addCapitalErrors = ref({})
const addCapitalSubmitting = ref(false)

const withdrawOpen = ref(false)
const withdrawForm = reactive({
  amount: '',
  transaction_date: new Date().toISOString().slice(0, 10),
  notes: '',
})
const withdrawErrors = ref({})
const withdrawSubmitting = ref(false)

const makeAvailableOpen = ref(false)
const makeUnavailableOpen = ref(false)
const pendingAvailability = ref(null)

const convertDraftOpen = ref(false)
const convertDraft = ref(null)
const convertDraftForm = reactive({ available: 'available' })

const removeDraftOpen = ref(false)
const removeDraft = ref(null)
const removeDraftForm = reactive({ removal_note: '' })
const removeDraftErrors = ref({})

const query = (page = 1) => ({
  page,
  ...Object.fromEntries(Object.entries(filters).filter(([, value]) => value !== '')),
})
const loadLoans = (page = 1) => loanStore.fetchLoans(query(page))
const load = () => Promise.all([loadLoans(), capitalStore.fetchCapital()])

const resetFilters = () => {
  Object.assign(filters, { status: '', borrower: '', from: '', to: '' })
  loadLoans().catch(() => { })
}

const initialize = async () => {
  initializing.value = true
  openingErrors.value = {}
  try {
    await capitalStore.initializeCapital(opening)
  } catch (error) {
    if (error.response?.status === 422) openingErrors.value = error.response.data.errors || {}
  } finally {
    initializing.value = false
  }
}

const movementLabel = (movement) => {
  if (movement.type === 'capital_draft') {
    return (
      { added: 'Capital draft', converted: 'Draft converted', removed: 'Draft removed' }[
      movement.draft_status
      ] || 'Capital draft'
    )
  }
  return (
    {
      opening_balance: 'Opening balance',
      capital_added: 'Capital added',
      capital_withdrawn: 'Capital withdrawn',
      capital_reserved: 'Capital reserved',
      capital_made_available: 'Capital made available',
      loan_issued: 'Loan issued',
      loan_repayment: 'Loan repayment',
      loan_cancelled: 'Loan cancellation',
    }[movement.type] || movement.type
  )
}

const draftStatusLabel = (status) =>
  ({ added: 'Draft', converted: 'Converted', removed: 'Removed' })[status] || status

// 'status-cancelled' doesn't exist in the global stylesheet — use defined classes
const draftStatusClass = (status) =>
  ({ added: 'status-info', converted: 'status-completed', removed: 'status-draft' })[status] ||
  'status-draft'

const movementsWithBalance = computed(() => {
  const all = [...capitalStore.transactions, ...capitalStore.draft_history]

  all.sort((a, b) => {
    if (b.transaction_date !== a.transaction_date) {
      return b.transaction_date.localeCompare(a.transaction_date)
    }
    return (b.created_at || '').localeCompare(a.created_at || '')
  })

  const chronological = [...all].reverse()
  let balance = 0

  return chronological
    .map((m) => {
      const isDraft = m.type === 'capital_draft'
      const impact = isDraft
        ? 0
        : m.type === 'capital_added' && !m.available
          ? 0
          : Number(m.amount)
      balance += impact
      return { ...m, running_balance: balance }
    })
    .reverse()
})

const capital = computed(() => {
  const availableToLend = Number(capitalStore.available_to_lend) || 0
  const lentOut = Number(capitalStore.lent_out) || 0
  const reserved = Number(capitalStore.reserved) || 0
  const total = Number(capitalStore.total_capital) || 0
  const pct = (value) => (total > 0 ? (value / total) * 100 : 0)
  return {
    availableToLend,
    lentOut,
    reserved,
    total,
    availablePct: pct(availableToLend),
    lentPct: pct(lentOut),
    reservedPct: pct(reserved),
  }
})

const pendingDraftsTotal = computed(() =>
  (capitalStore.drafts || []).reduce((sum, draft) => sum + Number(draft.amount || 0), 0),
)

const withdrawPreview = computed(() => {
  const available = Number(capitalStore.available_to_lend) || 0
  const withdrawal = Number(withdrawForm.amount) || 0
  const remaining = available - withdrawal
  return {
    available,
    withdrawal,
    remaining,
    isValid: withdrawal > 0 && withdrawal <= available,
  }
})

const openAddCapital = () => {
  Object.assign(addCapitalForm, {
    amount: '',
    transaction_date: new Date().toISOString().slice(0, 10),
    status: 'available',
    notes: '',
  })
  addCapitalErrors.value = {}
  addCapitalOpen.value = true
}

const submitAddCapital = async () => {
  addCapitalSubmitting.value = true
  addCapitalErrors.value = {}
  try {
    await capitalStore.addCapital({
      amount: addCapitalForm.amount,
      transaction_date: addCapitalForm.transaction_date,
      status: addCapitalForm.status,
      notes: addCapitalForm.notes || null,
    })
    addCapitalOpen.value = false
  } catch (error) {
    if (error.response?.status === 422) addCapitalErrors.value = error.response.data.errors || {}
  } finally {
    addCapitalSubmitting.value = false
  }
}

const openWithdraw = () => {
  Object.assign(withdrawForm, {
    amount: '',
    transaction_date: new Date().toISOString().slice(0, 10),
    notes: '',
  })
  withdrawErrors.value = {}
  withdrawOpen.value = true
}

const fillMaxWithdraw = () => {
  withdrawForm.amount = String(withdrawPreview.value.available)
}

const submitWithdraw = async () => {
  if (!withdrawPreview.value.isValid) return
  withdrawSubmitting.value = true
  withdrawErrors.value = {}
  try {
    await capitalStore.withdrawCapital(withdrawForm)
    withdrawOpen.value = false
  } catch (error) {
    if (error.response?.status === 422) withdrawErrors.value = error.response.data.errors || {}
  } finally {
    withdrawSubmitting.value = false
  }
}

const confirmMakeAvailable = (transaction) => {
  pendingAvailability.value = { transaction, available: true }
  makeAvailableOpen.value = true
}

const confirmMakeUnavailable = (transaction) => {
  pendingAvailability.value = { transaction, available: false }
  makeUnavailableOpen.value = true
}

const applyAvailabilityChange = async () => {
  const { transaction, available } = pendingAvailability.value
  try {
    await capitalStore.updateAvailability(transaction.id, { available })
  } catch {
    // handled by store
  } finally {
    makeAvailableOpen.value = false
    makeUnavailableOpen.value = false
    pendingAvailability.value = null
  }
}

const openConvertDraft = (draft) => {
  convertDraft.value = draft
  convertDraftForm.available = 'available'
  convertDraftOpen.value = true
}

const submitConvertDraft = async () => {
  try {
    await capitalStore.convertDraft(convertDraft.value.id, {
      available: convertDraftForm.available === 'available',
    })
    convertDraftOpen.value = false
    convertDraft.value = null
  } catch {
    // handled by store
  }
}

const openRemoveDraft = (draft) => {
  removeDraft.value = draft
  removeDraftForm.removal_note = ''
  removeDraftErrors.value = {}
  removeDraftOpen.value = true
}

const submitRemoveDraft = async () => {
  if (!removeDraftForm.removal_note) {
    removeDraftErrors.value = { removal_note: ['A removal note is required.'] }
    return
  }
  try {
    await capitalStore.removeDraft(removeDraft.value.id, {
      removal_note: removeDraftForm.removal_note,
    })
    removeDraftOpen.value = false
    removeDraft.value = null
  } catch (error) {
    if (error.response?.status === 422) removeDraftErrors.value = error.response.data.errors || {}
  }
}

const isDueUrgent = (loan) => {
  if (loan.status !== 'active' || !loan.due_date) return false
  return loan.due_date <= new Date().toISOString().slice(0, 10)
}

onMounted(() => load().catch(() => { }))
</script>

<template>
  <div class="entity-list-page loan-page">
    <div class="page-head">
      <div>
        <span class="section-kicker">Capital</span>
        <h1>Loans</h1>
        <p class="page-sub">
          {{ pagination.total }}
          {{ pagination.total === 1 ? 'loan' : 'loans' }} · company-issued principal, repayments,
          and lending capital.
        </p>
      </div>
      <RouterLink class="btn" to="/loans/create">+ Issue loan</RouterLink>
    </div>

    <div v-if="capitalStore.error" class="page-error" role="alert">
      <p>{{ capitalStore.error }}</p>
      <button type="button" class="btn-light" @click="capitalStore.fetchCapital()">
        Retry capital
      </button>
    </div>

    <div v-if="capitalStore.loading && !capitalStore.account" class="sk" style="height: 120px" aria-hidden="true"></div>

    <!-- First-run setup -->
    <section v-else-if="!capitalStore.initialized" class="card capital-setup">
      <div>
        <span class="section-kicker">Required setup</span>
        <h2>Set opening company capital</h2>
        <p class="hint">
          This one-time balance controls how much company-owned money can be issued as loans.
        </p>
      </div>
      <form class="capital-setup-form" @submit.prevent="initialize">
        <div class="field">
          <label for="opening-amount">Opening amount</label>
          <input id="opening-amount" v-model="opening.amount" inputmode="decimal" placeholder="0.00"
            :aria-invalid="Boolean(openingErrors.amount)" />
          <span v-if="openingErrors.amount" class="error">{{ openingErrors.amount[0] }}</span>
        </div>
        <div class="field">
          <label for="opening-date">Transaction date</label>
          <input id="opening-date" v-model="opening.transaction_date" type="date" />
          <span v-if="openingErrors.transaction_date" class="error">
            {{ openingErrors.transaction_date[0] }}
          </span>
        </div>
        <button type="submit" class="setup-submit" :disabled="initializing">
          {{ initializing ? 'Saving capital…' : 'Set opening capital' }}
        </button>
      </form>
    </section>

    <template v-else>
      <!-- ============ Capital strip — one compact card ============ -->
      <section class="card capital-strip" aria-label="Company capital summary">
        <header class="strip-head">
          <h2 class="strip-title">
            Company capital
            <InfoTip
              label="Company-owned money available for lending. Investor capital is tracked separately under Investments." />
          </h2>
          <div class="strip-actions">
            <button type="button" class="btn-light btn-sm" @click="openAddCapital">
              + Add capital
            </button>
            <button type="button" class="btn-light btn-sm" @click="openWithdraw">Withdraw</button>
          </div>
        </header>

        <div class="strip-bar" role="img" :aria-label="`Capital split of ${money(capital.total)}`">
          <span class="seg seg-lent" :style="{ width: `${capital.lentPct}%` }"
            :title="`Lent out: ${money(capital.lentOut)}`"></span>
          <span class="seg seg-reserved" :style="{ width: `${capital.reservedPct}%` }"
            :title="`Reserved: ${money(capital.reserved)}`"></span>
          <span class="seg seg-available" :style="{ width: `${capital.availablePct}%` }"
            :title="`Available: ${money(capital.availableToLend)}`"></span>
        </div>

        <div class="strip-stats">
          <span class="strip-stat">
            <b class="seg-dot seg-available" aria-hidden="true"></b>
            <span class="strip-label">
              Available
              <InfoTip label="Ready to issue as a new loan right now." />
            </span>
            <strong :class="capital.availableToLend === 0 ? 'is-zero' : 'is-green'">
              {{ money(capital.availableToLend) }}
            </strong>
          </span>
          <span class="strip-stat">
            <b class="seg-dot seg-lent" aria-hidden="true"></b>
            <span class="strip-label">
              Lent out
              <InfoTip label="Currently in issued loans — comes back as repayments arrive." />
            </span>
            <strong>{{ money(capital.lentOut) }}</strong>
          </span>
          <span class="strip-stat">
            <b class="seg-dot seg-reserved" aria-hidden="true"></b>
            <span class="strip-label">
              Reserved
              <InfoTip label="Company money held back from lending. Toggle it from the movements list below." />
            </span>
            <strong>{{ money(capital.reserved) }}</strong>
          </span>
          <span class="strip-stat is-total">
            <span class="strip-label">
              Total
              <InfoTip label="All company capital: available + lent out + reserved." />
            </span>
            <strong>{{ money(capital.total) }}</strong>
          </span>
          <span v-if="pendingDraftsTotal > 0" class="strip-pending">
            <InfoTip
              label="Drafts are planned capital that isn't in the books yet. Convert them below to add the money." />
            + {{ money(pendingDraftsTotal) }} in drafts
          </span>
        </div>
      </section>

      <!-- ============ Pending drafts ============ -->
      <section v-if="capitalStore.drafts.length" class="card drafts-card">
        <header class="strip-head">
          <h2 class="strip-title">
            <span class="drafts-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                stroke-linejoin="round">
                <path d="M12 3v12" />
                <path d="M12 17h.01" />
                <circle cx="12" cy="12" r="10" />
              </svg>
            </span>
            Pending drafts
            <InfoTip label="A draft is a placeholder — it doesn't count as company capital until you convert it." />
          </h2>
          <span class="count-badge amber">{{ capitalStore.drafts.length }}</span>
        </header>

        <ul class="draft-list">
          <li v-for="draft in capitalStore.drafts" :key="draft.id" class="draft-row">
            <strong class="draft-amount">{{ money(draft.amount) }}</strong>
            <span class="draft-note" :title="draft.note">{{ draft.note }}</span>
            <span class="draft-date">{{ draft.transaction_date }}</span>
            <span class="draft-actions">
              <button type="button" class="btn-light btn-sm" @click="openConvertDraft(draft)">
                Add to capital
              </button>
              <button type="button" class="btn-light btn-sm danger" @click="openRemoveDraft(draft)">
                Remove
              </button>
            </span>
          </li>
        </ul>
      </section>

      <!-- ============ Movements ============ -->
      <details v-if="movementsWithBalance.length" class="card movement-card">
        <summary>
          <span class="strip-title">Capital movements</span>
          <span class="count-badge">{{ movementsWithBalance.length }}</span>
          <span class="chevron" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
              stroke-linejoin="round">
              <path d="m6 9 6 6 6-6" />
            </svg>
          </span>
        </summary>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Date</th>
                <th>Movement</th>
                <th class="right">
                  Amount
                  <InfoTip
                    label="Green enters company capital, red leaves it. Drafts are amber — they move nothing yet." />
                </th>
                <th class="right">
                  Balance
                  <InfoTip label="Company capital after this movement. Drafts don't change it." />
                </th>
                <th>Status</th>
                <th class="right"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="transaction in movementsWithBalance" :key="transaction.id"
                :class="{ 'row-draft': transaction.type === 'capital_draft' }">
                <td>{{ transaction.transaction_date }}</td>
                <td>
                  {{ movementLabel(transaction) }}
                  <small v-if="transaction.description" class="movement-note">
                    {{ transaction.description }}
                  </small>
                </td>
                <td class="right" :class="transaction.type === 'capital_draft'
                    ? 'amt-draft'
                    : Number(transaction.amount) < 0
                      ? 'money-loss'
                      : 'money-profit'
                  ">
                  <template v-if="Number(transaction.amount) !== 0">
                    <template v-if="transaction.type === 'capital_draft'">
                      {{ money(transaction.amount) }}
                    </template>
                    <template v-else>
                      {{ Number(transaction.amount) > 0 ? '+' : '' }}{{ money(transaction.amount) }}
                    </template>
                  </template>
                  <template v-else>&mdash;</template>
                </td>
                <td class="right row-num">{{ money(transaction.running_balance) }}</td>
                <td>
                  <template v-if="transaction.type === 'capital_added'">
                    <span class="status" :class="transaction.available ? 'status-completed' : 'status-budgeted'">
                      {{ transaction.available ? 'Available' : 'Reserved' }}
                    </span>
                  </template>
                  <template v-else-if="transaction.type === 'capital_draft'">
                    <span class="status" :class="draftStatusClass(transaction.draft_status)">
                      {{ draftStatusLabel(transaction.draft_status) }}
                    </span>
                  </template>
                  <template v-else>&mdash;</template>
                </td>
                <td class="right">
                  <div class="row-actions">
                    <button v-if="transaction.type === 'capital_added' && !transaction.available" type="button"
                      class="btn-light btn-sm" @click="confirmMakeAvailable(transaction)">
                      Make available
                    </button>
                    <button v-else-if="transaction.type === 'capital_added' && transaction.available" type="button"
                      class="btn-light btn-sm" @click="confirmMakeUnavailable(transaction)">
                      Reserve
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </details>
    </template>

    <!-- ============ Loan register ============ -->
    
    <section class="card list-card">
      <div class="list-card-header">
        <div>
          <h2>Loan register</h2>
          <p class="hint">Open a loan to record repayments or revise its due date.</p>
        </div>
      </div>

      <form class="filter-toolbar" @submit.prevent="loadLoans()">
        <div class="filter-field grow">
          <label class="sr-only" for="borrower-filter">Borrower</label>
          <input id="borrower-filter" v-model="filters.borrower" type="search" placeholder="Search borrower…" />
        </div>
        <div class="filter-field">
          <label class="sr-only" for="status-filter">Status</label>
          <select id="status-filter" v-model="filters.status">
            <option value="">All statuses</option>
            <option value="active">Active</option>
            <option value="overdue">Overdue</option>
            <option value="paid">Paid</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>
        <div class="filter-field">
          <label class="sr-only" for="from-filter">From date</label>
          <input id="from-filter" v-model="filters.from" type="date" />
        </div>
        <div class="filter-field">
          <label class="sr-only" for="to-filter">To date</label>
          <input id="to-filter" v-model="filters.to" type="date" />
        </div>
        <button type="submit" class="btn-light btn-sm filter-apply">Apply</button>
        <button v-if="hasFilters" type="button" class="btn-light btn-sm" @click="resetFilters">
          Clear
        </button>
      </form>

      <div v-if="error" class="page-error" role="alert">
        <p>{{ error }}</p>
        <button type="button" class="btn-light" @click="loadLoans()">Try again</button>
      </div>

      <StatePanel :loading="loading && !loans.length" :empty="!loading && !error && !loans.length"
        empty-title="No loans match this view. Issue a loan or clear the filters." empty-action="Issue loan"
        empty-to="/loans/create">
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Loan</th>
                <th>Borrower</th>
                <th class="right">
                  Principal
                  <InfoTip label="The amount originally issued. Loans are interest-free." />
                </th>
                <th class="right">
                  Repaid
                  <InfoTip label="Total repayments received so far." />
                </th>
                <th class="right">
                  Outstanding
                  <InfoTip label="Principal minus repayments — what's still owed." />
                </th>
                <th>
                  Due
                  <InfoTip label="When the loan should be fully repaid. Red means past due." />
                </th>
                <th>Status</th>
                <th class="right"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="loan in loans" :key="loan.id">
                <td>
                  <RouterLink class="record-link" :to="`/loans/${loan.id}`">
                    {{ loan.loan_code }}
                  </RouterLink>
                  <span class="record-code">{{ loan.loan_date }}</span>
                </td>
                <td>
                  <div class="borrower-cell">
                    <span class="borrower-avatar" :style="avatarStyle(loan.borrower?.name || '?')" aria-hidden="true">
                      {{ initialOf(loan.borrower?.name || '?') }}
                    </span>
                    <span class="borrower-id">
                      <span class="borrower-name">{{ loan.borrower?.name || 'Unknown' }}</span>
                      <span class="record-code capitalize">{{ loan.borrower_type }}</span>
                    </span>
                  </div>
                </td>
                <td class="right row-num">{{ money(loan.amount) }}</td>
                <td class="right row-num">{{ money(loan.total_repaid) }}</td>
                <td class="right row-amount">{{ money(loan.outstanding_amount) }}</td>
                <td :class="{ 'due-urgent': isDueUrgent(loan) }">{{ loan.due_date }}</td>
                <td>
                  <FinanceStatus :status="loan.status" kind="loan" />
                </td>
                <td class="right">
                  <RouterLink class="icon-action" :to="`/loans/${loan.id}`" title="Open loan" aria-label="Open loan">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                      stroke-linejoin="round" aria-hidden="true">
                      <path d="M5 12h14" />
                      <path d="m12 5 7 7-7 7" />
                    </svg>
                  </RouterLink>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <Pagination :page="pagination.current_page" :last-page="pagination.last_page" :total="pagination.total"
          :per-page="pagination.per_page" @update:page="loadLoans" />
      </StatePanel>
    </section>

    <!-- ============ Dialogs ============ -->

    <ConfirmDialog :open="convertDraftOpen" title="Add draft to company capital?" confirm-label="Add to capital" wide
      @confirm="submitConvertDraft" @cancel="convertDraftOpen = false">
      <template #body>
        <div v-if="convertDraft">
          <p class="dialog-help">Converting this draft turns it into real company capital.</p>
          <div class="dialog-amount">
            <span>Amount</span>
            <strong>{{ money(convertDraft.amount) }}</strong>
          </div>
          <div class="field">
            <label>Availability</label>
            <div class="choice-row">
              <label class="choice-option">
                <input type="radio" value="available" v-model="convertDraftForm.available" />
                <span><strong>Available now</strong><small>Can be used for loans immediately.</small></span>
              </label>
              <label class="choice-option">
                <input type="radio" value="reserved" v-model="convertDraftForm.available" />
                <span><strong>Reserve for later</strong><small>Company capital, not lendable yet.</small></span>
              </label>
            </div>
          </div>
        </div>
      </template>
    </ConfirmDialog>

    <ConfirmDialog :open="removeDraftOpen" title="Remove this draft?" confirm-label="Remove draft" variant="danger"
      @confirm="submitRemoveDraft" @cancel="removeDraftOpen = false">
      <template #body>
        <form @submit.prevent="submitRemoveDraft">
          <div v-if="removeDraft">
            <p class="dialog-help">
              Removes the draft from pending status. Company capital is not affected.
            </p>
            <div class="dialog-amount">
              <span>Amount</span>
              <strong>{{ money(removeDraft.amount) }}</strong>
            </div>
          </div>
          <div class="field">
            <label for="remove-note">
              Removal note
              <InfoTip label="Why this draft is being dropped — kept in the movement history." />
            </label>
            <textarea id="remove-note" v-model="removeDraftForm.removal_note" rows="3"
              placeholder="e.g. Investor withdrew the commitment"
              :aria-invalid="Boolean(removeDraftErrors.removal_note)"></textarea>
            <span v-if="removeDraftErrors.removal_note" class="error">
              {{ removeDraftErrors.removal_note[0] }}
            </span>
          </div>
        </form>
      </template>
    </ConfirmDialog>

    <ConfirmDialog :open="addCapitalOpen" title="Add company capital"
      :confirm-label="addCapitalForm.status === 'draft' ? 'Add draft' : 'Add capital'" wide
      :loading="addCapitalSubmitting" @confirm="submitAddCapital" @cancel="addCapitalOpen = false">
      <template #body>
        <form @submit.prevent="submitAddCapital">
          <p class="dialog-help">
            Add company money to the books — or record it as a draft to decide later.
          </p>
          <div class="dialog-grid">
            <div class="field">
              <label for="add-amount">Amount</label>
              <input id="add-amount" v-model="addCapitalForm.amount" inputmode="decimal" placeholder="0.00"
                :aria-invalid="Boolean(addCapitalErrors.amount)" />
              <span v-if="addCapitalErrors.amount" class="error">{{ addCapitalErrors.amount[0] }}</span>
            </div>
            <div class="field">
              <label for="add-date">Transaction date</label>
              <input id="add-date" v-model="addCapitalForm.transaction_date" type="date"
                :aria-invalid="Boolean(addCapitalErrors.transaction_date)" />
              <span v-if="addCapitalErrors.transaction_date" class="error">
                {{ addCapitalErrors.transaction_date[0] }}
              </span>
            </div>
          </div>
          <div class="field">
            <label>
              Status
              <InfoTip
                label="Available and reserved are real capital immediately. A draft is a placeholder that only becomes capital when converted." />
            </label>
            <div class="choice-row three">
              <label class="choice-option">
                <input type="radio" value="available" v-model="addCapitalForm.status" />
                <span><strong>Available now</strong><small>Lendable immediately.</small></span>
              </label>
              <label class="choice-option">
                <input type="radio" value="reserved" v-model="addCapitalForm.status" />
                <span><strong>Reserve for later</strong><small>Not lendable yet.</small></span>
              </label>
              <label class="choice-option">
                <input type="radio" value="draft" v-model="addCapitalForm.status" />
                <span><strong>Draft</strong><small>Placeholder — decide later.</small></span>
              </label>
            </div>
            <span v-if="addCapitalErrors.status" class="error">{{ addCapitalErrors.status[0] }}</span>
          </div>
          <div class="field">
            <label for="add-notes">Notes</label>
            <textarea id="add-notes" v-model="addCapitalForm.notes" rows="2"></textarea>
          </div>
        </form>
      </template>
    </ConfirmDialog>

    <ConfirmDialog :open="withdrawOpen" title="Withdraw company capital" confirm-label="Withdraw" variant="danger"
      :loading="withdrawSubmitting" @confirm="submitWithdraw" @cancel="withdrawOpen = false">
      <template #body>
        <form @submit.prevent="submitWithdraw">
          <p class="dialog-help">
            Take company money out of the lending pool. Only available capital can be withdrawn.
          </p>
          <div class="field">
            <label for="withdraw-amount">Amount</label>
            <div class="amount-row">
              <input id="withdraw-amount" v-model="withdrawForm.amount" inputmode="decimal" placeholder="0.00"
                :aria-invalid="Boolean(withdrawErrors.amount)" />
              <button v-if="withdrawPreview.available > 0" type="button" class="btn-light btn-sm"
                @click="fillMaxWithdraw">
                All
              </button>
            </div>
            <span v-if="withdrawErrors.amount" class="error">{{ withdrawErrors.amount[0] }}</span>
          </div>
          <div class="field">
            <label for="withdraw-date">Transaction date</label>
            <input id="withdraw-date" v-model="withdrawForm.transaction_date" type="date" />
          </div>
          <div class="field">
            <label for="withdraw-notes">Reason / notes</label>
            <textarea id="withdraw-notes" v-model="withdrawForm.notes" rows="2"></textarea>
          </div>
          <div v-if="Number(withdrawForm.amount) > 0" class="mini-preview">
            <div class="preview-row">
              <span>Available to withdraw</span>
              <strong>{{ money(withdrawPreview.available) }}</strong>
            </div>
            <div class="preview-row preview-total">
              <span>Remaining available</span>
              <strong :class="withdrawPreview.remaining < 0 ? 'money-loss' : ''">
                {{ money(withdrawPreview.remaining) }}
              </strong>
            </div>
            <p v-if="!withdrawPreview.isValid" class="error">
              Only {{ money(withdrawPreview.available) }} is available to withdraw.
            </p>
          </div>
        </form>
      </template>
    </ConfirmDialog>

    <ConfirmDialog :open="makeAvailableOpen" title="Make capital available?"
      message="This reserved capital will become available for issuing loans." confirm-label="Make available"
      @confirm="applyAvailabilityChange" @cancel="makeAvailableOpen = false" />

    <ConfirmDialog :open="makeUnavailableOpen" title="Reserve this capital?"
      message="It will no longer be available for new loans. Existing loans are not affected." confirm-label="Reserve"
      variant="danger" @confirm="applyAvailabilityChange" @cancel="makeUnavailableOpen = false" />
  </div>
</template>

<style scoped>
.loan-page,
.list-card {
  min-width: 0;
}

.page-sub {
  color: var(--text-secondary);
  font-size: 14px;
  margin-top: var(--space-2);
}

.page-error {
  align-items: center;
  background: var(--danger-soft);
  border: 1px solid var(--danger);
  border-radius: var(--radius-md);
  display: flex;
  gap: var(--space-3);
  justify-content: space-between;
  margin-bottom: var(--space-4);
  padding: var(--space-3) var(--space-4);
}

.page-error p {
  color: var(--danger);
}

/* Skeleton */
.sk {
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  margin-bottom: var(--space-4);
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

@keyframes shimmer {
  100% {
    transform: translateX(100%);
  }
}

/* ---------- Setup ---------- */
.capital-setup {
  align-items: start;
  display: grid;
  gap: var(--space-6);
  grid-template-columns: minmax(0, 1fr) minmax(320px, 1fr);
  margin-bottom: var(--space-4);
}

.capital-setup .hint {
  display: block;
  margin: var(--space-2) 0 0;
}

.capital-setup-form {
  display: grid;
  gap: var(--space-3);
  grid-template-columns: 1fr 1fr;
}

.setup-submit {
  grid-column: 1 / -1;
}

/* ---------- Capital strip ---------- */
.capital-strip {
  margin-bottom: var(--space-4);
  padding: 16px 20px;
}

.strip-head {
  align-items: center;
  display: flex;
  gap: 12px;
  justify-content: space-between;
  margin-bottom: 12px;
}

.strip-title {
  align-items: center;
  display: inline-flex;
  font-size: 14px;
  font-weight: 600;
  gap: 6px;
  margin: 0;
}

.strip-actions {
  display: flex;
  gap: 8px;
}

.strip-bar {
  background: var(--surface-2);
  border-radius: 999px;
  display: flex;
  height: 8px;
  overflow: hidden;
}

.seg {
  display: block;
  height: 100%;
  transition: width 0.4s ease;
}

.seg-lent {
  background: var(--accent);
}

.seg-reserved {
  background: var(--warning);
}

.seg-available {
  background: var(--success);
}

.strip-stats {
  display: flex;
  flex-wrap: wrap;
  gap: 6px 28px;
  margin-top: 12px;

}

.strip-stat {
  align-items: baseline;
  display: inline-flex;
  gap: 8px;
  border: 1px solid var(--border);
  border-radius: 999px;
  padding: 6px 12px;
}

.seg-dot {
  align-self: center;
  border-radius: 3px;
  display: inline-block;
  height: 9px;
  width: 9px;
}

.strip-label {
  align-items: center;
  color: var(--text-muted);
  display: inline-flex;
  font-size: 12px;
  gap: 4px;
}

.strip-stat strong {
  color: var(--text-primary);
  font-size: 16px;
  font-variant-numeric: tabular-nums;
  font-weight: 700;
}

.strip-stat strong.is-green {
  color: var(--success);
}

.strip-stat strong.is-zero {
  color: var(--text-muted);
}

.strip-stat.is-total {
  border-left: 1px solid var(--border);
  padding-left: 28px;
}

.strip-stat.is-total .strip-label {
  color: var(--text-muted);
}

.strip-pending {
  align-items: center;
  background: var(--warning-soft);
  border-radius: 999px;
  color: var(--warning);
  display: inline-flex;
  font-size: 12px;
  font-weight: 600;
  gap: 4px;
  padding: 3px 10px;
}

/* ---------- Drafts ---------- */
.drafts-card {
  border-left: 3px solid var(--warning);
  margin-bottom: var(--space-4);
  padding: 16px 20px;
}

.drafts-icon {
  align-items: center;
  background: var(--warning-soft);
  border-radius: 8px;
  color: var(--warning);
  display: inline-flex;
  height: 26px;
  justify-content: center;
  width: 26px;
}

.drafts-icon svg {
  height: 14px;
  width: 14px;
}

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
}

.count-badge.amber {
  background: var(--warning-soft);
  color: var(--warning);
}

.draft-list {
  display: flex;
  flex-direction: column;
  list-style: none;
  margin: 0;
  padding: 0;
}

.draft-row {
  align-items: center;
  border-top: 1px solid var(--border);
  display: flex;
  flex-wrap: wrap;
  gap: 6px 14px;
  padding: 10px 0;
}

.draft-row:first-child {
  border-top: 0;
}

.draft-amount {
  color: var(--text-primary);
  flex: 0 0 auto;
  font-size: 15px;
  font-variant-numeric: tabular-nums;
  font-weight: 700;
}

.draft-note {
  color: var(--text-secondary);
  flex: 1 1 140px;
  font-size: 13px;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.draft-date {
  color: var(--text-muted);
  flex: 0 0 auto;
  font-size: 12px;
}

.draft-actions {
  display: flex;
  gap: 6px;
  margin-left: auto;
}

/* ---------- Movements ---------- */
.movement-card {
  margin-bottom: var(--space-4);
  padding: 0;
}

.movement-card summary {
  align-items: center;
  cursor: pointer;
  display: flex;
  gap: 10px;
  list-style: none;
  padding: 14px 20px;
  user-select: none;
}

.movement-card summary::-webkit-details-marker {
  display: none;
}

.movement-card[open] summary {
  border-bottom: 1px solid var(--border);
}

.chevron {
  color: var(--text-muted);
  display: inline-flex;
  margin-left: auto;
  transition: transform 0.2s ease;
}

.chevron svg {
  height: 16px;
  width: 16px;
}

.movement-card[open] .chevron {
  transform: rotate(180deg);
}

.movement-card .table-wrap {
  padding: 0 8px 8px;
}

.movement-note {
  color: var(--text-muted);
  display: block;
  font-size: 12px;
}

.row-draft td {
  background: rgb(217 119 6 / 4%);
}

.amt-draft {
  color: var(--warning);
  font-weight: 600;
}

.row-num {
  color: var(--text-secondary);
  font-variant-numeric: tabular-nums;
}

.row-actions {
  display: inline-flex;
  gap: 4px;
  justify-content: flex-end;
}

/* ---------- Register ---------- */
.list-card-header {
  align-items: flex-start;
  display: flex;
  justify-content: space-between;
  margin-bottom: var(--space-4);
}

.list-card-header h2 {
  font-size: 15px;
  font-weight: 600;
}

.filter-toolbar {
  align-items: center;
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: var(--space-4);
}

.filter-field {
  flex: 0 0 auto;
}

.filter-field.grow {
  flex: 1 1 200px;
  min-width: 180px;
}

.filter-field input,
.filter-field select {
  min-height: 38px;
}

.filter-apply {
  min-height: 38px;
}

.record-link {
  color: var(--text-primary);
  display: block;
  font-weight: 600;
  text-decoration: none;
}

.record-link:hover {
  color: var(--accent);
}

.record-code {
  color: var(--text-muted);
  display: block;
  font-size: 12px;
  margin-top: 1px;
}

.borrower-cell {
  align-items: center;
  display: flex;
  gap: 10px;
}

.borrower-avatar {
  align-items: center;
  border-radius: 9px;
  color: #fff;
  display: inline-flex;
  flex: 0 0 28px;
  font-size: 11px;
  font-weight: 600;
  height: 28px;
  justify-content: center;
  width: 28px;
}

.borrower-id {
  display: flex;
  flex-direction: column;
  gap: 1px;
  min-width: 0;
}

.borrower-name {
  color: var(--text-primary);
  font-size: 14px;
  font-weight: 500;
}

.row-amount {
  color: var(--text-primary);
  font-weight: 600;
  font-variant-numeric: tabular-nums;
}

.due-urgent {
  color: var(--danger);
  font-weight: 600;
}

.icon-action {
  align-items: center;
  background: transparent;
  border: 1px solid transparent;
  border-radius: 8px;
  color: var(--text-muted);
  display: inline-flex;
  height: 32px;
  justify-content: center;
  transition: background 0.15s ease, color 0.15s ease;
  width: 32px;
}

.icon-action svg {
  height: 15px;
  width: 15px;
}

.icon-action:hover {
  background: var(--accent-soft);
  color: var(--accent);
}

/* ---------- Dialog body helpers (slot content is parent-scoped) ---------- */
.dialog-help {
  color: var(--text-secondary);
  font-size: var(--text-sm);
  margin-bottom: var(--space-3);
}

.dialog-amount {
  align-items: center;
  background: var(--surface-2);
  border-radius: var(--radius-md);
  display: flex;
  font-size: var(--text-sm);
  justify-content: space-between;
  margin-bottom: var(--space-4);
  padding: var(--space-3) var(--space-4);
}

.dialog-amount span {
  color: var(--text-muted);
}

.dialog-amount strong {
  font-size: var(--text-lg);
  font-variant-numeric: tabular-nums;
  font-weight: var(--font-weight-semibold);
}

.dialog-grid {
  display: grid;
  gap: var(--space-3);
  grid-template-columns: 1fr 1fr;
}

.choice-row {
  display: grid;
  gap: var(--space-2);
  grid-template-columns: 1fr 1fr;
}

.choice-row.three {
  grid-template-columns: 1fr 1fr 1fr;
}

.choice-option {
  align-items: flex-start;
  background: var(--surface);
  border: 1px solid var(--border-strong);
  border-radius: var(--radius-md);
  cursor: pointer;
  display: flex;
  gap: var(--space-2);
  margin: 0;
  padding: 10px 12px;
  transition: background var(--transition-fast), border-color var(--transition-fast);
}

.choice-option:hover {
  border-color: var(--text-muted);
}

.choice-option:has(input:checked) {
  background: var(--accent-soft);
  border-color: var(--accent);
}

.choice-option input {
  flex: 0 0 auto;
  min-height: auto;
  width: auto;
}

.choice-option span {
  display: flex;
  flex-direction: column;
}

.choice-option strong {
  color: var(--text-primary);
  font-size: 13px;
}

.choice-option small {
  color: var(--text-secondary);
  font-size: 11px;
  margin-top: 2px;
}

.amount-row {
  display: flex;
  gap: 8px;
}

.amount-row input {
  flex: 1;
}

.amount-row .btn-light {
  flex: 0 0 auto;
}

.mini-preview {
  background: var(--surface-2);
  border-radius: var(--radius-md);
  margin-top: var(--space-3);
  padding: var(--space-3);
}

.preview-row {
  display: flex;
  font-size: var(--text-sm);
  justify-content: space-between;
  padding: var(--space-1) 0;
}

.preview-row span {
  color: var(--text-muted);
}

.preview-row strong {
  font-variant-numeric: tabular-nums;
}

.preview-total {
  border-top: 1px solid var(--border);
  font-weight: var(--font-weight-semibold);
  margin-top: var(--space-1);
  padding-top: var(--space-1);
}

.capitalize {
  text-transform: capitalize;
}

/* ---------- Responsive ---------- */
@media (max-width: 900px) {
  .capital-setup {
    grid-template-columns: 1fr;
  }

  .strip-stat.is-total {
    border-left: 0;
    padding-left: 0;
  }

  .choice-row.three {
    grid-template-columns: 1fr;
  }

  .dialog-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 560px) {
  .capital-setup-form {
    grid-template-columns: 1fr;
  }

  .choice-row {
    grid-template-columns: 1fr;
  }

  .page-error,
  .list-card-header {
    align-items: flex-start;
    flex-direction: column;
  }

  .strip-head {
    flex-direction: column;
    align-items: flex-start;
  }

  .draft-actions {
    margin-left: 0;
    width: 100%;
  }

  .draft-actions .btn-light {
    flex: 1;
    justify-content: center;
  }
}
</style>