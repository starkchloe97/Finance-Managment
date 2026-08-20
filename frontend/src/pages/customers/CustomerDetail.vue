<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { getCustomer } from '@/services/customerService'
import { money } from '@/utils/money'
import { statusLabel } from '@/utils/jobStatus'
import { estimateStatusLabel, estimateStatusClass } from '@/utils/estimateStatus'
import EntityDetailLayout from '@/components/ui/EntityDetailLayout.vue'

const route = useRoute()
const customer = ref(null)
const loading = ref(true)
const error = ref('')
const activeTab = ref('overview')

const tabs = [
  { key: 'overview', label: 'Overview' },
  { key: 'estimates', label: 'Estimates' },
  { key: 'jobs', label: 'Jobs' },
  { key: 'activity', label: 'Activity' },
]

const load = async () => {
  loading.value = true
  error.value = ''
  try {
    const { data } = await getCustomer(route.params.id)
    customer.value = data.data
  } catch (e) {
    error.value = e.response?.data?.message || 'Could not load customer.'
  } finally {
    loading.value = false
  }
}

onMounted(load)

const stats = computed(() => [
  { label: 'Jobs', value: String(customer.value?.job_count ?? 0), tone: 'neutral' },
  { label: 'Revenue', value: money(customer.value?.revenue), tone: 'revenue' },
  { label: 'Cost', value: money(customer.value?.cost), tone: 'cost' },
  {
    label: 'Profit',
    value: money(customer.value?.profit),
    tone: Number(customer.value?.profit) < 0 ? 'loss' : 'profit',
  },
])

const when = (value) => (value ? new Date(value).toLocaleDateString() : '—')
</script>

<template>
  <EntityDetailLayout
    v-if="customer || error"
    :tabs="tabs"
    v-model="activeTab"
    :loading="loading && !customer"
    :error="error"
    :stats="stats"
    @retry="load"
  >
    <template #title>
      <h1>{{ customer?.name }}</h1>
      <span v-if="customer?.company" class="hint">{{ customer.company }}</span>
    </template>

    <template #actions>
      <RouterLink class="btn-light" :to="`/customers/${customer?.id}/edit`">Edit</RouterLink>
      <RouterLink class="btn" :to="`/estimates/create?customer_id=${customer?.id}`"
        >New Estimate</RouterLink
      >
    </template>

    <!-- Overview -->
    <section v-if="activeTab === 'overview'" class="entity-section">
      <div class="grid entity-contact-grid">
        <div class="field">
          <label>Phone</label>
          <p>{{ customer?.phone || '—' }}</p>
        </div>
        <div class="field">
          <label>Email</label>
          <p>{{ customer?.email || '—' }}</p>
        </div>
        <div class="field">
          <label>Company</label>
          <p>{{ customer?.company || '—' }}</p>
        </div>
        <div class="field">
          <label>Address</label>
          <p>{{ customer?.address || '—' }}</p>
        </div>
      </div>
      <div v-if="customer?.notes" class="card">
        <h3>Notes</h3>
        <p>{{ customer.notes }}</p>
      </div>
    </section>

    <!-- Estimates -->
    <section v-if="activeTab === 'estimates'" class="entity-section">
      <div v-if="!customer?.estimates?.length" class="state-panel state-empty">
        <p>No estimates for this customer yet.</p>
        <RouterLink class="btn" :to="`/estimates/create?customer_id=${customer?.id}`"
          >New Estimate</RouterLink
        >
      </div>
      <div v-else class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Code</th>
              <th>Date</th>
              <th>Amount</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="estimate in customer.estimates" :key="estimate.id">
              <td>
                <RouterLink :to="`/estimates/${estimate.id}`">{{ estimate.code }}</RouterLink>
              </td>
              <td>{{ when(estimate.estimate_date) }}</td>
              <td class="right">{{ money(estimate.estimated_sell) }}</td>
              <td>
                <span class="status" :class="estimateStatusClass(estimate.status)">{{
                  estimateStatusLabel(estimate.status)
                }}</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <!-- Jobs -->
    <section v-if="activeTab === 'jobs'" class="entity-section">
      <div v-if="!customer?.jobs?.length" class="state-panel state-empty">
        <p>No jobs for this customer yet — convert an estimate to start one.</p>
        <RouterLink class="btn" :to="`/estimates/create?customer_id=${customer?.id}`"
          >New Estimate</RouterLink
        >
      </div>
      <div v-else class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Job</th>
              <th>Status</th>
              <th class="right">Revenue</th>
              <th class="right">Cost</th>
              <th class="right">Profit</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="job in customer.jobs" :key="job.id">
              <td>
                <RouterLink :to="`/jobs/${job.id}`">{{ job.code }}</RouterLink>
              </td>
              <td>
                <span class="status" :class="`status-${job.status}`">{{
                  statusLabel(job.status)
                }}</span>
              </td>
              <td class="right money-revenue">{{ money(job.sell_price) }}</td>
              <td class="right money-cost">
                {{ money(Number(job.cost_price) + Number(job.extra_costs)) }}
              </td>
              <td
                class="right"
                :class="Number(job.final_profit) < 0 ? 'money-loss' : 'money-profit'"
              >
                {{ money(job.final_profit) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <!-- Activity -->
    <section v-if="activeTab === 'activity'" class="entity-section">
      <div v-if="!customer?.activities?.length" class="state-panel state-empty">
        <p>No activity recorded for this customer yet.</p>
      </div>
      <ul v-else class="timeline">
        <li v-for="item in customer.activities" :key="item.id">
          <div class="timeline-when">{{ new Date(item.created_at).toLocaleString() }}</div>
          <div class="timeline-what">
            <b>{{ item.description }}</b>
            <span class="hint">{{ item.author || 'system' }}</span>
          </div>
        </li>
      </ul>
    </section>
  </EntityDetailLayout>
</template>
