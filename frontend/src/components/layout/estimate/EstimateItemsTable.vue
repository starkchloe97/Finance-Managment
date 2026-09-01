<script setup>
import { reactive, ref, onMounted } from 'vue'

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
| Collapsed / expanded vehicle panels
| We keep a reactive Set of item references so expansion state survives
| row removal/reordering (unlike an index-keyed map).
|--------------------------------------------------------------------------
*/

const expanded = reactive(new Set())

const isExpanded = (item) => expanded.has(item)

const toggleExpand = (item) => {
  if (expanded.has(item)) {
    expanded.delete(item)
  } else {
    expanded.add(item)
  }
}

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

  while (item.vehicles.length < quantity) {
    item.vehicles.push(
      createVehicleRequirement()
    )
  }

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
    expanded.add(item)
  } else {
    item.vehicles = []
    expanded.delete(item)
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

  item.cost_total = quantity * costPrice
  item.sell_total = quantity * sellPrice
  item.profit = item.sell_total - item.cost_total

  if (item.category === 'Vehicle') {
    syncVehicleRequirements(item)
  }
}

/*
|--------------------------------------------------------------------------
| Add / remove items
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

const removeRow = (index) => {
  if (props.form.items.length === 1) {
    return
  }

  const [removed] = props.form.items.splice(index, 1)
  expanded.delete(removed)
}

/*
|--------------------------------------------------------------------------
| Company vehicle helpers
|--------------------------------------------------------------------------
*/

const getSelectedVehicle = (vehicle) => {
  if (!vehicle.asset_id) {
    return null
  }

  return availableVehicles.value.find(
    (asset) => Number(asset.id) === Number(vehicle.asset_id)
  ) || null
}

/*
| A company vehicle can only fulfill one requirement per line —
* options already claimed by a sibling requirement are disabled.
*/

const isVehicleTaken = (assetId, item, vehicleIndex) =>
  (item.vehicles || []).some(
    (v, i) =>
      i !== vehicleIndex &&
      v.asset_id !== null &&
      Number(v.asset_id) === Number(assetId)
  )

const vehicleOptionLabel = (asset, item, vehicleIndex) => {
  let label = asset.name || 'Unnamed vehicle'

  if (asset.registration_number) {
    label += ` — ${asset.registration_number}`
  }

  if (isVehicleTaken(asset.id, item, vehicleIndex)) {
    label += ' (in use)'
  }

  return label
}

const vehicleSummary = (item) => {
  const vehicles = Array.isArray(item.vehicles) ? item.vehicles : []

  const specified = vehicles.filter((v) =>
    v.source === 'company'
      ? v.asset_id
      : (v.vehicle_name || v.registration_number)
  ).length

  return { total: vehicles.length, specified }
}

const vehicleCardTitle = (vehicle, index) => {
  if (vehicle.source === 'company') {
    const found = availableVehicles.value.find(
      (a) => Number(a.id) === Number(vehicle.asset_id)
    )

    return found?.name || `Vehicle ${index + 1}`
  }

  return vehicle.vehicle_name || `Vehicle ${index + 1}`
}

const setSource = (vehicle, source) => {
  if (vehicle.source === source) {
    return
  }

  vehicle.source = source

  // Matches the old radio behaviour: switching clears the company pick.
  vehicle.asset_id = null
}

/*
|--------------------------------------------------------------------------
| Init
|--------------------------------------------------------------------------
*/

onMounted(() => {
  loadAvailableVehicles()

  /*
   * Normalize loaded items: make sure vehicle requirements exist for
   * Vehicle lines, and auto-expand lines that already carry data.
   */
  props.form.items.forEach((item) => {
    if (item.category === 'Vehicle') {
      syncVehicleRequirements(item)

      const hasData = (item.vehicles || []).some(
        (v) => v.asset_id || v.vehicle_name
      )

      if (hasData) {
        expanded.add(item)
      }
    }
  })
})
</script>

