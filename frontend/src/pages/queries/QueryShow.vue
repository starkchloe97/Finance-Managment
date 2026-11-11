<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import * as service from '@/services/queryService'
import { QUERY_STATUSES } from '@/stores/queryStore'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'

const route = useRoute()
const router = useRouter()
const query = reactive({
  name: '',
  phone: '',
  service: '',
  pickup_location: '',
  dropoff_location: '',
  message: '',
  status: 'new',
})
const loading = ref(true)
const saving = ref(false)
const deleting = ref(false)
const error = ref('')
const saveError = ref('')
const saved = ref(false)
const receivedAt = ref('')

const loadQuery = async () => {
  loading.value = true
  error.value = ''

  try {
    const response = await service.getQuery(route.params.id)
    Object.assign(query, response.data.data)
    receivedAt.value = response.data.data.created_at
  } catch (loadError) {
    error.value = loadError.response?.data?.message || 'Could not load this query.'
  } finally {
    loading.value = false
  }
}

onMounted(loadQuery)

const save = async () => {
  saving.value = true
  saveError.value = ''
  saved.value = false

  try {
    const response = await service.updateQuery(route.params.id, { ...query })
    Object.assign(query, response.data.data)
    saved.value = true
  } catch (saveFailure) {
    saveError.value =
      saveFailure.response?.data?.message ||
      'Could not save your changes. Check the fields and try again.'
  } finally {
    saving.value = false
  }
}

const doDelete = async () => {
  try {
    await service.deleteQuery(route.params.id)
    await router.replace('/queries')
  } catch (deleteError) {
    saveError.value = deleteError.response?.data?.message || 'Could not delete this query.'
    deleting.value = false
  }
}

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
        <RouterLink class="back-link" to="/queries">← Queries</RouterLink>
        <h1>Query details</h1>
        <p class="page-sub">Received {{ dateLabel(receivedAt) }}</p>
      </div>
      <button class="btn btn-danger" type="button" @click="deleting = true">Delete query</button>
    </div>

    <div v-if="loading" class="card state-panel state-loading">Loading query…</div>
    <div v-else-if="error" class="card state-panel state-error">{{ error }}</div>
    <form v-else class="card query-form" @submit.prevent="save">
      <div class="form-grid">
        <label>
          Name
          <input v-model="query.name" required maxlength="120" autocomplete="name" />
        </label>
        <label>
          Phone
          <input v-model="query.phone" required maxlength="32" type="tel" autocomplete="tel" />
        </label>
        <label>
          Service
          <input v-model="query.service" maxlength="160" />
        </label>
        <label>
          Status
          <select v-model="query.status" required>
            <option v-for="status in QUERY_STATUSES" :key="status.value" :value="status.value">
              {{ status.label }}
            </option>
          </select>
        </label>
        <label>
          Pickup location
          <input v-model="query.pickup_location" maxlength="160" />
        </label>
        <label>
          Dropoff location
          <input v-model="query.dropoff_location" maxlength="160" />
        </label>
        <label class="full-width">
          Message
          <textarea v-model="query.message" rows="6"></textarea>
        </label>
      </div>
      <p v-if="saveError" class="form-error" role="alert">{{ saveError }}</p>
      <p v-else-if="saved" class="form-success" role="status">Query updated.</p>
      <div class="form-actions">
        <RouterLink class="btn btn-secondary" to="/queries">Back to queries</RouterLink>
        <button class="btn" type="submit" :disabled="saving">
          {{ saving ? 'Saving…' : 'Save changes' }}
        </button>
      </div>
    </form>

    <ConfirmDialog
      :open="deleting"
      title="Delete query?"
      :message="`Delete the query from ${query.name}?`"
      confirm-label="Delete"
      variant="danger"
      @confirm="doDelete"
      @cancel="deleting = false"
    />
  </div>
</template>

<style scoped>
.back-link {
  color: var(--text-muted);
  display: inline-block;
  font-size: var(--text-sm);
  margin-bottom: var(--space-2);
  text-decoration: none;
}

.query-form {
  padding: var(--space-6);
}

.form-grid {
  display: grid;
  gap: var(--space-5);
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

label {
  color: var(--text-secondary);
  display: flex;
  flex-direction: column;
  font-size: var(--text-sm);
  font-weight: 600;
  gap: var(--space-2);
}

input,
select,
textarea {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  color: var(--text-primary);
  font: inherit;
  font-weight: 400;
  min-height: var(--control-height);
  padding: var(--space-2) var(--space-3);
  width: 100%;
}

textarea {
  min-height: 140px;
  resize: vertical;
}

.full-width {
  grid-column: 1 / -1;
}

.form-actions {
  border-top: 1px solid var(--border);
  display: flex;
  gap: var(--space-3);
  justify-content: flex-end;
  margin-top: var(--space-6);
  padding-top: var(--space-5);
}

.btn-danger {
  background: var(--danger);
  border-color: var(--danger);
}

.btn-secondary {
  background: var(--surface);
  border: 1px solid var(--border);
  color: var(--text-primary);
  text-decoration: none;
}

.form-error {
  color: var(--danger);
}

.form-success {
  color: var(--success);
}

@media (max-width: 640px) {
  .form-grid {
    grid-template-columns: 1fr;
  }

  .full-width {
    grid-column: auto;
  }

  .form-actions {
    flex-direction: column-reverse;
  }
}
</style>
