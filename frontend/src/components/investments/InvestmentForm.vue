<script setup>
import { computed, reactive, watch } from 'vue'
import { money } from '@/utils/money'

const props = defineProps({
  mode: {
    type: String,
    default: 'create',
  },
  initialValues: {
    type: Object,
    default: () => ({}),
  },
  investors: {
    type: Array,
    default: () => [],
  },
  errors: {
    type: Object,
    default: () => ({}),
  },
  submitting: {
    type: Boolean,
    default: false,
  },
  error: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['submit', 'cancel'])

const form = reactive({
  investor_id: props.initialValues.investor_id ?? '',
  investment_date: props.initialValues.investment_date ?? '',
  amount: props.initialValues.amount ?? '',
  investment_category: props.initialValues.investment_category ?? 'normal',
  return_type: props.initialValues.return_type ?? 'percentage',
  return_percentage: props.initialValues.return_percentage ?? '',
  fixed_return_amount: props.initialValues.fixed_return_amount ?? '',
  period_months: props.initialValues.period_months ?? '',
  return_policy_days: props.initialValues.return_policy_days ?? '',
  deduction_amount: props.initialValues.deduction_amount ?? '0',
  notes: props.initialValues.notes ?? '',
})

const calculatedReturn = computed(() => {
  if (form.return_type !== 'percentage') return 0

  return Number(form.amount || 0) * (Number(form.return_percentage || 0) / 100)
})

watch(
  () => form.investment_category,
  (category) => {
    if (category === 'pool') {
      form.return_type = 'percentage'
      form.fixed_return_amount = ''
    }
  },
  { immediate: true },
)

const fieldError = (field) => props.errors[field]?.[0] || ''

const fieldAttrs = (field) => ({
  'aria-invalid': fieldError(field) ? 'true' : undefined,
  'aria-describedby': fieldError(field) ? `${field}-error` : undefined,
})

const submit = () => {
  const data = {
    investment_date: form.investment_date,
    amount: Number(form.amount),
    investment_category: form.investment_category,
    return_type: form.return_type,
    return_percentage:
      form.return_type === 'percentage' && form.return_percentage !== ''
        ? Number(form.return_percentage)
        : null,
    fixed_return_amount:
      form.return_type === 'fixed' && form.fixed_return_amount !== ''
        ? Number(form.fixed_return_amount)
        : null,
    period_months: form.period_months !== '' ? Number(form.period_months) : null,
    return_policy_days: form.return_policy_days !== '' ? Number(form.return_policy_days) : null,
    deduction_amount: form.deduction_amount !== '' ? Number(form.deduction_amount) : 0,
    notes: form.notes || null,
  }

  if (props.mode === 'create') {
    data.investor_id = Number(form.investor_id)
  }

  emit('submit', data)
}
</script>

<template>
  <form class="card investment-form" @submit.prevent="submit">
    <div v-if="error" class="notice" role="alert">{{ error }}</div>

    <div v-if="mode === 'create'" class="field">
      <label for="investor_id">Investor</label>
      <select
        id="investor_id"
        v-model="form.investor_id"
        v-bind="fieldAttrs('investor_id')"
        required
      >
        <option value="">Select investor</option>
        <option v-for="investor in investors" :key="investor.id" :value="investor.id">
          {{ investor.name }} — {{ investor.investor_code }}
        </option>
      </select>
      <small v-if="fieldError('investor_id')" id="investor_id-error" class="error">
        {{ fieldError('investor_id') }}
      </small>
    </div>

    <div v-else class="field">
      <label for="investor_display">Investor</label>
      <input id="investor_display" :value="initialValues.investor?.name" type="text" disabled />
    </div>

    <div class="grid">
      <div class="field">
        <label for="investment_date">Investment date</label>
        <input
          id="investment_date"
          v-model="form.investment_date"
          type="date"
          v-bind="fieldAttrs('investment_date')"
          required
        />
        <small v-if="fieldError('investment_date')" id="investment_date-error" class="error">
          {{ fieldError('investment_date') }}
        </small>
      </div>

      <div class="field">
        <label for="amount">Investment amount</label>
        <input
          id="amount"
          v-model="form.amount"
          type="number"
          min="0.01"
          step="0.01"
          inputmode="decimal"
          v-bind="fieldAttrs('amount')"
          required
        />
        <small v-if="fieldError('amount')" id="amount-error" class="error">
          {{ fieldError('amount') }}
        </small>
      </div>
    </div>

    <fieldset class="field">
      <legend>Investment category</legend>
      <div class="choice-group">
        <label class="choice-option">
          <input v-model="form.investment_category" type="radio" value="pool" />
          <span>Pool</span>
        </label>
        <label class="choice-option">
          <input v-model="form.investment_category" type="radio" value="normal" />
          <span>Normal</span>
        </label>
      </div>
      <small v-if="fieldError('investment_category')" id="investment_category-error" class="error">
        {{ fieldError('investment_category') }}
      </small>
    </fieldset>

    <div class="grid">
      <div class="field">
        <label for="period_months">Investment period</label>
        <div class="input-with-suffix">
          <input
            id="period_months"
            v-model="form.period_months"
            type="number"
            min="1"
            v-bind="fieldAttrs('period_months')"
            required
          />
          <span>months</span>
        </div>
        <small v-if="fieldError('period_months')" id="period_months-error" class="error">
          {{ fieldError('period_months') }}
        </small>
      </div>

      <div class="field">
        <label for="return_policy_days">Return policy</label>
        <div class="input-with-suffix">
          <input
            id="return_policy_days"
            v-model="form.return_policy_days"
            type="number"
            min="1"
            v-bind="fieldAttrs('return_policy_days')"
          />
          <span>days</span>
        </div>
        <small v-if="fieldError('return_policy_days')" id="return_policy_days-error" class="error">
          {{ fieldError('return_policy_days') }}
        </small>
      </div>
    </div>

    <fieldset class="field">
      <legend>Return type</legend>
      <div class="choice-group">
        <label v-if="form.investment_category === 'normal'" class="choice-option">
          <input v-model="form.return_type" type="radio" value="fixed" />
          <span>Fixed</span>
        </label>
        <label class="choice-option">
          <input v-model="form.return_type" type="radio" value="percentage" />
          <span>Percentage</span>
        </label>
      </div>
      <small v-if="fieldError('return_type')" id="return_type-error" class="error">
        {{ fieldError('return_type') }}
      </small>
    </fieldset>

    <div v-if="form.investment_category === 'normal' && form.return_type === 'fixed'" class="field">
      <label for="fixed_return_amount">Fixed return amount</label>
      <input
        id="fixed_return_amount"
        v-model="form.fixed_return_amount"
        type="number"
        min="0"
        step="0.01"
        inputmode="decimal"
        v-bind="fieldAttrs('fixed_return_amount')"
        required
      />
      <small v-if="fieldError('fixed_return_amount')" id="fixed_return_amount-error" class="error">
        {{ fieldError('fixed_return_amount') }}
      </small>
    </div>

    <div v-else class="grid">
      <div class="field">
        <label for="return_percentage">Return percentage</label>
        <div class="input-with-suffix">
          <input
            id="return_percentage"
            v-model="form.return_percentage"
            type="number"
            min="0"
            max="100"
            step="0.01"
            inputmode="decimal"
            v-bind="fieldAttrs('return_percentage')"
            required
          />
          <span>%</span>
        </div>
        <small v-if="fieldError('return_percentage')" id="return_percentage-error" class="error">
          {{ fieldError('return_percentage') }}
        </small>
      </div>

      <div class="field">
        <label for="calculated_return_amount">Maximum return</label>
        <input
          id="calculated_return_amount"
          :value="money(calculatedReturn)"
          type="text"
          readonly
          aria-readonly="true"
          class="derived-field"
        />
        <small class="hint">Calculated from the investment amount and return percentage.</small>
      </div>
    </div>

    <div class="field">
      <label for="deduction_amount">Deduction amount</label>
      <input
        id="deduction_amount"
        v-model="form.deduction_amount"
        type="number"
        min="0"
        step="0.01"
        inputmode="decimal"
        v-bind="fieldAttrs('deduction_amount')"
      />
      <small v-if="fieldError('deduction_amount')" id="deduction_amount-error" class="error">
        {{ fieldError('deduction_amount') }}
      </small>
    </div>

    <div class="field">
      <label for="notes">Notes</label>
      <textarea id="notes" v-model="form.notes" rows="4" v-bind="fieldAttrs('notes')"></textarea>
      <small v-if="fieldError('notes')" id="notes-error" class="error">
        {{ fieldError('notes') }}
      </small>
    </div>

    <div class="actions">
      <button class="btn-light" type="button" :disabled="submitting" @click="emit('cancel')">
        Cancel
      </button>
      <button type="submit" :disabled="submitting" :aria-busy="submitting">
        {{ mode === 'create' ? 'Create investment' : 'Save changes' }}
      </button>
    </div>
  </form>
</template>

<style scoped>
.choice-group {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-2);
}

