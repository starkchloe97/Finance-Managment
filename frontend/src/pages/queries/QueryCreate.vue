<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import * as service from '@/services/queryService'
import { QUERY_STATUSES } from '@/stores/queryStore'

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
const saving = ref(false)
const error = ref('')

const createQuery = async () => {
  saving.value = true
  error.value = ''

  try {
    const response = await service.createQuery({ ...query })
    await router.replace(`/queries/${response.data.data.id}`)
  } catch (createError) {
    error.value = createError.response?.data?.message || 'Could not create the query.'
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div>
    <div class="page-head">
      <div>
        <RouterLink class="back-link" to="/queries">← Queries</RouterLink>
        <h1>New query</h1>
        <p class="page-sub">Create a query record in the management system.</p>
      </div>
    </div>

    <form class="card query-form" @submit.prevent="createQuery">
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
      <p v-if="error" class="form-error" role="alert">{{ error }}</p>
      <div class="form-actions">
        <RouterLink class="btn btn-secondary" to="/queries">Cancel</RouterLink>
        <button class="btn" type="submit" :disabled="saving">
          {{ saving ? 'Creating…' : 'Create query' }}
        </button>
      </div>
    </form>
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

.btn-secondary {
  background: var(--surface);
  border: 1px solid var(--border);
  color: var(--text-primary);
  text-decoration: none;
}

.form-error {
  color: var(--danger);
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