<template>
  <div class="estimate-items">

    <div class="section-heading">
      <h3>Quoted items</h3>

      <p>
        Each line prices one piece of the job. Line profit updates as you
        type — the running totals sit in the panel on the right.
      </p>
    </div>


    <!-- Column captions for the compact (wide) layout.
         Hidden on narrow layouts, where each field shows its own label. -->
    <div class="items-header" aria-hidden="true">
      <span>Title</span>
      <span>Category</span>
      <span>Qty</span>

      <span>
        Cost / unit
        <InfoTip label="Your internal cost for one unit. Never shown to the customer." />
      </span>

      <span>
        Sell / unit
        <InfoTip label="What the customer pays for one unit." />
      </span>

      <span class="h-right">Line total</span>

      <span class="h-right">
        Profit
        <InfoTip label="Line total minus your cost for the whole line." />
      </span>
    </div>


    <div class="items-list">

      <section
        v-for="(item, index) in form.items"
        :key="index"
        class="line-card"
      >

        <!-- Line summary + actions -->
        <div class="line-head">

          <div class="line-head-left">
            <span class="line-number">
              Line {{ index + 1 }}
            </span>

            <span
              v-if="item.category === 'Vehicle'"
              class="line-chip"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M5 18H3c-.6 0-1-.4-1-1V7c0-.6.4-1 1-1h10c.6 0 1 .4 1 1v11" />
                <path d="M14 9h4l4 4v4c0 .6-.4 1-1 1h-2" />
                <circle cx="7.5" cy="17.5" r="2.5" />
                <circle cx="17.5" cy="17.5" r="2.5" />
              </svg>

              {{ vehicleSummary(item).specified }}/{{ vehicleSummary(item).total }}
              specified
            </span>
          </div>


          <div class="line-head-actions">

            <button
              v-if="item.category === 'Vehicle'"
              type="button"
              class="expand-toggle"
              :aria-expanded="isExpanded(item)"
              @click="toggleExpand(item)"
            >
              <svg
                class="chevron"
                :class="{ 'is-open': isExpanded(item) }"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                aria-hidden="true"
              >
                <path d="m6 9 6 6 6-6" />
              </svg>

              <span>Vehicle requirements</span>

              <span class="expand-count">
                {{ item.vehicles?.length || 0 }}
              </span>
            </button>


            <button
              type="button"
              class="icon-remove"
              :disabled="form.items.length === 1"
              title="Remove line"
              aria-label="Remove line"
              @click="removeRow(index)"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M3 6h18" />
                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" />
                <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
              </svg>
            </button>

          </div>

        </div>


        <!-- Inputs -->
        <div class="line-grid">

          <label class="cell c-title">
            <span class="cell-label">Title</span>
            <input
              v-model="item.title"
              class="cell-input"
              placeholder="Freight"
            />
          </label>


          <label class="cell c-cat">
            <span class="cell-label">Category</span>
            <select
              v-model="item.category"
              class="cell-input"
              @change="handleCategoryChange(item)"
            >
              <option value="">Category</option>

              <option
                v-for="category in CATEGORIES"
                :key="category"
                :value="category"
              >
                {{ category }}
              </option>
            </select>
          </label>


          <label class="cell c-qty">
            <span class="cell-label">Qty</span>
            <input
              type="number"
              min="1"
              v-model="item.quantity"
              class="cell-input num"
              @input="calculateRow(item)"
            />
          </label>


          <label class="cell c-cost">
            <span class="cell-label">
              Cost / unit
              <InfoTip label="Your internal cost for one unit. Never shown to the customer." />
            </span>
            <input
              type="number"
              min="0"
              step="0.01"
              v-model="item.cost_price"
              class="cell-input num"
              placeholder="0.00"
              @input="calculateRow(item)"
            />
          </label>


          <label class="cell c-sell">
            <span class="cell-label">
              Sell / unit
              <InfoTip label="What the customer pays for one unit." />
            </span>
            <input
              type="number"
              min="0"
              step="0.01"
              v-model="item.sell_price"
              class="cell-input num"
              placeholder="0.00"
              @input="calculateRow(item)"
            />
          </label>


          <div class="cell c-total">
            <span class="cell-label">Line total</span>
            <span class="cell-value">
              {{ money(item.sell_total) }}
            </span>
          </div>


          <div class="cell c-profit">
            <span class="cell-label">
              Profit
              <InfoTip label="Line total minus your cost for the whole line." />
            </span>
            <span
              class="cell-value"
              :class="Number(item.profit) < 0 ? 'money-loss' : 'money-profit'"
            >
              {{ money(item.profit) }}
            </span>
          </div>

        </div>


        <!-- Vehicle requirements -->
        <Transition name="reveal">
          <div
            v-if="item.category === 'Vehicle' && isExpanded(item)"
            class="vehicle-panel-wrap"
          >
            <div class="vehicle-panel">

              <div class="vehicle-panel-head">
                <div>
                  <h4>Vehicle requirements</h4>
                  <p>
                    One requirement per unit — change the Qty above to
                    add or remove vehicles.
                  </p>
                </div>

                <span
                  class="vehicle-count"
                  :class="{
                    'is-complete':
                      vehicleSummary(item).total > 0 &&
                      vehicleSummary(item).specified === vehicleSummary(item).total
                  }"
                >
                  {{ vehicleSummary(item).specified }} of
                  {{ vehicleSummary(item).total }} specified
                </span>
              </div>


              <!-- Asset list states (non-blocking: hired entry still works) -->
              <div v-if="vehiclesLoading" class="vehicle-state">
                <span class="mini-spinner" aria-hidden="true"></span>
                Loading company vehicles…
              </div>

              <div v-else-if="vehiclesError" class="vehicle-state vehicle-state-error">
                <span>{{ vehiclesError }}</span>

                <button
                  type="button"
                  class="retry-btn"
                  @click="loadAvailableVehicles"
                >
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M21 12a9 9 0 1 1-2.64-6.36L21 8" />
                    <path d="M21 3v5h-5" />
                  </svg>
                  Retry
                </button>
              </div>

              <div v-else-if="!availableVehicles.length" class="vehicle-state">
                No company vehicles available right now — hired vehicles
                can still be entered below.
              </div>


              <!-- Requirement cards -->
              <article
                v-for="(vehicle, vehicleIndex) in item.vehicles"
                :key="vehicleIndex"
                class="vehicle-card"
              >
                <header class="vehicle-card-head">
                  <div class="vehicle-identity">
                    <span class="vehicle-index">
                      {{ vehicleIndex + 1 }}
                    </span>

                    <span class="vehicle-title">
                      {{ vehicleCardTitle(vehicle, vehicleIndex) }}
                    </span>
                  </div>

                  <div
                    class="seg"
                    role="group"
                    :aria-label="`Source for vehicle ${vehicleIndex + 1}`"
                  >
                    <button
                      type="button"
                      class="seg-option"
                      :class="{ 'is-active': vehicle.source === 'company' }"
                      @click="setSource(vehicle, 'company')"
                    >
                      Company own
                    </button>

                    <button
                      type="button"
                      class="seg-option"
                      :class="{ 'is-active': vehicle.source === 'hired' }"
                      @click="setSource(vehicle, 'hired')"
                    >
                      Hired
                    </button>
                  </div>
                </header>


                <div class="vehicle-card-body">

                  <!-- Company own -->
                  <div v-if="vehicle.source === 'company'" class="company-pane">
                    <label class="field">
                      <span class="field-label">Company vehicle</span>

                      <select
                        v-model="vehicle.asset_id"
                        class="cell-input"
                        :disabled="vehiclesLoading"
                      >
                        <option :value="null">Select company vehicle…</option>

                        <option
                          v-for="asset in availableVehicles"
                          :key="asset.id"
                          :value="asset.id"
                          :disabled="isVehicleTaken(asset.id, item, vehicleIndex)"
                        >
                          {{ vehicleOptionLabel(asset, item, vehicleIndex) }}
                        </option>
                      </select>
                    </label>


                    <div
                      v-if="getSelectedVehicle(vehicle)"
                      class="vehicle-preview"
                    >
                      <div class="vehicle-preview-head">
                        <span class="vehicle-preview-name">
                          {{ getSelectedVehicle(vehicle)?.name || 'Company vehicle' }}
                        </span>

                        <span
                          v-if="getSelectedVehicle(vehicle)?.registration_number"
                          class="mono-chip"
                        >
                          {{ getSelectedVehicle(vehicle).registration_number }}
                        </span>
                      </div>

                      <dl class="vehicle-preview-grid">
                        <div>
                          <dt>Asset code</dt>
                          <dd>{{ getSelectedVehicle(vehicle)?.asset_code || '—' }}</dd>
                        </div>

                        <div>
                          <dt>Make</dt>
                          <dd>{{ getSelectedVehicle(vehicle)?.make || '—' }}</dd>
                        </div>

                        <div>
                          <dt>Model</dt>
                          <dd>{{ getSelectedVehicle(vehicle)?.model || '—' }}</dd>
                        </div>

                        <div>
                          <dt>Year</dt>
                          <dd>{{ getSelectedVehicle(vehicle)?.model_year || '—' }}</dd>
                        </div>

                        <div>
                          <dt>Type</dt>
                          <dd>{{ getSelectedVehicle(vehicle)?.vehicle_type || '—' }}</dd>
                        </div>

                        <div>
                          <dt>Color</dt>
                          <dd>{{ getSelectedVehicle(vehicle)?.color || '—' }}</dd>
                        </div>
                      </dl>
                    </div>

                    <p v-else class="vehicle-hint">
                      Select a vehicle to see its details.
                    </p>
                  </div>


                  <!-- Hired -->
                  <div v-else class="vehicle-form-grid">
                    <label class="field">
                      <span class="field-label">Vehicle name</span>
                      <input
                        v-model="vehicle.vehicle_name"
                        class="cell-input"
                        placeholder="e.g. Hired truck"
                      />
                    </label>

                    <label class="field">
                      <span class="field-label">Make</span>
                      <input
                        v-model="vehicle.make"
                        class="cell-input"
                        placeholder="Toyota"
                      />
                    </label>

                    <label class="field">
                      <span class="field-label">Model</span>
                      <input
                        v-model="vehicle.model"
                        class="cell-input"
                        placeholder="Hilux"
                      />
                    </label>

                    <label class="field">
                      <span class="field-label">Model year</span>
                      <input
                        type="number"
                        v-model="vehicle.model_year"
                        class="cell-input num"
                        placeholder="2024"
                      />
                    </label>

                    <label class="field">
                      <span class="field-label">Registration number</span>
                      <input
                        v-model="vehicle.registration_number"
                        class="cell-input"
                        placeholder="ABC-123"
                      />
                    </label>

                    <label class="field">
                      <span class="field-label">VIN / Chassis number</span>
                      <input
                        v-model="vehicle.vin"
                        class="cell-input"
                        placeholder="VIN"
                      />
                    </label>

                    <label class="field">
                      <span class="field-label">Engine number</span>
                      <input
                        v-model="vehicle.engine_number"
                        class="cell-input"
                        placeholder="Engine number"
                      />
                    </label>

                    <label class="field">
                      <span class="field-label">Vehicle type</span>
                      <input
                        v-model="vehicle.vehicle_type"
                        class="cell-input"
                        placeholder="Pickup / Truck"
                      />
                    </label>

                    <label class="field">
                      <span class="field-label">Color</span>
                      <input
                        v-model="vehicle.color"
                        class="cell-input"
                        placeholder="White"
                      />
                    </label>

                    <label class="field field-full">
                      <span class="field-label">Notes</span>
                      <textarea
                        v-model="vehicle.notes"
                        class="cell-input"
                        rows="2"
                        placeholder="Additional vehicle details…"
                      ></textarea>
                    </label>
                  </div>

                </div>
              </article>

            </div>
          </div>
        </Transition>

      </section>

    </div>


    <div class="items-actions">
      <button type="button" class="btn-light btn-sm" @click="addRow">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true">
          <path d="M12 5v14M5 12h14" />
        </svg>
        Add item
      </button>
    </div>

  </div>
