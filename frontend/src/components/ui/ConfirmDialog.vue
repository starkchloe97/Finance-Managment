<script setup>
import { nextTick, ref, watch, onBeforeUnmount } from 'vue'

const props = defineProps({
  open: Boolean,
  title: { type: String, default: 'Are you sure?' },
  message: String,
  confirmLabel: { type: String, default: 'Confirm' },
  cancelLabel: { type: String, default: 'Cancel' },
  variant: { type: String, default: 'primary' }, // primary | danger
  loading: Boolean,
  wide: Boolean,
})
const emit = defineEmits(['confirm', 'cancel'])

const dialog = ref(null)
let trigger = null

const focusables = () =>
  [...(dialog.value?.querySelectorAll('button, [href], input, select, textarea') || [])].filter(
    (element) => !element.disabled,
  )

const focusDialog = async () => {
  await nextTick()
  focusables()[0]?.focus()
}

const onKeydown = (event) => {
  if (!props.open) return

  if (event.key === 'Escape') {
    event.preventDefault()
    close()
    return
  }

  if (event.key !== 'Tab') return

  const elements = focusables()
  if (!elements.length) {
    event.preventDefault()
    return
  }

  const first = elements[0]
  const last = elements[elements.length - 1]
  if (event.shiftKey && document.activeElement === first) {
    event.preventDefault()
    last.focus()
  } else if (!event.shiftKey && document.activeElement === last) {
    event.preventDefault()
    first.focus()
  }
}

const close = () => {
  if (!props.loading) emit('cancel')
}

watch(
  () => props.open,
  (open) => {
    if (open) {
      trigger = document.activeElement
      document.body.style.overflow = 'hidden'   // ← add
      document.addEventListener('keydown', onKeydown)
      focusDialog()
    } else {
      document.removeEventListener('keydown', onKeydown)
      document.body.style.overflow = ''          // ← add
      if (trigger && typeof trigger.focus === 'function') trigger.focus()
      trigger = null
    }
  },
)

onBeforeUnmount(() => {
  document.removeEventListener('keydown', onKeydown)
  document.body.style.overflow = ''             // ← add
})
</script>

<template>
  <div v-if="open" class="dialog-backdrop" @click.self="close">
    <div
      ref="dialog"
      class="dialog"
      :class="{ 'dialog-wide': wide }"
      role="dialog"
      aria-modal="true"
      aria-labelledby="confirm-dialog-title"
      tabindex="-1"
    >
      <div class="dialog-head">
        <span
          class="dialog-icon"
          :class="variant === 'danger' ? 'is-danger' : 'is-primary'"
          aria-hidden="true"
        >
          <svg v-if="variant === 'danger'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" /><path d="M12 9v4" /><path d="M12 17h.01" />
          </svg>
          <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10" /><path d="M12 16v-4" /><path d="M12 8h.01" />
          </svg>
        </span>
        <h3 id="confirm-dialog-title">{{ title }}</h3>
      </div>

      <p v-if="message" class="dialog-message">{{ message }}</p>
      <slot name="body"></slot>

      <div class="dialog-actions">
        <button type="button" class="btn-light" :disabled="loading" @click="close">
          {{ cancelLabel }}
        </button>
        <button
          type="button"
          class="btn dialog-confirm"
          :class="{ 'is-danger': variant === 'danger' }"
          :disabled="loading"
          @click="emit('confirm')"
        >
          <span v-if="loading" class="spinner" aria-hidden="true"></span>
          {{ loading ? 'Working…' : confirmLabel }}
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.dialog-head {
  align-items: center;
  display: flex;
  gap: 12px;
  margin-bottom: 6px;
}

.dialog-head h3 {
  margin: 0;
}

.dialog-icon {
  align-items: center;
  border-radius: 10px;
  display: flex;
  flex: 0 0 36px;
  height: 36px;
  justify-content: center;
  width: 36px;
}
.dialog-icon svg { height: 18px; width: 18px; }
.dialog-icon.is-primary { background: var(--accent-soft); color: var(--accent); }
.dialog-icon.is-danger { background: var(--danger-soft); color: var(--danger); }

/* Wider canvas for form dialogs */
.dialog-wide {
  max-width: 600px;
}

/* Never overflow small viewports */
.dialog {
  animation: dialog-pop 0.18s cubic-bezier(0.34, 1.2, 0.64, 1);
  max-height: calc(100vh - 48px);
  overflow-y: auto;
}

.dialog-backdrop {
  animation: backdrop-fade 0.15s ease;
}

.dialog-confirm.is-danger {
  background: var(--danger);
  border-color: var(--danger);
}

.dialog-confirm.is-danger:hover:not(:disabled) {
  background: #b91c1c;
  border-color: #b91c1c;
}

.spinner {
  animation: spin 0.7s linear infinite;
  border: 2px solid rgb(255 255 255 / 40%);
  border-radius: 50%;
  border-top-color: #fff;
  height: 14px;
  width: 14px;
}

@keyframes dialog-pop {
  from { opacity: 0; transform: translateY(8px) scale(0.97); }
}
@keyframes backdrop-fade {
  from { opacity: 0; }
}
@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>