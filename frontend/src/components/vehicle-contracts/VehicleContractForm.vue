<script setup>
import { computed } from 'vue'

const props = defineProps({
  modelValue: {
    type: Object,
    required: true,
  },
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


const calculateTotalMonthlyRental = (vehicles, rental) => {
  return (Number(vehicles) || 0) * (Number(rental) || 0)
}

const updateRental = (value) => {
  emit('update:modelValue', {
    ...props.modelValue,
    monthly_rental_per_vehicle: value,
    total_monthly_rental: calculateTotalMonthlyRental(
      props.modelValue.total_vehicles,
      value
    ),
  })
}
const updateTotalVehicles = (value) => {
  emit('update:modelValue', {
    ...props.modelValue,
    total_vehicles: value,
    total_monthly_rental: calculateTotalMonthlyRental(
      value,
      props.modelValue.monthly_rental_per_vehicle
    ),
  })
}
</script>

<template>
  <div class="contract-form">
    <!-- Agreement -->
    <section class="form-section">
      <div class="section-heading">
        <div>
          <h2>Agreement</h2>
          <p>Basic agreement information and validity.</p>
        </div>
      </div>

      <div class="form-grid">
        <div class="form-field">
          <label for="agreement_date">Agreement Date</label>

          <input
            id="agreement_date"
            type="date"
            :value="form.agreement_date"
            @input="updateField('agreement_date', $event.target.value)"
          />
        </div>

        <div class="form-field">
          <label for="effective_date">Effective Date</label>

          <input
            id="effective_date"
            type="date"
            :value="form.effective_date"
            @input="updateField('effective_date', $event.target.value)"
          />
        </div>

        <div class="form-field">
          <label for="end_date">End Date</label>

          <input
            id="end_date"
            type="date"
            :value="form.end_date"
            @input="updateField('end_date', $event.target.value)"
          />
        </div>

        <div class="form-field">
          <label for="duration_months">Duration</label>

          <div class="input-with-suffix">
            <input
              id="duration_months"
              type="number"
              min="1"
              :value="form.duration_months"
              @input="
                updateField(
                  'duration_months',
                  Number($event.target.value)
                )
              "
            />

            <span>months</span>
          </div>
        </div>
      </div>
    </section>

    <!-- Vendor -->
    <section class="form-section">
      <div class="section-heading">
        <div>
          <h2>Vendor</h2>
          <p>Company information appearing on the agreement.</p>
        </div>
      </div>

      <div class="form-grid">
        <div class="form-field form-field-full">
          <label for="vendor_name">Vendor Name</label>

          <input
            id="vendor_name"
            type="text"
            :value="form.vendor_name"
            @input="updateField('vendor_name', $event.target.value)"
          />
        </div>

        <div class="form-field form-field-full">
          <label for="vendor_address">Office Address</label>

          <textarea
            id="vendor_address"
            rows="3"
            :value="form.vendor_address"
            @input="updateField('vendor_address', $event.target.value)"
          />
        </div>
      </div>
    </section>

    <!-- Customer -->
    <section class="form-section">
      <div class="section-heading">
        <div>
          <h2>Customer / User</h2>
          <p>Enter the party receiving the rental vehicles.</p>
        </div>
      </div>

      <div class="form-grid">
        <div class="form-field form-field-full">
          <label for="customer_name">Customer Name</label>

          <input
            id="customer_name"
            type="text"
            :value="form.customer_name"
            @input="updateField('customer_name', $event.target.value)"
          />
        </div>

        <div class="form-field form-field-full">
          <label for="customer_address">Head Office / Address</label>

          <textarea
            id="customer_address"
            rows="3"
            :value="form.customer_address"
            @input="
              updateField(
                'customer_address',
                $event.target.value
              )
            "
          />
        </div>

        <div class="form-field">
          <label for="customer_tin">TIN</label>

          <input
            id="customer_tin"
            type="text"
            :value="form.customer_tin"
            @input="updateField('customer_tin', $event.target.value)"
          />
        </div>
      </div>
    </section>

    <!-- Vehicle -->
    <section class="form-section">
      <div class="section-heading">
        <div>
          <h2>Vehicle</h2>
          <p>Vehicle details used throughout the agreement.</p>
        </div>
      </div>

      <div class="form-grid">
        <div class="form-field">
          <label for="total_vehicles">Total Vehicles</label>

          <input
            id="total_vehicles"
            type="number"
            min="1"
            :value="form.total_vehicles"
           @input="updateField('total_vehicles', Number($event.target.value))"
          />
        </div>

        <div class="form-field">
          <label for="vehicle_make">Make</label>

          <input
            id="vehicle_make"
            type="text"
            :value="form.vehicle_make"
            @input="updateField('vehicle_make', $event.target.value)"
          />
        </div>

        <div class="form-field">
          <label for="vehicle_model">Model</label>

          <input
            id="vehicle_model"
            type="text"
            :value="form.vehicle_model"
            @input="updateField('vehicle_model', $event.target.value)"
          />
        </div>

        <div class="form-field">
          <label for="vehicle_model_year">Model Year</label>

          <input
            id="vehicle_model_year"
            type="text"
            :value="form.vehicle_model_year"
            @input="
              updateField(
                'vehicle_model_year',
                $event.target.value
              )
            "
          />
        </div>

        <div class="form-field form-field-full">
          <label for="vehicle_type">Vehicle Type</label>

          <input
            id="vehicle_type"
            type="text"
            :value="form.vehicle_type"
            @input="updateField('vehicle_type', $event.target.value)"
          />
        </div>
      </div>
    </section>

    <!-- Rental -->
    <section class="form-section">
      <div class="section-heading">
        <div>
          <h2>Rental & Duty</h2>
          <p>Commercial terms used by the agreement.</p>
        </div>
      </div>

      <div class="form-grid">
        <div class="form-field">
          <label for="monthly_rental">
            Monthly Rental / Vehicle
          </label>

          <input
            id="monthly_rental"
            type="number"
            min="0"
            step="0.01"
            :value="form.monthly_rental_per_vehicle"
            @input="
              updateRental(
                Number($event.target.value)
              )
            "
          />
        </div>

        <div class="form-field">
          <label>Total Monthly Rental</label>

          <div class="calculated-value">
            PKR {{ totalMonthlyRental.toLocaleString() }}
          </div>
        </div>

        <div class="form-field">
          <label for="duty_hours">Duty Hours / Day</label>

          <input
            id="duty_hours"
            type="number"
            min="0"
            :value="form.duty_hours_per_day"
            @input="
              updateField(
                'duty_hours_per_day',
                Number($event.target.value)
              )
            "
          />
        </div>

        <div class="form-field">
          <label for="duty_days">Duty Days / Week</label>

          <input
            id="duty_days"
            type="number"
            min="0"
            max="7"
            :value="form.duty_days_per_week"
            @input="
              updateField(
                'duty_days_per_week',
                Number($event.target.value)
              )
            "
          />
        </div>

        <div class="form-field">
          <label for="public_holiday_rate">
            Public Holiday Rate
          </label>

          <input
            id="public_holiday_rate"
            type="number"
            min="0"
            step="0.01"
            :value="form.public_holiday_rate"
            @input="
              updateField(
                'public_holiday_rate',
                Number($event.target.value)
              )
            "
          />
        </div>

        <div class="form-field">
          <label for="overtime_rate">
            Overtime / Hour
          </label>

          <input
            id="overtime_rate"
            type="number"
            min="0"
            step="0.01"
            :value="form.overtime_rate"
            @input="
              updateField(
                'overtime_rate',
                Number($event.target.value)
              )
            "
          />
        </div>
      </div>
    </section>

    <!-- Payment -->
    <section class="form-section">
      <div class="section-heading">
        <div>
          <h2>Payment</h2>
          <p>Invoice, advance and payment conditions.</p>
        </div>
      </div>

      <div class="form-grid">
        <div class="form-field">
          <label for="payment_terms">Payment Terms</label>

          <input
            id="payment_terms"
            type="text"
            :value="form.payment_terms"
            @input="
              updateField(
                'payment_terms',
                $event.target.value
              )
            "
          />
        </div>

        <div class="form-field">
          <label for="advance_months">Advance Rental</label>

          <div class="input-with-suffix">
            <input
              id="advance_months"
              type="number"
              min="0"
              :value="form.advance_months"
              @input="
                updateField(
                  'advance_months',
                  Number($event.target.value)
                )
              "
            />

            <span>months</span>
          </div>
        </div>
      </div>
    </section>

    <!-- Mileage -->
    <section class="form-section">
      <div class="section-heading">
        <div>
          <h2>Mileage & Insurance</h2>
          <p>Limits and loss settlement conditions.</p>
        </div>
      </div>

      <div class="form-grid">
        <div class="form-field">
          <label for="mileage_limit">
            Monthly Mileage Limit
          </label>

          <div class="input-with-suffix">
            <input
              id="mileage_limit"
              type="number"
              min="0"
              :value="form.monthly_mileage_limit"
              @input="
                updateField(
                  'monthly_mileage_limit',
                  Number($event.target.value)
                )
              "
            />

            <span>KM</span>
          </div>
        </div>

        <div class="form-field">
          <label for="excess_mileage_rate">
            Excess Mileage Rate
          </label>

          <div class="input-with-suffix">
            <input
              id="excess_mileage_rate"
              type="number"
              min="0"
              step="0.01"
              :value="form.excess_mileage_rate"
              @input="
                updateField(
                  'excess_mileage_rate',
                  Number($event.target.value)
                )
              "
            />

            <span>PKR / KM</span>
          </div>
        </div>

        <div class="form-field">
          <label for="insurance_period">
            Insurance Claim Period
          </label>

          <div class="input-with-suffix">
            <input
              id="insurance_period"
              type="number"
              min="0"
              :value="form.insurance_claim_period_days"
              @input="
                updateField(
                  'insurance_claim_period_days',
                  Number($event.target.value)
                )
              "
            />

            <span>days</span>
          </div>
        </div>

        <div class="form-field">
          <label for="termination_months">
            Early Termination
          </label>

          <div class="input-with-suffix">
            <input
              id="termination_months"
              type="number"
              min="0"
              :value="form.early_termination_months"
              @input="
                updateField(
                  'early_termination_months',
                  Number($event.target.value)
                )
              "
            />

            <span>months</span>
          </div>
        </div>
      </div>
    </section>

    <!-- Service -->
    <section class="form-section">
      <div class="section-heading">
        <div>
          <h2>Service Configuration</h2>
          <p>Define what is included in the rental.</p>
        </div>
      </div>

      <div class="form-grid">
        <div class="form-field">
          <label for="service_type">Driver</label>

          <select
            id="service_type"
            :value="form.service_type"
            @change="
              updateField(
                'service_type',
                $event.target.value
              )
            "
          >
            <option value="with_driver">
              With Driver
            </option>

            <option value="without_driver">
              Without Driver
            </option>
          </select>
        </div>

        <label class="checkbox-field">
          <input
            type="checkbox"
            :checked="form.fuel_included"
            @change="
              updateField(
                'fuel_included',
                $event.target.checked
              )
            "
          />

          <span>Fuel Included</span>
        </label>

        <label class="checkbox-field">
          <input
            type="checkbox"
            :checked="form.routine_maintenance_included"
            @change="
              updateField(
                'routine_maintenance_included',
                $event.target.checked
              )
            "
          />

          <span>Routine Maintenance Included</span>
        </label>
      </div>
    </section>

    <!-- Signatories -->
    <section class="form-section">
      <div class="section-heading">
        <div>
          <h2>Signatories & Witnesses</h2>
          <p>People who will appear in the signature section.</p>
        </div>
      </div>

      <div class="subsection">
        <h3>Vendor Representative</h3>

        <div class="form-grid">
          <div class="form-field">
            <label>Name</label>

            <input
              type="text"
              :value="form.vendor_signatory_name"
              @input="
                updateField(
                  'vendor_signatory_name',
                  $event.target.value
                )
              "
            />
          </div>

          <div class="form-field">
            <label>Designation</label>

            <input
              type="text"
              :value="form.vendor_signatory_designation"
              @input="
                updateField(
                  'vendor_signatory_designation',
                  $event.target.value
                )
              "
            />
          </div>

          <div class="form-field">
            <label>CNIC</label>

            <input
              type="text"
              :value="form.vendor_signatory_cnic"
              @input="
                updateField(
                  'vendor_signatory_cnic',
                  $event.target.value
                )
              "
            />
          </div>

          <div class="form-field">
            <label>Signature Date</label>

            <input
              type="date"
              :value="form.vendor_signature_date"
              @input="
                updateField(
                  'vendor_signature_date',
                  $event.target.value
                )
              "
            />
          </div>
        </div>
      </div>

      <div class="subsection">
        <h3>Customer Representative</h3>

        <div class="form-grid">
          <div class="form-field">
            <label>Name</label>

            <input
              type="text"
              :value="form.customer_signatory_name"
              @input="
                updateField(
                  'customer_signatory_name',
                  $event.target.value
                )
              "
            />
          </div>

          <div class="form-field">
            <label>Designation</label>

            <input
              type="text"
              :value="form.customer_signatory_designation"
              @input="
                updateField(
                  'customer_signatory_designation',
                  $event.target.value
                )
              "
            />
          </div>

          <div class="form-field">
            <label>CNIC</label>

            <input
              type="text"
              :value="form.customer_signatory_cnic"
              @input="
                updateField(
                  'customer_signatory_cnic',
                  $event.target.value
                )
              "
            />
          </div>

          <div class="form-field">
            <label>Signature Date</label>

            <input
              type="date"
              :value="form.customer_signature_date"
              @input="
                updateField(
                  'customer_signature_date',
                  $event.target.value
                )
              "
            />
          </div>
        </div>
      </div>

      <div class="subsection">
        <h3>Witnesses</h3>

        <div class="form-grid">
          <div class="form-field">
            <label>Witness 1 Name</label>

            <input
              type="text"
              :value="form.witness_1_name"
              @input="
                updateField(
                  'witness_1_name',
                  $event.target.value
                )
              "
            />
          </div>

          <div class="form-field">
            <label>Witness 1 CNIC</label>

            <input
              type="text"
              :value="form.witness_1_cnic"
              @input="
                updateField(
                  'witness_1_cnic',
                  $event.target.value
                )
              "
            />
          </div>

          <div class="form-field">
            <label>Witness 2 Name</label>

            <input
              type="text"
              :value="form.witness_2_name"
              @input="
                updateField(
                  'witness_2_name',
                  $event.target.value
                )
              "
            />
          </div>

          <div class="form-field">
            <label>Witness 2 CNIC</label>

            <input
              type="text"
              :value="form.witness_2_cnic"
              @input="
                updateField(
                  'witness_2_cnic',
                  $event.target.value
                )
              "
            />
          </div>
        </div>
      </div>
    </section>
  </div>
</template>


<style scoped>
.contract-form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.form-section {
  padding: 1.25rem;
  border: 1px solid var(--border-color, #e5e7eb);
  border-radius: 12px;
  background: var(--surface-color, #fff);
}

.section-heading {
  display: flex;
  justify-content: space-between;
  margin-bottom: 1rem;
}

.section-heading h2 {
  margin: 0;
  font-size: 1rem;
}

.section-heading p {
  margin: 0.25rem 0 0;
  font-size: 0.8rem;
  color: var(--text-muted, #6b7280);
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1rem;
}

.form-field {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.form-field-full {
  grid-column: 1 / -1;
}

.form-field label {
  font-size: 0.8rem;
  font-weight: 600;
}

.form-field input,
.form-field textarea,
.form-field select {
  width: 100%;
  border: 1px solid var(--border-color, #d1d5db);
  border-radius: 8px;
  padding: 0.65rem 0.75rem;
  background: var(--input-bg, #fff);
  color: inherit;
  font: inherit;
  box-sizing: border-box;
}

.form-field textarea {
  resize: vertical;
}

.input-with-suffix {
  display: flex;
  align-items: center;
  border: 1px solid var(--border-color, #d1d5db);
  border-radius: 8px;
  overflow: hidden;
}

.input-with-suffix input {
  border: 0;
  border-radius: 0;
}

.input-with-suffix span {
  padding: 0 0.75rem;
  white-space: nowrap;
  color: var(--text-muted, #6b7280);
  font-size: 0.8rem;
}

.calculated-value {
  min-height: 42px;
  display: flex;
  align-items: center;
  padding: 0 0.75rem;
  border-radius: 8px;
  background: var(--surface-muted, #f3f4f6);
  font-weight: 700;
}

.checkbox-field {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  min-height: 42px;
  font-size: 0.85rem;
}

.checkbox-field input {
  width: 16px;
  height: 16px;
}

.subsection {
  padding-top: 1rem;
  margin-top: 1rem;
  border-top: 1px solid var(--border-color, #e5e7eb);
}

.subsection h3 {
  margin: 0 0 1rem;
  font-size: 0.9rem;
}

@media (max-width: 768px) {
  .form-grid {
    grid-template-columns: 1fr;
  }

  .form-field-full {
    grid-column: auto;
  }
}
</style>