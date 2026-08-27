<script setup>
import { computed, onMounted, ref } from 'vue'
import { getEstimates } from '@/services/estimateService'
import { ESTIMATE_STATUSES } from '@/utils/estimateStatus'
import SearchInput from '@/components/ui/SearchInput.vue'
import FilterSelect from '@/components/ui/FilterSelect.vue'
import Pagination from '@/components/ui/Pagination.vue'
import StatePanel from '@/components/ui/StatePanel.vue'
import EstimateTable from '@/components/estimates/EstimateTable.vue'
import EstimateConversionDialog from '@/components/estimates/EstimateConversionDialog.vue'

const estimates = ref([])
const loading = ref(true)
const error = ref('')
const search = ref('')
const status = ref('')
const page = ref(1)
const pagination = ref({ current_page: 1, last_page: 1, per_page: 10, total: 0 })
const searchTimer = ref(null)

// The estimate to convert, or null when the dialog is closed.
const converting = ref(null)

const load = async () => {
  loading.value = true
  error.value = ''
  try {
    const { data } = await getEstimates({
      search: search.value,
      status: status.value,
      page: page.value,
    })
    estimates.value = data?.data ?? []
    pagination.value = data?.meta ?? pagination.value
  } catch (e) {
    error.value = e.response?.data?.message || 'Could not load estimates.'
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

const onStatus = (value) => {
  status.value = value
  page.value = 1
  load()
}

const hasFilters = computed(() => Boolean(search.value || status.value))

const clearFilters = () => {
  search.value = ''
  status.value = ''
  page.value = 1
  load()
}

const goToPage = (next) => {
  page.value = next
  load()
}

onMounted(load)

const openConvert = (estimate) => {
  converting.value = estimate
}
</script>

<template>
  <div class="entity-list-page">
    <div class="page-head">
      <div>
        <span class="section-kicker">Operations</span>
        <h1>Estimates</h1>
        <p class="page-sub">
          {{ pagination.total }}
          {{ pagination.total === 1 ? 'quote' : 'quotes' }} — build, review, and turn them into
          transport jobs.
        </p>
      </div>
      <RouterLink class="btn" to="/estimates/create">+ New estimate</RouterLink>
    </div>

    <div class="card list-card">
      <div class="toolbar">
        <SearchInput
          class="toolbar-search"
          :model-value="search"
          placeholder="Search by code or customer…"
          @update:model-value="onSearch"
        />
        <FilterSelect
          :model-value="status"
          :options="ESTIMATE_STATUSES"
          placeholder="All statuses"
          @update:model-value="onStatus"
        />
        <button v-if="hasFilters" class="btn-light btn-sm" type="button" @click="clearFilters">
          Clear filters
        </button>
      </div>

      <StatePanel
        :loading="loading && !estimates.length"
        :error="error"
        :empty="!loading && !error && !estimates.length"
        empty-title="No estimates yet — create one to get started."
        empty-action="New Estimate"
        empty-to="/estimates/create"
      >
        <EstimateTable :estimates="estimates" />

        <div class="table-actions">
          <Pagination
            :page="pagination.current_page"
            :last-page="pagination.last_page"
            :total="pagination.total"
            :per-page="pagination.per_page"
            @update:page="goToPage"
          />
        </div>
      </StatePanel>
    </div>

    <EstimateConversionDialog
      :open="Boolean(converting)"
      :estimate="converting"
      @close="converting = null"
    />
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

.toolbar :deep(.toolbar-search) {
  flex: 1;
  min-width: 220px;
}
.toolbar :deep(.toolbar-search input) {
  width: 100%;
}

.table-actions {
  display: flex;
  justify-content: center;
}
</style>