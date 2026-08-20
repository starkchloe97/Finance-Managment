<script setup>
import { onMounted, ref, onUnmounted } from 'vue'
import { getJobs } from '@/services/transportJobService'
import { money } from '@/utils/money'
import { statusLabel } from '@/utils/jobStatus'
import SearchInput from '@/components/ui/SearchInput.vue'
import FilterSelect from '@/components/ui/FilterSelect.vue'
import Pagination from '@/components/ui/Pagination.vue'
import StatePanel from '@/components/ui/StatePanel.vue'
import JobTable from '@/components/jobs/JobTable.vue'
import { useCustomerStore } from '@/stores/customerStore'

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

const applyFilter = () => {
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

const customerOptions = () =>
  customers.customers.map((customer) => ({ value: customer.id, label: customer.name }))
</script>

<template>
  <div>
    <div class="page-head">
      <h1>Transport Jobs</h1>
    </div>

    <div class="card">
      <p class="hint">
        Jobs start from accepted estimates. Base profit is agreed at quotation; unexpected expenses
        come off it to give the final profit.
      </p>

      <div class="toolbar">
        <SearchInput
          :model-value="search"
          placeholder="Search by job code or customer…"
          @update:model-value="onSearch"
        />
        <FilterSelect
          :model-value="status"
          :options="STATUSES"
          placeholder="Status"
          @update:model-value="applyFilter"
        />
        <FilterSelect
          :model-value="customerId"
          :options="customerOptions()"
          placeholder="Customer"
          @update:model-value="applyFilter"
        />
      </div>

      <StatePanel
        :loading="loading && !jobs.length"
        :error="error"
        :empty="!loading && !error && !jobs.length"
        empty-title="No jobs yet — convert an accepted estimate to create one."
        empty-action="New Estimate"
        empty-to="/estimates/create"
      >
        <!-- Desktop table -->
        <JobTable v-if="!isMobile" :jobs="jobs" />

        <!-- Mobile cards: identifier, customer, status, revenue/cost/profit -->
        <div v-else class="entity-card-grid">
          <article v-for="job in jobs" :key="job.id" class="entity-card">
            <div class="entity-card-top">
              <div>
                <h3>
                  <RouterLink :to="`/jobs/${job.id}`">{{ job.code }}</RouterLink>
                </h3>
                <p class="hint">{{ job.customer?.name || '—' }}</p>
              </div>
              <span class="status" :class="`status-${job.status}`">{{
                statusLabel(job.status)
              }}</span>
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
                <strong :class="Number(job.final_profit) < 0 ? 'money-loss' : 'money-profit'">{{
                  money(job.final_profit)
                }}</strong>
              </div>
            </div>
            <div class="entity-card-actions">
              <RouterLink class="btn-light btn-sm" :to="`/jobs/${job.id}`">View →</RouterLink>
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
