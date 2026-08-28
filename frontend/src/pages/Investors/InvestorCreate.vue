<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useInvestorStore } from '@/stores/investorStore'
import InvestorFormFields from '@/components/investors/InvestorFormFields.vue'

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
</script>

<template>
  <div class="form-page">
    <div class="page-head">
      <div>
        <span class="section-kicker">Capital</span>
        <h1>Add investor</h1>
        <p class="page-sub">Create a profile first — then record their investments and loans on it.</p>
      </div>
    </div>

    <form class="card form-card" @submit.prevent="submit">
      <InvestorFormFields :form="form" :field-error="fieldError" :submitting="submitting" />

      <p v-if="investorStore.error" class="form-error" role="alert">
        {{ investorStore.error }}
      </p>

      <div class="form-actions">
        <RouterLink class="btn-light" to="/investors">Cancel</RouterLink>
        <button type="submit" :disabled="submitting" :aria-busy="submitting">
          {{ submitting ? 'Creating investor' : 'Create investor' }}
        </button>
      </div>
    </form>
  </div>
</template>

<style scoped>
.form-page,
.form-card { min-width: 0; }

.page-sub {
  color: var(--text-secondary);
  font-size: 14px;
  margin-top: var(--space-2);
}

.form-card { max-width: 980px; }

.form-error {
  background: var(--danger-soft);
  border: 1px solid var(--danger);
  border-radius: var(--radius-md);
  color: var(--danger);
  margin-top: var(--space-4);
  padding: var(--space-3);
}

.form-actions {
  border-top: 1px solid var(--border);
  display: flex;
  gap: var(--space-3);
  justify-content: flex-end;
  margin-top: var(--space-5);
  padding-top: var(--space-5);
}

@media (max-width: 560px) {
  .form-actions { flex-direction: column-reverse; }
  .form-actions button,
  .form-actions .btn-light { width: 100%; }
}
</style>