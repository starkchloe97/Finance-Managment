<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { getEstimate } from '@/services/estimateService'
import { money } from '@/utils/money'
import EstimateStatus from '@/components/estimates/EstimateStatus.vue'
import EstimateSummary from '@/components/estimates/EstimateSummary.vue'
import EstimateItems from '@/components/estimates/EstimateItems.vue'
import EstimateConversionDialog from '@/components/estimates/EstimateConversionDialog.vue'

const route = useRoute()
const estimate = ref(null)
const loading = ref(true)
const error = ref('')
const converting = ref(false)

const load = async () => {
  loading.value = true
  error.value = ''
  try {
    const { data } = await getEstimate(route.params.id)
    estimate.value = data.data
  } catch (e) {
    error.value = e.response?.data?.message || 'Could not load estimate.'
  } finally {
    loading.value = false
  }
}

onMounted(load)

const converted = computed(() => Boolean(estimate.value?.transport_job))

const when = (value) => (value ? String(value).slice(0, 10) : '—')
</script>

<template>
  <div>
    <!-- Loading / error ladder -->
    <div v-if="error" class="state-panel state-error">
      <p>{{ error }}</p>
      <button type="button" class="btn" @click="load">Try again</button>
    </div>

    <div v-else-if="loading && !estimate" class="state-panel state-loading">
      <div class="skeleton-block"></div>
    </div>

    <div v-else-if="estimate">
      <div class="page-head estimate-detail-head">
        <div class="entity-title-block">
          <span class="section-kicker">Operations / Estimates</span>
          <div class="head-title">
            <h1>{{ estimate.code }}</h1>
            <EstimateStatus :status="estimate.status" />
          </div>
          <p class="page-subtitle">
            {{ estimate.customer?.name || 'No customer' }} · {{ when(estimate.estimate_date) }}
          </p>
        </div>
        <div class="actions">
          <RouterLink v-if="!converted" class="btn-light" :to="`/estimates/${estimate.id}/edit`">
            Edit estimate
          </RouterLink>
          <button
            v-else
            class="btn-light"
            type="button"
            @click="$router.push(`/jobs/${estimate.transport_job.id}`)"
          >
            View job
          </button>
          <button v-if="!converted" class="btn" type="button" @click="converting = true">
            Convert to job
          </button>
        </div>
      </div>

      <EstimateSummary
        :amount="estimate.estimated_sell"
        :date="when(estimate.estimate_date)"
        :valid-until="when(estimate.valid_until)"
        :items="estimate.items?.length || 0"
      />

      <!-- Quote-only. No cost/profit surfaces here — that separation is
                 architectural: the estimate is customer-facing, the job owns
                 the internal figures. -->
      <div class="card">
        <h3>Quoted items</h3>
        <EstimateItems :items="estimate.items || []" />
        <div class="totals">
          <dl>
            <div>
              <dt>Total</dt>
              <dd>{{ money(estimate.estimated_sell) }}</dd>
            </div>
          </dl>
        </div>
      </div>

      <div class="card">
        <h3>Details</h3>
        <div class="grid">
          <div class="field">
            <label>Customer</label>
            <p>{{ estimate.customer?.name || '—' }}</p>
          </div>
          <div class="field">
            <label>Route</label>
            <p>{{ estimate.pickup }} → {{ estimate.destination }}</p>
          </div>
          <div class="field">
            <label>Service type</label>
            <p>{{ estimate.service_type }}</p>
          </div>
          <div class="field">
            <label>Estimate date</label>
            <p>{{ when(estimate.estimate_date) }}</p>
          </div>
          <div class="field">
            <label>Valid until</label>
            <p>{{ when(estimate.valid_until) }}</p>
          </div>
        </div>
        <div v-if="estimate.remarks" class="field">
          <label>Remarks</label>
          <p>{{ estimate.remarks }}</p>
        </div>
      </div>

      <div class="actions">
        <RouterLink class="btn btn-light" to="/estimates">Back to estimates</RouterLink>
      </div>
    </div>

    <EstimateConversionDialog :open="converting" :estimate="estimate" @close="converting = false" />
  </div>
</template>
