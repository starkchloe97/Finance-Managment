<script setup>
defineProps({
  title: { type: String, default: '' },
  message: { type: String, default: '' },
  variant: { type: String, default: 'error' },
})
</script>

<template>
  <div
    class="inline-notice"
    :class="`inline-notice-${variant}`"
    :role="variant === 'error' ? 'alert' : 'status'"
  >
    <span class="notice-icon" aria-hidden="true">
      <svg
        v-if="variant === 'error'"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
      >
        <circle cx="12" cy="12" r="9" />
        <path d="M12 8v5M12 16h.01" />
      </svg>
      <svg
        v-else
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
      >
        <path d="m5 12 4 4L19 6" />
      </svg>
    </span>
    <span class="notice-copy">
      <strong v-if="title">{{ title }}</strong>
      <span v-if="message">{{ message }}</span>
      <slot />
    </span>
  </div>
</template>

<style scoped>
.inline-notice {
  align-items: flex-start;
  background: var(--danger-soft);
  border: 1px solid color-mix(in srgb, var(--danger) 24%, white);
  border-radius: var(--radius-md);
  color: var(--danger);
  display: flex;
  gap: var(--space-2);
  padding: 10px 12px;
}

.inline-notice-success {
  background: var(--success-soft);
  border-color: color-mix(in srgb, var(--success) 24%, white);
  color: var(--success);
}

.notice-icon {
  display: flex;
  flex: 0 0 16px;
  margin-top: 1px;
}

.notice-icon svg {
  height: 16px;
  width: 16px;
}

.notice-copy {
  color: var(--text-secondary);
  display: grid;
  gap: 2px;
  min-width: 0;
}

.notice-copy strong {
  color: var(--text-primary);
  font-size: var(--text-sm);
  font-weight: var(--font-weight-semibold);
}

.notice-copy > span {
  font-size: var(--text-sm);
  overflow-wrap: anywhere;
}
</style>
