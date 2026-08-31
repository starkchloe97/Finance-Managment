<script setup>
import InfoTip from '@/components/ui/InfoTip.vue'

defineProps({
  form: { type: Object, required: true },
  fieldError: { type: Function, default: () => '' },
  clearFieldError: { type: Function, default: () => {} },
  submitting: Boolean,
})

const STATUS_OPTIONS = [
  { value: 'active', label: 'Active — in service' },
  { value: 'maintenance', label: 'Maintenance — under repair' },
  { value: 'inactive', label: 'Inactive — retired' },
]
</script>

<template>
  <!-- Vehicle information -->
  <section class="card form-card">
    <header class="section-head-row">
      <span class="section-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M5 17h14" /><path d="M6 17V8l2-3h8l2 3v9" /><path d="M4 11h16" /><circle cx="7" cy="17" r="2" /><circle cx="17" cy="17" r="2" />
        </svg>
      </span>
      <div>
        <h2>Vehicle information</h2>
        <p class="section-hint">
          What it is — name, type, make, and model.
          <InfoTip label="The name is what your team will see everywhere. Make and model feed the vehicle label on lists and job assignment." />
        </p>
      </div>
    </header>

    <div class="grid two">
      <div class="field">
        <label for="asset-name">
          Vehicle name
          <span class="required">*</span>
        </label>
        <input
          id="asset-name"
          v-model="form.name"
          type="text"
          placeholder="e.g. Company Truck 01"
          autocomplete="off"
          required
          :disabled="submitting"
          :aria-invalid="Boolean(fieldError('name'))"
          @input="clearFieldError('name')"
        />
        <span v-if="fieldError('name')" class="error">{{ fieldError('name') }}</span>
      </div>

      <div class="field">
        <label for="asset-vehicle-type">
          Vehicle type
          <InfoTip label="Free text — Truck, Pickup, Van. Used to group similar vehicles." />
        </label>
        <input
          id="asset-vehicle-type"
          v-model="form.vehicle_type"
          type="text"
          placeholder="e.g. Truck, Pickup, Van"
          :disabled="submitting"
          :aria-invalid="Boolean(fieldError('vehicle_type'))"
          @input="clearFieldError('vehicle_type')"
        />
        <span v-if="fieldError('vehicle_type')" class="error">{{ fieldError('vehicle_type') }}</span>
      </div>

      <div class="field">
        <label for="asset-make">Make</label>
        <input
          id="asset-make"
          v-model="form.make"
          type="text"
          placeholder="e.g. Hino"
          :disabled="submitting"
          :aria-invalid="Boolean(fieldError('make'))"
          @input="clearFieldError('make')"
        />
        <span v-if="fieldError('make')" class="error">{{ fieldError('make') }}</span>
      </div>

      <div class="field">
        <label for="asset-model">Model</label>
        <input
          id="asset-model"
          v-model="form.model"
          type="text"
          placeholder="e.g. 500 Series"
          :disabled="submitting"
          :aria-invalid="Boolean(fieldError('model'))"
          @input="clearFieldError('model')"
        />
        <span v-if="fieldError('model')" class="error">{{ fieldError('model') }}</span>
      </div>

      <div class="field">
        <label for="asset-model-year">Model year</label>
        <input
          id="asset-model-year"
          v-model="form.model_year"
          type="number"
          inputmode="numeric"
          min="1900"
          :max="new Date().getFullYear() + 1"
          placeholder="e.g. 2024"
          :disabled="submitting"
          :aria-invalid="Boolean(fieldError('model_year'))"
          @input="clearFieldError('model_year')"
        />
        <span v-if="fieldError('model_year')" class="error">{{ fieldError('model_year') }}</span>
      </div>

      <div class="field">
        <label for="asset-color">Color</label>
        <input
          id="asset-color"
          v-model="form.color"
          type="text"
          placeholder="e.g. White"
          :disabled="submitting"
          :aria-invalid="Boolean(fieldError('color'))"
          @input="clearFieldError('color')"
        />
        <span v-if="fieldError('color')" class="error">{{ fieldError('color') }}</span>
      </div>
    </div>
  </section>

  <!-- Identification -->
  <section class="card form-card">
    <header class="section-head-row">
      <span class="section-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="4" width="18" height="16" rx="2" /><path d="M7 8h10" /><path d="M7 12h10" /><path d="M7 16h6" />
        </svg>
      </span>
      <div>
        <h2>Identification</h2>
        <p class="section-hint">
          Registration and manufacturer numbers.
          <InfoTip label="These identifiers matter later — when assigning vehicles to jobs, tracking maintenance, or handling insurance claims." />
        </p>
      </div>
    </header>

    <div class="grid two">
      <div class="field">
        <label for="asset-registration">
          Registration number
          <InfoTip label="The vehicle's official plate number — e.g. ABC-123." />
        </label>
        <input
          id="asset-registration"
          v-model="form.registration_number"
          type="text"
          placeholder="e.g. ABC-123"
          autocomplete="off"
          :disabled="submitting"
          :aria-invalid="Boolean(fieldError('registration_number'))"
          @input="clearFieldError('registration_number')"
        />
        <span v-if="fieldError('registration_number')" class="error">
          {{ fieldError('registration_number') }}
        </span>
      </div>

      <div class="field">
        <label for="asset-vin">
          VIN / Chassis number
          <InfoTip label="The manufacturer's unique vehicle identification number, stamped on the chassis." />
        </label>
        <input
          id="asset-vin"
          v-model="form.vin"
          type="text"
          placeholder="Vehicle identification number"
          autocomplete="off"
          :disabled="submitting"
          :aria-invalid="Boolean(fieldError('vin'))"
          @input="clearFieldError('vin')"
        />
        <span v-if="fieldError('vin')" class="error">{{ fieldError('vin') }}</span>
      </div>

      <div class="field">
        <label for="asset-engine">
          Engine number
          <InfoTip label="The engine block's serial number — different from the chassis number." />
        </label>
        <input
          id="asset-engine"
          v-model="form.engine_number"
          type="text"
          placeholder="Engine identification number"
          autocomplete="off"
          :disabled="submitting"
          :aria-invalid="Boolean(fieldError('engine_number'))"
          @input="clearFieldError('engine_number')"
        />
        <span v-if="fieldError('engine_number')" class="error">
          {{ fieldError('engine_number') }}
        </span>
      </div>
    </div>
  </section>

  <!-- Value & status -->
  <section class="card form-card">
    <header class="section-head-row">
      <span class="section-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="9" /><path d="M12 7v10" /><path d="M15 9.5c0-1.1-1.3-2-3-2s-3 .9-3 2 1.3 2 3 2 3 .9 3 2-1.3 2-3 2-3-.9-3-2" />
        </svg>
      </span>
      <div>
        <h2>Value & status</h2>
        <p class="section-hint">What it cost, what it's worth, and whether it's working.</p>
      </div>
    </header>

    <div class="grid two">
      <div class="field">
        <label for="asset-purchase-date">Purchase date</label>
        <input
          id="asset-purchase-date"
          v-model="form.purchase_date"
          type="date"
          :disabled="submitting"
          :aria-invalid="Boolean(fieldError('purchase_date'))"
          @input="clearFieldError('purchase_date')"
        />
        <span v-if="fieldError('purchase_date')" class="error">
          {{ fieldError('purchase_date') }}
        </span>
      </div>

      <div class="field">
        <label for="asset-status">
          Status
          <InfoTip label="Maintenance vehicles are temporarily unavailable for jobs. Inactive means retired — nothing is wrong, it's just out of service." />
        </label>
        <select
          id="asset-status"
          v-model="form.status"
          :disabled="submitting"
          :aria-invalid="Boolean(fieldError('status'))"
          @change="clearFieldError('status')"
        >
          <option v-for="option in STATUS_OPTIONS" :key="option.value" :value="option.value">
            {{ option.label }}
          </option>
        </select>
        <span v-if="fieldError('status')" class="error">{{ fieldError('status') }}</span>
      </div>

      <div class="field">
        <label for="asset-purchase-price">
          Purchase price
          <InfoTip label="What the company paid when it acquired the vehicle." />
        </label>
        <div class="money-input">
          <span>PKR</span>
          <input
            id="asset-purchase-price"
            v-model="form.purchase_price"
            type="number"
            min="0"
            step="0.01"
            inputmode="decimal"
            placeholder="0.00"
            :disabled="submitting"
            :aria-invalid="Boolean(fieldError('purchase_price'))"
            @input="clearFieldError('purchase_price')"
          />
        </div>
        <span v-if="fieldError('purchase_price')" class="error">
          {{ fieldError('purchase_price') }}
        </span>
      </div>

      <div class="field">
        <label for="asset-current-value">
          Current value
          <InfoTip label="What it's worth today — accounts for depreciation. This is the number shown on the assets list." />
        </label>
        <div class="money-input">
          <span>PKR</span>
          <input
            id="asset-current-value"
            v-model="form.current_value"
            type="number"
            min="0"
            step="0.01"
            inputmode="decimal"
            placeholder="0.00"
            :disabled="submitting"
            :aria-invalid="Boolean(fieldError('current_value'))"
            @input="clearFieldError('current_value')"
          />
        </div>
        <span v-if="fieldError('current_value')" class="error">
          {{ fieldError('current_value') }}
        </span>
      </div>
    </div>

    <div class="field notes-field">
      <label for="asset-notes">
        Notes
        <InfoTip label="Internal only — service history, fuel preferences, anything the team should know. Never shown outside the company." />
      </label>
      <textarea
        id="asset-notes"
        v-model="form.notes"
        rows="4"
        placeholder="Add any additional information about this vehicle…"
        :disabled="submitting"
        :aria-invalid="Boolean(fieldError('notes'))"
        @input="clearFieldError('notes')"
      ></textarea>
      <span v-if="fieldError('notes')" class="error">{{ fieldError('notes') }}</span>
    </div>
  </section>
