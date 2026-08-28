<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useLoanStore } from '@/stores/loanStore'
import { useInvestorStore } from '@/stores/investorStore'
import { useCompanyCapitalStore } from '@/stores/companyCapitalStore'
import { money } from '@/utils/money'
import InfoTip from '@/components/ui/InfoTip.vue'

const route = useRoute()
const router = useRouter()
const loanStore = useLoanStore()
const investorStore = useInvestorStore()
const capitalStore = useCompanyCapitalStore()
const { investors } = storeToRefs(investorStore)
const { borrowers, borrowerLoading } = storeToRefs(loanStore)
const today = new Date().toISOString().slice(0, 10)

const form = reactive({
  borrower_type: route.query.investor_id ? 'investor' : 'outsider',
  investor_id: route.query.investor_id || '',
  loan_borrower_id: '',
  outsider_name: '',
  outsider_email: '',
  outsider_phone: '',
  outsider_address: '',
  amount: '',
  loan_date: today,
  due_date: '',
  notes: '',
})

const outsiderMode = ref('existing')
const investorSearch = ref('')
const borrowerSearch = ref('')
const errors = ref({})
const submitting = ref(false)
const searchingInvestors = ref(false)
let investorTimer
let borrowerTimer

const availableCapitalAmount = computed(() => Number(capitalStore.available_to_lend) || 0)
const loanAmount = computed(() => Number(form.amount) || 0)
const remainingAfter = computed(() => availableCapitalAmount.value - loanAmount.value)
const loanExceedsCapital = computed(() => loanAmount.value > availableCapitalAmount.value)

const fillMax = () => {
  if (availableCapitalAmount.value > 0) form.amount = String(availableCapitalAmount.value)
}

const searchInvestors = () => {
  clearTimeout(investorTimer)
  investorTimer = setTimeout(async () => {
    searchingInvestors.value = true
    try {
      await investorStore.fetchInvestors({
        search: investorSearch.value || undefined,
        per_page: 100,
      })
    } finally {
      searchingInvestors.value = false
    }
  }, 250)
}

const searchBorrowers = () => {
  clearTimeout(borrowerTimer)
  borrowerTimer = setTimeout(
    () => loanStore.fetchBorrowers({ search: borrowerSearch.value || undefined }).catch(() => {}),
    250,
  )
}

const payload = () => {
  const data = {
    borrower_type: form.borrower_type,
    amount: form.amount,
    loan_date: form.loan_date,
    due_date: form.due_date,
    notes: form.notes || null,
  }
  if (form.borrower_type === 'investor') data.investor_id = form.investor_id
  else if (outsiderMode.value === 'existing') data.loan_borrower_id = form.loan_borrower_id
  else
    Object.assign(data, {
      outsider_name: form.outsider_name,
      outsider_email: form.outsider_email || null,
      outsider_phone: form.outsider_phone || null,
      outsider_address: form.outsider_address || null,
    })
  return data
}

const submit = async () => {
  errors.value = {}
  if (loanExceedsCapital.value) {
    errors.value = {
      ...errors.value,
      __capital__: [
        `Insufficient company capital. You can currently lend up to ${money(availableCapitalAmount.value)}.`,
      ],
    }
    return
  }

  submitting.value = true
  try {
    const loan = await loanStore.createLoan(payload())
    await router.push(`/loans/${loan.id}`)
  } catch (error) {
    if (error.response?.status === 422) errors.value = error.response.data.errors || {}
  } finally {
    submitting.value = false
  }
}

const fieldError = (field) => errors.value[field]?.[0]

watch(
  () => form.borrower_type,
  () => {
    errors.value = {}
  },
)
watch(outsiderMode, () => {
  errors.value = {}
})

onMounted(() =>
  Promise.all([
    investorStore.fetchInvestors({ per_page: 100 }),
    loanStore.fetchBorrowers(),
    capitalStore.fetchCapital(),
  ]).catch(() => {}),
)
</script>

