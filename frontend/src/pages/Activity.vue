<script setup>
import { onMounted, reactive, ref } from 'vue'
import { getActivities } from '@/services/activityService'
import Pagination from '@/components/ui/Pagination.vue'
import StatePanel from '@/components/ui/StatePanel.vue'

const activities = ref([])
const loading = ref(false)
const error = ref('')
const pagination = reactive({ page: 1, lastPage: 1, perPage: 25, total: 0 })
const filters = reactive({ search: '', action: '', from: '', to: '' })

const formatDate = (value) =>
  new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'short' }).format(
    new Date(value),
  )

const formatField = (field) =>
  field.replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase())

const formatValue = (value) => {
  if (value === null || value === undefined || value === '') return '—'
  return String(value)
}

const load = async (page = pagination.page) => {
  loading.value = true
  error.value = ''
  try {
    const response = await getActivities({
      ...filters,
      page,
      per_page: pagination.perPage,
    })
    activities.value = response.data.data
    pagination.page = response.data.meta.current_page
    pagination.lastPage = response.data.meta.last_page
    pagination.perPage = response.data.meta.per_page
    pagination.total = response.data.meta.total
  } catch (requestError) {
    error.value =
      requestError.response?.data?.message ||
      'Could not load activity. Check your connection and try again.'
  } finally {
    loading.value = false
  }
}

const applyFilters = () => load(1)

onMounted(() => load(1))
</script>

<template>
  <div class="activity-page">
    <header class="page-heading">
      <div>
        <h1>Activity</h1>
        <p class="muted">
          A record of meaningful changes to business records. Sign-in and system events are
          excluded.
        </p>
      </div>
    </header>

    <form class="card activity-filters" @submit.prevent="applyFilters">
      <input
        v-model="filters.search"
        placeholder="Search activity or record"
        aria-label="Search activity"
      />
      <select v-model="filters.action" aria-label="Filter by action">
        <option value="">All actions</option>
        <option value="created">Created</option>
        <option value="updated">Updated</option>
        <option value="deleted">Deleted</option>
        <option value="restored">Restored</option>
      </select>
      <div class="date-range">
        <input v-model="filters.from" type="date" aria-label="From date" />
        <span aria-hidden="true">to</span>
        <input v-model="filters.to" type="date" aria-label="To date" />
      </div>
      <button class="btn" type="submit" :disabled="loading">Apply filters</button>
    </form>

    <StatePanel
      :loading="loading"
      :error="error"
      :empty="!loading && !error && !activities.length"
      empty-title="No activity matches these filters."
    >
      <template #error-action>
        <button class="btn" type="button" @click="load()">Try again</button>
      </template>

      <section class="activity-list" aria-label="Recent activity">
        <article v-for="activity in activities" :key="activity.id" class="card activity-entry">
          <div class="activity-entry-main">
            <span class="activity-action" :class="`action-${activity.action}`">
              {{ activity.action }}
            </span>
            <div class="activity-copy">
              <strong>{{ activity.description }}</strong>
              <div v-if="activity.changes" class="activity-changes">
                <span v-for="(change, field) in activity.changes" :key="field">
                  <b>{{ formatField(field) }}:</b>
                  <template v-if="'old' in change">
                    {{ formatValue(change.old) }} <span aria-hidden="true">→</span>
                    {{ formatValue(change.new) }}
                  </template>
                  <template v-else>{{ formatValue(change.new) }}</template>
                </span>
              </div>
            </div>
          </div>
          <div class="activity-meta">
            <span>{{ activity.actor }}</span>
            <time :datetime="activity.created_at">{{ formatDate(activity.created_at) }}</time>
          </div>
        </article>
        <Pagination
          :page="pagination.page"
          :last-page="pagination.lastPage"
          :total="pagination.total"
          :per-page="pagination.perPage"
          @update:page="load"
        />
      </section>
    </StatePanel>
  </div>
</template>

<style scoped>
.activity-page {
  display: grid;
  gap: var(--space-4);
  min-width: 0;
}

.page-heading h1 {
  font-size: var(--text-2xl);
}

.page-heading p {
  color: var(--text-secondary);
  margin-top: var(--space-1);
}

.activity-filters {
  align-items: end;
  display: grid;
  gap: var(--space-3);
  grid-template-columns: minmax(180px, 2fr) minmax(130px, 1fr) minmax(280px, 2fr) auto;
}

.activity-filters input,
.activity-filters select {
  background: var(--surface);
  border: 1px solid var(--border-strong);
  border-radius: var(--radius-sm);
  color: var(--text-primary);
  min-height: var(--control-height);
  min-width: 0;
  padding: var(--space-2) var(--space-3);
  width: 100%;
}

.date-range {
  align-items: center;
  display: flex;
  gap: var(--space-2);
  min-width: 0;
}

.date-range span {
  color: var(--text-secondary);
  font-size: var(--text-sm);
  flex: 0 0 auto;
}

.date-range input {
  flex: 1 1 0;
}

.activity-list {
  display: grid;
  gap: var(--space-2);
}

.activity-entry {
  align-items: flex-start;
  display: flex;
  gap: var(--space-4);
  justify-content: space-between;
  min-width: 0;
  padding: var(--space-4);
}

.activity-entry-main {
  align-items: flex-start;
  display: flex;
  gap: var(--space-3);
  min-width: 0;
}

.activity-action {
  background: var(--surface-2);
  border-radius: var(--radius-pill);
  color: var(--text-secondary);
  flex: 0 0 auto;
  font-size: var(--text-xs);
  padding: 3px 9px;
  text-transform: capitalize;
}

.action-created,
.action-restored {
  background: var(--success-soft);
  color: var(--success);
}

.action-deleted {
  background: var(--danger-soft);
  color: var(--danger);
}

.activity-copy {
  min-width: 0;
}

.activity-copy strong {
  overflow-wrap: anywhere;
}

.activity-changes {
  color: var(--text-secondary);
  display: flex;
  flex-wrap: wrap;
  font-size: var(--text-sm);
  gap: var(--space-1) var(--space-3);
  margin-top: var(--space-1);
}

.activity-meta {
  color: var(--text-muted);
  display: grid;
  flex: 0 0 auto;
  font-size: var(--text-sm);
  justify-items: end;
  gap: var(--space-1);
  text-align: right;
}

@media (max-width: 900px) {
  .activity-filters {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .date-range {
    grid-column: 1 / -1;
  }
}

@media (max-width: 560px) {
  .activity-filters {
    grid-template-columns: 1fr;
  }

  .date-range {
    grid-column: auto;
  }

  .activity-entry {
    flex-direction: column;
  }

  .activity-meta {
    justify-items: start;
    text-align: left;
  }
}
</style>
