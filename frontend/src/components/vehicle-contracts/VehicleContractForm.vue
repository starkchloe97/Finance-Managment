<script setup>
import { computed } from 'vue'
import InfoTip from '@/components/ui/InfoTip.vue'

const props = defineProps({
  modelValue: { type: Object, required: true },
})

const emit = defineEmits(['update:modelValue'])

const form = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
})

const updateField = (field, value) => {
  emit('update:modelValue', {
    ...props.modelValue,
    [field]: value,
  })
}

const totalMonthlyRental = computed(() => {
  const vehicles = Number(form.value.total_vehicles) || 0
  const rental = Number(form.value.monthly_rental_per_vehicle) || 0
  return vehicles * rental
})

const calculateTotalMonthlyRental = (vehicles, rental) =>
  (Number(vehicles) || 0) * (Number(rental) || 0)

const updateRental = (value) => {
  emit('update:modelValue', {
    ...props.modelValue,
    monthly_rental_per_vehicle: value,
    total_monthly_rental: calculateTotalMonthlyRental(
      props.modelValue.total_vehicles,
      value,
    ),
  })
}

const updateTotalVehicles = (value) => {
  emit('update:modelValue', {
    ...props.modelValue,
    total_vehicles: value,
    total_monthly_rental: calculateTotalMonthlyRental(
      value,
      props.modelValue.monthly_rental_per_vehicle,
    ),
  })
}
</script>

