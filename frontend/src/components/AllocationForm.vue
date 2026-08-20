<script setup>
import { computed, ref } from 'vue'
import { createAllocation } from '@/services/investmentFinanceService'
import { getJobs } from '@/services/transportJobService'

const props = defineProps({ investment: { type: Object, required: true } })
const emit = defineEmits(['created'])
const jobs = ref([])
const jobId = ref('')
const amount = ref('')
const error = ref('')
const saving = ref(false)
const canSubmit = computed(() => Number(amount.value) > 0 && Number(amount.value) <= Number(props.investment.remaining_capital) && jobId.value)

getJobs().then((response) => { jobs.value = response.data.data })
const submit = async () => {
  if (!canSubmit.value || saving.value) return
  saving.value = true
  error.value = ''
  try {
    await createAllocation(props.investment.id, { transport_job_id: jobId.value, amount: amount.value })
    jobId.value = ''
    amount.value = ''
    emit('created')
  } catch (e) {
    error.value = e.response?.data?.errors?.amount?.[0] || e.response?.data?.message || 'Could not create allocation.'
  } finally { saving.value = false }
}
</script>

<style scoped>
.allocation-form {
  display: grid;
  grid-template-columns: minmax(0, 1.4fr) minmax(160px, 1fr) auto;
  gap: 14px;
  align-items: center;
}

.allocation-form .error {
  grid-column: 1 / -1;
  margin: 0;
}

@media (max-width: 600px) {
  .allocation-form {
    grid-template-columns: 1fr;
    gap: 12px;
  }

  .allocation-form button {
    width: 100%;
  }
}
</style>

<template>
  <form class="allocation-form" @submit.prevent="submit">
    <select v-model="jobId" :disabled="saving"><option value="">Choose a job</option><option v-for="job in jobs" :key="job.id" :value="job.id">{{ job.code }}</option></select>
    <input v-model="amount" type="number" min="0.01" step="0.01" :max="investment.remaining_capital" placeholder="Amount" :disabled="saving">
    <button type="submit" :disabled="!canSubmit || saving">Allocate</button>
    <small v-if="error" class="error">{{ error }}</small>
  </form>
</template>
