<script setup>
defineProps({
  modelValue: String,
  placeholder: { type: String, default: 'Search…' },
})
const emit = defineEmits(['update:modelValue'])
</script>

<template>
  <div class="search-input">
    <svg
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      stroke-width="2"
      stroke-linecap="round"
      stroke-linejoin="round"
      aria-hidden="true"
    >
      <circle cx="11" cy="11" r="8" />
      <path d="m21 21-4.3-4.3" />
    </svg>
    <input
      :value="modelValue"
      type="search"
      :placeholder="placeholder"
      @input="emit('update:modelValue', $event.target.value)"
    />
  </div>
</template>

<style scoped>
.search-input {
  align-items: center;
  background: var(--surface);
  border: 1px solid var(--border-strong);
  border-radius: var(--radius-md);
  display: inline-flex;
  gap: var(--space-2);
  min-height: var(--control-height);
  padding: var(--space-0) var(--space-3);
  transition:
    border-color var(--transition-fast),
    box-shadow var(--transition-fast);
}

.search-input:focus-within {
  border-color: var(--accent);
  box-shadow: 0 0 0 var(--space-1) var(--focus-ring);
}

.search-input svg {
  color: var(--text-muted);
  flex: 0 0 var(--space-4);
  height: var(--space-4);
  width: var(--space-4);
}

.search-input input {
  background: transparent;
  border: var(--space-0);
  box-shadow: none;
  min-height: var(--control-height-sm);
  padding: var(--space-0);
  width: 220px;
}

.search-input input:focus {
  box-shadow: none;
}
</style>
