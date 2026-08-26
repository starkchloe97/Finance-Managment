<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useInvestorStore } from '@/stores/investorStore'

const router = useRouter()
const investorStore = useInvestorStore()
const submitting = ref(false)
const validationErrors = ref({})

const form = reactive({
  name: '',
  email: '',
  phone: '',
  address: '',
  status: 'active',
  notes: '',
})

const fieldError = (field) => validationErrors.value[field]?.[0] || ''

async function submit() {
  submitting.value = true
  validationErrors.value = {}

  try {
    const investor = await investorStore.createInvestor(form)
    router.push(`/investors/${investor.id}`)
  } catch (error) {
    if (error.response?.status === 422) validationErrors.value = error.response.data.errors || {}
  } finally {
    submitting.value = false
  }
}

function cancel() {
  router.push('/investors')
}
</script>

<template>
  <div class="form-page">
    <div class="page-head">
      <div>
        <span class="section-kicker">Capital</span>
        <h1>Add investor</h1>
        <p class="page-subtitle">Create a profile before recording their investments.</p>
      </div>
    </div>

    <form class="card form-card" @submit.prevent="submit">
      <section class="form-section">
        <div class="form-section-heading">
          <h2>Profile details</h2>
          <p>Use the contact information your team will recognize.</p>
        </div>
        <div class="grid">
          <div class="field">
            <label for="name">Name</label>
            <input
              id="name"
              v-model="form.name"
              type="text"
              autocomplete="name"
              required
              :aria-invalid="fieldError('name') ? 'true' : undefined"
              aria-describedby="name-error"
            />
            <small v-if="fieldError('name')" id="name-error" class="error">{{
              fieldError('name')
            }}</small>
          </div>
          <div class="field">
            <label for="email">Email</label>
            <input
              id="email"
              v-model="form.email"
              type="email"
              autocomplete="email"
              :aria-invalid="fieldError('email') ? 'true' : undefined"
              aria-describedby="email-error"
            />
            <small v-if="fieldError('email')" id="email-error" class="error">{{
              fieldError('email')
            }}</small>
          </div>
          <div class="field">
            <label for="phone">Phone</label>
            <input
              id="phone"
              v-model="form.phone"
              type="tel"
              autocomplete="tel"
              :aria-invalid="fieldError('phone') ? 'true' : undefined"
              aria-describedby="phone-error"
            />
            <small v-if="fieldError('phone')" id="phone-error" class="error">{{
              fieldError('phone')
            }}</small>
          </div>
          <div class="field">
            <label for="status">Status</label>
            <select id="status" v-model="form.status">
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
        </div>
      </section>

      <section class="form-section">
        <div class="form-section-heading">
          <h2>Additional information</h2>
          <p>Keep context available for future investment conversations.</p>
        </div>
        <div class="field">
          <label for="address">Address</label>
          <textarea
            id="address"
            v-model="form.address"
            rows="3"
            autocomplete="street-address"
          ></textarea>
        </div>
        <div class="field">
          <label for="notes">Notes</label>
          <textarea id="notes" v-model="form.notes" rows="3"></textarea>
        </div>
      </section>

      <p v-if="investorStore.error" class="form-error" role="alert">{{ investorStore.error }}</p>

      <div class="form-actions">
        <button class="btn-light" type="button" @click="cancel">Cancel</button>
        <button type="submit" :disabled="submitting" :aria-busy="submitting">
          {{ submitting ? 'Creating investor' : 'Create investor' }}
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

  .form-actions button {
    width: 100%;
  }
}
</style>
