<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useBankAccountStore } from '@/stores/bankAccountStore'
import StatePanel from '@/components/ui/StatePanel.vue'
import { money } from '@/utils/money'

const store = useBankAccountStore()
const selectedId = ref('')
const saving = ref(false)
const formError = ref('')
const form = reactive({
  name: '',
  bank_name: '',
  account_number: '',
  branch_name: '',
  opening_balance: 0,
})
const selectedAccount = computed(
  () => store.accounts.find((account) => String(account.id) === String(selectedId.value)) || null,
)

const load = async () => {
  try {
    await store.fetchAccounts()
    if (!selectedId.value && store.accounts.length) selectedId.value = store.accounts[0].id
    else if (selectedId.value) await store.fetchTransactions(selectedId.value)
  } catch (error) {
    store.error = error.response?.data?.message || 'Could not load bank account information.'
  }
}

watch(selectedId, async (id) => {
  if (!id) {
    store.transactions = []
    return
  }
  await store.fetchTransactions(id).catch(() => {})
})

const save = async () => {
  saving.value = true
  formError.value = ''
  try {
    const account = await store.addAccount({
      ...form,
      opening_balance: Number(form.opening_balance),
    })
    selectedId.value = account.id
    Object.assign(form, {
      name: '',
      bank_name: '',
      account_number: '',
      branch_name: '',
      opening_balance: 0,
    })
  } catch (error) {
    formError.value =
      Object.values(error.response?.data?.errors || {})[0]?.[0] ||
      error.response?.data?.message ||
      'Could not create the bank account.'
  } finally {
    saving.value = false
  }
}

const loadMore = async () => {
  try {
    await store.fetchMoreTransactions(selectedAccount.value.id)
  } catch {
    // The store exposes the error through the page state.
  }
}

onMounted(load)
</script>

<template>
  <div class="bank-accounts-page">
    <header>
      <span class="section-kicker">Finance / Capital</span>
      <h1>Bank accounts</h1>
      <p class="muted">Track opening balances, invoice receipts, and payments from each account.</p>
    </header>

    <form class="card account-form" @submit.prevent="save">
      <h2>Add bank account</h2>
      <div v-if="formError" class="form-error" role="alert">{{ formError }}</div>
      <div class="form-grid">
        <label>Account name<input v-model="form.name" maxlength="255" required /></label>
        <label>Bank name<input v-model="form.bank_name" maxlength="255" /></label>
        <label>Account number<input v-model="form.account_number" maxlength="100" /></label>
        <label>Branch<input v-model="form.branch_name" maxlength="255" /></label>
        <label
          >Opening balance
          <input v-model="form.opening_balance" type="number" min="0" step="0.01" required
        /></label>
      </div>
      <button class="btn" type="submit" :disabled="saving">
        {{ saving ? 'Saving…' : 'Add account' }}
      </button>
    </form>

    <StatePanel
      :loading="store.loading && !store.accounts.length"
      :error="store.error"
      :empty="!store.loading && !store.error && !store.accounts.length"
      empty-title="No bank accounts yet. Add one above to start recording balances."
    >
      <template #error-action><button class="btn" @click="load">Try again</button></template>
      <section v-if="store.accounts.length" class="accounts-layout">
        <div class="account-list">
          <button
            v-for="account in store.accounts"
            :key="account.id"
            class="card account-card"
            :class="{ selected: String(account.id) === String(selectedId) }"
            type="button"
            @click="selectedId = account.id"
          >
            <span class="account-title">{{ account.name }}</span>
            <span>{{ account.bank_name || 'Bank not specified' }}</span>
            <strong>{{ money(account.current_balance) }}</strong>
            <span v-if="account.account_number">Account {{ account.account_number }}</span>
          </button>
        </div>

        <section v-if="selectedAccount" class="card ledger">
          <header class="ledger-heading">
            <div>
              <h2>{{ selectedAccount.name }}</h2>
              <span class="muted">Current balance</span>
            </div>
            <strong>{{ money(selectedAccount.current_balance) }}</strong>
          </header>
          <p v-if="!store.transactions.length" class="muted">No bank transactions recorded.</p>
          <div
            v-for="transaction in store.transactions"
            :key="transaction.id"
            class="transaction-row"
          >
            <div>
              <strong>{{ transaction.type.replaceAll('_', ' ') }}</strong>
              <span
                >{{ transaction.transaction_date
                }}<span v-if="transaction.description"> · {{ transaction.description }}</span></span
              >
            </div>
            <div>
              <strong :class="transaction.type === 'payment' ? 'outflow' : 'inflow'">
                {{ transaction.type === 'payment' ? '−' : '+' }}{{ money(transaction.amount) }}
              </strong>
              <span>Balance {{ money(transaction.balance_after) }}</span>
            </div>
          </div>
          <button
            v-if="
              store.transactionPagination?.current_page < store.transactionPagination?.last_page
            "
            class="btn-light"
            type="button"
            @click="loadMore"
          >
            Load older transactions
          </button>
        </section>
      </section>
    </StatePanel>
  </div>
</template>

<style scoped>
.bank-accounts-page,
.account-form,
.ledger {
  display: grid;
  gap: var(--space-4);
}
.section-kicker {
  color: var(--text-muted);
  display: block;
  font-size: var(--text-xs);
  text-transform: uppercase;
}
.account-form h2,
.ledger h2 {
  margin: 0;
}
.form-grid {
  display: grid;
  gap: var(--space-3);
  grid-template-columns: repeat(2, minmax(0, 1fr));
}
label {
  display: grid;
  gap: var(--space-1);
  color: var(--text-secondary);
  font-size: var(--text-sm);
}
input {
  border: 1px solid var(--border-strong);
  border-radius: var(--radius-sm);
  padding: 9px 10px;
  width: 100%;
}
.form-error,
.outflow {
  color: var(--danger);
}
.accounts-layout {
  display: grid;
  gap: var(--space-4);
  grid-template-columns: minmax(220px, 1fr) 2fr;
}
.account-list {
  align-content: start;
  display: grid;
  gap: var(--space-2);
}
.account-card {
  color: inherit;
  cursor: pointer;
  display: grid;
  gap: var(--space-1);
  text-align: left;
}
.account-card.selected {
  border-color: var(--accent);
}
.account-title,
.transaction-row span,
.transaction-row div:last-child span {
  color: var(--text-muted);
  font-size: var(--text-sm);
}
.account-card strong,
.ledger-heading > strong {
  font-size: var(--text-lg);
}
.ledger-heading,
.transaction-row {
  align-items: center;
  display: flex;
  justify-content: space-between;
  gap: var(--space-3);
}
.transaction-row {
  border-bottom: 1px solid var(--border);
  padding: 12px 0;
}
.transaction-row > div {
  display: grid;
  gap: var(--space-1);
}
.transaction-row > div:last-child {
  text-align: right;
}
.inflow {
  color: var(--success);
}
@media (max-width: 700px) {
  .accounts-layout,
  .form-grid {
    grid-template-columns: 1fr;
  }
}
</style>
