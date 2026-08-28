<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useInvestorStore } from '@/stores/investorStore'
import InvestorFormFields from '@/components/investors/InvestorFormFields.vue'

const route = useRoute()
const router = useRouter()
const investorStore = useInvestorStore()
const { investor, loading } = storeToRefs(investorStore)
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

onMounted(async () => {
  await investorStore.fetchInvestor(route.params.id)
  if (!investor.value) return
  Object.assign(form, {
    name: investor.value.name || '',
    email: investor.value.email || '',
    phone: investor.value.phone || '',
    address: investor.value.address || '',
    status: investor.value.status || 'active',
    notes: investor.value.notes || '',
  })
})

async function submit() {
  submitting.value = true
  validationErrors.value = {}
  try {
    await investorStore.updateInvestor(route.params.id, form)
    router.push(`/investors/${route.params.id}`)
  } catch (error) {
    if (error.response?.status === 422) validationErrors.value = error.response.data.errors || {}
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="form-page">
    <div v-if="loading && !investor" class="state-panel state-loading">
      <div class="skeleton-block"></div>
    </div>

    <template v-else-if="investor">
      <div class="page-head">
        <div>
          <span class="section-kicker">Capital / Investors</span>
          <h1>Edit investor</h1>
          <p class="page-sub">{{ investor.investor_code }}</p>
        </div>
      </div>

      <form class="card form-card" @submit.prevent="submit">
        <InvestorFormFields :form="form" :field-error="fieldError" :submitting="submitting" />

        <p v-if="investorStore.error" class="form-error" role="alert">
          {{ investorStore.error }}
        </p>

        <div class="form-actions">
          <RouterLink class="btn-light" :to="`/investors/${route.params.id}`">Cancel</RouterLink>
          <button type="submit" :disabled="submitting" :aria-busy="submitting">
            {{ submitting ? 'Saving changes' : 'Save changes' }}
          </button>
        </div>
      </form>
    </template>
  </div>
</template>

<style scoped>
/* identical to the create page — or move these shared rules to the global stylesheet */
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