<script setup>
import { computed, reactive, watch } from 'vue'
import { money } from '@/utils/money'
import InfoTip from '@/components/ui/InfoTip.vue'

const props = defineProps({
  mode: { type: String, default: 'create' },
  initialValues: { type: Object, default: () => ({}) },
  investors: { type: Array, default: () => [] },
  errors: { type: Object, default: () => ({}) },
  submitting: { type: Boolean, default: false },
  error: { type: String, default: '' },
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

// ---- Live preview math (mirrors the detail page's payout flow) ----
const principalNum = computed(() => Number(form.amount || 0))

const returnPreview = computed(() => {
  if (form.return_type === 'fixed') return Number(form.fixed_return_amount || 0)
  return principalNum.value * (Number(form.return_percentage || 0) / 100)
})

const deductionNum = computed(() => Number(form.deduction_amount || 0))
const settlementPreview = computed(() => principalNum.value + returnPreview.value - deductionNum.value)

const maturityPreview = computed(() => {
  if (!form.investment_date || !form.period_months) return ''
  const date = new Date(form.investment_date)
  date.setMonth(date.getMonth() + Number(form.period_months))
  return date.toISOString().slice(0, 10)
})

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
  <form class="investment-form-layout" @submit.prevent="submit">
    <div v-if="error" class="notice form-error" role="alert">{{ error }}</div>

    <div class="form-columns">
      <!-- ===== Main column ===== -->
      <div class="form-main">
        <section class="card form-section">
          <div class="section-heading">
            <h3>Basics</h3>
            <p>Who is investing, how much, and what kind of placement this is.</p>
          </div>

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
              <label for="amount">
                Investment amount
                <InfoTip label="The capital the investor is placing with the company." />
              </label>
              <input
                id="amount"
                v-model="form.amount"
                type="number"
                min="0.01"
                step="0.01"
                inputmode="decimal"
                placeholder="0.00"
                v-bind="fieldAttrs('amount')"
                required
              />
              <small v-if="fieldError('amount')" id="amount-error" class="error">
                {{ fieldError('amount') }}
              </small>
            </div>
          </div>

          <fieldset class="field">
            <legend>
              Investment category
              <InfoTip label="Pool capital is spread automatically across jobs. Direct capital is placed on specific jobs you choose." />
            </legend>
            <div class="choice-group">
              <label class="choice-option">
                <input v-model="form.investment_category" type="radio" value="pool" />
                <span>
                  <b>Pool</b>
                  <small>Spread across jobs automatically</small>
                </span>
              </label>
              <label class="choice-option">
                <input v-model="form.investment_category" type="radio" value="normal" />
                <span>
                  <b>Direct</b>
                  <small>Allocated to jobs you pick</small>
                </span>
              </label>
            </div>
            <small v-if="fieldError('investment_category')" id="investment_category-error" class="error">
              {{ fieldError('investment_category') }}
            </small>
          </fieldset>
        </section>

        <section class="card form-section">
          <div class="section-heading">
            <h3>Terms</h3>
            <p>How long the capital works and how it earns.</p>
          </div>

          <div class="grid">
            <div class="field">
              <label for="period_months">
                Investment period
                <InfoTip label="How many months the capital stays invested. The maturity date is set from this." />
              </label>
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
              <label for="return_policy_days">
                Return policy
                <InfoTip label="How many days after the term ends before the return is paid out." />
              </label>
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
            <legend>
              Return type
              <InfoTip label="Percentage returns share in job profit. Fixed returns pay a set amount, regardless of profit." />
            </legend>
            <div class="choice-group">
              <label v-if="form.investment_category === 'normal'" class="choice-option">
                <input v-model="form.return_type" type="radio" value="fixed" />
                <span>
                  <b>Fixed</b>
                  <small>A set amount</small>
                </span>
              </label>
              <label class="choice-option">
                <input v-model="form.return_type" type="radio" value="percentage" />
                <span>
                  <b>Percentage</b>
                  <small>A share of profit</small>
                </span>
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
              placeholder="0.00"
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
              <label for="calculated_return_amount">
                Expected return
                <InfoTip label="Calculated live from the amount and percentage — what the investment is expected to earn." />
              </label>
              <input
                id="calculated_return_amount"
                :value="money(returnPreview)"
                type="text"
                readonly
                aria-readonly="true"
                class="derived-field"
              />
            </div>
          </div>

          <div class="field">
            <label for="deduction_amount">
              Deduction amount
              <InfoTip label="An agreed amount held back from the final payout — for example to cover fees or risk share. Use 0 for none." />
            </label>
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
            <label for="notes">
              Notes
              <InfoTip label="Internal only — agreements and context. Never shown to the investor." />
            </label>
            <textarea id="notes" v-model="form.notes" rows="4" v-bind="fieldAttrs('notes')"></textarea>
            <small v-if="fieldError('notes')" id="notes-error" class="error">
              {{ fieldError('notes') }}
            </small>
          </div>
        </section>
      </div>

      <!-- ===== Sticky preview panel ===== -->
      <aside class="form-aside">
        <div class="card panel-card">
          <h2 class="panel-title">Live preview</h2>
          <p class="panel-hint">Updates as you type.</p>

          <div class="panel-row">
            <span>Principal</span>
            <strong>{{ money(principalNum) }}</strong>
          </div>
          <div class="panel-row">
            <span>
              Return
              <InfoTip :label="form.return_type === 'fixed'
                ? 'The fixed amount you entered.'
                : 'Amount × percentage.'" />
            </span>
            <strong>{{ money(returnPreview) }}</strong>
          </div>
          <div class="panel-row">
            <span>Deduction</span>
            <strong>− {{ money(deductionNum) }}</strong>
          </div>
          <div class="panel-row panel-grand">
            <span>Settlement</span>
            <strong :class="settlementPreview < 0 ? 'money-loss' : 'money-profit'">
              {{ money(settlementPreview) }}
            </strong>
          </div>

          <div v-if="maturityPreview" class="panel-date">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <rect width="18" height="18" x="3" y="4" rx="2" /><path d="M16 2v4" /><path d="M8 2v4" /><path d="M3 10h18" />
            </svg>
            <span>Matures <b>{{ maturityPreview }}</b></span>
          </div>

          <div class="panel-actions">
            <button type="submit" :disabled="submitting" :aria-busy="submitting">
              {{ mode === 'create' ? 'Create investment' : 'Save changes' }}
            </button>
            <button
              class="btn-light"
              type="button"
              :disabled="submitting"
              @click="emit('cancel')"
            >
              Cancel
            </button>
          </div>
        </div>
      </aside>
    </div>
  </form>
</template>

<style scoped>
.investment-form-layout { min-width: 0; }

.form-error {
  border: 1px solid var(--danger);
  color: var(--danger);
  margin-bottom: var(--space-4);
}

.form-columns {
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

.form-section { padding: 20px; }

.section-heading { margin-bottom: var(--space-4); }
.section-heading h3 { font-size: 15px; font-weight: 600; margin: 0; }
.section-heading p { color: var(--text-muted); font-size: 13px; margin: 2px 0 0; }

.field label,
legend {
  align-items: center;
  display: flex;
  gap: 5px;
}

/* Choice cards — richer than plain radios */
.choice-group {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-2);
}

.choice-option {
  align-items: flex-start;
  background: var(--surface);
  border: 1px solid var(--border-strong);
  border-radius: var(--radius-md);
  cursor: pointer;
  display: inline-flex;
  flex: 1 1 160px;
  flex-direction: column;
  gap: 6px;
  margin: 0;
  min-height: var(--control-height);
  padding: 10px 14px;
  transition:
    background var(--transition-fast),
    border-color var(--transition-fast),
    box-shadow var(--transition-fast);
}

.choice-option:hover {
  border-color: var(--text-muted);
}

.choice-option:has(input:checked) {
  background: var(--accent-soft);
  border-color: var(--accent);
  box-shadow: 0 0 0 1px var(--accent);
}

.choice-option input {
  min-height: auto;
  width: auto;
}

.choice-option b {
  color: var(--text-primary);
  font-size: 14px;
  font-weight: 600;
}

.choice-option:has(input:checked) b {
  color: var(--accent-hover);
}

.choice-option small {
  color: var(--text-muted);
  font-size: 12px;
  margin-left: 0;
}

fieldset {
  border: 0;
  margin: 0 0 var(--space-4);
  padding: 0;
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
  border-radius: var(--radius-md) 0 0 var(--radius-md);
}

.input-with-suffix span {
  align-items: center;
  background: var(--surface-muted);
  border: 1px solid var(--border-strong);
  border-left: 0;
  border-radius: 0 var(--radius-md) var(--radius-md) 0;
  color: var(--text-secondary);
  display: inline-flex;
  padding: 0 var(--space-3);
}

.derived-field {
  background: var(--surface-muted);
  font-variant-numeric: tabular-nums;
  font-weight: 600;
}

/* ---------- Preview panel ---------- */
.form-aside {
  position: sticky;
  top: 20px;
}

.panel-card { padding: 18px; }

.panel-title {
  font-size: 15px;
  font-weight: 600;
  margin: 0;
}
.panel-hint {
  color: var(--text-muted);
  font-size: 12px;
  margin: 2px 0 14px;
}

.panel-row {
  align-items: center;
  border-bottom: 1px solid var(--border);
  display: flex;
  font-size: 14px;
  justify-content: space-between;
  padding: 9px 0;
}

.panel-row span {
  align-items: center;
  color: var(--text-muted);
  display: flex;
  font-size: 12px;
  font-weight: 500;
  gap: 5px;
}

.panel-row strong {
  color: var(--text-primary);
  font-variant-numeric: tabular-nums;
  font-weight: 600;
}

.panel-row.panel-grand strong {
  color: var(--success);
  font-size: 20px;
  font-weight: 700;
}
.panel-row.panel-grand strong.money-loss { color: var(--danger); }

.panel-date {
  align-items: center;
  color: var(--text-secondary);
  display: flex;
  font-size: 13px;
  gap: 8px;
  margin-top: 12px;
}
.panel-date svg { color: var(--text-muted); height: 15px; width: 15px; }
.panel-date b { color: var(--text-primary); font-variant-numeric: tabular-nums; }

.panel-actions {
  border-top: 1px solid var(--border);
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-top: 14px;
  padding-top: 14px;
}

/* ---------- Responsive ---------- */
@media (max-width: 1024px) {
  .form-columns { grid-template-columns: 1fr; }

  .form-aside {
    bottom: 0;
    position: sticky;
    top: auto;
    z-index: 10;
  }

  .panel-card {
    border-radius: var(--radius-lg) var(--radius-lg) 0 0;
    box-shadow: var(--shadow-md);
    padding: 14px 16px;
  }

  .panel-hint,
  .panel-date { display: none; }

  .panel-row {
    border-bottom: 0;
    display: inline-flex;
    font-size: 13px;
    padding: 0;
  }

  .panel-row span { font-size: 11px; }
  .panel-row strong { font-size: 14px; }
  .panel-row.panel-grand strong { font-size: 15px; }

  .panel-actions {
    border-top: 0;
    flex-direction: row;
    margin-top: 10px;
    padding-top: 0;
  }
  .panel-actions button,
  .panel-actions .btn-light { flex: 1; }
}

@media (min-width: 1025px) {
  .panel-rows { display: contents; }
}
</style>