</template>

<style scoped>
.form-card { padding: 20px; }

.section-head-row {
  align-items: flex-start;
  display: flex;
  gap: 12px;
  margin-bottom: 16px;
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

.grid.two {
  display: grid;
  gap: 14px 16px;
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

.field { min-width: 0; }

.field label {
  align-items: center;
  color: var(--text-secondary);
  display: flex;
  font-size: 13px;
  font-weight: var(--font-weight-medium);
  gap: 5px;
  margin-bottom: 6px;
}

.required { color: var(--danger); }

.notes-field { margin-top: 14px; }

.error {
  color: var(--danger);
  display: block;
  font-size: 11px;
  line-height: 1.35;
  margin-top: 5px;
}

.field :deep(input[aria-invalid='true']),
.field :deep(select[aria-invalid='true']),
.field :deep(textarea[aria-invalid='true']) {
  border-color: var(--danger);
}

.money-input { display: flex; align-items: stretch; }

.money-input span {
  align-items: center;
  background: var(--surface-2);
  border: 1px solid var(--border-strong);
  border-right: 0;
  border-radius: var(--radius-md) 0 0 var(--radius-md);
  color: var(--text-muted);
  display: flex;
  font-size: 11px;
  font-weight: 700;
  padding: 0 10px;
}

.money-input input { border-radius: 0 var(--radius-md) var(--radius-md) 0; }

@media (max-width: 620px) {
  .grid.two { grid-template-columns: 1fr; }
  .form-card { padding: 16px; }
}
</style>