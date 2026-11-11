<script setup>
import { onMounted, onUnmounted, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useQueryStore, QUERY_STATUSES } from '@/stores/queryStore'
import SearchInput from '@/components/ui/SearchInput.vue'
import FilterSelect from '@/components/ui/FilterSelect.vue'
import Pagination from '@/components/ui/Pagination.vue'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
import StatePanel from '@/components/ui/StatePanel.vue'

const store = useQueryStore()
const { queries, loading, error, search, pagination } = storeToRefs(store)
const deleting = ref(null)
const searchTimer = ref(null)

const onSearch = (value) => {
  search.value = value
  clearTimeout(searchTimer.value)
  searchTimer.value = setTimeout(() => store.setSearch(value), 300)
}

onMounted(() => store.fetchQueries())
onUnmounted(() => clearTimeout(searchTimer.value))

const confirmDelete = (query) => {
  deleting.value = query
}

const doDelete = async () => {
  if (!deleting.value) return

  try {
    await store.deleteQuery(deleting.value.id)
    deleting.value = null
  } catch (deleteError) {
    store.error = deleteError.response?.data?.message || 'Could not delete the query.'
    deleting.value = null
  }
}

const statusLabel = (status) =>
  QUERY_STATUSES.find((option) => option.value === status)?.label || status

const dateLabel = (value) =>
  value
    ? new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'short' }).format(
        new Date(value),
      )
    : '—'
</script>

<template>
  <div>
    <div class="page-head">
      <div>
        <h1>Queries</h1>
        <p class="page-sub">
          {{ pagination.total }} {{ pagination.total === 1 ? 'query' : 'queries' }} · public service
          inquiries
        </p>
      </div>
      <RouterLink class="btn" to="/queries/create">+ New query</RouterLink>
    </div>

    <div class="card">
      <div class="toolbar">
        <SearchInput
          :model-value="search"
          placeholder="Search name, phone, service or message…"
          @update:model-value="onSearch"
        />
        <FilterSelect
          :model-value="store.status"
          :options="QUERY_STATUSES"
          placeholder="All statuses"
          @update:model-value="store.setStatus"
        />
      </div>

      <StatePanel
        :loading="loading && !queries.length"
        :error="error"
        :empty="!loading && !error && !queries.length"
        empty-title="No queries match your search."
      >
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Contact</th>
                <th>Service</th>
                <th>Route</th>
                <th>Status</th>
                <th>Received</th>
                <th class="right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="query in queries" :key="query.id">
                <td>
                  <div class="contact">
                    <RouterLink :to="`/queries/${query.id}`">{{ query.name }}</RouterLink>
                    <a :href="`tel:${query.phone}`" class="hint">{{ query.phone }}</a>
                  </div>
                </td>
                <td>{{ query.service || '—' }}</td>
                <td>
                  <span>{{ query.pickup_location || '—' }}</span>
                  <span v-if="query.dropoff_location" class="hint">
                    → {{ query.dropoff_location }}
                  </span>
                </td>
                <td>
                  <span class="status" :class="`status-${query.status}`">
                    {{ statusLabel(query.status) }}
                  </span>
                </td>
                <td>{{ dateLabel(query.created_at) }}</td>
                <td class="right">
                  <div class="actions">
                    <RouterLink class="btn btn-small" :to="`/queries/${query.id}`">
                      View
                    </RouterLink>
                    <button
                      class="btn btn-small btn-danger"
                      type="button"
                      @click="confirmDelete(query)"
                    >
                      Delete
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <Pagination
          :page="pagination.current_page"
          :last-page="pagination.last_page"
          :total="pagination.total"
          :per-page="pagination.per_page"
          @update:page="store.setPage"
        />
      </StatePanel>
    </div>

    <ConfirmDialog
      :open="Boolean(deleting)"
      title="Delete query?"
      :message="deleting ? `Delete the query from ${deleting.name}?` : ''"
      confirm-label="Delete"
      variant="danger"
      @confirm="doDelete"
      @cancel="deleting = null"
    />
  </div>
</template>

<style scoped>
.contact {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.contact a:first-child {
  color: var(--text-primary);
  font-weight: 600;
  text-decoration: none;
}

.contact a:first-child:hover {
  color: var(--accent);
}

.hint {
  color: var(--text-muted);
  font-size: var(--text-sm);
}

.status {
  background: var(--surface-2);
  border-radius: 999px;
  color: var(--text-secondary);
  display: inline-flex;
  font-size: var(--text-sm);
  font-weight: 600;
  padding: 4px 10px;
  white-space: nowrap;
}

.status-new {
  background: var(--accent-soft);
  color: var(--accent);
}

.status-resolved {
  background: var(--success-soft);
  color: var(--success);
}

.actions {
  display: inline-flex;
  gap: var(--space-2);
}

.btn-small {
  min-height: 32px;
  padding: 4px 10px;
}

.btn-danger {
  background: transparent;
  border-color: var(--border);
  color: var(--danger);
}

.btn-danger:hover {
  background: var(--danger-soft);
  border-color: var(--danger);
}
</style>
