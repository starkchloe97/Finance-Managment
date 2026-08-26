<script setup>
import { computed, onMounted, ref, onUnmounted } from 'vue'
import { getJobs } from '@/services/transportJobService'
import { money } from '@/utils/money'
import { statusLabel } from '@/utils/jobStatus'
import SearchInput from '@/components/ui/SearchInput.vue'
import FilterSelect from '@/components/ui/FilterSelect.vue'
import Pagination from '@/components/ui/Pagination.vue'
import StatePanel from '@/components/ui/StatePanel.vue'
import JobTable from '@/components/jobs/JobTable.vue'
import { useCustomerStore } from '@/stores/customerStore'
import { avatarStyle, initialOf } from '@/utils/avatar'

const customers = useCustomerStore()

const jobs = ref([])
const loading = ref(true)
const error = ref('')
const search = ref('')
const status = ref('')
const customerId = ref('')
const page = ref(1)
const pagination = ref({ current_page: 1, last_page: 1, per_page: 10, total: 0 })
const searchTimer = ref(null)
const isMobile = ref(false)

const STATUSES = [
  { value: 'draft', label: 'Draft' },
  { value: 'confirmed', label: 'Confirmed' },
  { value: 'assigned', label: 'Assigned' },
  { value: 'in_transit', label: 'In transit' },
  { value: 'delivered', label: 'Delivered' },
  { value: 'completed', label: 'Completed' },
]

const checkMobile = () => {
  isMobile.value = window.innerWidth < 820
}

const load = async () => {
  loading.value = true
  error.value = ''
  try {
    const { data } = await getJobs({
      search: search.value,
      status: status.value,
      customer_id: customerId.value || undefined,
      page: page.value,
    })
    jobs.value = data?.data ?? []
    pagination.value = data?.meta ?? pagination.value
  } catch (e) {
    error.value = e.response?.data?.message || 'Could not load jobs.'
  } finally {
    loading.value = false
  }
}

const onSearch = (value) => {
  search.value = value
  clearTimeout(searchTimer.value)
  searchTimer.value = setTimeout(() => {
    page.value = 1
    load()
  }, 300)
}

// FIX: the filter value must land in the ref before reloading —
// the old handler dropped it, so filters never applied.
const setStatus = (value) => {
  status.value = value
  page.value = 1
  load()
}

const setCustomer = (value) => {
  customerId.value = value
  page.value = 1
  load()
}

const hasFilters = computed(() => Boolean(search.value || status.value || customerId.value))

const clearFilters = () => {
  search.value = ''
  status.value = ''
  customerId.value = ''
  page.value = 1
  load()
}

const goToPage = (next) => {
  page.value = next
  load()
}

onMounted(() => {
  checkMobile()
  window.addEventListener('resize', checkMobile)
  customers.loadOptions()
  load()
})

onUnmounted(() => {
  clearTimeout(searchTimer.value)
  window.removeEventListener('resize', checkMobile)
})

const customerOptions = computed(() =>
  customers.customers.map((customer) => ({ value: customer.id, label: customer.name })),
)
</script>

<template>
  <div class="entity-list-page">
    <div class="page-head">
      <div>
        <span class="section-kicker">Operations</span>
        <h1>Transport jobs</h1>
        <p class="page-sub">
          {{ pagination.total }} {{ pagination.total === 1 ? 'job' : 'jobs' }} — track work in
          motion and the profit left after unexpected costs.
        </p>
      </div>
      <RouterLink class="btn-light" to="/estimates/create">+ New Estimate</RouterLink>
    </div>

    <div class="card list-card">
      <div class="toolbar">
        <SearchInput
          class="toolbar-search"
          :model-value="search"
          placeholder="Search by job code or customer…"
          @update:model-value="onSearch"
        />
        <FilterSelect
          :model-value="status"
          :options="STATUSES"
          placeholder="All statuses"
          @update:model-value="setStatus"
        />
        <FilterSelect
          :model-value="customerId"
          :options="customerOptions"
          placeholder="All customers"
          @update:model-value="setCustomer"
        />
        <button v-if="hasFilters" class="btn-light btn-sm" type="button" @click="clearFilters">
          Clear filters
        </button>
      </div>

      <StatePanel
        :loading="loading && !jobs.length"
        :error="error"
        :empty="!loading && !error && !jobs.length"
        empty-title="No jobs yet — convert an accepted estimate to create one."
        empty-action="New Estimate"
        empty-to="/estimates/create"
      >
        <JobTable v-if="!isMobile" :jobs="jobs" />

        <!-- Mobile cards -->
        <div v-else class="entity-card-grid">
          <article v-for="job in jobs" :key="job.id" class="entity-card">
            <div class="entity-card-top">
              <div class="mobile-id">
                <h3>
                  <RouterLink :to="`/jobs/${job.id}`">{{ job.code }}</RouterLink>
                </h3>
                <div v-if="job.customer?.name" class="mobile-customer">
                  <span
                    class="customer-avatar"
                    :style="avatarStyle(job.customer.name)"
                    aria-hidden="true"
                  >
                    {{ initialOf(job.customer.name) }}
                  </span>
                  <p class="hint">{{ job.customer.name }}</p>
                </div>
                <p v-else class="hint">No customer</p>
              </div>
              <span class="status" :class="`status-${job.status}`">
                {{ statusLabel(job.status) }}
              </span>
            </div>
            <div class="entity-card-stats">
              <div>
                <span class="hint">Revenue</span>
                <strong class="money-revenue">{{ money(job.sell_price) }}</strong>
              </div>
              <div>
                <span class="hint">Cost</span>
                <strong class="money-cost">{{ money(job.cost_price) }}</strong>
              </div>
              <div>
                <span class="hint">Profit</span>
                <strong :class="Number(job.final_profit) < 0 ? 'money-loss' : 'money-profit'">
                  {{ money(job.final_profit) }}
                </strong>
              </div>
            </div>
            <div class="entity-card-actions">
              <RouterLink class="btn-light btn-sm" :to="`/jobs/${job.id}`">Open job →</RouterLink>
            </div>
          </article>
        </div>

        <Pagination
          :page="pagination.current_page"
          :last-page="pagination.last_page"
          :total="pagination.total"
          :per-page="pagination.per_page"
          @update:page="goToPage"
        />
      </StatePanel>
    </div>
  </div>
</template>

<style scoped>
.entity-list-page,
.list-card {
  min-width: 0;
}

.page-sub {
  color: var(--text-secondary);
  font-size: 14px;
  margin-top: var(--space-2);
}

.toolbar {
  margin-bottom: var(--space-4);
}

/* Search grows, selects keep natural width */
.toolbar :deep(.toolbar-search) {
  flex: 1;
  min-width: 220px;
}
.toolbar :deep(.toolbar-search input) {
  width: 100%;
}

.mobile-id { min-width: 0; }
.mobile-customer {
  align-items: center;
  display: flex;
  gap: 8px;
  margin-top: 4px;
}
.customer-avatar {
  align-items: center;
  border-radius: 9px;
  color: #fff;
  display: inline-flex;
  flex: 0 0 24px;
  font-size: 10px;
  font-weight: 600;
  height: 24px;
  justify-content: center;
  width: 24px;
}

@media (max-width: 560px) {
  .entity-card-actions,
  .entity-card-actions .btn-light {
    width: 100%;
  }
}
</style>