</template>

<style scoped>
/*
 * The root is a size container: the line layout adapts to the REAL
 * available width (the totals panel takes 300px on desktop, so viewport
 * media queries would lie). Browsers without container-query support
 * simply keep the stacked labeled layout below, which works everywhere.
 */
.estimate-items {
  --line-columns:
    minmax(140px, 1.9fr) 118px 56px
    minmax(92px, 1fr) minmax(92px, 1fr)
    minmax(92px, 1fr) minmax(92px, 1fr);

  container-type: inline-size;
  min-width: 0;
}

.section-heading {
  margin-bottom: var(--space-4, 16px);
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

/* ---------- line cards ---------- */

.items-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.line-card {
  background: var(--surface, #fff);
  border: 1px solid var(--border-color, #e5e7eb);
  border-radius: 10px;
  padding: 12px;
}

.line-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  flex-wrap: wrap;
  margin-bottom: 10px;
}

.line-head-left {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
  min-width: 0;
}

.line-number {
  font-size: 11px;
  font-weight: 700;
  letter-spacing: .05em;
  text-transform: uppercase;
  color: var(--text-muted);
}

.line-chip {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  background: var(--accent-soft, #eef2ff);
  color: var(--accent, #4f46e5);
  border-radius: 999px;
  padding: 3px 9px;
  font-size: 11px;
  font-weight: 650;
  white-space: nowrap;
}

.line-chip svg {
  width: 12px;
  height: 12px;
  flex: 0 0 auto;
}

.line-head-actions {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-left: auto;
}

.expand-toggle {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  border: 1px solid var(--border-color, #e5e7eb);
  background: var(--surface, #fff);
  color: var(--text-muted);
  border-radius: 8px;
  padding: 5px 10px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: background .15s ease, color .15s ease;
}

.expand-toggle:hover {
  background: var(--surface-muted, #f8fafc);
  color: var(--text-primary);
}

.expand-toggle:focus-visible {
  outline: 2px solid currentColor;
  outline-offset: 1px;
}

.expand-toggle .chevron {
  width: 13px;
  height: 13px;
  transition: transform .18s ease;
}

.expand-toggle .chevron.is-open {
  transform: rotate(180deg);
}

.expand-count {
  background: var(--surface-muted, #f8fafc);
  border-radius: 999px;
  padding: 1px 7px;
  font-size: 11px;
  font-weight: 700;
  color: var(--text-muted);
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
  transition: background .15s ease, color .15s ease;
  width: 32px;
  flex: 0 0 auto;
}

.icon-remove svg {
  height: 14px;
  width: 14px;
}

.icon-remove:hover:not(:disabled) {
  background: var(--danger-soft, #fdecec);
  color: var(--danger, #c23b3b);
}

.icon-remove:disabled {
  cursor: not-allowed;
  opacity: .35;
}

.icon-remove:focus-visible {
  outline: 2px solid currentColor;
  outline-offset: 1px;
}

/* ---------- line inputs ---------- */

/*
 * Base layout: labeled, fully fluid 12-column grid. Kicks in below 860px
 * of container width (and in browsers without container-query support).
 */

.items-header {
  display: none;
}

.line-grid {
  display: grid;
  grid-template-columns: repeat(12, minmax(0, 1fr));
  gap: 10px 8px;
}

.cell {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 0;
}

.cell-label {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 11px;
  font-weight: 600;
  color: var(--text-muted);
}

.c-title { grid-column: span 12; }
.c-cat   { grid-column: span 6; }
.c-qty   { grid-column: span 6; }

.c-cost,
.c-sell,
.c-total,
.c-profit { grid-column: span 3; }

.cell-input {
  border-radius: 8px;
  font-size: 13px;
  min-height: 36px;
  padding: 6px 9px;
  width: 100%;
}

.cell-input.num {
  font-variant-numeric: tabular-nums;
  text-align: right;
}

.cell-value {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  min-height: 36px;
  padding: 0 4px;
  font-size: 13px;
  font-weight: 600;
  color: var(--text-primary);
  font-variant-numeric: tabular-nums;
}

.money-loss   { color: var(--danger, #c23b3b); }
.money-profit { color: var(--success, #18864b); }

/* Slightly narrower containers: two numeric fields per row */
@container (max-width: 520px) {
  .c-cost,
  .c-sell,
  .c-total,
  .c-profit { grid-column: span 6; }
}

/*
 * Compact layout for wide containers: single row per line with shared
 * column captions, mirroring the old table look — without the table.
 */
@container (min-width: 860px) {
  .items-header {
    display: grid;
    grid-template-columns: var(--line-columns);
    gap: 8px;
    align-items: center;
    padding: 0 13px 9px;
  }

  .items-header span {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .06em;
    text-transform: uppercase;
    color: var(--text-muted);
  }

  .items-header .h-right {
    justify-content: flex-end;
  }

  .line-grid {
    grid-template-columns: var(--line-columns);
    gap: 8px;
  }

  .c-title,
  .c-cat,
  .c-qty,
  .c-cost,
  .c-sell,
  .c-total,
  .c-profit {
    grid-column: auto;
  }

  /* Captions live in the header row; per-field labels become sr-only */
  .cell-label {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0 0 0 0);
    white-space: nowrap;
    border: 0;
  }
}

/* ---------- vehicle requirements panel ---------- */

.vehicle-panel-wrap {
  display: grid;
  grid-template-rows: 1fr;
}

.vehicle-panel {
  margin-top: 12px;
  padding: 12px;
  background: var(--surface-muted, #f8fafc);
  border: 1px solid var(--border-color, #e5e7eb);
  border-radius: 9px;
  min-height: 0;
}

.vehicle-panel-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
  margin-bottom: 10px;
}

.vehicle-panel-head h4 {
  font-size: 13px;
  font-weight: 600;
  margin: 0;
}

.vehicle-panel-head p {
  color: var(--text-muted);
  font-size: 11.5px;
  margin: 2px 0 0;
}

.vehicle-count {
  background: var(--accent-soft, #eef2ff);
  color: var(--accent, #4f46e5);
  border-radius: 999px;
  padding: 4px 10px;
  font-size: 11px;
  font-weight: 650;
  white-space: nowrap;
}

.vehicle-count.is-complete {
  background: var(--success-soft, #eaf8ef);
  color: var(--success, #18864b);
}

.vehicle-state {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
  padding: 6px 2px;
  font-size: 12px;
  color: var(--text-muted);
}

.vehicle-state-error {
  color: var(--danger, #c23b3b);
}

.mini-spinner {
  width: 14px;
  height: 14px;
  border: 2px solid var(--border-color, #e5e7eb);
  border-top-color: var(--text-muted);
  border-radius: 50%;
  animation: spin .7s linear infinite;
  flex: 0 0 auto;
}

.retry-btn {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  border: 1px solid var(--border-color, #e5e7eb);
  background: var(--surface, #fff);
  border-radius: 7px;
  padding: 4px 10px;
  font-size: 12px;
  font-weight: 600;
  color: inherit;
  cursor: pointer;
}

.retry-btn svg {
  width: 12px;
  height: 12px;
}

/* ---------- vehicle requirement cards ---------- */

.vehicle-card {
  background: var(--surface, #fff);
  border: 1px solid var(--border-color, #e5e7eb);
  border-radius: 9px;
  margin-top: 10px;
}

.vehicle-card-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
  padding: 10px 12px;
  background: var(--surface-muted, #f8fafc);
  border-bottom: 1px solid var(--border-color, #e5e7eb);
}

.vehicle-identity {
  display: flex;
  align-items: center;
  gap: 9px;
  min-width: 0;
}

.vehicle-index {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  border-radius: 7px;
  background: var(--accent-soft, #eef2ff);
  color: var(--accent, #4f46e5);
  font-size: 11.5px;
  font-weight: 700;
  flex: 0 0 auto;
}

.vehicle-title {
  font-size: 13px;
  font-weight: 600;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* Source switch (matches the segmented filters used elsewhere) */

.seg {
  display: inline-flex;
  gap: 2px;
  padding: 3px;
  border: 1px solid var(--border-color, #e5e7eb);
  border-radius: 9px;
  background: var(--surface, #fff);
}

.seg-option {
  border: 0;
  background: transparent;
  border-radius: 7px;
  padding: 5px 11px;
  font-size: 12px;
  font-weight: 600;
  color: var(--text-muted);
  cursor: pointer;
  transition: background .15s ease, color .15s ease;
}

.seg-option.is-active {
  background: var(--surface-muted, #f8fafc);
  color: var(--text-primary);
}

.seg-option:focus-visible {
  outline: 2px solid currentColor;
  outline-offset: 1px;
}

.vehicle-card-body {
  padding: 12px;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 5px;
  min-width: 0;
}

.field-label {
  font-size: 11px;
  font-weight: 600;
  color: var(--text-muted);
}

.vehicle-hint {
  margin: 8px 0 0;
  font-size: 12px;
  color: var(--text-muted);
}

/* Company preview */

.vehicle-preview {
  margin-top: 10px;
  border: 1px solid var(--border-color, #e5e7eb);
  border-radius: 8px;
  background: var(--surface, #fff);
  overflow: hidden;
}

.vehicle-preview-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  flex-wrap: wrap;
  padding: 9px 12px;
  background: var(--surface-muted, #f8fafc);
  border-bottom: 1px solid var(--border-color, #e5e7eb);
}

.vehicle-preview-name {
  font-size: 12.5px;
  font-weight: 600;
}

.mono-chip {
  font-family: ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, monospace;
  font-size: 11.5px;
  letter-spacing: .03em;
  padding: 2px 8px;
  border: 1px solid var(--border-color, #e5e7eb);
  border-radius: 6px;
  background: var(--surface, #fff);
}

.vehicle-preview-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
  gap: 10px 14px;
  margin: 0;
  padding: 11px 12px;
}

.vehicle-preview-grid dt {
  font-size: 10px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: .05em;
  color: var(--text-muted);
  margin-bottom: 2px;
}

.vehicle-preview-grid dd {
  margin: 0;
  font-size: 12.5px;
  font-weight: 600;
}

/* Hired form: fluid columns, no fixed breakpoints needed */

.vehicle-form-grid {
  display: grid;
  gap: 10px;
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
}

.field-full {
  grid-column: 1 / -1;
}

/* ---------- add item ---------- */

.items-actions {
  display: flex;
  margin-top: var(--space-3, 12px);
}

.items-actions .btn-light {
  align-items: center;
  display: inline-flex;
  gap: 6px;
}

.items-actions svg {
  width: 13px;
  height: 13px;
}

/* ---------- expand/collapse transition ---------- */

.reveal-enter-active,
.reveal-leave-active {
  transition: grid-template-rows .22s ease, opacity .2s ease;
}

.reveal-enter-active .vehicle-panel,
.reveal-leave-active .vehicle-panel {
  min-height: 0;
  overflow: hidden;
}

.reveal-enter-from,
.reveal-leave-to {
  grid-template-rows: 0fr;
  opacity: 0;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>