.choice-option {
  align-items: center;
  background: var(--surface);
  border: 1px solid var(--border-strong);
  border-radius: var(--radius-md);
  cursor: pointer;
  display: inline-flex;
  gap: var(--space-2);
  margin: var(--space-0);
  min-height: var(--control-height);
  padding: var(--space-2) var(--space-3);
}

.choice-option:has(input:checked) {
  background: var(--accent-soft);
  border-color: var(--accent);
  color: var(--accent-hover);
}

.choice-option input {
  min-height: auto;
  width: auto;
}

fieldset {
  border: var(--space-0);
  margin: var(--space-0) var(--space-0) var(--space-4);
  padding: var(--space-0);
}

legend {
  color: var(--text-secondary);
  font-size: var(--text-sm);
  font-weight: var(--font-weight-medium);
  margin-bottom: var(--space-2);
}

.input-with-suffix {
  align-items: stretch;
  display: flex;
}

.input-with-suffix input {
  border-radius: var(--radius-md) var(--space-0) var(--space-0) var(--radius-md);
}

.input-with-suffix span {
  align-items: center;
  background: var(--surface-muted);
  border: 1px solid var(--border-strong);
  border-left: var(--space-0);
  border-radius: var(--space-0) var(--radius-md) var(--radius-md) var(--space-0);
  color: var(--text-secondary);
  display: inline-flex;
  padding: var(--space-0) var(--space-3);
}

.derived-field {
  background: var(--surface-muted);
  font-variant-numeric: tabular-nums;
}
</style>
