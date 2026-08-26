<script setup>
import { nextTick, ref, watch, onBeforeUnmount } from 'vue'

const props = defineProps({
  open: Boolean,
  title: { type: String, default: 'Are you sure?' },
  message: String,
  confirmLabel: { type: String, default: 'Confirm' },
  cancelLabel: { type: String, default: 'Cancel' },
  variant: { type: String, default: 'primary' },
  loading: Boolean,
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
      document.addEventListener('keydown', onKeydown)
      focusDialog()
    } else {
      document.removeEventListener('keydown', onKeydown)
      if (trigger && typeof trigger.focus === 'function') trigger.focus()
      trigger = null
    }
  },
)

onBeforeUnmount(() => document.removeEventListener('keydown', onKeydown))
</script>

<template>
  <div v-if="open" class="dialog-backdrop" @click.self="close">
    <div
      ref="dialog"
      class="dialog"
      role="dialog"
      aria-modal="true"
      aria-labelledby="confirm-dialog-title"
      tabindex="-1"
    >
      <h3 id="confirm-dialog-title">{{ title }}</h3>
      <p v-if="message" class="dialog-message">{{ message }}</p>
      <slot name="body"></slot>

      <div class="dialog-actions">
        <button type="button" class="btn-light" :disabled="loading" @click="close">
          {{ cancelLabel }}
        </button>
        <button
          type="button"
          class="btn"
          :class="{ 'btn-danger-solid': variant === 'danger' }"
          :disabled="loading"
          @click="emit('confirm')"
        >
          {{ loading ? 'Working…' : confirmLabel }}
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.btn-danger-solid {
  background: var(--danger);
  border-color: var(--danger);
}

.btn-danger-solid:hover {
  background: #b91c1c;
  border-color: #b91c1c;
}
</style>
