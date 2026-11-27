<script setup>
import { useToast } from '@/composables/useToast'

const { toast, dismiss } = useToast()

const titleFor = (notification) =>
  notification.title ||
  (notification.type === 'error'
    ? 'Something needs your attention'
    : notification.type === 'success'
      ? 'Done'
      : 'Notice')
</script>

<template>
  <Teleport to="body">
    <Transition name="toast">
      <div
        v-if="toast"
        class="toast"
        :class="`toast-${toast.type}`"
        :role="toast.type === 'error' ? 'alert' : 'status'"
        aria-live="polite"
      >
        <span class="toast-icon" aria-hidden="true">
          <svg
            v-if="toast.type === 'success'"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          >
            <circle cx="12" cy="12" r="10" />
            <path d="m9 12 2 2 4-4" />
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
            <path
              d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"
            />
            <path d="M12 9v4" />
            <path d="M12 17h.01" />
          </svg>
        </span>

        <span class="toast-copy">
          <strong>{{ titleFor(toast) }}</strong>
          <span class="toast-message">{{ toast.message }}</span>
        </span>

        <button
          type="button"
          class="toast-close"
          aria-label="Dismiss notification"
          @click="dismiss"
        >
          <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
          >
            <path d="M18 6 6 18M6 6l12 12" />
          </svg>
        </button>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.toast {
  align-items: center;
  background: var(--surface);
  border: 1px solid color-mix(in srgb, var(--border-strong) 72%, white);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-lg);
  gap: var(--space-3);
  inset-inline: 0;
  margin-inline: auto;
  max-width: min(540px, calc(100vw - 32px));
  padding: 12px 12px 12px 14px;
  position: fixed;
  top: max(16px, env(safe-area-inset-top));
  z-index: 100;
}

.toast-icon {
  align-items: center;
  align-self: flex-start;
  display: flex;
  flex: 0 0 18px;
  justify-content: center;
  margin-top: 2px;
}

.toast-icon svg {
  height: 18px;
  width: 18px;
}

.toast-success .toast-icon {
  color: var(--success);
}

.toast-error .toast-icon {
  color: var(--danger);
}

.toast-success {
  background: var(--success-soft);
  border-color: color-mix(in srgb, var(--success) 22%, white);
}

.toast-error {
  background: var(--danger-soft);
  border-color: color-mix(in srgb, var(--danger) 22%, white);
}

.toast-copy {
  display: grid;
  flex: 1;
  gap: 2px;
  min-width: 0;
}

.toast-copy strong {
  color: var(--text-primary);
  font-size: var(--text-sm);
  font-weight: var(--font-weight-semibold);
}

.toast-message {
  color: var(--text-secondary);
  font-size: var(--text-sm);
  overflow-wrap: anywhere;
}

.toast-close {
  align-self: flex-start;
  background: transparent;
  border: 0;
  border-radius: var(--radius-sm);
  color: var(--text-muted);
  cursor: pointer;
  display: flex;
  flex: 0 0 auto;
  padding: 4px;
}

.toast-close svg {
  height: 14px;
  width: 14px;
}

.toast-close:hover {
  background: rgb(15 23 42 / 6%);
  color: var(--text-primary);
}

.toast-close:focus-visible {
  outline: 2px solid currentColor;
  outline-offset: 2px;
}

.toast-enter-active,
.toast-leave-active {
  transition:
    opacity 0.18s ease,
    transform 0.18s ease;
}

.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}
</style>
