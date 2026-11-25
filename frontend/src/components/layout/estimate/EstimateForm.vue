<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { createEstimate, updateEstimate, getNextEstimateNumber } from '@/services/estimateService'
import EstimateInformation from './EstimateInformation.vue'
import EstimateItemsTable from './EstimateItemsTable.vue'
import RepairEstimateItems from './RepairEstimateItems.vue'
import InfoTip from '@/components/ui/InfoTip.vue'
import { money } from '@/utils/money'

const router = useRouter()
const route = useRoute()
const props = defineProps({
  estimate: { type: Object, default: null },
})

const saving = ref(false)
const loading = ref(false)
const errors = ref({})
const notice = ref('')
const estimateNumber = ref('')

const rentalTerms = `Rates are subject to the agreed vehicle type and duty requirements.
Fuel will be charged as per actual consumption / agreed terms.
Additional kilometres and overtime will be charged separately.
Toll tax, parking and government charges are borne by the customer unless included.
Work outside the agreed scope will be charged separately.
Quotation validity: 15 days.`

const transportationTerms = `Fuel basis: actual consumption or as included in this estimate.
Additional kilometres and overtime will be charged at the agreed rates.
Vehicle replacement is subject to availability.
Toll tax, parking and government charges are borne by the customer unless included.
Work outside the agreed scope will be charged separately.
Quotation validity: 15 days.`

const repairTerms = `This estimate is based on the initial inspection of the vehicle.
Any additional repair or replacement will be communicated for approval.
Work will commence after customer approval.
Additional parts or labour requirements will be charged separately.
Actual cost may vary depending on the vehicle's condition.
Estimate validity: 7 days.`

const termsFor = (type) =>
  type === 'repair' ? repairTerms : type === 'rental' ? rentalTerms : transportationTerms

const defaultValidity = (type, from = new Date()) => {
  const date = new Date(from)
  date.setDate(date.getDate() + (type === 'repair' ? 7 : 15))
  return date.toISOString().slice(0, 10)
}

const makeDetails = (type) => {
  if (type === 'rental') {
    return {
      customer_company: '',
      customer_location: '',
      job_name: '',
      vehicle_required: '',
      vehicle_model: '',
      quantity: 1,
      service_mode: 'With Driver',
      route_location: '',
      duty_hours: '',
      monthly_daily_km: '',
      contract_period: '',
    }
  }

  if (type === 'transportation') {
    return {
      customer_company: '',
      customer_location: '',
      job_type: 'goods',
      vehicle_type: '',
      vehicle_model: '',
      vehicle_quantity: 1,
      route: '',
      daily_running_km: '',
      duty_hours: '',
      working_days: '',
      contract_period: '',
    }
  }

  return {
    customer_company: '',
    customer_location: '',
    vehicle_make: '',
    vehicle_model: '',
    registration_number: '',
    engine_number: '',
    current_km: '',
    job_location: '',
    estimated_completion_days: '',
    workshop_charges: 0,
    towing_charges: 0,
    other_expenses: 0,
  }
}

const line = (title = '', category = 'Other') => ({
  title,
  category,
  quantity: 1,
  cost_price: 0,
  sell_price: 0,
  parts_cost: 0,
  labor_cost: 0,
  cost_total: 0,
  sell_total: 0,
  profit: 0,
  remarks: '',
  vehicles: [],
})

const defaultItems = (type) => {
  const titles =
    type === 'rental'
      ? [
          ['Vehicle Rental Charges', 'Vehicle Rental'],
          ['Driver Salary / Charges', 'Labor'],
          ['Fuel Charges', 'Fuel'],
          ['Maintenance & Repair', 'Maintenance'],
          ['Toll Tax / Parking', 'Toll / Parking'],
          ['Other Expenses', 'Other'],
        ]
      : type === 'transportation'
        ? [
            ['Vehicle Rental / Depreciation', 'Vehicle Rental'],
            ['Fuel Expenses', 'Fuel'],
            ['Driver Salary', 'Labor'],
            ['Driver Food / Accommodation', 'Driver Food'],
            ['Vehicle Maintenance', 'Maintenance'],
            ['Tyres & Lubricants', 'Tyres & Lubricants'],
            ['Insurance & Tracker', 'Insurance & Tracker'],
            ['Route Permit / Taxes', 'Route Permit'],
            ['Toll Tax / Parking', 'Toll / Parking'],
            ['Other Expenses', 'Other'],
            ['Profit / Service Charges', 'Service Charges'],
          ]
        : [
            ['Engine / Mechanical Work', 'Repair'],
            ['Suspension Work', 'Repair'],
            ['Brake System', 'Repair'],
            ['AC System', 'Repair'],
            ['Electrical Work', 'Repair'],
            ['Tyres / Wheel Alignment', 'Repair'],
            ['Oil & Filter Replacement', 'Repair'],
            ['Other Repairs', 'Repair'],
          ]

  return titles.map(([title, category]) => line(title, category))
}