<template>
  <div class="loan-create-page">
    <div class="page-head">
      <div>
        <span class="section-kicker">Capital / Loans</span>
        <h1>Issue loan</h1>
        <p class="page-sub">Record company money issued to an investor or outsider.</p>
      </div>
    </div>

    <div v-if="!capitalStore.loading && !capitalStore.initialized" class="page-error" role="alert">
      <p>Opening company capital must be configured before issuing a loan.</p>
      <RouterLink class="btn-light" to="/loans">Configure capital</RouterLink>
    </div>

    <form class="loan-layout" @submit.prevent="submit">
      <!-- ===== Main column ===== -->
      <div class="form-main">
        <!-- Borrower -->
        <section class="card form-card">
          <header class="section-head-row">
            <span class="section-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="M22 21v-2a4 4 0 0 0-3-3.87" /><path d="M16 3.13a4 4 0 0 1 0 7.75" />
              </svg>
            </span>
            <div>
              <h2>Borrower</h2>
              <p class="section-hint">
                Who is receiving the money.
                <InfoTip label="Investor borrowers already have a profile here. Outsiders are standalone contacts you can create on the spot." />
              </p>
            </div>
          </header>

          <div class="choice-row">
            <label class="choice-option">
              <input v-model="form.borrower_type" type="radio" value="investor" />
              <span><strong>Investor</strong><small>Existing investor profile</small></span>
            </label>
            <label class="choice-option">
              <input v-model="form.borrower_type" type="radio" value="outsider" />
              <span><strong>Outsider</strong><small>Standalone borrower</small></span>
            </label>
          </div>
          <span v-if="fieldError('borrower_type')" class="error">{{ fieldError('borrower_type') }}</span>

          <div v-if="form.borrower_type === 'investor'" class="grid two">
            <div class="field">
              <label for="investor-search">Search</label>
              <input
                id="investor-search"
                v-model="investorSearch"
                type="search"
                placeholder="Name, code, email…"
                @input="searchInvestors"
              />
            </div>
            <div class="field">
              <label for="investor-id">Investor</label>
              <select
                id="investor-id"
                v-model="form.investor_id"
                :aria-invalid="Boolean(fieldError('investor_id'))"
              >
                <option value="">{{ searchingInvestors ? 'Searching…' : 'Select an investor' }}</option>
                <option v-for="investor in investors" :key="investor.id" :value="investor.id">
                  {{ investor.name }} · {{ investor.investor_code }}
                </option>
              </select>
              <span v-if="fieldError('investor_id')" class="error">{{ fieldError('investor_id') }}</span>
            </div>
          </div>

          <template v-else>
            <div class="choice-row sub">
              <label class="choice-option">
                <input v-model="outsiderMode" type="radio" value="existing" />
                <span><strong>Existing</strong><small>Select a saved borrower</small></span>
              </label>
              <label class="choice-option">
                <input v-model="outsiderMode" type="radio" value="new" />
                <span><strong>New</strong><small>Create with this loan</small></span>
              </label>
            </div>

            <div v-if="outsiderMode === 'existing'" class="grid two">
              <div class="field">
                <label for="borrower-search">Search</label>
                <input
                  id="borrower-search"
                  v-model="borrowerSearch"
                  type="search"
                  placeholder="Name, code, email…"
                  @input="searchBorrowers"
                />
              </div>
              <div class="field">
                <label for="borrower-id">Outsider</label>
                <select
                  id="borrower-id"
                  v-model="form.loan_borrower_id"
                  :aria-invalid="Boolean(fieldError('loan_borrower_id'))"
                >
                  <option value="">{{ borrowerLoading ? 'Searching…' : 'Select an outsider' }}</option>
                  <option v-for="borrower in borrowers" :key="borrower.id" :value="borrower.id">
                    {{ borrower.name }} · {{ borrower.borrower_code }}
                  </option>
                </select>
                <span v-if="fieldError('loan_borrower_id')" class="error">
                  {{ fieldError('loan_borrower_id') }}
                </span>
              </div>
            </div>

            <div v-else class="grid two">
              <div class="field">
                <label for="outsider-name">Name</label>
                <input id="outsider-name" v-model="form.outsider_name" autocomplete="name" :aria-invalid="Boolean(fieldError('outsider_name'))" />
                <span v-if="fieldError('outsider_name')" class="error">{{ fieldError('outsider_name') }}</span>
              </div>
              <div class="field">
                <label for="outsider-email">Email</label>
                <input id="outsider-email" v-model="form.outsider_email" type="email" autocomplete="email" :aria-invalid="Boolean(fieldError('outsider_email'))" />
                <span v-if="fieldError('outsider_email')" class="error">{{ fieldError('outsider_email') }}</span>
              </div>
              <div class="field">
                <label for="outsider-phone">Phone</label>
                <input id="outsider-phone" v-model="form.outsider_phone" type="tel" autocomplete="tel" :aria-invalid="Boolean(fieldError('outsider_phone'))" />
                <span v-if="fieldError('outsider_phone')" class="error">{{ fieldError('outsider_phone') }}</span>
              </div>
              <div class="field">
                <label for="outsider-address">Address</label>
                <input id="outsider-address" v-model="form.outsider_address" autocomplete="street-address" :aria-invalid="Boolean(fieldError('outsider_address'))" />
                <span v-if="fieldError('outsider_address')" class="error">{{ fieldError('outsider_address') }}</span>
              </div>
            </div>
          </template>
        </section>

        <!-- Terms -->
        <section class="card form-card">
          <header class="section-head-row">
            <span class="section-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect width="20" height="12" x="2" y="6" rx="2" /><circle cx="12" cy="12" r="2" /><path d="M6 12h.01M18 12h.01" />
              </svg>
            </span>
            <div>
              <h2>Terms</h2>
              <p class="section-hint">
                Interest-free — only the principal is owed back.
                <InfoTip label="Loan repayments return money to company capital. Returns on investments are tracked separately under Investments." />
              </p>
            </div>
          </header>

          <div class="field">
            <label for="loan-amount">
              Loan amount
              <InfoTip label="Cannot exceed the available company capital — watch the panel on the right." />
            </label>
            <div class="amount-row">
              <input
                id="loan-amount"
                v-model="form.amount"
                inputmode="decimal"
                placeholder="0.00"
                :aria-invalid="Boolean(fieldError('amount'))"
              />
              <button
                v-if="availableCapitalAmount > 0"
                type="button"
                class="btn-light btn-sm"
                title="Fill with all available capital"
                @click="fillMax"
              >
                Max
              </button>
            </div>
            <p v-if="loanExceedsCapital" class="capital-warn" role="alert">
              Exceeds available capital ({{ money(availableCapitalAmount) }}) — reduce the amount or
              add capital first.
            </p>
            <span v-if="fieldError('amount')" class="error">{{ fieldError('amount') }}</span>
          </div>

          <div class="grid two">
            <div class="field">
              <label for="loan-date">Loan date</label>
              <input id="loan-date" v-model="form.loan_date" type="date" :aria-invalid="Boolean(fieldError('loan_date'))" />
              <span v-if="fieldError('loan_date')" class="error">{{ fieldError('loan_date') }}</span>
            </div>
            <div class="field">
              <label for="due-date">
                Due date
                <InfoTip label="When the loan should be fully repaid. The loan turns overdue after this date." />
              </label>
              <input id="due-date" v-model="form.due_date" type="date" :aria-invalid="Boolean(fieldError('due_date'))" />
              <span v-if="fieldError('due_date')" class="error">{{ fieldError('due_date') }}</span>
            </div>
          </div>

          <div class="field">
            <label for="loan-notes">
              Notes
              <InfoTip label="Internal context for the team — never shown to the borrower." />
            </label>
            <textarea id="loan-notes" v-model="form.notes" rows="3"></textarea>
            <span v-if="fieldError('notes')" class="error">{{ fieldError('notes') }}</span>
          </div>

          <p v-if="loanStore.error" class="page-error" role="alert">{{ loanStore.error }}</p>
          <p v-if="errors.__capital__" class="page-error" role="alert">{{ errors.__capital__[0] }}</p>
        </section>
      </div>

      <!-- ===== Sticky capital panel ===== -->
      <aside class="form-aside">
        <div class="card panel-card">
          <h2 class="panel-title">
            Capital check
            <InfoTip label="Live check against company capital while you type." />
          </h2>

          <div class="panel-available">
            <span>Available to lend</span>
            <strong :class="loanExceedsCapital ? 'is-red' : 'is-green'">
              {{ money(availableCapitalAmount) }}
            </strong>
          </div>

          <div v-if="loanAmount > 0" class="panel-rows">
            <div class="panel-row">
              <span>This loan</span>
              <strong>{{ money(loanAmount) }}</strong>
            </div>
            <div class="panel-row panel-total">
              <span>Remaining after</span>
              <strong :class="remainingAfter < 0 ? 'money-loss' : 'money-profit'">
                {{ money(remainingAfter) }}
              </strong>
            </div>
          </div>

          <p class="panel-meta">Interest-free · principal only · company funds</p>

          <div class="panel-actions">
            <button type="submit" :disabled="submitting || !capitalStore.initialized" :aria-busy="submitting">
              {{ submitting ? 'Issuing…' : 'Issue loan' }}
            </button>
            <RouterLink class="btn-light" to="/loans">Cancel</RouterLink>
          </div>
        </div>
      </aside>
    </form>
  </div>
