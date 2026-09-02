<script setup>
import { reactive, ref, watch } from 'vue'

const props = defineProps({
  contractVehicle: {
    type: Object,
    required: true,
  },
  initialData: {
    type: Object,
    default: () => ({}),
  },
  errors: {
    type: Object,
    default: () => ({}),
  },
  submitting: {
    type: Boolean,
    default: false,
  },
  submitLabel: {
    type: String,
    default: 'Save Report',
  },
})

const emit = defineEmits(['submit', 'cancel'])

const form = reactive({
  report_date: '',
  time_in: '',
  time_out: '',
  meter_in: '',
  meter_out: '',
  fuel_drawn: '',
  is_public_holiday: false,
  is_weekly_off: false,
  status: 'draft',
  remarks: '',
})

watch(
  () => props.initialData,
  (data) => {
    Object.assign(form, {
      report_date: data.report_date || '',
      time_in: data.time_in || '',
      time_out: data.time_out || '',
      meter_in: data.meter_in ?? '',
      meter_out: data.meter_out ?? '',
      fuel_drawn: data.fuel_drawn ?? '',
      is_public_holiday: !!data.is_public_holiday,
      is_weekly_off: !!data.is_weekly_off,
      status: data.status || 'draft',
      remarks: data.remarks || '',
    })
  },
  { immediate: true }
)

const fieldError = (field) =>
  props.errors[field]?.[0] || ''

const clearFieldError = (field) => {
  if (props.errors[field]) {
    delete props.errors[field]
  }
}

const submit = () => {
  const payload = {
    report_date: form.report_date || null,
    time_in: form.time_in || null,
    time_out: form.time_out || null,
    meter_in: form.meter_in === '' ? null : Number(form.meter_in),
    meter_out: form.meter_out === '' ? null : Number(form.meter_out),
    fuel_drawn: form.fuel_drawn === '' ? null : Number(form.fuel_drawn),
    is_public_holiday: form.is_public_holiday,
    is_weekly_off: form.is_weekly_off,
    status: form.status,
    remarks: form.remarks || null,
  }

  emit('submit', payload)
}
</script>