const form = reactive({
  customer_id: null,
  estimate_type: 'rental',
  estimate_date: new Date().toISOString().slice(0, 10),
  valid_until: defaultValidity('rental'),
  pickup: '',
  destination: '',
  service_type: 'vehicle',
  tonnage: '',
  details: makeDetails('rental'),
  terms: rentalTerms,
  payment_terms: '',
  tax_rate: 0,
  prepared_by: '',
  checked_by: '',
  approved_by: '',
  customer_approval: '',
  status: 'draft',
  remarks: '',
  items: defaultItems('rental'),
})

const isEdit = computed(() => Boolean(props.estimate))
const additionalCharges = computed(() => {
  if (form.estimate_type !== 'repair') return 0
  const details = form.details
  return ['workshop_charges', 'towing_charges', 'other_expenses'].reduce(
    (total, field) => total + Number(details[field] || 0),
    0,
  )
})
const sum = (field) => form.items.reduce((total, item) => total + Number(item[field] || 0), 0)
const totalCost = computed(() => sum('cost_total') + additionalCharges.value)
const subtotal = computed(() => sum('sell_total') + additionalCharges.value)
const taxAmount = computed(() => Math.round(subtotal.value * Number(form.tax_rate || 0)) / 100)
const grandTotal = computed(() => subtotal.value + taxAmount.value)
const totalProfit = computed(() => subtotal.value - totalCost.value)
const margin = computed(() =>
  subtotal.value > 0 ? ((totalProfit.value / subtotal.value) * 100).toFixed(1) : null,
)
const errorMessages = computed(() => Object.values(errors.value).flat())

const hasEnteredData = () =>
  form.items.some(
    (item) => Number(item.cost_price || 0) > 0 || Number(item.sell_price || 0) > 0 || item.remarks,
  ) ||
  Object.entries(form.details).some(
    ([key, value]) =>
      ![
        'customer_company',
        'customer_location',
        'job_type',
        'service_mode',
        'quantity',
        'vehicle_quantity',
        'workshop_charges',
        'towing_charges',
        'other_expenses',
      ].includes(key) &&
      value !== '' &&
      value !== null &&
      value !== 0,
  ) ||
  form.remarks ||
  form.payment_terms ||
  form.prepared_by ||
  form.checked_by ||
  form.approved_by ||
  form.customer_approval ||
  Number(form.tax_rate || 0) > 0 ||
  form.status !== 'draft' ||
  form.terms !== termsFor(form.estimate_type) ||
  form.valid_until !== defaultValidity(form.estimate_type)

const changeType = (type) => {
  if (type === form.estimate_type) return
  if (
    hasEnteredData() &&
    !window.confirm(
      'Changing the estimate type replaces its line items and type-specific details. Continue?',
    )
  )
    return

  form.estimate_type = type
  form.service_type = type === 'rental' || type === 'repair' ? 'vehicle' : 'goods'
  form.details = {
    ...makeDetails(type),
    customer_company: form.details.customer_company || '',
    customer_location: form.details.customer_location || '',
  }
  form.items = defaultItems(type)
  form.terms = termsFor(type)
  form.valid_until = defaultValidity(type)
}