<template>
  <div class="contract-form">
    <div class="card contract-card">
      <!-- ========== AGREEMENT ========== -->
      <section class="sec">
        <header class="sec-head">
          <span class="sec-icon si-accent" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect width="18" height="18" x="3" y="4" rx="2" /><path d="M16 2v4" /><path d="M8 2v4" /><path d="M3 10h18" />
            </svg>
          </span>
          <h2 class="sec-title">Agreement</h2>
          <InfoTip label="When the contract starts and ends, and its total length in months." />
        </header>

        <div class="grid">
          <div class="ff always-float">
            <input id="agreement_date" type="date" :value="form.agreement_date" @input="updateField('agreement_date', $event.target.value)" />
            <label for="agreement_date">Agreement date</label>
          </div>

          <div class="ff always-float">
            <input id="end_date" type="date" :value="form.end_date" @input="updateField('end_date', $event.target.value)" />
            <label for="end_date">End date</label>
          </div>

          <div class="ff has-unit span-2">
            <input id="duration_months" type="number" min="1" placeholder=" " :value="form.duration_months" @input="updateField('duration_months', Number($event.target.value))" />
            <label for="duration_months">Duration</label>
            <span class="unit">months</span>
          </div>
        </div>
      </section>

      <!-- ========== PARTIES ========== -->
      <section class="sec">
        <header class="sec-head">
          <span class="sec-icon si-violet" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="M22 21v-2a4 4 0 0 0-3-3.87" /><path d="M16 3.13a4 4 0 0 1 0 7.75" />
            </svg>
          </span>
          <h2 class="sec-title">Parties</h2>
          <InfoTip label="The two sides of the agreement — your company (vendor) and the customer receiving the vehicles." />
        </header>

        <div class="group">
          <span class="group-chip vendor">Vendor</span>
          <div class="grid">
            <div class="ff span-2">
              <input id="vendor_name" type="text" placeholder=" " :value="form.vendor_name" @input="updateField('vendor_name', $event.target.value)" />
              <label for="vendor_name">Company name</label>
            </div>
            <div class="ff span-2">
              <textarea id="vendor_address" rows="2" placeholder=" " :value="form.vendor_address" @input="updateField('vendor_address', $event.target.value)"></textarea>
              <label for="vendor_address">Office address</label>
            </div>
          </div>
        </div>

        <div class="group">
          <span class="group-chip customer">Customer</span>
          <div class="grid">
            <div class="ff span-2">
              <input id="customer_name" type="text" placeholder=" " :value="form.customer_name" @input="updateField('customer_name', $event.target.value)" />
              <label for="customer_name">Customer name</label>
            </div>
            <div class="ff span-2">
              <input id="customer_tin" type="text" class="mono" placeholder=" " :value="form.customer_tin" @input="updateField('customer_tin', $event.target.value)" />
              <label for="customer_tin">TIN</label>
            </div>
            <div class="ff span-4">
              <textarea id="customer_address" rows="2" placeholder=" " :value="form.customer_address" @input="updateField('customer_address', $event.target.value)"></textarea>
              <label for="customer_address">Head office / address</label>
            </div>
          </div>
        </div>
      </section>

      <!-- ========== VEHICLE ========== -->
      <section class="sec">
        <header class="sec-head">
          <span class="sec-icon si-info" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2" /><path d="M15 18H9" /><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.62l-3.48-4.35A1 1 0 0 0 17.52 8H14" /><circle cx="17" cy="18" r="2" /><circle cx="7" cy="18" r="2" />
            </svg>
          </span>
          <h2 class="sec-title">Vehicle</h2>
          <InfoTip label="The vehicle description used throughout the agreement." />
        </header>

        <div class="grid">
          <div class="ff">
            <input id="total_vehicles" type="number" min="1" placeholder=" " :value="form.total_vehicles" @input="updateTotalVehicles(Number($event.target.value))" />
            <label for="total_vehicles">Vehicles</label>
          </div>
          <div class="ff">
            <input id="vehicle_make" type="text" placeholder=" " :value="form.vehicle_make" @input="updateField('vehicle_make', $event.target.value)" />
            <label for="vehicle_make">Make</label>
          </div>
          <div class="ff">
            <input id="vehicle_model" type="text" placeholder=" " :value="form.vehicle_model" @input="updateField('vehicle_model', $event.target.value)" />
            <label for="vehicle_model">Model</label>
          </div>
          <div class="ff">
            <input id="vehicle_model_year" type="text" placeholder=" " :value="form.vehicle_model_year" @input="updateField('vehicle_model_year', $event.target.value)" />
            <label for="vehicle_model_year">Year</label>
          </div>
          <div class="ff span-4">
            <input id="vehicle_type" type="text" placeholder=" " :value="form.vehicle_type" @input="updateField('vehicle_type', $event.target.value)" />
            <label for="vehicle_type">Vehicle type</label>
          </div>
        </div>
      </section>

      <!-- ========== RENTAL & PAYMENT ========== -->
      <section class="sec">
        <header class="sec-head">
          <span class="sec-icon si-success" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect width="20" height="12" x="2" y="6" rx="2" /><circle cx="12" cy="12" r="2" /><path d="M6 12h.01M18 12h.01" />
            </svg>
          </span>
          <h2 class="sec-title">Rental & payment</h2>
          <InfoTip label="Commercial terms. Duty hours are covered by the base rental — hours beyond them bill as overtime, and the holiday rate applies on public holidays." />
        </header>

        <div class="grid">
          <div class="ff has-unit span-2">
            <input id="monthly_rental" type="number" min="0" step="0.01" placeholder=" " :value="form.monthly_rental_per_vehicle" @input="updateRental(Number($event.target.value))" />
            <label for="monthly_rental">Rental / vehicle</label>
            <span class="unit">PKR</span>
          </div>

          <div class="derived span-2">
            <span>Total / month</span>
            <strong>PKR {{ totalMonthlyRental.toLocaleString() }}</strong>
            <InfoTip label="Calculated automatically — vehicles × rental per vehicle." />
          </div>

          <div class="ff">
            <input id="duty_hours" type="number" min="0" placeholder=" " :value="form.duty_hours_per_day" @input="updateField('duty_hours_per_day', Number($event.target.value))" />
            <label for="duty_hours">Duty hrs / day</label>
          </div>

          <div class="ff">
            <input id="duty_days" type="number" min="0" max="7" placeholder=" " :value="form.duty_days_per_week" @input="updateField('duty_days_per_week', Number($event.target.value))" />
            <label for="duty_days">Duty days / wk</label>
          </div>

          <div class="ff has-unit">
            <input id="public_holiday_rate" type="number" min="0" step="0.01" placeholder=" " :value="form.public_holiday_rate" @input="updateField('public_holiday_rate', Number($event.target.value))" />
            <label for="public_holiday_rate">Holiday rate</label>
            <span class="unit">PKR</span>
          </div>

          <div class="ff has-unit">
            <input id="overtime_rate" type="number" min="0" step="0.01" placeholder=" " :value="form.overtime_rate" @input="updateField('overtime_rate', Number($event.target.value))" />
            <label for="overtime_rate">Overtime / hr</label>
            <span class="unit">PKR</span>
          </div>

          <div class="ff span-2">
            <input id="payment_terms" type="text" placeholder=" " :value="form.payment_terms" @input="updateField('payment_terms', $event.target.value)" />
            <label for="payment_terms">Payment terms</label>
          </div>

          <div class="ff has-unit span-2">
            <input id="advance_months" type="number" min="0" placeholder=" " :value="form.advance_months" @input="updateField('advance_months', Number($event.target.value))" />
            <label for="advance_months">Advance rental</label>
            <span class="unit">months</span>
          </div>
        </div>
      </section>

      <!-- ========== MILEAGE & INSURANCE ========== -->
      <section class="sec">
        <header class="sec-head">
          <span class="sec-icon si-warning" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="m12 14 4-4" /><path d="M3.34 19a10 10 0 1 1 17.32 0" />
            </svg>
          </span>
          <h2 class="sec-title">Mileage & insurance</h2>
          <InfoTip label="Kilometres included per month, charges beyond the limit, the window to report damage, and the notice period to end the contract early." />
        </header>

        <div class="grid">
          <div class="ff has-unit">
            <input id="mileage_limit" type="number" min="0" placeholder=" " :value="form.monthly_mileage_limit" @input="updateField('monthly_mileage_limit', Number($event.target.value))" />
            <label for="mileage_limit">Mileage limit</label>
            <span class="unit">KM</span>
          </div>

          <div class="ff has-unit">
            <input id="excess_mileage_rate" type="number" min="0" step="0.01" placeholder=" " :value="form.excess_mileage_rate" @input="updateField('excess_mileage_rate', Number($event.target.value))" />
            <label for="excess_mileage_rate">Excess / KM</label>
            <span class="unit">PKR</span>
          </div>

          <div class="ff has-unit">
            <input id="insurance_period" type="number" min="0" placeholder=" " :value="form.insurance_claim_period_days" @input="updateField('insurance_claim_period_days', Number($event.target.value))" />
            <label for="insurance_period">Claim period</label>
            <span class="unit">days</span>
          </div>

          <div class="ff has-unit">
            <input id="termination_months" type="number" min="0" placeholder=" " :value="form.early_termination_months" @input="updateField('early_termination_months', Number($event.target.value))" />
            <label for="termination_months">Early termination</label>
            <span class="unit">months</span>
          </div>
        </div>
      </section>

      <!-- ========== SERVICE ========== -->
      <section class="sec">
        <header class="sec-head">
          <span class="sec-icon si-neutral" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="4" x2="4" y1="21" y2="14" /><line x1="4" x2="4" y1="10" y2="3" /><line x1="12" x2="12" y1="21" y2="12" /><line x1="12" x2="12" y1="8" y2="3" /><line x1="20" x2="20" y1="21" y2="16" /><line x1="20" x2="20" y1="12" y2="3" /><line x1="2" x2="6" y1="14" y2="14" /><line x1="10" x2="14" y1="8" y2="8" /><line x1="18" x2="22" y1="16" y2="16" />
            </svg>
          </span>
          <h2 class="sec-title">Service</h2>
          <InfoTip label="What the rental includes." />
        </header>

        <div class="grid">
          <div class="ff always-float span-2">
            <select id="service_type" :value="form.service_type" @change="updateField('service_type', $event.target.value)">
              <option value="with_driver">With driver</option>
              <option value="without_driver">Without driver</option>
            </select>
            <label for="service_type">Driver</label>
          </div>

          <label class="toggle-chip">
            <input type="checkbox" :checked="form.fuel_included" @change="updateField('fuel_included', $event.target.checked)" />
            <span>Fuel included</span>
          </label>

          <label class="toggle-chip">
            <input type="checkbox" :checked="form.routine_maintenance_included" @change="updateField('routine_maintenance_included', $event.target.checked)" />
            <span>Maintenance included</span>
          </label>
        </div>
      </section>

      <!-- ========== SIGNATORIES ========== -->
      <section class="sec last">
        <header class="sec-head">
          <span class="sec-icon si-neutral" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z" />
            </svg>
          </span>
          <h2 class="sec-title">Signatories & witnesses</h2>
          <InfoTip label="People appearing in the signature section of the printed agreement." />
        </header>

        <div class="group">
          <span class="group-chip vendor">Vendor rep</span>
          <div class="grid">
            <div class="ff">
              <input id="vendor_signatory_name" type="text" placeholder=" " :value="form.vendor_signatory_name" @input="updateField('vendor_signatory_name', $event.target.value)" />
              <label for="vendor_signatory_name">Name</label>
            </div>
            <div class="ff">
              <input id="vendor_signatory_designation" type="text" placeholder=" " :value="form.vendor_signatory_designation" @input="updateField('vendor_signatory_designation', $event.target.value)" />
              <label for="vendor_signatory_designation">Designation</label>
            </div>
            <div class="ff">
              <input id="vendor_signatory_cnic" type="text" class="mono" placeholder=" " :value="form.vendor_signatory_cnic" @input="updateField('vendor_signatory_cnic', $event.target.value)" />
              <label for="vendor_signatory_cnic">CNIC</label>
            </div>
            <div class="ff always-float">
              <input id="vendor_signature_date" type="date" :value="form.vendor_signature_date" @input="updateField('vendor_signature_date', $event.target.value)" />
              <label for="vendor_signature_date">Signs on</label>
            </div>
          </div>
        </div>

        <div class="group">
          <span class="group-chip customer">Customer rep</span>
          <div class="grid">
            <div class="ff">
              <input id="customer_signatory_name" type="text" placeholder=" " :value="form.customer_signatory_name" @input="updateField('customer_signatory_name', $event.target.value)" />
              <label for="customer_signatory_name">Name</label>
            </div>
            <div class="ff">
              <input id="customer_signatory_designation" type="text" placeholder=" " :value="form.customer_signatory_designation" @input="updateField('customer_signatory_designation', $event.target.value)" />
              <label for="customer_signatory_designation">Designation</label>
            </div>
            <div class="ff">
              <input id="customer_signatory_cnic" type="text" class="mono" placeholder=" " :value="form.customer_signatory_cnic" @input="updateField('customer_signatory_cnic', $event.target.value)" />
              <label for="customer_signatory_cnic">CNIC</label>
            </div>
            <div class="ff always-float">
              <input id="customer_signature_date" type="date" :value="form.customer_signature_date" @input="updateField('customer_signature_date', $event.target.value)" />
              <label for="customer_signature_date">Signs on</label>
            </div>
          </div>
        </div>

        <div class="group">
          <span class="group-chip neutral">Witnesses</span>
          <div class="grid">
            <div class="ff">
              <input id="witness_1_name" type="text" placeholder=" " :value="form.witness_1_name" @input="updateField('witness_1_name', $event.target.value)" />
              <label for="witness_1_name">Witness 1 — name</label>
            </div>
            <div class="ff">
              <input id="witness_1_cnic" type="text" class="mono" placeholder=" " :value="form.witness_1_cnic" @input="updateField('witness_1_cnic', $event.target.value)" />
              <label for="witness_1_cnic">Witness 1 — CNIC</label>
            </div>
            <div class="ff">
              <input id="witness_2_name" type="text" placeholder=" " :value="form.witness_2_name" @input="updateField('witness_2_name', $event.target.value)" />
              <label for="witness_2_name">Witness 2 — name</label>
            </div>
            <div class="ff">
              <input id="witness_2_cnic" type="text" class="mono" placeholder=" " :value="form.witness_2_cnic" @input="updateField('witness_2_cnic', $event.target.value)" />
              <label for="witness_2_cnic">Witness 2 — CNIC</label>
            </div>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>