<template>
  <form
    class="report-form"
    @submit.prevent="submit"
  >
    <div class="form-row">
      <div class="form-field">
        <label for="report_date">
          Report Date <span class="required">*</span>
        </label>

        <input
          id="report_date"
          v-model="form.report_date"
          type="date"
          :class="{ 'is-invalid': fieldError('report_date') }"
          @input="clearFieldError('report_date')"
        />

        <span
          v-if="fieldError('report_date')"
          class="field-error"
        >
          {{ fieldError('report_date') }}
        </span>
      </div>

      <div class="form-field">
        <label for="status">
          Status
        </label>

        <select
          id="status"
          v-model="form.status"
        >
          <option value="draft">Draft</option>
          <option value="approved">Approved</option>
          <option value="rejected">Rejected</option>
        </select>
      </div>
    </div>

    <div class="form-row">
      <div class="form-field">
        <label for="time_in">
          Time In
        </label>

        <input
          id="time_in"
          v-model="form.time_in"
          type="time"
          :class="{ 'is-invalid': fieldError('time_in') }"
          @input="clearFieldError('time_in')"
        />

        <span
          v-if="fieldError('time_in')"
          class="field-error"
        >
          {{ fieldError('time_in') }}
        </span>
      </div>

      <div class="form-field">
        <label for="time_out">
          Time Out
        </label>

        <input
          id="time_out"
          v-model="form.time_out"
          type="time"
          :class="{ 'is-invalid': fieldError('time_out') }"
          @input="clearFieldError('time_out')"
        />

        <span
          v-if="fieldError('time_out')"
          class="field-error"
        >
          {{ fieldError('time_out') }}
        </span>
      </div>
    </div>

    <div class="form-row">
      <div class="form-field">
        <label for="meter_in">
          Meter In (KM)
        </label>

        <input
          id="meter_in"
          v-model="form.meter_in"
          type="number"
          step="0.01"
          min="0"
          :class="{ 'is-invalid': fieldError('meter_in') }"
          @input="clearFieldError('meter_in')"
        />

        <span
          v-if="fieldError('meter_in')"
          class="field-error"
        >
          {{ fieldError('meter_in') }}
        </span>
      </div>

      <div class="form-field">
        <label for="meter_out">
          Meter Out (KM)
        </label>

        <input
          id="meter_out"
          v-model="form.meter_out"
          type="number"
          step="0.01"
          min="0"
          :class="{ 'is-invalid': fieldError('meter_out') }"
          @input="clearFieldError('meter_out')"
        />

        <span
          v-if="fieldError('meter_out')"
          class="field-error"
        >
          {{ fieldError('meter_out') }}
        </span>
      </div>
    </div>

    <div class="form-row">
      <div class="form-field">
        <label for="fuel_drawn">
          Fuel Drawn (L)
        </label>

        <input
          id="fuel_drawn"
          v-model="form.fuel_drawn"
          type="number"
          step="0.01"
          min="0"
          :class="{ 'is-invalid': fieldError('fuel_drawn') }"
          @input="clearFieldError('fuel_drawn')"
        />

        <span
          v-if="fieldError('fuel_drawn')"
          class="field-error"
        >
          {{ fieldError('fuel_drawn') }}
        </span>
      </div>

      <div class="form-field checkbox-group">
        <label class="checkbox">
          <input
            v-model="form.is_public_holiday"
            type="checkbox"
          />
          Public Holiday
        </label>

        <label class="checkbox">
          <input
            v-model="form.is_weekly_off"
            type="checkbox"
          />
          Weekly Off
        </label>
      </div>
    </div>

    <div class="form-field">
      <label for="remarks">
        Remarks
      </label>

      <textarea
        id="remarks"
        v-model="form.remarks"
        rows="3"
        :class="{ 'is-invalid': fieldError('remarks') }"
        @input="clearFieldError('remarks')"
      />

      <span
        v-if="fieldError('remarks')"
        class="field-error"
      >
        {{ fieldError('remarks') }}
      </span>
    </div>

    <div class="form-actions">
      <button
        type="button"
        class="btn secondary"
        @click="$emit('cancel')"
      >
        Cancel
      </button>

      <button
        type="submit"
        class="btn primary"
        :disabled="submitting"
      >
        {{ submitting ? 'Saving…' : submitLabel }}
      </button>
    </div>
  </form>
</template>

<style scoped>
.report-form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.form-row {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
}

.form-field {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.form-field label {
  font-size: 0.72rem;
  font-weight: 600;
  color: var(--text-muted, #6b7280);
}

.form-field .required {
  color: #dc2626;
}

.form-field input,
.form-field select,
.form-field textarea {
  padding: 0.55rem 0.7rem;
  border: 1px solid var(--border-color, #e5e7eb);
  border-radius: 8px;
  font: inherit;
  font-size: 0.85rem;
  background: var(--surface-color, #fff);
}

.form-field input.is-invalid,
.form-field select.is-invalid,
.form-field textarea.is-invalid {
  border-color: #dc2626;
}

.field-error {
  font-size: 0.72rem;
  color: #dc2626;
}

.checkbox-group {
  flex-direction: row;
  align-items: flex-end;
  gap: 1rem;
}

.checkbox {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.85rem;
  color: var(--text, #111827);
  cursor: pointer;
}

.checkbox input {
  width: 1rem;
  height: 1rem;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.6rem;
  margin-top: 0.5rem;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 0;
  border-radius: 8px;
  padding: 0.6rem 0.85rem;
  font: inherit;
  font-size: 0.8rem;
  font-weight: 600;
  text-decoration: none;
  cursor: pointer;
}

.btn.primary {
  background: var(--primary, #111827);
  color: white;
}

.btn.primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn.secondary {
  background: var(--surface-muted, #f3f4f6);
  color: var(--text, #111827);
}

@media (max-width: 700px) {
  .form-row {
    grid-template-columns: 1fr;
  }

  .checkbox-group {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>
