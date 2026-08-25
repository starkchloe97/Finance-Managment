<script setup>
defineProps({
  open: Boolean,
  title: { type: String, default: 'Are you sure?' },
  message: String,
  confirmLabel: { type: String, default: 'Confirm' },
  cancelLabel: { type: String, default: 'Cancel' },
  variant: { type: String, default: 'primary' }, // primary | danger
  loading: Boolean,
})
const emit = defineEmits(['confirm', 'cancel'])

const close = () => {
  if (!props.loading) emit('cancel')
}
</script>

<template>
  <div v-if="open" class="dialog-backdrop" @click.self="close">
    <div class="dialog" role="dialog" aria-modal="true">
      <h3>{{ title }}</h3>
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