onMounted(async () => {
  if (!props.estimate) {
    if (route.query.customer_id) form.customer_id = Number(route.query.customer_id)
    try {
      const { data } = await getNextEstimateNumber()
      estimateNumber.value = data.code
    } catch (error) {
      notice.value = error.response?.data?.message || 'Estimate number preview could not be loaded.'
    }
    return
  }

  loading.value = true
  try {
    const estimate = props.estimate
    estimateNumber.value = estimate.code
    Object.assign(form, {
      customer_id: estimate.customer_id,
      estimate_type: estimate.estimate_type || 'transportation',
      estimate_date: String(estimate.estimate_date || '').slice(0, 10),
      valid_until: String(estimate.valid_until || '').slice(0, 10) || '',
      pickup: estimate.pickup || '',
      destination: estimate.destination || '',
      service_type: estimate.service_type || 'goods',
      tonnage: estimate.tonnage || '',
      details: {
        ...makeDetails(estimate.estimate_type || 'transportation'),
        ...(estimate.details || {}),
      },
      terms: estimate.terms || termsFor(estimate.estimate_type || 'transportation'),
      payment_terms: estimate.payment_terms || '',
      tax_rate: Number(estimate.tax_rate || 0),
      prepared_by: estimate.prepared_by || '',
      checked_by: estimate.checked_by || '',
      approved_by: estimate.approved_by || '',
      customer_approval: estimate.customer_approval || '',
      status: estimate.status || 'draft',
      remarks: estimate.remarks || '',
      items: estimate.items?.length
        ? estimate.items.map((item) => ({
            ...line(item.title, item.category),
            ...item,
            quantity: Number(item.quantity),
            cost_price: Number(item.cost_price),
            sell_price: Number(item.sell_price),
            cost_total: Number(item.cost_total),
            sell_total: Number(item.sell_total),
            profit: Number(item.profit),
            parts_cost: Number(item.parts_cost || 0),
            labor_cost: Number(item.labor_cost || 0),
            remarks: item.remarks || '',
            vehicles:
              item.vehicles?.map((vehicle) => ({
                source: vehicle.source || 'company',
                asset_id: vehicle.asset_id || null,
                vehicle_name: vehicle.vehicle_name || '',
                supplier_name: vehicle.supplier_name || '',
                make: vehicle.make || '',
                model: vehicle.model || '',
                model_year: vehicle.model_year || '',
                registration_number: vehicle.registration_number || '',
                vin: vehicle.vin || '',
                engine_number: vehicle.engine_number || '',
                vehicle_type: vehicle.vehicle_type || '',
                color: vehicle.color || '',
                notes: vehicle.notes || '',
              })) || [],
          }))
        : defaultItems(estimate.estimate_type || 'transportation'),
    })
  } catch (error) {
    notice.value = error.response?.data?.message || 'Could not load estimate.'
  } finally {
    loading.value = false
  }
})

