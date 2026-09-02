<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import VehicleContractForm from '@/components/vehicle-contracts/VehicleContractForm.vue'
import VehicleContractPreview from '@/components/vehicle-contracts/VehicleContractPreview.vue'

import {
  getVehicleContract,
  updateVehicleContract,
} from '@/services/vehicleContractService'

const route = useRoute()
const router = useRouter()

const loading = ref(true)
const saving = ref(false)
const error = ref('')

const form = ref({
  agreement_date: '',
  vendor_name: '',
  vendor_address: '',

  customer_name: '',
  customer_address: '',
  customer_tin: '',

  end_date: '',
  duration_months: 12,

  service_type: 'with_driver',

  fuel_included: false,
  routine_maintenance_included: true,

  total_vehicles: 1,

  vehicle_make: '',
  vehicle_model: '',
  vehicle_model_year: '',

  vehicle_type: '',

  monthly_rental_per_vehicle: 0,
  total_monthly_rental: 0,

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

const assignContract = (contract) => {
  Object.keys(form.value).forEach((key) => {
    if (contract[key] !== undefined) {
      form.value[key] = contract[key]
    }
  })
}

const loadContract = async () => {
  loading.value = true
  error.value = ''

  try {
    const response = await getVehicleContract(route.params.id)

    const contract =
      response.data?.data ??
      response.data

    if (!contract?.id) {
      throw new Error('Contract not found.')
    }

    assignContract(contract)
  } catch (err) {
    error.value =
      err.response?.data?.message ||
      err.message ||
      'Unable to load the contract.'
  } finally {
    loading.value = false
  }
}

const save = async () => {
  saving.value = true
  error.value = ''

  try {
    const response = await updateVehicleContract(
      route.params.id,
      form.value
    )

    const contract =
      response.data?.data ??
      response.data

    router.push({
      name: 'vehicle-contracts.show',
      params: {
        id: contract.id || route.params.id,
      },
    })
  } catch (err) {
    error.value =
      err.response?.data?.message ||
      'Unable to update the contract.'
  } finally {
    saving.value = false
  }
}

onMounted(loadContract)
</script>

<template>
  <div class="contract-edit-page">

    <!-- Loading -->
    <div
      v-if="loading"
      class="loading-state"
    >
      Loading contract...
    </div>

    <template v-else>

      <header class="page-head">

        <div>
          <p class="eyebrow">
            Operations / Vehicle Contracts
          </p>

          <h1>
            Edit Vehicle Contract
          </h1>

          <p>
            Update the rental agreement and preview the changes live.
          </p>
        </div>

        <div class="page-actions">

          <RouterLink
            class="btn secondary"
            :to="{
              name: 'vehicle-contracts.show',
              params: {
                id: route.params.id,
              },
            }"
          >
            Cancel
          </RouterLink>

          <button
            class="btn primary"
            type="button"
            :disabled="saving"
            @click="save"
          >
            {{ saving ? 'Saving…' : 'Save Changes' }}
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

        <!-- Form -->
        <section class="contract-editor-form">

          <VehicleContractForm
            v-model="form"
          />

        </section>


        <!-- Preview -->
        <section class="contract-editor-preview">

          <div class="preview-header">

            <span>
              Live Preview
            </span>

            <span class="preview-status">
              {{ form.status || 'Draft' }}
            </span>

          </div>

          <VehicleContractPreview
            :contract="form"
          />

        </section>

      </div>

    </template>

  </div>
</template>

<style scoped>
.contract-edit-page {
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
  grid-template-columns:
    minmax(360px, 0.8fr)
    minmax(500px, 1.2fr);
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
  text-transform: capitalize;
}

.form-error {
  margin-bottom: 1rem;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  background: #fee2e2;
  color: #991b1b;
}

.loading-state {
  padding: 4rem;
  text-align: center;
  color: var(--text-muted, #6b7280);
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