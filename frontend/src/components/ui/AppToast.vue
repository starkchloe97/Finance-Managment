<script setup>
import { useToast } from '@/composables/useToast'

const { toast, dismiss } = useToast()
</script>

<template>
  <Teleport to="body">
    <Transition name="toast">
      <div
        v-if="toast"
        class="toast"
        :class="`toast-${toast.type}`"
        :role="toast.type === 'error' ? 'alert' : 'status'"
      >
        <span class="toast-icon" aria-hidden="true">
          <svg v-if="toast.type === 'success'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10" /><path d="m9 12 2 2 4-4" />
          </svg>
          <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" /><path d="M12 9v4" /><path d="M12 17h.01" />
          </svg>
        </span>

        <span class="toast-message">{{ toast.message }}</span>

        <button type="button" class="toast-close" aria-label="Dismiss notification" @click="dismiss">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
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
  border: 1px solid var(--border);
  border-radius: 12px;
  bottom: 20px;
  box-shadow: var(--shadow-md);
  display: flex;
  font-size: 13px;
  gap: 10px;
  max-width: 360px;
  padding: 11px 11px 11px 14px;
  position: fixed;
  right: 20px;
  z-index: 70;
}

.toast-icon { display: flex; }
.toast-icon svg { height: 17px; width: 17px; }
.toast-success .toast-icon { color: var(--success); }
.toast-error .toast-icon { color: var(--danger); }

.toast-message { color: var(--text-primary); flex: 1; }

.toast-close {
  background: transparent;
  border: 0;
  border-radius: 6px;
  color: var(--text-muted);
  cursor: pointer;
  display: flex;
  padding: 4px;
}
.toast-close svg { height: 13px; width: 13px; }
.toast-close:hover { background: var(--surface-2); color: var(--text-primary); }

.toast-enter-active,
.toast-leave-active { transition: opacity 0.22s ease, transform 0.22s ease; }
.toast-enter-from,
.toast-leave-to { opacity: 0; transform: translateY(10px); }
</style>