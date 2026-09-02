<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'

import VehicleContractForm from '@/components/vehicle-contracts/VehicleContractForm.vue'
import VehicleContractPreview from '@/components/vehicle-contracts/VehicleContractPreview.vue'
import { createVehicleContract } from '@/services/vehicleContractService'

const router = useRouter()

const saving = ref(false)
const error = ref('')

const createEmptyVehicle = () => ({
  vehicle_number: '',
})


const syncVehicles = (quantity) => {
  const count = Math.max(Number(quantity) || 1, 1)

  const current = Array.isArray(form.value.vehicles)
    ? form.value.vehicles
    : []

  const vehicles = Array.from(
    { length: count },
    (_, index) => current[index] ?? createEmptyVehicle()
  )

  form.value.vehicles = vehicles
}

const updateTotalVehicles = (value) => {
  const total = Math.max(Number(value) || 1, 1)

  form.value.total_vehicles = total

  form.value.total_monthly_rental =
    total *
    (Number(form.value.monthly_rental_per_vehicle) || 0)

  syncVehicles(total)
}

const form = ref({
  agreement_date: '2026-09-01',

  vendor_name: "M/s Dynamic Logistics Int'l Pvt Ltd",

  vendor_address:
    'Suite 405-406, Progressive Centre, 30-A Block 6, P.E.C.H.S, Karachi, Pakistan',

  customer_name: '',
  customer_address: '',
  customer_tin: '',

  end_date: '2027-08-31',
  duration_months: 12,

  service_type: 'with_driver',

  fuel_included: false,
  routine_maintenance_included: true,

  vehicles: [
  {
    vehicle_number: '',
  },
  {
    vehicle_number: '',
  },
],

  total_vehicles: 2,

  vehicle_make: 'Suzuki',
  vehicle_model: 'Ravi',
  vehicle_model_year: '2020 onwards',

  vehicle_type:
    'Plug-in Charging Refrigerated Container',

  monthly_rental_per_vehicle: 97000,
  total_monthly_rental: 194000,

  duty_hours_per_day: 10,
  duty_days_per_week: 6,

  public_holiday_rate: 5000,
  overtime_rate: 300,

  payment_terms: '10-15 days',
  advance_months: 1,

  insurance_claim_period_days: 45,

  monthly_mileage_limit: 2500,
  excess_mileage_rate: 50,

  refrigeration_customer_responsibility: true,

  early_termination_months: 3,

  vendor_signatory_name: '',
  vendor_signatory_designation: '',
  vendor_signatory_cnic: '',
  vendor_signature_date: '',

  customer_signatory_name: '',
  customer_signatory_designation: '',
  customer_signatory_cnic: '',
  customer_signature_date: '',

  witness_1_name: '',
  witness_1_cnic: '',

  witness_2_name: '',
  witness_2_cnic: '',

  status: 'draft',

  notes: '',
})

const save = async () => {
  saving.value = true
  error.value = ''

  try {
    const response = await createVehicleContract(form.value)

    const contract =
      response.data?.data ??
      response.data

    router.push({
      name: 'vehicle-contracts.show',
      params: {
        id: contract.id,
      },
    })
  } catch (err) {
    error.value =
      err.response?.data?.message ||
      'Unable to create the contract.'
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="contract-create-page">

    <header class="page-head">
      <div>
        <p class="eyebrow">
          Operations / Vehicle Contracts
        </p>

        <h1>New Vehicle Contract</h1>

        <p>
          Create a rental vehicle agreement and preview it live.
        </p>
      </div>

      <div class="page-actions">
        <RouterLink
          class="btn secondary"
          :to="{ name: 'vehicle-contracts.index' }"
        >
          Cancel
        </RouterLink>

        <button
          class="btn primary"
          type="button"
          :disabled="saving"
          @click="save"
        >
          {{ saving ? 'Saving…' : 'Save Contract' }}
        </button>
      </div>
    </header>

    <div
      v-if="error"
      class="form-error"
    >
      {{ error }}
    </div>

    <div class="contract-editor">

        
        
      <section class="contract-editor-preview">
        <div class="preview-header">
          <span>Live Preview</span>

          <span class="preview-status">
            Draft
          </span>
        </div>

        <VehicleContractPreview
          :contract="form"
        />
      </section>

      <section class="contract-editor-form">
          <VehicleContractForm
            v-model="form"
            @total-vehicles-change="updateTotalVehicles"
          />
        </section>

    </div>
  </div>
</template>

<style scoped>
.contract-create-page {
  min-width: 0;
}

.page-head {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
  margin-bottom: 1.25rem;
}

.page-actions {
  display: flex;
  gap: 0.6rem;
}

.contract-editor {
  display: grid;
  grid-template-columns: minmax(500px, 1.2fr) minmax(360px, 0.8fr);
  gap: 1rem;
  align-items: start;
}

.contract-editor-form {
  min-width: 0;
}

.contract-editor-preview {
  min-width: 0;
  position: sticky;
  top: 1rem;
  max-height: calc(100vh - 2rem);
  overflow: auto;
  border: 1px solid var(--border-color, #e5e7eb);
  border-radius: 12px;
  background: #f3f4f6;
}

.preview-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem 1rem;
  border-bottom: 1px solid #e5e7eb;
  background: #fff;
  font-size: 0.8rem;
  font-weight: 700;
  position: sticky;
  top: 0;
  z-index: 2;
}

.preview-status {
  font-size: 0.7rem;
  font-weight: 600;
  padding: 0.25rem 0.5rem;
  border-radius: 999px;
  background: #fef3c7;
  color: #92400e;
}

.form-error {
  margin-bottom: 1rem;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  background: #fee2e2;
  color: #991b1b;
}

@media (max-width: 1200px) {
  .contract-editor {
    grid-template-columns: 1fr;
  }

  .contract-editor-preview {
    position: static;
    max-height: none;
  }
}

@media (max-width: 768px) {
  .page-head {
    flex-direction: column;
  }

  .page-actions {
    width: 100%;
  }

  .page-actions .btn {
    flex: 1;
  }
}
</style>