<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { createEstimate, updateEstimate, getEstimate } from '@/services/estimateService'
import EstimateInformation from './EstimateInformation.vue'
import EstimateItemsTable from './EstimateItemsTable.vue'
import { money } from '@/utils/money'

const router = useRouter()
const route = useRoute()

// When editing an existing quote, its id is present; otherwise this is the
// create form. The quote is loaded by the page and passed down.
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
    // Pre-fill the customer from the ?customer_id= query param (detail pages
    // link "New Estimate" for a specific customer).
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
  <div class="page-head estimate-form-head">
    <div>
      <span class="section-kicker">Operations / Estimates</span>
      <h1>{{ isEdit ? 'Edit estimate' : 'New estimate' }}</h1>
      <p class="page-subtitle">Build a customer quote that can become a transport job.</p>
    </div>
  </div>

  <form class="estimate-form" @submit.prevent="save">
    <div v-if="notice && !loading" class="form-error" role="alert">{{ notice }}</div>
    <div v-if="loading" class="state-panel state-loading"><div class="skeleton-block"></div></div>

    <template v-else>
      <section class="card form-section">
        <EstimateInformation :form="form" />
      </section>

      <section class="card form-section">
        <EstimateItemsTable :form="form" />
      </section>

      <section class="card form-section review-section">
        <div class="form-section-heading">
          <h2>Review and send</h2>
          <p>Set the quote status and add any customer-facing context.</p>
        </div>
        <div class="field">
          <label for="estimate-status">Status</label>
          <select id="estimate-status" v-model="form.status">
            <option value="draft">Draft</option>
            <option value="sent">Sent</option>
            <option value="accepted">Accepted</option>
            <option value="rejected">Rejected</option>
            <option value="expired">Expired</option>
          </select>
        </div>
        <div class="field">
          <label for="estimate-remarks">Customer-facing remarks</label>
          <textarea
            id="estimate-remarks"
            v-model="form.remarks"
            placeholder="Anything the customer should see on the quote"
          ></textarea>
        </div>
        <div class="totals">
          <dl>
            <div>
              <dt>Our cost</dt>
              <dd>{{ money(totalCost) }}</dd>
            </div>
            <div>
              <dt>Customer pays</dt>
              <dd>{{ money(totalSell) }}</dd>
            </div>
            <div class="grand">
              <dt>Profit</dt>
              <dd>{{ money(totalProfit) }}</dd>
            </div>
          </dl>
        </div>
      </section>

      <div class="estimate-actions">
        <RouterLink
          class="btn-light"
          :to="isEdit ? `/estimates/${props.estimate.id}` : '/estimates'"
          >Cancel</RouterLink
        >
        <button type="submit" :disabled="saving" :aria-busy="saving">
          {{ saving ? 'Saving estimate' : isEdit ? 'Save changes' : 'Save estimate' }}
        </button>
      </div>
    </template>
  </form>
</template>

<style scoped>
.estimate-form-head {
  align-items: flex-start;
}

.page-subtitle {
  color: var(--text-secondary);
  margin-top: var(--space-2);
}

.estimate-form {
  min-width: 0;
}

.form-section {
  max-width: 1180px;
}

.form-section-heading {
  margin-bottom: var(--space-4);
}

.form-section-heading h2 {
  font-size: var(--text-lg);
}

.form-section-heading p {
  color: var(--text-muted);
  font-size: var(--text-sm);
  margin-top: var(--space-1);
}

.review-section {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(260px, 0.55fr);
}

.review-section .form-section-heading,
.review-section .field:nth-of-type(2) {
  grid-column: 1 / -1;
}

.form-error {
  background: var(--danger-soft);
  border: 1px solid var(--danger);
  border-radius: var(--radius-md);
  color: var(--danger);
  margin-bottom: var(--space-4);
  max-width: 1180px;
  padding: var(--space-3);
}

.estimate-actions {
  display: flex;
  gap: var(--space-3);
  justify-content: flex-end;
  margin: var(--space-4) var(--space-0) var(--space-6);
  max-width: 1180px;
}

@media (max-width: 700px) {
  .review-section {
    display: block;
  }

  .estimate-actions {
    flex-direction: column-reverse;
  }

  .estimate-actions button,
  .estimate-actions .btn-light {
    width: 100%;
  }
}
</style>
