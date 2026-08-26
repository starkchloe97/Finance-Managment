<script setup>
import { reactive, ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { getCustomer, updateCustomer } from '@/services/customerService'

const route = useRoute()
const router = useRouter()
const saving = ref(false)
const loading = ref(true)
const errors = ref({})
const notice = ref('')

const form = reactive({
  name: '',
  phone: '',
  email: '',
  company: '',
  address: '',
  notes: '',
})

const fieldError = (field) => errors.value[field]?.[0] || ''

onMounted(async () => {
  try {
    const { data } = await getCustomer(route.params.id)
    Object.assign(form, data.data)
  } catch (error) {
    notice.value = error.response?.data?.message || 'Could not load customer.'
  } finally {
    loading.value = false
  }
})

const submit = async () => {
  if (saving.value) return
  saving.value = true
  errors.value = {}
  notice.value = ''
  try {
    await updateCustomer(route.params.id, form)
    router.push('/customers')
  } catch (error) {
    errors.value = error.response?.data?.errors || {}
    if (!Object.keys(errors.value).length) {
      notice.value = error.response?.data?.message || 'Could not save customer.'
    }
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="form-page">
    <div class="page-head">
      <div>
        <span class="section-kicker">Operations / Customers</span>
        <h1>Edit customer</h1>
        <p class="page-subtitle">Update the account information used by your operations team.</p>
      </div>
    </div>

    <div v-if="loading" class="state-panel state-loading">
      <div class="skeleton-block"></div>
    </div>

    <form v-else class="card form-card" @submit.prevent="submit">
      <section class="form-section">
        <div class="form-section-heading">
          <h2>Customer details</h2>
          <p>Keep contact information current before creating the next estimate.</p>
        </div>
        <div class="grid">
          <div class="field">
            <label for="customer-name">Name</label>
            <input
              id="customer-name"
              v-model="form.name"
              autocomplete="name"
              required
              :class="{ invalid: errors.name }"
              :aria-invalid="fieldError('name') ? 'true' : undefined"
              aria-describedby="customer-name-error"
            />
            <small v-if="fieldError('name')" id="customer-name-error" class="error">{{
              fieldError('name')
            }}</small>
          </div>
          <div class="field">
            <label for="customer-phone">Phone</label>
            <input
              id="customer-phone"
              v-model="form.phone"
              type="tel"
              autocomplete="tel"
              :class="{ invalid: errors.phone }"
              :aria-invalid="fieldError('phone') ? 'true' : undefined"
              aria-describedby="customer-phone-error"
            />
            <small v-if="fieldError('phone')" id="customer-phone-error" class="error">{{
              fieldError('phone')
            }}</small>
          </div>
          <div class="field">
            <label for="customer-email">Email</label>
            <input
              id="customer-email"
              v-model="form.email"
              type="email"
              autocomplete="email"
              :class="{ invalid: errors.email }"
              :aria-invalid="fieldError('email') ? 'true' : undefined"
              aria-describedby="customer-email-error"
            />
            <small v-if="fieldError('email')" id="customer-email-error" class="error">{{
              fieldError('email')
            }}</small>
          </div>
          <div class="field">
            <label for="customer-company">Company</label>
            <input
              id="customer-company"
              v-model="form.company"
              :class="{ invalid: errors.company }"
              :aria-invalid="fieldError('company') ? 'true' : undefined"
              aria-describedby="customer-company-error"
            />
            <small v-if="fieldError('company')" id="customer-company-error" class="error">{{
              fieldError('company')
            }}</small>
          </div>
        </div>
      </section>

      <section class="form-section">
        <div class="form-section-heading">
          <h2>Additional information</h2>
          <p>Keep address and account context available for future quotes.</p>
        </div>
        <div class="field">
          <label for="customer-address">Address</label>
          <textarea
            id="customer-address"
            v-model="form.address"
            autocomplete="street-address"
          ></textarea>
        </div>
        <div class="field">
          <label for="customer-notes">Notes</label>
          <textarea id="customer-notes" v-model="form.notes"></textarea>
        </div>
      </section>

      <p v-if="notice" class="form-error" role="alert">{{ notice }}</p>

      <div class="form-actions">
        <RouterLink class="btn-light" to="/customers">Cancel</RouterLink>
        <button type="submit" :disabled="saving" :aria-busy="saving">
          {{ saving ? 'Saving changes' : 'Save changes' }}
        </button>
      </div>
    </form>
  </div>
</template>

<style scoped>
.form-page,
.form-card {
  min-width: 0;
}

.page-subtitle {
  color: var(--text-secondary);
  margin-top: var(--space-2);
}

.form-card {
  max-width: 980px;
}

.form-section + .form-section {
  border-top: 1px solid var(--border);
  margin-top: var(--space-5);
  padding-top: var(--space-5);
}

.form-section-heading {
  margin-bottom: var(--space-4);
}

.form-section-heading h2 {
  font-size: var(--text-lg);
}

.form-section-heading p {
  color: var(--text-muted);
  font-size: var(--text-sm);
  margin-top: var(--space-1);
}

.form-error {
  background: var(--danger-soft);
  border: 1px solid var(--danger);
  border-radius: var(--radius-md);
  color: var(--danger);
  margin-top: var(--space-4);
  padding: var(--space-3);
}

.form-actions {
  display: flex;
  gap: var(--space-3);
  justify-content: flex-end;
  margin-top: var(--space-5);
}

@media (max-width: 560px) {
  .form-actions {
    flex-direction: column-reverse;
  }

  .form-actions button,
  .form-actions .btn-light {
    width: 100%;
  }
}
</style>