<style scoped>
.contract-form {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.contract-card { padding: 16px 18px; }

/* ---------- Sections ---------- */

.sec {
  border-top: 1px solid var(--border);
  padding: 13px 0 14px;
}
.sec:first-child { border-top: 0; padding-top: 0; }
.sec.last { padding-bottom: 0; }

.sec-head {
  align-items: center;
  display: flex;
  gap: 8px;
  margin-bottom: 10px;
}

.sec-icon {
  align-items: center;
  border-radius: 8px;
  display: flex;
  flex: 0 0 26px;
  height: 26px;
  justify-content: center;
  width: 26px;
}
.sec-icon svg { height: 13px; width: 13px; }
.si-accent  { background: var(--accent-soft);  color: var(--accent); }
.si-violet  { background: var(--violet-soft);  color: var(--violet); }
.si-info    { background: var(--info-soft);    color: var(--info); }
.si-success { background: var(--success-soft); color: var(--success); }
.si-warning { background: var(--warning-soft); color: var(--warning); }
.si-neutral { background: var(--surface-2);    color: var(--text-secondary); }

.sec-title {
  color: var(--text-primary);
  font-size: 13px;
  font-weight: 600;
  margin: 0;
}

/* ---------- Grid ---------- */

.grid {
  display: grid;
  gap: 10px 12px;
  grid-template-columns: repeat(4, minmax(0, 1fr));
}

.span-2 { grid-column: span 2; }
.span-4 { grid-column: 1 / -1; }

/* ---------- Floating-label fields (notch style) ---------- */
/* DOM order matters: control first, label after — the sibling */
/* selectors below drive the float without any JS.            */

.ff { position: relative; }

.ff > input,
.ff > select,
.ff > textarea {
  background: var(--surface);
  border: 1px solid var(--border-strong);
  border-radius: 10px;
  color: var(--text-primary);
  font: inherit;
  font-size: 13.5px;
  min-height: 44px;
  padding: 0 12px;
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
  width: 100%;
}

.ff > textarea {
  min-height: 66px;
  padding: 14px 12px 8px;
  resize: vertical;
}

.ff > input.mono {
  font-family: ui-monospace, SFMono-Regular, 'SF Mono', Menlo, Consolas, monospace;
  font-size: 13px;
}

/* The label: placeholder position at rest, notch on the border when floated */
.ff > label {
  color: var(--text-muted);
  font-size: 13px;
  left: 12px;
  line-height: 1.2;
  max-width: calc(100% - 26px);
  overflow: hidden;
  pointer-events: none;
  position: absolute;
  text-overflow: ellipsis;
  top: 50%;
  transform: translateY(-50%);
  transition: all 0.16s cubic-bezier(0.4, 0, 0.2, 1);
  white-space: nowrap;
}

.ff > textarea ~ label,
.ff > textarea + label { top: 20px; }

.ff > input:focus ~ label,
.ff > textarea:focus ~ label,
.ff > input:not(:placeholder-shown) ~ label,
.ff > textarea:not(:placeholder-shown) ~ label,
.ff.always-float > label {
  background: var(--surface);
  color: var(--text-muted);
  font-size: 10px;
  font-weight: 700;
  left: 8px;
  letter-spacing: 0.05em;
  line-height: 1;
  max-width: calc(100% - 18px);
  padding: 0 5px;
  text-transform: uppercase;
  top: 0;
  transform: translateY(-50%);
}

.ff > input:focus ~ label,
.ff > select:focus ~ label,
.ff > textarea:focus ~ label {
  color: var(--accent);
}

.ff > input:focus,
.ff > select:focus,
.ff > textarea:focus {
  border-color: var(--accent);
  box-shadow: 0 0 0 3px var(--focus-ring);
  outline: none;
}

/* Unit suffix — always aligned with the vertically-centered value */
.unit {
  color: var(--text-muted);
  font-size: 10.5px;
  font-weight: 700;
  pointer-events: none;
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  white-space: nowrap;
}

.ff.has-unit > input { padding-right: 58px; }
.ff.has-unit > label { max-width: calc(100% - 74px); }

/* ---------- Derived total ---------- */

.derived {
  align-items: center;
  background: var(--success-soft);
  border: 1px solid transparent;
  border-radius: 10px;
  display: flex;
  gap: 6px;
  min-height: 44px;
  padding: 0 12px;
}

.derived > span {
  color: var(--success);
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  white-space: nowrap;
}

.derived > strong {
  color: var(--success);
  font-size: 14px;
  font-variant-numeric: tabular-nums;
  font-weight: 700;
  margin-left: auto;
  white-space: nowrap;
}

/* ---------- Subgroups ---------- */

.group { margin-bottom: 12px; }
.group:last-child { margin-bottom: 0; }

.group-chip {
  align-items: center;
  border-radius: 999px;
  display: inline-flex;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.04em;
  margin-bottom: 8px;
  padding: 2px 9px;
  text-transform: uppercase;
}
.group-chip::before {
  border-radius: 50%;
  content: '';
  height: 5px;
  margin-right: 5px;
  width: 5px;
}
.group-chip.vendor {
  background: var(--accent-soft);
  color: var(--accent);
}
.group-chip.vendor::before { background: var(--accent); }
.group-chip.customer {
  background: var(--violet-soft);
  color: var(--violet);
}
.group-chip.customer::before { background: var(--violet); }
.group-chip.neutral {
  background: var(--surface-2);
  color: var(--text-secondary);
}
.group-chip.neutral::before { background: var(--text-muted); }

/* ---------- Service toggles ---------- */

.toggle-chip {
  align-items: center;
  background: var(--surface);
  border: 1px solid var(--border-strong);
  border-radius: 10px;
  cursor: pointer;
  display: inline-flex;
  font-size: 13px;
  font-weight: var(--font-weight-medium);
  gap: 8px;
  min-height: 44px;
  padding: 0 14px;
  transition: background 0.15s ease, border-color 0.15s ease;
}

.toggle-chip:hover { border-color: var(--text-muted); }

.toggle-chip input {
  accent-color: var(--accent);
  height: 16px;
  margin: 0;
  min-height: 0;
  width: 16px;
}

.toggle-chip:has(input:checked) {
  background: var(--accent-soft);
  border-color: var(--accent);
  color: var(--accent);
}

/* ---------- Responsive ---------- */

@media (max-width: 1000px) {
  .grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .span-2,
  .span-4 { grid-column: span 2; }
}

@media (max-width: 560px) {
  .grid { grid-template-columns: 1fr; }
  .span-2,
  .span-4 { grid-column: auto; }
  .contract-card { padding: 14px; }
}
</style>