</template>

<style scoped>
.loan-create-page { min-width: 0; }

.page-sub {
  color: var(--text-secondary);
  font-size: 14px;
  margin-top: var(--space-2);
}

.page-error {
  background: var(--danger-soft);
  border: 1px solid var(--danger);
  border-radius: var(--radius-md);
  color: var(--danger);
  margin-top: var(--space-3);
  padding: 10px 14px;
}
.page-error p { margin: 0; }

.loan-layout {
  align-items: start;
  display: grid;
  gap: 20px;
  grid-template-columns: minmax(0, 1fr) 280px;
}

.form-main {
  display: flex;
  flex-direction: column;
  gap: 20px;
  min-width: 0;
}

.form-card { padding: 20px; }

.section-head-row {
  align-items: flex-start;
  display: flex;
  gap: 12px;
  margin-bottom: 16px;
}
.section-head-row h2 { font-size: 15px; font-weight: 600; margin: 0; }
.section-hint {
  align-items: center;
  color: var(--text-muted);
  display: flex;
  flex-wrap: wrap;
  font-size: 13px;
  gap: 4px;
  margin: 2px 0 0;
}

.section-icon {
  align-items: center;
  background: var(--accent-soft);
  border-radius: 9px;
  color: var(--accent);
  display: flex;
  flex: 0 0 32px;
  height: 32px;
  justify-content: center;
  width: 32px;
}
.section-icon svg { height: 15px; width: 15px; }

