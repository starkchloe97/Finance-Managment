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
  <div class="page-head">
    <h1>{{ isEdit ? 'Edit Estimate' : 'New Estimate' }}</h1>
  </div>

  <form @submit.prevent="save">
    <div v-if="loading" class="state-panel state-loading"><div class="skeleton-block"></div></div>

    <template v-else>
      <div class="card">
        <EstimateInformation :form="form" />
      </div>

      <div class="card">
        <EstimateItemsTable :form="form" />
      </div>

      <div class="card">
        <h3>Status</h3>
        <div class="field">
          <select v-model="form.status">
            <option value="draft">Draft</option>
            <option value="sent">Sent</option>
            <option value="accepted">Accepted</option>
            <option value="rejected">Rejected</option>
            <option value="expired">Expired</option>
          </select>
        </div>

        <h3>Remarks</h3>
        <div class="field">
          <textarea
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
      </div>

      <p v-if="notice" class="error">{{ notice }}</p>

      <div class="actions">
        <button type="submit" :disabled="saving">
          {{ saving ? 'Saving…' : isEdit ? 'Save Changes' : 'Save Estimate' }}
        </button>
        <RouterLink
          class="btn btn-light"
          :to="isEdit ? `/estimates/${props.estimate.id}` : '/estimates'"
          >Cancel</RouterLink
        >
      </div>
    </template>
  </form>
</template>
