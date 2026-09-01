<script setup>
import { ref, onMounted } from 'vue'

import { assetService } from '@/services/assetService'
import { money } from '@/utils/money'
import InfoTip from '@/components/ui/InfoTip.vue'

const props = defineProps({
  form: Object,
})

const CATEGORIES = [
  'Labor',
  'Transport',
  'Vehicle',
  'Fuel',
  'Machinery',
  'Agent',
  'Other',
]

/*
|--------------------------------------------------------------------------
| Company vehicles
|--------------------------------------------------------------------------
*/

const availableVehicles = ref([])
const vehiclesLoading = ref(false)
const vehiclesError = ref('')

const loadAvailableVehicles = async () => {
  vehiclesLoading.value = true
  vehiclesError.value = ''

  try {
    const response = await assetService.getAvailableVehicles()

    availableVehicles.value = response.data ?? response
  } catch (error) {
    console.error('Failed to load available vehicles:', error)

    vehiclesError.value =
      error.response?.data?.message ||
      'Unable to load available company vehicles.'
  } finally {
    vehiclesLoading.value = false
  }
}

/*
|--------------------------------------------------------------------------
| Vehicle requirement
|--------------------------------------------------------------------------
*/

const createVehicleRequirement = () => ({
  source: 'company',
  asset_id: null,

  // Used only for hired vehicles
  vehicle_name: '',
  make: '',
  model: '',
  model_year: '',
  registration_number: '',
  vin: '',
  engine_number: '',
  vehicle_type: '',
  color: '',
  notes: '',
})

/*
|--------------------------------------------------------------------------
| Keep vehicle requirements synchronized with quantity
|--------------------------------------------------------------------------
*/

const syncVehicleRequirements = (item) => {
  if (item.category !== 'Vehicle') {
    item.vehicles = []
    return
  }

  const quantity = Math.max(
    1,
    Number(item.quantity || 1)
  )

  if (!Array.isArray(item.vehicles)) {
    item.vehicles = []
  }

  /*
   * Add vehicle requirement records until
   * their count matches the requested quantity.
   */
  while (item.vehicles.length < quantity) {
    item.vehicles.push(
      createVehicleRequirement()
    )
  }

  /*
   * Remove extra vehicle requirement records
   * if quantity has been reduced.
   */
  if (item.vehicles.length > quantity) {
    item.vehicles.splice(quantity)
  }
}

/*
|--------------------------------------------------------------------------
| Category changed
|--------------------------------------------------------------------------
*/

const handleCategoryChange = (item) => {
  if (item.category === 'Vehicle') {
    syncVehicleRequirements(item)
  } else {
    item.vehicles = []
  }
}

/*
|--------------------------------------------------------------------------
| Item calculations
|--------------------------------------------------------------------------
*/

const calculateRow = (item) => {
  const quantity = Number(item.quantity || 0)
  const costPrice = Number(item.cost_price || 0)
  const sellPrice = Number(item.sell_price || 0)

  item.cost_total =
    quantity * costPrice

  item.sell_total =
    quantity * sellPrice

  item.profit =
    item.sell_total - item.cost_total

  /*
   * Vehicle quantity must always match
   * the number of vehicle requirement records.
   */
  if (item.category === 'Vehicle') {
    syncVehicleRequirements(item)
  }
}

/*
|--------------------------------------------------------------------------
| Add item
|--------------------------------------------------------------------------
*/

const addRow = () => {
  props.form.items.push({
    title: '',
    category: '',
    quantity: 1,
    cost_price: 0,
    sell_price: 0,
    cost_total: 0,
    sell_total: 0,
    profit: 0,
    remarks: '',

    vehicles: [],
  })
}

/*
|--------------------------------------------------------------------------
| Remove item
|--------------------------------------------------------------------------
*/

const removeRow = (index) => {
  if (props.form.items.length === 1) {
    return
  }

  props.form.items.splice(index, 1)
}

/*
|--------------------------------------------------------------------------
| Find selected company vehicle
|--------------------------------------------------------------------------
*/

const getSelectedVehicle = (vehicle) => {
  if (!vehicle.asset_id) {
    return null
  }

  return availableVehicles.value.find(
    asset => Number(asset.id) === Number(vehicle.asset_id)
  ) || null
}