/* Choice cards */
.choice-row {
  display: grid;
  gap: var(--space-2);
  grid-template-columns: 1fr 1fr;
  margin-bottom: var(--space-4);
}
.choice-row.sub { margin-bottom: var(--space-4); margin-top: 0; }

.choice-option {
  align-items: flex-start;
  background: var(--surface);
  border: 1px solid var(--border-strong);
  border-radius: var(--radius-md);
  cursor: pointer;
  display: flex;
  gap: var(--space-2);
  margin: 0;
  padding: 10px 14px;
  transition: background var(--transition-fast), border-color var(--transition-fast);
}
.choice-option:hover { border-color: var(--text-muted); }
.choice-option:has(input:checked) {
  background: var(--accent-soft);
  border-color: var(--accent);
}
.choice-option input { flex: 0 0 auto; min-height: auto; width: auto; }
.choice-option span { display: flex; flex-direction: column; }
.choice-option strong { color: var(--text-primary); font-size: 13px; }
.choice-option small { color: var(--text-secondary); font-size: 11px; margin-top: 2px; }

.grid.two {
  display: grid;
  gap: var(--space-3) var(--space-4);
  grid-template-columns: 1fr 1fr;
}

.amount-row { display: flex; gap: 8px; }
.amount-row input { flex: 1; }
.amount-row .btn-light { flex: 0 0 auto; }

