<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useLoanStore } from '@/stores/loanStore'
import { useInvestorStore } from '@/stores/investorStore'
import { useCompanyCapitalStore } from '@/stores/companyCapitalStore'
import { money } from '@/utils/money'

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

const availableCapital = computed(() => money(capitalStore.current_balance))
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
  submitting.value = true
  errors.value = {}
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
  <div class="page-container loan-create-page">
    <div class="page-head">
      <div>
        <span class="section-kicker">Loans</span>
        <h1>Issue loan</h1>
        <p class="page-subtitle">Record company money issued to an investor or outsider.</p>
      </div>
      <RouterLink class="btn-light" to="/loans">Back to loans</RouterLink>
    </div>

    <div v-if="!capitalStore.loading && !capitalStore.initialized" class="page-error" role="alert">
      <p>Opening company capital must be configured before issuing a loan.</p>
      <RouterLink class="btn-light" to="/loans">Configure capital</RouterLink>
    </div>

    <form class="loan-create-layout" @submit.prevent="submit">
      <main class="card form-card">
        <div class="section-head">
          <div>
            <h2>Borrower and terms</h2>
            <p class="hint">Loans are interest-free and remain separate from investments.</p>
          </div>
        </div>

        <fieldset class="mode-fieldset">
          <legend>Borrower type</legend>
          <div class="choice-row">
            <label class="choice-option"
              ><input v-model="form.borrower_type" type="radio" value="investor" /><span
                ><strong>Investor</strong><small>Link to an existing investor profile</small></span
              ></label
            >
            <label class="choice-option"
              ><input v-model="form.borrower_type" type="radio" value="outsider" /><span
                ><strong>Outsider</strong><small>Use or create a standalone borrower</small></span
              ></label
            >
          </div>
          <span v-if="fieldError('borrower_type')" class="error">{{
            fieldError('borrower_type')
          }}</span>
        </fieldset>

        <section v-if="form.borrower_type === 'investor'" class="borrower-panel">
          <div class="field">
            <label for="investor-search">Search investors</label>
            <input
              id="investor-search"
              v-model="investorSearch"
              type="search"
              placeholder="Name, code, email, or phone"
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
              <option value="">
                {{ searchingInvestors ? 'Searching…' : 'Select an investor' }}
              </option>
              <option v-for="investor in investors" :key="investor.id" :value="investor.id">
                {{ investor.name }} · {{ investor.investor_code }}
              </option>
            </select>
            <span v-if="fieldError('investor_id')" class="error">{{
              fieldError('investor_id')
            }}</span>
          </div>
        </section>

        <section v-else class="borrower-panel">
          <fieldset class="mode-fieldset compact">
            <legend>Outsider record</legend>
            <div class="choice-row">
              <label class="choice-option"
                ><input v-model="outsiderMode" type="radio" value="existing" /><span
                  ><strong>Existing</strong><small>Select a saved borrower</small></span
                ></label
              >
              <label class="choice-option"
                ><input v-model="outsiderMode" type="radio" value="new" /><span
                  ><strong>New</strong><small>Create a borrower with this loan</small></span
                ></label
              >
            </div>
          </fieldset>

          <template v-if="outsiderMode === 'existing'">
            <div class="field">
              <label for="borrower-search">Search outsiders</label
              ><input
                id="borrower-search"
                v-model="borrowerSearch"
                type="search"
                placeholder="Name, code, email, or phone"
                @input="searchBorrowers"
              />
            </div>
            <div class="field">
              <label for="borrower-id">Outsider</label
              ><select
                id="borrower-id"
                v-model="form.loan_borrower_id"
                :aria-invalid="Boolean(fieldError('loan_borrower_id'))"
              >
                <option value="">
                  {{ borrowerLoading ? 'Searching…' : 'Select an outsider' }}
                </option>
                <option v-for="borrower in borrowers" :key="borrower.id" :value="borrower.id">
                  {{ borrower.name }} · {{ borrower.borrower_code }}
                </option></select
              ><span v-if="fieldError('loan_borrower_id')" class="error">{{
                fieldError('loan_borrower_id')
              }}</span>
            </div>
          </template>
          <div v-else class="grid">
            <div class="field">
              <label for="outsider-name">Name</label
              ><input
                id="outsider-name"
                v-model="form.outsider_name"
                autocomplete="name"
                :aria-invalid="Boolean(fieldError('outsider_name'))"
              /><span v-if="fieldError('outsider_name')" class="error">{{
                fieldError('outsider_name')
              }}</span>
            </div>
            <div class="field">
              <label for="outsider-email">Email</label
              ><input
                id="outsider-email"
                v-model="form.outsider_email"
                type="email"
                autocomplete="email"
                :aria-invalid="Boolean(fieldError('outsider_email'))"
              /><span v-if="fieldError('outsider_email')" class="error">{{
                fieldError('outsider_email')
              }}</span>
            </div>
            <div class="field">
              <label for="outsider-phone">Phone</label
              ><input
                id="outsider-phone"
                v-model="form.outsider_phone"
                type="tel"
                autocomplete="tel"
                :aria-invalid="Boolean(fieldError('outsider_phone'))"
              /><span v-if="fieldError('outsider_phone')" class="error">{{
                fieldError('outsider_phone')
              }}</span>
            </div>
            <div class="field">
              <label for="outsider-address">Address</label
              ><input
                id="outsider-address"
                v-model="form.outsider_address"
                autocomplete="street-address"
                :aria-invalid="Boolean(fieldError('outsider_address'))"
              /><span v-if="fieldError('outsider_address')" class="error">{{
                fieldError('outsider_address')
              }}</span>
            </div>
          </div>
        </section>

        <div class="grid terms-grid">
          <div class="field">
            <label for="loan-amount">Loan amount</label
            ><input
              id="loan-amount"
              v-model="form.amount"
              inputmode="decimal"
              :aria-invalid="Boolean(fieldError('amount'))"
            /><span v-if="fieldError('amount')" class="error">{{ fieldError('amount') }}</span>
          </div>
          <div class="field">
            <label for="loan-date">Loan date</label
            ><input
              id="loan-date"
              v-model="form.loan_date"
              type="date"
              :aria-invalid="Boolean(fieldError('loan_date'))"
            /><span v-if="fieldError('loan_date')" class="error">{{
              fieldError('loan_date')
            }}</span>
          </div>
          <div class="field">
            <label for="due-date">Due date</label
            ><input
              id="due-date"
              v-model="form.due_date"
              type="date"
              :aria-invalid="Boolean(fieldError('due_date'))"
            /><span v-if="fieldError('due_date')" class="error">{{ fieldError('due_date') }}</span>
          </div>
        </div>
        <div class="field">
          <label for="loan-notes">Notes</label
          ><textarea id="loan-notes" v-model="form.notes" rows="4"></textarea
          ><span v-if="fieldError('notes')" class="error">{{ fieldError('notes') }}</span>
        </div>
        <div v-if="loanStore.error" class="page-error" role="alert">
          <p>{{ loanStore.error }}</p>
        </div>
        <div class="actions">
          <button type="submit" :disabled="submitting || !capitalStore.initialized">
            {{ submitting ? 'Issuing loan…' : 'Issue loan' }}</button
          ><RouterLink class="btn-light" to="/loans">Cancel</RouterLink>
        </div>
      </main>

      <aside class="card capital-context">
        <span class="section-kicker">Available now</span>
        <strong>{{ availableCapital }}</strong>
        <p>
          Loan issuance reduces this balance. Repayments restore only the amount actually received.
        </p>
        <dl>
          <div>
            <dt>Interest</dt>
            <dd>None</dd>
          </div>
          <div>
            <dt>Return</dt>
            <dd>Principal only</dd>
          </div>
          <div>
            <dt>Capital source</dt>
            <dd>Company ledger</dd>
          </div>
        </dl>
      </aside>
    </form>
  </div>