/*
|--------------------------------------------------------------------------
| Load company vehicles
|--------------------------------------------------------------------------
*/

onMounted(() => {
  loadAvailableVehicles()
})
</script>

<template>
  <div class="estimate-items">

    <!-- ============================================================
         SECTION HEADING
    ============================================================= -->

    <div class="section-heading">
      <h3>Quoted items</h3>

      <p>
        Each line prices one piece of the job. Line profit updates as you type —
        the running totals sit in the panel on the right.
      </p>
    </div>


    <!-- ============================================================
         ESTIMATE ITEMS TABLE
    ============================================================= -->

    <div class="table-wrap">

      <table>

        <thead>
          <tr>
            <th class="col-title">
              Title
            </th>

            <th class="col-cat">
              Category
            </th>

            <th class="col-num">
              Qty
            </th>

            <th class="col-num">
              Cost / unit

              <InfoTip
                label="Your internal cost for one unit. Never shown to the customer."
              />
            </th>

            <th class="col-num">
              Sell / unit

              <InfoTip
                label="What the customer pays for one unit."
              />
            </th>

            <th class="col-num right">
              Line total
            </th>

            <th class="col-num right">
              Profit

              <InfoTip
                label="Line total minus your cost for the whole line."
              />
            </th>

            <th class="col-del"></th>
          </tr>
        </thead>


        <tbody>

          <!-- ======================================================
               IMPORTANT:
               template owns the v-for so both the item row and
               vehicle row have access to "item".
          ======================================================= -->

          <template
            v-for="(item, index) in form.items"
            :key="index"
          >

            <!-- ==================================================
                 NORMAL ESTIMATE ITEM
            =================================================== -->

            <tr>

              <td class="col-title">
                <input
                  v-model="item.title"
                  class="cell-input"
                  placeholder="Freight"
                />
              </td>


              <td class="col-cat">
                <select
                  v-model="item.category"
                  class="cell-input"
                  @change="handleCategoryChange(item)"
                >
                  <option value="">
                    Category
                  </option>

                  <option
                    v-for="category in CATEGORIES"
                    :key="category"
                    :value="category"
                  >
                    {{ category }}
                  </option>
                </select>
              </td>


              <td class="col-num">
                <input
                  type="number"
                  min="1"
                  v-model="item.quantity"
                  class="cell-input num"
                  @input="calculateRow(item)"
                />
              </td>


              <td class="col-num">
                <input
                  type="number"
                  min="0"
                  step="0.01"
                  v-model="item.cost_price"
                  class="cell-input num"
                  placeholder="0.00"
                  @input="calculateRow(item)"
                />
              </td>


              <td class="col-num">
                <input
                  type="number"
                  min="0"
                  step="0.01"
                  v-model="item.sell_price"
                  class="cell-input num"
                  placeholder="0.00"
                  @input="calculateRow(item)"
                />
              </td>


              <td class="col-num right cell-total">
                {{ money(item.sell_total) }}
              </td>


              <td
                class="col-num right cell-profit"
                :class="
                  Number(item.profit) < 0
                    ? 'money-loss'
                    : 'money-profit'
                "
              >
                {{ money(item.profit) }}
              </td>


              <td class="col-del">

                <button
                  type="button"
                  class="icon-remove"
                  :disabled="form.items.length === 1"
                  title="Remove line"
                  aria-label="Remove line"
                  @click="removeRow(index)"
                >
                  <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true"
                  >
                    <path d="M3 6h18" />

                    <path
                      d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"
                    />

                    <path
                      d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"
                    />
                  </svg>
                </button>

              </td>

            </tr>


            <!-- ==================================================
                 VEHICLE REQUIREMENTS
            =================================================== -->

            <tr
              v-if="item.category === 'Vehicle'"
              class="vehicle-details-row"
            >

              <td colspan="8">

                <div class="vehicle-details">

                  <!-- HEADER -->

                  <div class="vehicle-heading">

                    <div>
                      <h4>
                        Vehicle requirements
                      </h4>

                      <p>
                        Select how each required vehicle will be provided.
                      </p>
                    </div>

                    <span class="vehicle-count">
                      {{ item.vehicles?.length || 0 }}
                      vehicle{{
                        (item.vehicles?.length || 0) === 1
                          ? ''
                          : 's'
                      }}
                    </span>

                  </div>


                  <!-- LOADING -->

                  <div
                    v-if="vehiclesLoading"
                    class="vehicle-state"
                  >
                    Loading company vehicles...
                  </div>


                  <!-- ERROR -->

                  <div
                    v-else-if="vehiclesError"
                    class="vehicle-state vehicle-state-error"
                  >
                    {{ vehiclesError }}
                  </div>


                  <!-- VEHICLES -->

                  <div
                    v-else
                    v-for="(
                      vehicle,
                      vehicleIndex
                    ) in item.vehicles"
                    :key="vehicleIndex"
                    class="vehicle-card"
                  >

                    <!-- VEHICLE HEADER -->

                    <div class="vehicle-card-header">

                      <div class="vehicle-number">
                        Vehicle {{ vehicleIndex + 1 }}
                      </div>


                      <!-- SOURCE -->

                      <div class="vehicle-source">

                        <label>
                          <input
                            type="radio"
                            :name="
                              `vehicle-source-${index}-${vehicleIndex}`
                            "
                            value="company"
                            v-model="vehicle.source"
                            @change="
                              vehicle.asset_id = null
                            "
                          />

                          <span>
                            Company Own
                          </span>
                        </label>


                        <label>
                          <input
                            type="radio"
                            :name="
                              `vehicle-source-${index}-${vehicleIndex}`
                            "
                            value="hired"
                            v-model="vehicle.source"
                            @change="
                              vehicle.asset_id = null
                            "
                          />

                          <span>
                            Hired
                          </span>
                        </label>

                      </div>

                    </div>


                    <!-- ==================================================
                         COMPANY OWN
                    =================================================== -->

                    <div
                      v-if="vehicle.source === 'company'"
                      class="vehicle-content"
                    >

                      <div class="field">

                        <label>
                          Company Vehicle
                        </label>

                        <select
                          v-model="vehicle.asset_id"
                          class="cell-input"
                        >

                          <option :value="null">
                            Select company vehicle
                          </option>

                          <option
                            v-for="asset in availableVehicles"
                            :key="asset.id"
                            :value="asset.id"
                          >
                            {{ asset.name || 'Unnamed vehicle' }}

                            <template
                              v-if="
                                asset.registration_number
                              "
                            >
                              —
                              {{ asset.registration_number }}
                            </template>
                          </option>

                        </select>

                      </div>


                      <!-- SELECTED COMPANY VEHICLE -->

                      <div
                        v-if="
                          getSelectedVehicle(vehicle)
                        "
                        class="vehicle-preview"
                      >

                        <div class="vehicle-preview-title">
                          {{
                            getSelectedVehicle(vehicle)?.name
                            || 'Company Vehicle'
                          }}
                        </div>


                        <div class="vehicle-preview-grid">

                          <div>
                            <span>
                              Asset Code
                            </span>

                            <strong>
                              {{
                                getSelectedVehicle(vehicle)
                                  ?.asset_code || '—'
                              }}
                            </strong>
                          </div>


                          <div>
                            <span>
                              Registration
                            </span>

                            <strong>
                              {{
                                getSelectedVehicle(vehicle)
                                  ?.registration_number || '—'
                              }}
                            </strong>
                          </div>


                          <div>
                            <span>
                              Make
                            </span>

                            <strong>
                              {{
                                getSelectedVehicle(vehicle)
                                  ?.make || '—'
                              }}
                            </strong>
                          </div>


                          <div>
                            <span>
                              Model
                            </span>

                            <strong>
                              {{
                                getSelectedVehicle(vehicle)
                                  ?.model || '—'
                              }}
                            </strong>
                          </div>


                          <div>
                            <span>
                              Year
                            </span>

                            <strong>
                              {{
                                getSelectedVehicle(vehicle)
                                  ?.model_year || '—'
                              }}
                            </strong>
                          </div>


                          <div>
                            <span>
                              Vehicle Type
                            </span>

                            <strong>
                              {{
                                getSelectedVehicle(vehicle)
                                  ?.vehicle_type || '—'
                              }}
                            </strong>
                          </div>


                          <div>
                            <span>
                              Color
                            </span>

                            <strong>
                              {{
                                getSelectedVehicle(vehicle)
                                  ?.color || '—'
                              }}
                            </strong>
                          </div>

                        </div>

                      </div>

                    </div>


                    <!-- ==================================================
                         HIRED VEHICLE
                    =================================================== -->

                    <div
                      v-else
                      class="vehicle-content"
                    >

                      <div class="vehicle-form-grid">

                        <div class="field">
                          <label>
                            Vehicle Name
                          </label>

                          <input
                            v-model="vehicle.vehicle_name"
                            class="cell-input"
                            placeholder="e.g. Hired Truck"
                          />
                        </div>


                        <div class="field">
                          <label>
                            Make
                          </label>

                          <input
                            v-model="vehicle.make"
                            class="cell-input"
                            placeholder="Toyota"
                          />
                        </div>


                        <div class="field">
                          <label>
                            Model
                          </label>

                          <input
                            v-model="vehicle.model"
                            class="cell-input"
                            placeholder="Hilux"
                          />
                        </div>


                        <div class="field">
                          <label>
                            Model Year
                          </label>

                          <input
                            type="number"
                            v-model="vehicle.model_year"
                            class="cell-input"
                            placeholder="2024"
                          />
                        </div>


                        <div class="field">
                          <label>
                            Registration Number
                          </label>

                          <input
                            v-model="vehicle.registration_number"
                            class="cell-input"
                            placeholder="ABC-123"
                          />
                        </div>


                        <div class="field">
                          <label>
                            VIN / Chassis Number
                          </label>

                          <input
                            v-model="vehicle.vin"
                            class="cell-input"
                            placeholder="VIN"
                          />
                        </div>


                        <div class="field">
                          <label>
                            Engine Number
                          </label>

                          <input
                            v-model="vehicle.engine_number"
                            class="cell-input"
                            placeholder="Engine number"
                          />
                        </div>


                        <div class="field">
                          <label>
                            Vehicle Type
                          </label>

                          <input
                            v-model="vehicle.vehicle_type"
                            class="cell-input"
                            placeholder="Pickup / Truck"
                          />
                        </div>


                        <div class="field">
                          <label>
                            Color
                          </label>

                          <input
                            v-model="vehicle.color"
                            class="cell-input"
                            placeholder="White"
                          />
                        </div>


                        <div class="field field-full">
                          <label>
                            Notes
                          </label>

                          <textarea
                            v-model="vehicle.notes"
                            class="cell-input"
                            rows="2"
                            placeholder="Additional vehicle details..."
                          ></textarea>
                        </div>

                      </div>

                    </div>

                  </div>

                </div>

              </td>

            </tr>

          </template>

        </tbody>

      </table>

    </div>


    <!-- ============================================================
         ADD ITEM
    ============================================================= -->

    <div class="items-actions">

      <button
        type="button"
        class="btn-light btn-sm"
        @click="addRow"
      >
        + Add item
      </button>

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