.capital-warn {
  background: var(--warning-soft);
  border-radius: var(--radius-md);
  color: var(--warning);
  font-size: 13px;
  margin: 8px 0 0;
  padding: 8px 12px;
}

/* ---------- Panel ---------- */
.form-aside {
  position: sticky;
  top: 20px;
}

.panel-card { padding: 18px; }

.panel-title {
  align-items: center;
  display: flex;
  font-size: 15px;
  font-weight: 600;
  gap: 5px;
  margin: 0 0 14px;
}

.panel-available > span {
  color: var(--text-muted);
  display: block;
  font-size: 12px;
  font-weight: 500;
}
.panel-available strong {
  display: block;
  font-size: 26px;
  font-variant-numeric: tabular-nums;
  font-weight: 700;
  letter-spacing: -0.02em;
  margin-top: 3px;
}
.panel-available strong.is-green { color: var(--success); }
.panel-available strong.is-red { color: var(--danger); }

.panel-row {
  align-items: center;
  border-bottom: 1px solid var(--border);
  display: flex;
  font-size: 13px;
  justify-content: space-between;
  padding: 8px 0;
}
.panel-row span { color: var(--text-muted); font-size: 12px; }
.panel-row strong {
  color: var(--text-primary);
  font-variant-numeric: tabular-nums;
  font-weight: 600;
}
.panel-row.panel-total { border-bottom: 0; padding-bottom: 4px; }
.panel-row.panel-total strong { font-size: 17px; font-weight: 700; }
.panel-row.panel-total strong.money-loss { color: var(--danger); }
.panel-row.panel-total strong.money-profit { color: var(--success); }

.panel-meta {
  border-top: 1px solid var(--border);
  color: var(--text-muted);
  font-size: 11px;
  margin: 12px 0 0;
  padding-top: 12px;
}

.panel-actions {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-top: 14px;
}

/* ---------- Responsive ---------- */
@media (max-width: 1024px) {
  .loan-layout { grid-template-columns: 1fr; }

  /* Panel becomes a sticky bottom bar */
  .form-aside {
    bottom: 0;
    position: sticky;
    top: auto;
    z-index: 10;
  }

  .panel-card {
    border-radius: var(--radius-lg) var(--radius-lg) 0 0;
    box-shadow: var(--shadow-md);
    padding: 12px 16px;
  }

  .panel-title { display: none; }

  .panel-available { display: inline-flex; align-items: baseline; gap: 8px; }
  .panel-available > span { font-size: 11px; }
  .panel-available strong { font-size: 16px; margin: 0; }

  .panel-rows { display: inline-flex; gap: 14px; margin-left: 12px; }
  .panel-row { border-bottom: 0; display: inline-flex; padding: 0; }
  .panel-row strong { font-size: 13px; }
  .panel-row.panel-total strong { font-size: 14px; }

  .panel-meta { display: none; }

  .panel-actions { flex-direction: row; margin-top: 10px; }
  .panel-actions .btn,
  .panel-actions .btn-light { flex: 1; }
}

@media (max-width: 560px) {
  .choice-row,
  .grid.two { grid-template-columns: 1fr; }
}
</style>