const save = async () => {
  saving.value = true
  errors.value = {}
  notice.value = ''

  try {
    const response = isEdit.value
      ? await updateEstimate(props.estimate.id, form)
      : await createEstimate(form)
    const savedEstimate = response.data.data

    if (savedEstimate.transport_job?.id) {
      router.push(`/jobs/${savedEstimate.transport_job.id}`)
    } else {
      router.push('/estimates')
    }
  } catch (error) {
    errors.value = error.response?.data?.errors || {}
    if (!Object.keys(errors.value).length) {
      notice.value = error.response?.data?.message || 'Could not save estimate.'
    }
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="estimate-form-page">
    <div class="page-head">
      <div>
        <span class="section-kicker">Operations / Estimates</span>
        <h1>{{ isEdit ? 'Edit estimate' : 'New estimate' }}</h1>
        <p class="page-sub">Create a transport, rent-a-car, or repair quotation for a customer.</p>
      </div>
    </div>

    <form class="estimate-form" @submit.prevent="save">
      <div v-if="notice && !loading" class="form-error" role="alert">{{ notice }}</div>
      <div v-if="errorMessages.length && !loading" class="form-error" role="alert">
        <strong>Check the estimate details:</strong>
        <ul>
          <li v-for="(message, index) in errorMessages" :key="index">{{ message }}</li>
        </ul>
      </div>

      <div v-if="loading" class="state-panel state-loading">
        <div class="skeleton-block"></div>
      </div>

      <template v-else>
        <div class="form-layout">
          <div class="form-main">
            <section class="card form-section">
              <EstimateInformation
                :form="form"
                :estimate-number="estimateNumber"
                @change-type="changeType"
              />
            </section>

            <section class="card form-section">
              <RepairEstimateItems v-if="form.estimate_type === 'repair'" :form="form" />
              <EstimateItemsTable v-else :form="form" :estimate-type="form.estimate_type" />
            </section>

            <section class="card form-section">
              <div class="section-heading">
                <h3>Commercial terms & approval</h3>
                <p>These terms are stored with the quotation and shown with its totals.</p>
              </div>

              <div class="review-grid">
                <div class="field">
                  <label for="estimate-payment-terms">Payment terms</label>
                  <input
                    id="estimate-payment-terms"
                    v-model="form.payment_terms"
                    placeholder="e.g. Due on completion"
                  />
                </div>
                <div class="field">
                  <label for="estimate-tax-rate">GST / applicable tax (%)</label>
                  <input
                    id="estimate-tax-rate"
                    v-model.number="form.tax_rate"
                    type="number"
                    min="0"
                    max="100"
                    step="0.01"
                  />
                </div>
                <div v-if="form.estimate_type === 'repair'" class="field">
                  <label for="repair-workshop-charges">Workshop charges (PKR)</label>
                  <input
                    id="repair-workshop-charges"
                    v-model.number="form.details.workshop_charges"
                    type="number"
                    min="0"
                    step="0.01"
                  />
                </div>
                <div v-if="form.estimate_type === 'repair'" class="field">
                  <label for="repair-towing-charges">Transportation / towing (PKR)</label>
                  <input
                    id="repair-towing-charges"
                    v-model.number="form.details.towing_charges"
                    type="number"
                    min="0"
                    step="0.01"
                  />
                </div>
                <div v-if="form.estimate_type === 'repair'" class="field">
                  <label for="repair-other-expenses">Other expenses (PKR)</label>
                  <input
                    id="repair-other-expenses"
                    v-model.number="form.details.other_expenses"
                    type="number"
                    min="0"
                    step="0.01"
                  />
                </div>
                <div class="field">
                  <label for="estimate-prepared-by">Prepared by</label>
                  <input id="estimate-prepared-by" v-model="form.prepared_by" />
                </div>
                <div class="field">
                  <label for="estimate-checked-by">Checked by</label>
                  <input id="estimate-checked-by" v-model="form.checked_by" />
                </div>
                <div class="field">
                  <label for="estimate-approved-by">Approved by</label>
                  <input id="estimate-approved-by" v-model="form.approved_by" />
                </div>
                <div class="field">
                  <label for="estimate-customer-approval">Customer approval</label>
                  <input id="estimate-customer-approval" v-model="form.customer_approval" />
                </div>
                <div class="field field-wide">
                  <label for="estimate-terms">Terms & conditions</label>
                  <textarea id="estimate-terms" v-model="form.terms" rows="7"></textarea>
                </div>
                <div class="field field-wide">
                  <label for="estimate-remarks">
                    Additional customer notes
                    <InfoTip
                      label="Add quote-specific notes here. Internal notes do not belong on a customer estimate."
                    />
                  </label>
                  <textarea
                    id="estimate-remarks"
                    v-model="form.remarks"
                    placeholder="Additional details for the customer"
                  ></textarea>
                </div>
                <div v-if="isEdit" class="field field-wide">
                  <label for="estimate-status">
                    Status
                    <InfoTip
                      label="Approving an estimate automatically creates and links its transport job."
                    />
                  </label>
                  <select id="estimate-status" v-model="form.status">
                    <option value="draft">Draft — still writing</option>
                    <option value="sent">Sent — with the customer</option>
                    <option value="accepted">Approved — create job</option>
                    <option value="rejected">Rejected — customer declined</option>
                    <option value="expired">Expired — validity passed</option>
                  </select>
                </div>
              </div>
            </section>
          </div>

          <aside class="form-aside">
            <div class="card panel-card">
              <h2 class="panel-title">Estimate total</h2>
              <p class="panel-hint">PKR · calculated from the line items above.</p>

              <div class="panel-row">
                <span
                  >Our cost <InfoTip label="Internal cost total, including repair extras."
                /></span>
                <strong>{{ money(totalCost) }}</strong>
              </div>
              <div class="panel-row">
                <span>Subtotal</span>
                <strong>{{ money(subtotal) }}</strong>
              </div>
              <div class="panel-row">
                <span>GST / tax ({{ Number(form.tax_rate || 0).toFixed(2) }}%)</span>
                <strong>{{ money(taxAmount) }}</strong>
              </div>
              <div class="panel-row panel-grand">
                <span>Grand total</span>
                <strong>{{ money(grandTotal) }}</strong>
              </div>
              <div class="panel-row panel-margin">
                <span>Estimated profit</span>
                <strong :class="totalProfit < 0 ? 'money-loss' : 'money-profit'">
                  {{ money(totalProfit) }}
                </strong>
              </div>

              <div class="panel-meta">
                {{ form.items.length }} {{ form.items.length === 1 ? 'line item' : 'line items' }}
                <span v-if="form.valid_until">· valid through {{ form.valid_until }}</span>
              </div>

              <div class="panel-actions">
                <button type="submit" :disabled="saving" :aria-busy="saving">
                  {{
                    saving
                      ? 'Saving…'
                      : form.status === 'accepted'
                        ? 'Approve & create job'
                        : isEdit
                          ? 'Save changes'
                          : 'Save estimate'
                  }}
                </button>
                <RouterLink
                  class="btn-light"
                  :to="isEdit ? `/estimates/${props.estimate.id}` : '/estimates'"
                >
                  Cancel
                </RouterLink>
              </div>
            </div>
          </aside>
        </div>
      </template>
    </form>
  </div>
</template>

<style scoped>
.estimate-form-page,
.estimate-form {
  min-width: 0;
}
.page-sub {
  color: var(--text-secondary);
  font-size: 14px;
  margin-top: var(--space-2);
}
.form-error {
  background: var(--danger-soft);
  border: 1px solid var(--danger);
  border-radius: var(--radius-md);
  color: var(--danger);
  margin-bottom: var(--space-4);
  padding: var(--space-3) var(--space-4);
}
.form-error ul {
  margin: 6px 0 0;
  padding-left: 20px;
}
.form-layout {
  align-items: start;
  display: grid;
  gap: 20px;
  grid-template-columns: minmax(0, 1fr) 300px;
}
.form-main {
  display: flex;
  flex-direction: column;
  gap: 20px;
  min-width: 0;
}
.form-aside {
  position: sticky;
  top: 20px;
}
.panel-card {
  padding: 18px;
}
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
.panel-row:last-of-type {
  border-bottom: 0;
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
.panel-row.panel-grand strong.money-loss {
  color: var(--danger);
}
.panel-meta {
  color: var(--text-muted);
  font-size: 12px;
  margin-top: 6px;
}
.panel-actions {
  border-top: 1px solid var(--border);
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-top: 14px;
  padding-top: 14px;
}
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
.review-grid {
  display: grid;
  gap: var(--space-4);
  grid-template-columns: repeat(2, minmax(0, 1fr));
}
.field label {
  align-items: center;
  display: flex;
  gap: 5px;
}
.field-wide {
  grid-column: 1 / -1;
}
@media (max-width: 1024px) {
  .form-layout {
    grid-template-columns: 1fr;
  }
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
  .panel-hint {
    display: none;
  }
  .panel-row {
    border-bottom: 0;
    display: inline-flex;
    font-size: 13px;
    padding: 0;
  }
  .panel-row span {
    font-size: 11px;
  }
  .panel-row strong {
    font-size: 14px;
  }
  .panel-row.panel-grand strong {
    font-size: 15px;
  }
  .panel-row.panel-margin,
  .panel-meta {
    display: none;
  }
  .panel-actions {
    border-top: 0;
    flex-direction: row;
    margin-top: 10px;
    padding-top: 0;
  }
  .panel-actions .btn,
  .panel-actions .btn-light {
    flex: 1;
  }
}
@media (max-width: 640px) {
  .review-grid {
    grid-template-columns: 1fr;
  }
  .field-wide {
    grid-column: auto;
  }
}
</style>