.table-wrap table {
  min-width: 860px;
}

th.col-title {
  min-width: 180px;
}

th.col-cat {
  min-width: 130px;
}

th.col-num,
td.col-num {
  min-width: 110px;
}

th.right {
  text-align: right;
}

.cell-input {
  border-radius: 8px;
  font-size: 13px;
  min-height: 36px;
  padding: 6px 9px;
}

.cell-input.num {
  font-variant-numeric: tabular-nums;
  text-align: right;
}

.cell-total {
  color: var(--text-primary);
  font-weight: 600;
}

.cell-profit {
  font-weight: 600;
}

.col-del {
  width: 44px;
}

.icon-remove {
  align-items: center;
  background: transparent;
  border: 1px solid transparent;
  border-radius: 8px;
  color: var(--text-muted);
  cursor: pointer;
  display: inline-flex;
  height: 32px;
  justify-content: center;
  transition:
    background 0.15s ease,
    color 0.15s ease;
  width: 32px;
}

.icon-remove svg {
  height: 14px;
  width: 14px;
}

.icon-remove:hover:not(:disabled) {
  background: var(--danger-soft);
  color: var(--danger);
}

.icon-remove:disabled {
  cursor: not-allowed;
  opacity: 0.35;
}

.items-actions {
  display: flex;
  margin-top: var(--space-3);
}


