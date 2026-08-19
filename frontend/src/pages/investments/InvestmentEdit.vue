<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useInvestmentStore } from '@/stores/investmentStore'

const route = useRoute()
const router = useRouter()

const investmentStore = useInvestmentStore()

const investment = computed(() => investmentStore.investment)

const loading = computed(() => investmentStore.loading)

const submitting = ref(false)
const validationErrors = ref({})

const form = reactive({
  investment_date: '',
  amount: '',
  period_months: '',
  return_policy_days: '',
  min_return_percent: '',
  max_return_percent: '',
  deduction_amount: '',
  notes: '',
})

const fieldError = (field) => {
  return validationErrors.value[field]?.[0] || ''
}

onMounted(async () => {
  await investmentStore.fetchInvestment(route.params.id)

  if (!investment.value) {
    return
  }

  form.investment_date = investment.value.investment_date

  form.amount = investment.value.amount

  form.period_months = investment.value.period_months ?? ''

  form.return_policy_days = investment.value.return_policy_days ?? ''

  form.min_return_percent = investment.value.min_return_percent ?? ''

  form.max_return_percent = investment.value.max_return_percent ?? ''

  form.deduction_amount = investment.value.deduction_amount ?? 0

  form.notes = investment.value.notes ?? ''
})

const submit = async () => {
  submitting.value = true
  validationErrors.value = {}

  try {
    const data = {
      investment_date: form.investment_date,
      amount: Number(form.amount),

      period_months: form.period_months !== '' ? Number(form.period_months) : null,

      return_policy_days: form.return_policy_days !== '' ? Number(form.return_policy_days) : null,

      min_return_percent: form.min_return_percent !== '' ? Number(form.min_return_percent) : null,

      max_return_percent: form.max_return_percent !== '' ? Number(form.max_return_percent) : null,

      deduction_amount: form.deduction_amount !== '' ? Number(form.deduction_amount) : 0,

      notes: form.notes || null,
    }

    await investmentStore.updateInvestment(route.params.id, data)

    router.push({
      name: 'investments.show',
      params: {
        id: route.params.id,
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
    name: 'investments.show',
    params: {
      id: route.params.id,
    },
  })
}
</script>

<template>
  <div class="page-container">
    <div v-if="loading" class="loading-state">Loading investment...</div>

    <template v-else-if="investment">
      <div class="page-header">
        <div>
          <h1>
            Edit
            {{ investment.investment_code }}
          </h1>

          <p>Update investment information.</p>
        </div>
      </div>

      <form class="form-container" @submit.prevent="submit">
        <div class="form-group">
          <label> Investor </label>

          <input :value="investment.investor?.name" type="text" disabled />
        </div>

        <div class="form-group">
          <label for="investment_date"> Investment Date </label>

          <input id="investment_date" v-model="form.investment_date" type="date" required />

          <small v-if="fieldError('investment_date')" class="field-error">
            {{ fieldError('investment_date') }}
          </small>
        </div>

        <div class="form-group">
          <label for="amount"> Investment Amount </label>

          <input id="amount" v-model="form.amount" type="number" min="0.01" step="0.01" required />

          <small v-if="fieldError('amount')" class="field-error">
            {{ fieldError('amount') }}
          </small>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="period_months"> Period </label>

            <input id="period_months" v-model="form.period_months" type="number" min="1" />
          </div>

          <div class="form-group">
            <label for="return_policy_days"> Return Policy </label>

            <input
              id="return_policy_days"
              v-model="form.return_policy_days"
              type="number"
              min="1"
            />
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="min_return_percent"> Minimum Return % </label>

            <input
              id="min_return_percent"
              v-model="form.min_return_percent"
              type="number"
              min="0"
              max="100"
              step="0.01"
            />
          </div>

          <div class="form-group">
            <label for="max_return_percent"> Maximum Return % </label>

            <input
              id="max_return_percent"
              v-model="form.max_return_percent"
              type="number"
              min="0"
              max="100"
              step="0.01"
            />
          </div>
        </div>

        <div class="form-group">
          <label for="deduction_amount"> Deduction Amount </label>

          <input
            id="deduction_amount"
            v-model="form.deduction_amount"
            type="number"
            min="0"
            step="0.01"
          />
        </div>

        <div class="form-group">
          <label for="notes"> Notes </label>

          <textarea id="notes" v-model="form.notes" rows="4"></textarea>
        </div>

        <div class="form-actions">
          <button type="button" @click="cancel" :disabled="submitting">Cancel</button>

          <button type="submit" :disabled="submitting">
            {{ submitting ? 'Saving...' : 'Save Changes' }}
          </button>
        </div>
      </form>
    </template>
  </div>
</template>
