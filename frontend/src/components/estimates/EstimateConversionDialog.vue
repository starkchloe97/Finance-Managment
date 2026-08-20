<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { convertEstimate } from '@/services/transportJobService'
import { money } from '@/utils/money'

const props = defineProps({
  open: Boolean,
  estimate: { type: Object, default: null },
})
const emit = defineEmits(['close'])

const router = useRouter()
const converting = ref(false)
const error = ref('')
const createdJob = ref(null)

const close = () => {
  if (converting.value) return
  createdJob.value = null
  error.value = ''
  emit('close')
}

const convert = async () => {
  if (converting.value || !props.estimate) return

  converting.value = true
  error.value = ''

  try {
    const { data } = await convertEstimate(props.estimate.id)
    createdJob.value = data.data
  } catch (e) {
    error.value =
      e.response?.data?.errors?.estimate?.[0] ||
      e.response?.data?.message ||
      'Could not convert the estimate.'
  } finally {
    converting.value = false
  }
}

const viewJob = () => {
  if (!createdJob.value) return
  const id = createdJob.value.id
  close()
  router.push(`/jobs/${id}`)
}
</script>

<template>
  <div v-if="open" class="dialog-backdrop" @click.self="close">
    <div class="dialog" role="dialog" aria-modal="true">
      <!-- Success state: the job exists, point at it -->
      <template v-if="createdJob">
        <div class="dialog-success">
          <span class="success-check">✓</span>
          <h3>Job created</h3>
          <p>
            {{ estimate.customer?.name }}'s quote has become job
            <strong>{{ createdJob.code }}</strong
            >.
          </p>
          <p class="hint">The estimate is now marked as accepted and the job is ready to run.</p>
        </div>
        <div class="dialog-actions">
          <button type="button" class="btn-light" @click="close">Stay here</button>
          <button type="button" class="btn" @click="viewJob">View Job →</button>
        </div>
      </template>

      <!-- Confirmation state -->
      <template v-else>
        <h3>Convert estimate to job?</h3>
        <p class="dialog-message">This accepts the quote and starts a transport job for it.</p>

        <div class="dialog-detail">
          <div>
            <span>Customer</span><strong>{{ estimate?.customer?.name }}</strong>
          </div>
          <div>
            <span>Estimate</span><strong>{{ estimate?.code }}</strong>
          </div>
          <div>
            <span>Quoted amount</span><strong>{{ money(estimate?.estimated_sell) }}</strong>
          </div>
        </div>

        <p class="hint">The estimate becomes accepted and can no longer be edited.</p>

        <p v-if="error" class="error">{{ error }}</p>

        <div class="dialog-actions">
          <button type="button" class="btn-light" :disabled="converting" @click="close">
            Cancel
          </button>
          <button type="button" class="btn" :disabled="converting" @click="convert">
            {{ converting ? 'Converting…' : 'Convert to Job' }}
          </button>
        </div>
      </template>
    </div>
  </div>
</template>