</template>

<style scoped>
.page-subtitle {
  color: var(--text-secondary);
  margin-top: var(--space-2);
}
.loan-create-layout {
  align-items: start;
  display: grid;
  gap: var(--space-5);
  grid-template-columns: minmax(0, 1fr) 280px;
}
.form-card {
  margin: 0;
}
.form-card .hint {
  display: block;
  margin: var(--space-1) 0 0;
}
.mode-fieldset {
  border: 0;
  margin: 0 0 var(--space-5);
  padding: 0;
}
.mode-fieldset legend {
  color: var(--text-secondary);
  font-size: var(--text-sm);
  font-weight: var(--font-weight-medium);
  margin-bottom: var(--space-2);
}
.mode-fieldset.compact {
  margin-bottom: var(--space-4);
}
.choice-row {
  display: grid;
  gap: var(--space-3);
  grid-template-columns: 1fr 1fr;
}
.choice-option {
  align-items: flex-start;
  background: var(--surface);
  border: 1px solid var(--border-strong);
  border-radius: var(--radius-md);
  cursor: pointer;
  display: flex;
  gap: var(--space-3);
  margin: 0;
  padding: var(--space-3);
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
.choice-option small {
  margin: var(--space-1) 0 0;
}
.borrower-panel {
  background: var(--surface-muted);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  margin-bottom: var(--space-5);
  padding: var(--space-4);
}
.borrower-panel .field:last-child {
  margin-bottom: 0;
}
.terms-grid {
  grid-template-columns: repeat(3, 1fr);
}
.capital-context {
  position: sticky;
  top: var(--space-5);
}
.capital-context > strong {
  color: var(--accent);
  display: block;
  font-size: var(--text-2xl);
  font-variant-numeric: tabular-nums;
  margin: var(--space-1) 0 var(--space-3);
}
.capital-context p {
  color: var(--text-secondary);
  font-size: var(--text-sm);
}
.capital-context dl {
  border-top: 1px solid var(--border);
  margin-top: var(--space-4);
  padding-top: var(--space-2);
}
.capital-context dl div {
  display: flex;
  font-size: var(--text-sm);
  justify-content: space-between;
  padding: var(--space-2) 0;
}
.capital-context dt {
  color: var(--text-muted);
}
.capital-context dd {
  font-weight: var(--font-weight-medium);
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
@media (max-width: 900px) {
  .loan-create-layout {
    grid-template-columns: 1fr;
  }
  .capital-context {
    grid-row: 1;
    position: static;
  }
  .terms-grid {
    grid-template-columns: 1fr 1fr;
  }
}
@media (max-width: 560px) {
  .choice-row,
  .terms-grid {
    grid-template-columns: 1fr;
  }
  .actions > * {
    flex: 1;
  }
}
</style>
