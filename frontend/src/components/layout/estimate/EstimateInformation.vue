<script setup>
import { computed, onMounted, watch } from 'vue'
import { useCustomerStore } from '@/stores/customerStore'

const props = defineProps({
  form: { type: Object, required: true },
  estimateNumber: { type: String, default: '' },
})
const emit = defineEmits(['change-type'])

const customers = useCustomerStore()
const selectedCustomer = computed(() =>
  customers.customers.find((customer) => Number(customer.id) === Number(props.form.customer_id)),
)

const syncCustomerDetails = (customer = selectedCustomer.value) => {
  if (!customer) return
  props.form.details.customer_company = customer.company || ''
  props.form.details.customer_location = customer.address || ''
}

const requestTypeChange = (event) => {
  const selectedType = event.target.value
  emit('change-type', selectedType)
  if (props.form.estimate_type !== selectedType) {
    event.target.value = props.form.estimate_type
  }
}

watch(
  selectedCustomer,
  (customer) => {
    if (!props.form.details.customer_company && !props.form.details.customer_location) {
      syncCustomerDetails(customer)
    }
  },
  { immediate: true },
)

onMounted(() => customers.loadOptions())
</script>

<template>
  <div>
    <div class="section-heading">
      <h3>Estimate information</h3>
      <p>Choose the customer and the form that matches the work being quoted.</p>
    </div>

    <div class="grid">
      <div class="field">
        <label for="estimate-type">Estimate type</label>
        <select id="estimate-type" :value="form.estimate_type" @change="requestTypeChange">
          <option value="rental">Transportation / rent-a-car</option>
          <option value="transportation">Transportation job</option>
          <option value="repair">Vehicle repair & maintenance</option>
        </select>
      </div>

      <div class="field">
        <label for="estimate-customer">To / Customer</label>
        <select id="estimate-customer" v-model="form.customer_id" @change="syncCustomerDetails()">
          <option :value="null">Select customer</option>
          <option v-for="customer in customers.customers" :key="customer.id" :value="customer.id">
            {{ customer.name }}
          </option>
        </select>
      </div>

      <div class="field">
        <label for="estimate-company">Company</label>
        <input id="estimate-company" :value="selectedCustomer?.company || ''" readonly />
      </div>

      <div class="field">
        <label for="estimate-location">Customer location</label>
        <input id="estimate-location" :value="selectedCustomer?.address || ''" readonly />
      </div>

      <div class="field">
        <label for="estimate-date">Date</label>
        <input id="estimate-date" v-model="form.estimate_date" type="date" />
      </div>

      <div class="field">
        <label for="estimate-valid-until">Quotation valid until</label>
        <input id="estimate-valid-until" v-model="form.valid_until" type="date" />
      </div>

      <div class="field">
        <label for="estimate-number">Estimate No.</label>
        <input id="estimate-number" :value="estimateNumber || 'Generating…'" readonly />
        <small>Assigned automatically when saved.</small>
      </div>

      <template v-if="form.estimate_type === 'rental'">
        <div class="field">
          <label for="rental-job-name">Job / Project</label>
          <input id="rental-job-name" v-model="form.details.job_name" />
        </div>
        <div class="field">
          <label for="rental-vehicle">Vehicle required</label>
          <input
            id="rental-vehicle"
            v-model="form.details.vehicle_required"
            placeholder="e.g. Sedan"
          />
        </div>
        <div class="field">
          <label for="rental-model">Vehicle model</label>
          <input id="rental-model" v-model="form.details.vehicle_model" />
        </div>
        <div class="field">
          <label for="rental-quantity">Quantity</label>
          <input
            id="rental-quantity"
            v-model.number="form.details.quantity"
            type="number"
            min="1"
          />
        </div>
        <div class="field">
          <label for="rental-service-mode">Service type</label>
          <select id="rental-service-mode" v-model="form.details.service_mode">
            <option>With Driver</option>
            <option>Without Driver</option>
          </select>
        </div>
        <div class="field">
          <label for="rental-route">Route / Location</label>
          <input id="rental-route" v-model="form.details.route_location" />
        </div>
        <div class="field">
          <label for="rental-duty-hours">Duty hours</label>
          <input id="rental-duty-hours" v-model="form.details.duty_hours" />
        </div>
        <div class="field">
          <label for="rental-distance">Monthly / Daily KM</label>
          <input id="rental-distance" v-model="form.details.monthly_daily_km" />
        </div>
        <div class="field">
          <label for="rental-contract-period">Contract period</label>
          <input id="rental-contract-period" v-model="form.details.contract_period" />
        </div>
      </template>

      <template v-else-if="form.estimate_type === 'transportation'">
        <div class="field">
          <label for="transport-job-type">Job type</label>
          <select id="transport-job-type" v-model="form.details.job_type">
            <option value="goods">Goods transportation</option>
            <option value="staff">Staff transportation</option>
          </select>
        </div>
        <div class="field">
          <label for="transport-vehicle-type">Vehicle type</label>
          <input id="transport-vehicle-type" v-model="form.details.vehicle_type" />
        </div>
        <div class="field">
          <label for="transport-model">Vehicle model</label>
          <input id="transport-model" v-model="form.details.vehicle_model" />
        </div>
        <div class="field">
          <label for="transport-quantity">Vehicle quantity</label>
          <input
            id="transport-quantity"
            v-model.number="form.details.vehicle_quantity"
            type="number"
            min="1"
          />
        </div>
        <div class="field">
          <label for="transport-route">Route</label>
          <input id="transport-route" v-model="form.details.route" />
        </div>
        <div class="field">
          <label for="transport-distance">Daily running (KM)</label>
          <input
            id="transport-distance"
            v-model.number="form.details.daily_running_km"
            type="number"
            min="0"
          />
        </div>
        <div class="field">
          <label for="transport-duty-hours">Duty hours</label>
          <input id="transport-duty-hours" v-model="form.details.duty_hours" />
        </div>
        <div class="field">
          <label for="transport-working-days">Working days</label>
          <input
            id="transport-working-days"
            v-model.number="form.details.working_days"
            type="number"
            min="0"
          />
        </div>
        <div class="field">
          <label for="transport-contract-period">Contract period</label>
          <input id="transport-contract-period" v-model="form.details.contract_period" />
        </div>
      </template>

      <template v-else>
        <div class="field">
          <label for="repair-make">Vehicle make</label>
          <input id="repair-make" v-model="form.details.vehicle_make" />
        </div>
        <div class="field">
          <label for="repair-model">Vehicle model</label>
          <input id="repair-model" v-model="form.details.vehicle_model" />
        </div>
        <div class="field">
          <label for="repair-registration">Registration No.</label>
          <input id="repair-registration" v-model="form.details.registration_number" />
        </div>
        <div class="field">
          <label for="repair-engine">Engine No.</label>
          <input id="repair-engine" v-model="form.details.engine_number" />
        </div>
        <div class="field">
          <label for="repair-current-km">Current KM</label>
          <input
            id="repair-current-km"
            v-model.number="form.details.current_km"
            type="number"
            min="0"
          />
        </div>
        <div class="field">
          <label for="repair-job-location">Job location</label>
          <input id="repair-job-location" v-model="form.details.job_location" />
        </div>
        <div class="field">
          <label for="repair-completion">Estimated completion (working days)</label>
          <input
            id="repair-completion"
            v-model.number="form.details.estimated_completion_days"
            type="number"
            min="0"
          />
        </div>
      </template>
    </div>
  </div>
</template>

<style scoped>
.section-heading {
  margin-bottom: var(--space-4);
}
.section-heading h3 {
  font-size: 15px;
  font-weight: 600;
  margin: 0;
}
.section-heading p {
  color: var(--text-muted);
  font-size: 13px;
  margin: 2px 0 0;
}
.field label {
  align-items: center;
  display: flex;
  gap: 5px;
}
.field small {
  color: var(--text-muted);
  font-size: 11px;
}
.field input[readonly] {
  background: var(--surface-2);
  color: var(--text-secondary);
}
</style>
