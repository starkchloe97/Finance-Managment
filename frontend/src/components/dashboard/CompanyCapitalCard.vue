<script setup>
import { computed, onMounted, ref } from 'vue'
import companyCapitalService from '@/services/companyCapitalService'
import { money } from '@/utils/money'

const capital = ref(null)
const profit = ref(null)
const loading = ref(true)
const submitting = ref(false)
const error = ref('')
const showForm = ref(false)
const amount = ref('')
const notes = ref('')

const availableProfit = computed(() => Number(profit.value?.available_profit || 0))
const availableCapital = computed(() => Number(capital.value?.available_to_lend || 0))
const transferAmount = computed(() => Number(amount.value || 0))
const canSubmit = computed(
  () => transferAmount.value > 0 && transferAmount.value <= availableProfit.value && !submitting.value,
)

const load = async () => {
  loading.value = true
  error.value = ''
  try {
    const [capitalResponse, profitResponse] = await Promise.all([
      companyCapitalService.getCapital(),
      companyCapitalService.getProfitSnapshot(),
    ])
    capital.value = capitalResponse.data
    profit.value = profitResponse.data
  } catch (e) {
    error.value = e.response?.data?.message || 'Could not load company capital.'
  } finally {
    loading.value = false
  }
}

const openForm = () => {
  amount.value = availableProfit.value.toFixed(2)
  notes.value = ''
  error.value = ''
  showForm.value = true
}

const closeForm = () => {
  if (submitting.value) return
  showForm.value = false
}

const submit = async () => {
  if (!canSubmit.value) return

  submitting.value = true
  error.value = ''
  try {
    const response = await companyCapitalService.addProfitToCapital({
      amount: transferAmount.value,
      transaction_date: new Date().toISOString().slice(0, 10),
      notes: notes.value || null,
    })

    capital.value = response.data
    const profitResponse = await companyCapitalService.getProfitSnapshot()
    profit.value = profitResponse.data
    showForm.value = false
  } catch (e) {
    error.value = e.response?.data?.message || e.response?.data?.errors?.amount?.[0] || 'Could not add profit to capital.'
  } finally {
    submitting.value = false
  }
}

onMounted(load)
</script>

<template>
  <section class="capital-card">
    <div class="capital-head">
      <div>
        <span class="capital-kicker">Company capital</span>
        <h2>Available company capital</h2>
      </div>
      <span class="capital-badge">Available to lend</span>
    </div>

    <div v-if="loading" class="capital-loading">Loading capital…</div>

    <template v-else>
      <strong class="capital-value">{{ money(availableCapital) }}</strong>

      <div class="capital-meta">
        <span>Undistributed profit</span>
        <strong>{{ money(availableProfit) }}</strong>
      </div>

      <p class="capital-help">
        Move realized company profit into available capital when you want it to become usable for loans.
      </p>

      <button
        v-if="!showForm"
        type="button"
        class="capital-action"
        :disabled="availableProfit <= 0"
        @click="openForm"
      >
        Add profit to capital
      </button>

      <form v-else class="capital-form" @submit.prevent="submit">
        <div class="capital-form-row">
          <label>
            Amount
            <input v-model="amount" type="number" min="0.01" :max="availableProfit" step="0.01" required />
          </label>
          <button type="button" class="fill-profit" @click="amount = availableProfit.toFixed(2)">
            Use all
          </button>
        </div>

        <label>
          Note <span>(optional)</span>
          <input v-model="notes" type="text" maxlength="255" placeholder="Profit transferred to company capital" />
        </label>

        <div v-if="error" class="capital-error" role="alert">{{ error }}</div>

        <div class="capital-form-actions">
          <button type="button" class="secondary-action" :disabled="submitting" @click="closeForm">Cancel</button>
          <button type="submit" class="capital-action" :disabled="!canSubmit">
            {{ submitting ? 'Adding…' : 'Add to capital' }}
          </button>
        </div>
      </form>

      <div v-if="!showForm && error" class="capital-error" role="alert">{{ error }}</div>
    </template>
  </section>
</template>

<style scoped>
.capital-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-xs);
  margin-bottom: var(--space-4);
  padding: 18px;
}

.capital-head,
.capital-meta,
.capital-form-actions,
.capital-form-row {
  align-items: center;
  display: flex;
  justify-content: space-between;
}

.capital-kicker {
  color: var(--text-muted);
  display: block;
  font-size: var(--text-xs);
  font-weight: var(--font-weight-semibold);
  letter-spacing: .06em;
  margin-bottom: 3px;
  text-transform: uppercase;
}

.capital-head h2 {
  color: var(--text-primary);
  font-size: 16px;
  margin: 0;
}

.capital-badge {
  background: var(--accent-soft);
  border-radius: 999px;
  color: var(--accent);
  font-size: 11px;
  font-weight: 600;
  padding: 4px 9px;
}

.capital-value {
  display: block;
  font-size: 30px;
  letter-spacing: -.03em;
  margin: 16px 0 12px;
}

.capital-meta {
  background: var(--surface-2);
  border-radius: var(--radius-md);
  color: var(--text-secondary);
  font-size: 13px;
  padding: 10px 12px;
}

.capital-meta strong { color: var(--text-primary); }
.capital-help { color: var(--text-muted); font-size: 12px; line-height: 1.5; margin: 11px 0; }
.capital-loading { color: var(--text-muted); padding: 20px 0; }

.capital-action,
.secondary-action,
.fill-profit {
  border: 1px solid var(--border-strong);
  border-radius: var(--radius-md);
  cursor: pointer;
  font: inherit;
  font-weight: 600;
  padding: 9px 13px;
}

.capital-action { background: var(--accent); color: var(--surface); border-color: var(--accent); width: 100%; }
.capital-action:disabled { cursor: not-allowed; opacity: .5; }
.secondary-action { background: var(--surface); color: var(--text-secondary); }
.fill-profit { background: var(--surface); color: var(--accent); font-size: 12px; padding: 7px 10px; }

.capital-form { display: grid; gap: 11px; margin-top: 14px; }
.capital-form label { color: var(--text-secondary); display: grid; font-size: 12px; font-weight: 600; gap: 5px; }
.capital-form label span { color: var(--text-muted); font-weight: 400; }
.capital-form input { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-md); color: var(--text-primary); font: inherit; padding: 9px 10px; width: 100%; }
.capital-form input:focus { border-color: var(--accent); outline: 2px solid var(--focus-ring); }
.capital-form-row { align-items: end; gap: 8px; }
.capital-form-row label { flex: 1; }
.capital-form-actions { gap: 8px; margin-top: 2px; }
.capital-form-actions .capital-action { width: auto; }
.capital-error { background: var(--danger-soft); border-radius: var(--radius-md); color: var(--danger); font-size: 12px; padding: 8px 10px; }
</style>
