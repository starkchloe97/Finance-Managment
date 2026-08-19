<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useInvestorStore } from '@/stores/investorStore'
import { useInvestmentStore } from '@/stores/investmentStore'

const router = useRouter()

const investorStore = useInvestorStore()
const investmentStore = useInvestmentStore()

const investors = computed(() => investorStore.investors)

const submitting = ref(false)
const validationErrors = ref({})

const form = reactive({
  investor_id: '',
  investment_date: '',
  amount: '',
  period_months: '',
  return_policy_days: '',
  min_return_percent: '',
  max_return_percent: '',
  deduction_amount: '0',
  notes: '',
})

onMounted(async () => {
  await investorStore.fetchInvestors()
})

const submit = async () => {
  submitting.value = true
  validationErrors.value = {}

  try {
    const data = {
      investor_id: Number(form.investor_id),
      investment_date: form.investment_date,
      amount: Number(form.amount),
      period_months: form.period_months ? Number(form.period_months) : null,
      return_policy_days: form.return_policy_days ? Number(form.return_policy_days) : null,
      min_return_percent: form.min_return_percent !== '' ? Number(form.min_return_percent) : null,
      max_return_percent: form.max_return_percent !== '' ? Number(form.max_return_percent) : null,
      deduction_amount: form.deduction_amount !== '' ? Number(form.deduction_amount) : 0,
      notes: form.notes || null,
    }

    const investment = await investmentStore.createInvestment(data)

    router.push({
      name: 'investments.show',
      params: {
        id: investment.id,
      },
    })
  } catch (error) {
    if (error.response?.status === 422) {
      validationErrors.value = error.response.data.errors || {}
    }
  } finally {
    submitting.value = false
  }
}

const cancel = () => {
  router.push({
    name: 'investments.index',
  })
}

const fieldError = (field) => {
  return validationErrors.value[field]?.[0] || ''
}
</script>
<style scoped>
.form-container {
  max-width: 800px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-bottom: 20px;
}

.form-group label {
  font-weight: 600;
}

.form-group input,
.form-group select,
.form-group textarea {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font: inherit;
  box-sizing: border-box;
}

.form-row {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 20px;
}

.input-with-suffix {
  display: flex;
  align-items: stretch;
}

.input-with-suffix input {
  border-radius: 6px 0 0 6px;
}

.input-with-suffix span {
  display: flex;
  align-items: center;
  padding: 0 12px;
  border: 1px solid #d1d5db;
  border-left: 0;
  border-radius: 0 6px 6px 0;
}

.field-error {
  color: #dc2626;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 30px;
}

@media (max-width: 768px) {
  .form-row {
    grid-template-columns: 1fr;
  }
}
</style>
<template>
  <div class="page-container">
    <div class="page-header">
      <div>
        <h1>Create Investment</h1>

        <p>Add a new investment for an investor.</p>
      </div>
    </div>

    <form class="form-container" @submit.prevent="submit">
      <!-- Investor -->

      <div class="form-group">
        <label for="investor_id"> Investor </label>

        <select id="investor_id" v-model="form.investor_id" required>
          <option value="">Select Investor</option>

          <option v-for="investor in investors" :key="investor.id" :value="investor.id">
            {{ investor.name }}
            —
            {{ investor.investor_code }}
          </option>
        </select>

        <small v-if="fieldError('investor_id')" class="field-error">
          {{ fieldError('investor_id') }}
        </small>
      </div>

      <!-- Investment Date -->

      <div class="form-group">
        <label for="investment_date"> Investment Date </label>

        <input id="investment_date" v-model="form.investment_date" type="date" required />

        <small v-if="fieldError('investment_date')" class="field-error">
          {{ fieldError('investment_date') }}
        </small>
      </div>

      <!-- Amount -->

      <div class="form-group">
        <label for="amount"> Investment Amount </label>

        <input
          id="amount"
          v-model="form.amount"
          type="number"
          min="0.01"
          step="0.01"
          placeholder="500000"
          required
        />

        <small v-if="fieldError('amount')" class="field-error">
          {{ fieldError('amount') }}
        </small>
      </div>

      <!-- Period -->

      <div class="form-group">
        <label for="period_months"> Investment Period </label>

        <div class="input-with-suffix">
          <input
            id="period_months"
            v-model="form.period_months"
            type="number"
            min="1"
            required
            placeholder="6"
          />

          <span>months</span>
        </div>

        <small v-if="fieldError('period_months')" class="field-error">
          {{ fieldError('period_months') }}
        </small>
      </div>

      <!-- Return Policy -->

      <div class="form-group">
        <label for="return_policy_days"> Return Policy </label>

        <div class="input-with-suffix">
          <input
            id="return_policy_days"
            v-model="form.return_policy_days"
            type="number"
            min="1"
            placeholder="45"
          />

          <span>days</span>
        </div>

        <small v-if="fieldError('return_policy_days')" class="field-error">
          {{ fieldError('return_policy_days') }}
        </small>
      </div>

      <!-- Return Range -->

      <div class="form-row">
        <div class="form-group">
          <label for="min_return_percent"> Minimum Return </label>

          <div class="input-with-suffix">
            <input
              id="min_return_percent"
              v-model="form.min_return_percent"
              type="number"
              min="0"
              max="100"
              step="0.01"
              placeholder="2"
            />

            <span>%</span>
          </div>

          <small v-if="fieldError('min_return_percent')" class="field-error">
            {{ fieldError('min_return_percent') }}
          </small>
        </div>

        <div class="form-group">
          <label for="max_return_percent"> Maximum Return </label>

          <div class="input-with-suffix">
            <input
              id="max_return_percent"
              v-model="form.max_return_percent"
              type="number"
              min="0"
              max="100"
              step="0.01"
              placeholder="3"
            />

            <span>%</span>
          </div>

          <small v-if="fieldError('max_return_percent')" class="field-error">
            {{ fieldError('max_return_percent') }}
          </small>
        </div>
      </div>

      <!-- Deduction -->

      <div class="form-group">
        <label for="deduction_amount"> Deduction Amount </label>

        <input
          id="deduction_amount"
          v-model="form.deduction_amount"
          type="number"
          min="0"
          step="0.01"
          placeholder="0"
        />

        <small v-if="fieldError('deduction_amount')" class="field-error">
          {{ fieldError('deduction_amount') }}
        </small>
      </div>

      <!-- Notes -->

      <div class="form-group">
        <label for="notes"> Notes </label>

        <textarea
          id="notes"
          v-model="form.notes"
          rows="4"
          placeholder="Optional notes..."
        ></textarea>

        <small v-if="fieldError('notes')" class="field-error">
          {{ fieldError('notes') }}
        </small>
      </div>

      <!-- Form Actions -->

      <div class="form-actions">
        <button type="button" @click="cancel" :disabled="submitting">Cancel</button>

        <button type="submit" :disabled="submitting">
          {{ submitting ? 'Creating...' : 'Create Investment' }}
        </button>
      </div>
    </form>
  </div>
</template>
