<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { createEstimate, updateEstimate, getEstimate } from '@/services/estimateService'
import EstimateInformation from './EstimateInformation.vue'
import EstimateItemsTable from './EstimateItemsTable.vue'
import InfoTip from '@/components/ui/InfoTip.vue'
import { money } from '@/utils/money'

const router = useRouter()
const route = useRoute()

// When editing an existing quote, its id is present; otherwise this is the
// create form.
const props = defineProps({
  estimate: { type: Object, default: null },
})

const saving = ref(false)
const loading = ref(false)
const errors = ref({})
const notice = ref('')

const line = () => ({
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

const form = reactive({
  customer_id: null,
  estimate_date: new Date().toISOString().substring(0, 10),
  valid_until: '',
  pickup: '',
  destination: '',
  service_type: 'goods',
  status: 'draft',
  remarks: '',
  items: [line()],
})

onMounted(async () => {
  if (!props.estimate) {
    // Pre-fill the customer from the ?customer_id= query param.
    const customerId = route.query.customer_id
    if (customerId) form.customer_id = Number(customerId)
    return
  }

  loading.value = true
  try {
    const estimate = props.estimate
    Object.assign(form, {
      customer_id: estimate.customer_id,
      estimate_date: String(estimate.estimate_date || '').slice(0, 10),
      valid_until: String(estimate.valid_until || '').slice(0, 10) || '',
      pickup: estimate.pickup || '',
      destination: estimate.destination || '',
      service_type: estimate.service_type || 'goods',
      status: estimate.status || 'draft',
      remarks: estimate.remarks || '',
      items: estimate.items?.length
        ? estimate.items.map((item) => ({
            title: item.title,
            category: item.category,
            quantity: Number(item.quantity),
            cost_price: Number(item.cost_price),
            sell_price: Number(item.sell_price),
            cost_total: Number(item.cost_total),
            sell_total: Number(item.sell_total),
            profit: Number(item.profit),
            remarks: item.remarks || '',
            vehicles: item.vehicles?.map((v) => ({
            source: v.source || 'company',
            asset_id: v.asset_id || null,
            vehicle_name: v.vehicle_name || '',
            make: v.make || '',
            model: v.model || '',
            model_year: v.model_year || '',
            registration_number: v.registration_number || '',
            vin: v.vin || '',
            engine_number: v.engine_number || '',
            vehicle_type: v.vehicle_type || '',
            color: v.color || '',
            notes: v.notes || '',
            })) || [],
          }))
        : [line()],
    })
  } catch (e) {
    notice.value = e.response?.data?.message || 'Could not load estimate.'
  } finally {
    loading.value = false
  }
})

const sum = (field) => form.items.reduce((total, item) => total + Number(item[field] || 0), 0)

const totalCost = computed(() => sum('cost_total'))
const totalSell = computed(() => sum('sell_total'))
const totalProfit = computed(() => totalSell.value - totalCost.value)
const margin = computed(() =>
  totalSell.value > 0 ? ((totalProfit.value / totalSell.value) * 100).toFixed(1) : null,
)

const isEdit = computed(() => Boolean(props.estimate))

const save = async () => {
  saving.value = true
  errors.value = {}
  notice.value = ''

  try {
    if (isEdit.value) {
      await updateEstimate(props.estimate.id, form)
    } else {
      await createEstimate(form)
    }
    router.push('/estimates')
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
        <p class="page-sub">Build a customer quote that can become a transport job.</p>
      </div>
    </div>

    <form class="estimate-form" @submit.prevent="save">
      <div v-if="notice && !loading" class="form-error" role="alert">{{ notice }}</div>

      <div v-if="loading" class="state-panel state-loading">
        <div class="skeleton-block"></div>
      </div>

      <template v-else>
        <div class="form-layout">
          <!-- ===== Main column ===== -->
          <div class="form-main">
            <section class="card form-section">
              <EstimateInformation :form="form" />
            </section>

            <section class="card form-section">
              <EstimateItemsTable :form="form" />
            </section>

            <section class="card form-section">
              <div class="section-heading">
                <h3>Review and send</h3>
                <p>Set the quote status and add anything the customer should see.</p>
              </div>

              <div class="review-grid">
                <div class="field">
                  <label for="estimate-status">
                    Status
                    <InfoTip label="Draft is private. Sent means the customer has it. Accepted quotes can become jobs." />
                  </label>
                  <select id="estimate-status" v-model="form.status">
                    <option value="draft">Draft — still writing</option>
                    <option value="sent">Sent — with the customer</option>
                    <option value="accepted">Accepted — customer said yes</option>
                    <option value="rejected">Rejected — customer declined</option>
                    <option value="expired">Expired — valid-until passed</option>
                  </select>
                </div>

                <div class="field field-wide">
                  <label for="estimate-remarks">
                    Customer-facing remarks
                    <InfoTip label="These notes appear on the quote the customer sees. Keep internal notes out of here." />
                  </label>
                  <textarea
                    id="estimate-remarks"
                    v-model="form.remarks"
                    placeholder="Anything the customer should see on the quote"
                  ></textarea>
                </div>
              </div>
            </section>
          </div>

          <!-- ===== Sticky totals panel ===== -->
          <aside class="form-aside">
            <div class="card panel-card">
              <h2 class="panel-title">Live totals</h2>
              <p class="panel-hint">Updates as you type in the items table.</p>

              <div class="panel-row">
                <span>
                  Our cost
                  <InfoTip label="Internal only — the total of every line's cost. Never shown to the customer." />
                </span>
                <strong>{{ money(totalCost) }}</strong>
              </div>
              <div class="panel-row">
                <span>
                  Customer pays
                  <InfoTip label="The total of every line's sell price — the quote amount." />
                </span>
                <strong>{{ money(totalSell) }}</strong>
              </div>
              <div class="panel-row panel-grand">
                <span>Profit</span>
                <strong :class="totalProfit < 0 ? 'money-loss' : 'money-profit'">
                  {{ money(totalProfit) }}
                </strong>
              </div>
              <div class="panel-row panel-margin">
                <span>Margin</span>
                <strong>{{ margin !== null ? `${margin}%` : '—' }}</strong>
              </div>

              <div class="panel-meta">
                {{ form.items.length }} {{ form.items.length === 1 ? 'line item' : 'line items' }}
              </div>

              <div class="panel-actions">
                <button type="submit" :disabled="saving" :aria-busy="saving">
                  {{ saving ? 'Saving…' : isEdit ? 'Save changes' : 'Save estimate' }}
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
.estimate-form-page { min-width: 0; }

.page-sub {
  color: var(--text-secondary);
  font-size: 14px;
  margin-top: var(--space-2);
}

.estimate-form { min-width: 0; }

.form-error {
  background: var(--danger-soft);
  border: 1px solid var(--danger);
  border-radius: var(--radius-md);
  color: var(--danger);
  margin-bottom: var(--space-4);
  padding: var(--space-3) var(--space-4);
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

/* The panel column stays with you while the form scrolls */
.form-aside {
  position: sticky;
  top: 20px;
}

.panel-card { padding: 18px; }

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
.panel-row:last-of-type { border-bottom: 0; }

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
.panel-row.panel-grand strong.money-loss { color: var(--danger); }

.panel-row.panel-margin { padding-top: 4px; }

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

.section-heading { margin-bottom: var(--space-4); }
.section-heading h3 { font-size: 15px; font-weight: 600; margin: 0; }
.section-heading p { color: var(--text-muted); font-size: 13px; margin: 2px 0 0; }

.review-grid {
  display: grid;
  gap: var(--space-4);
  grid-template-columns: minmax(0, 1fr);
}
.field label {
  align-items: center;
  display: flex;
  gap: 5px;
}

/* ---------- Responsive ---------- */
@media (max-width: 1024px) {
  .form-layout { grid-template-columns: 1fr; }

  /* Panel becomes a sticky bottom bar — Save is always reachable */
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

  .panel-hint { display: none; }

  .panel-row {
    border-bottom: 0;
    display: inline-flex;
    font-size: 13px;
    padding: 0;
  }

  .panel-rows {
    display: flex;
    flex-wrap: wrap;
    gap: 4px 18px;
  }

  .panel-row span { font-size: 11px; }
  .panel-row strong { font-size: 14px; }
  .panel-row.panel-grand strong { font-size: 15px; }
  .panel-row.panel-margin { display: none; }
  .panel-meta { display: none; }

  .panel-actions {
    border-top: 0;
    flex-direction: row;
    margin-top: 10px;
    padding-top: 0;
  }
  .panel-actions .btn,
  .panel-actions .btn-light { flex: 1; }
}

@media (min-width: 1025px) {
  /* keep the rows stacked on desktop; wrap for the mobile bar */
  .panel-rows { display: contents; }
}
</style>