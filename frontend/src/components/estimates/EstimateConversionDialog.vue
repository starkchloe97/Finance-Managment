<script setup>
import { nextTick, ref, watch, onBeforeUnmount } from 'vue'
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
const dialog = ref(null)
let opener = null

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

const onKeydown = (event) => {
  if (event.key === 'Escape' && !converting.value) {
    event.preventDefault()
    close()
  }
}

watch(
  () => props.open,
  async (open) => {
    if (open) {
      opener = document.activeElement
      document.addEventListener('keydown', onKeydown)
      await nextTick()
      dialog.value?.querySelector('button')?.focus()
    } else {
      document.removeEventListener('keydown', onKeydown)
      opener?.focus?.()
      opener = null
    }
  },
)

onBeforeUnmount(() => document.removeEventListener('keydown', onKeydown))
</script>

<template>
  <div v-if="open" class="dialog-backdrop" @click.self="close">
    <div ref="dialog" class="dialog" role="dialog" aria-modal="true" aria-labelledby="convert-dialog-title">
      <!-- Success state -->
      <template v-if="createdJob">
        <div class="dialog-success">
          <span class="success-check" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
              <path d="M20 6 9 17l-5-5" />
            </svg>
          </span>
          <h3 id="convert-dialog-title">Job created</h3>
          <p>
            {{ estimate.customer?.name }}'s quote has become job
            <strong>{{ createdJob.code }}</strong>.
          </p>
          <p class="success-hint">
            The estimate is marked accepted and locked — all tracking now happens on the job.
          </p>
        </div>
        <div class="dialog-actions">
          <button type="button" class="btn-light" @click="close">Stay here</button>
          <button type="button" class="btn" @click="viewJob">View job →</button>
        </div>
      </template>

      <!-- Confirmation state -->
      <template v-else>
        <h3 id="convert-dialog-title">Convert estimate to job?</h3>
        <p class="dialog-message">The customer accepted — turn this quote into a transport job.</p>

        <div class="dialog-detail">
          <div>
            <span>Customer</span>
            <strong>{{ estimate?.customer?.name || '—' }}</strong>
          </div>
          <div>
            <span>Estimate</span>
            <strong>{{ estimate?.code }}</strong>
          </div>
          <div>
            <span>Quoted amount</span>
            <strong>{{ money(estimate?.estimated_sell) }}</strong>
          </div>
        </div>

        <div class="dialog-note">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="12" cy="12" r="10" /><path d="M12 16v-4" /><path d="M12 8h.01" />
          </svg>
          The estimate becomes accepted and can no longer be edited.
        </div>

        <p v-if="error" class="error">{{ error }}</p>

        <div class="dialog-actions">
          <button type="button" class="btn-light" :disabled="converting" @click="close">
            Cancel
          </button>
          <button type="button" class="btn" :disabled="converting" @click="convert">
            {{ converting ? 'Converting…' : 'Convert to job' }}
          </button>
        </div>
      </template>
    </div>
  </div>
</template>

<style scoped>
.dialog-success { text-align: center; }

.success-check {
  align-items: center;
  animation: pop 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
  background: var(--success);
  border-radius: 50%;
  color: #fff;
  display: inline-flex;
  height: 56px;
  justify-content: center;
  margin-bottom: var(--space-3);
  width: 56px;
}
.success-check svg { height: 26px; width: 26px; }

@keyframes pop {
  from { transform: scale(0.6); opacity: 0; }
}

.success-hint {
  color: var(--text-muted);
  font-size: 13px;
  margin: var(--space-2) 0 0;
}

.dialog-detail {
  background: var(--surface-2);
  border-radius: var(--radius-md);
  margin: var(--space-4) 0;
  padding: var(--space-3) var(--space-4);
}

.dialog-detail > div {
  align-items: center;
  display: flex;
  justify-content: space-between;
  padding: var(--space-1) 0;
}

.dialog-detail span {
  color: var(--text-muted);
  font-size: var(--text-sm);
}

.dialog-note {
  align-items: flex-start;
  background: var(--warning-soft);
  border-radius: var(--radius-md);
  color: var(--warning);
  display: flex;
  font-size: 13px;
  gap: 8px;
  padding: 10px 12px;
}
.dialog-note svg { flex: 0 0 16px; height: 16px; margin-top: 1px; width: 16px; }
</style>