/* ================================================================
   VEHICLE REQUIREMENTS
================================================================ */

.vehicle-details-row > td {
  border-top: 0;
  padding: 0;
}

.vehicle-details {
  background: var(--surface-muted, #f8fafc);
  border: 1px solid var(--border-color, #e5e7eb);
  border-radius: 10px;
  margin: 0 0 var(--space-3);
  padding: var(--space-4);
}

.vehicle-heading {
  align-items: center;
  display: flex;
  gap: 16px;
  justify-content: space-between;
  margin-bottom: var(--space-3);
}

.vehicle-heading h4 {
  font-size: 14px;
  font-weight: 600;
  margin: 0;
}

.vehicle-heading p {
  color: var(--text-muted);
  font-size: 12px;
  margin: 3px 0 0;
}

.vehicle-count {
  background: var(--accent-soft);
  border-radius: 999px;
  color: var(--accent);
  font-size: 12px;
  font-weight: 600;
  padding: 5px 9px;
  white-space: nowrap;
}

.vehicle-state {
  color: var(--text-muted);
  font-size: 12px;
  padding: 12px;
}

.vehicle-state-error {
  color: var(--danger);
}


/* ================================================================
   VEHICLE CARD
================================================================ */

.vehicle-card {
  background: var(--surface, #fff);
  border: 1px solid var(--border-color, #e5e7eb);
  border-radius: 10px;
  margin-top: 10px;
  overflow: hidden;
}

.vehicle-card-header {
  align-items: center;
  background: var(--surface-muted, #f8fafc);
  border-bottom: 1px solid var(--border-color, #e5e7eb);
  display: flex;
  gap: 16px;
  justify-content: space-between;
  padding: 10px 12px;
}

.vehicle-number {
  font-size: 13px;
  font-weight: 600;
}

.vehicle-source {
  align-items: center;
  display: flex;
  gap: 14px;
}

.vehicle-source label {
  align-items: center;
  cursor: pointer;
  display: flex;
  font-size: 12px;
  gap: 6px;
}

.vehicle-source input {
  margin: 0;
}

.vehicle-content {
  padding: 12px;
}


/* ================================================================
   HIRED VEHICLE FORM
================================================================ */

.vehicle-form-grid {
  display: grid;
  gap: 10px;
  grid-template-columns: repeat(
    3,
    minmax(0, 1fr)
  );
}

.field {
  min-width: 0;
}

.field label {
  display: block;
  font-size: 11px;
  font-weight: 600;
  margin-bottom: 5px;
}

.field-full {
  grid-column: 1 / -1;
}


/* ================================================================
   COMPANY VEHICLE PREVIEW
================================================================ */

.vehicle-preview {
  background: var(--surface-muted, #f8fafc);
  border: 1px solid var(--border-color, #e5e7eb);
  border-radius: 8px;
  margin-top: 10px;
  padding: 12px;
}

.vehicle-preview-title {
  font-size: 13px;
  font-weight: 600;
  margin-bottom: 10px;
}

.vehicle-preview-grid {
  display: grid;
  gap: 10px;
  grid-template-columns: repeat(
    4,
    minmax(0, 1fr)
  );
}

.vehicle-preview-grid span {
  color: var(--text-muted);
  display: block;
  font-size: 10px;
  margin-bottom: 2px;
}

.vehicle-preview-grid strong {
  font-size: 12px;
  font-weight: 600;
}


/* ================================================================
   RESPONSIVE
================================================================ */

@media (max-width: 900px) {
  .vehicle-form-grid {
    grid-template-columns: repeat(
      2,
      minmax(0, 1fr)
    );
  }

  .vehicle-preview-grid {
    grid-template-columns: repeat(
      2,
      minmax(0, 1fr)
    );
  }
}

@media (max-width: 600px) {
  .vehicle-card-header {
    align-items: flex-start;
    flex-direction: column;
  }

  .vehicle-source {
    flex-wrap: wrap;
  }

  .vehicle-form-grid,
  .vehicle-preview-grid {
    grid-template-columns: 1fr;
  }

  .field-full {
    grid-column: auto;
  }
}
</style>