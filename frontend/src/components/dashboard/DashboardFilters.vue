<script setup>
defineProps({
  modelValue: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
  options: {
    type: Array,
    default: () => [
      { value: 'this_week', label: 'Week' },
      { value: 'this_month', label: 'Month' },
      { value: 'this_quarter', label: 'Quarter' },
      { value: 'this_year', label: 'Year' },
      { value: 'all_time', label: 'All time' },
      { value: 'custom', label: 'Custom' },
    ],
  },
})
defineEmits(['update:model-value'])
</script>

<template>
  <div class="segmented" role="tablist" aria-label="Reporting period">
    <button
      v-for="option in options"
      :key="option.value"
      type="button"
      role="tab"
      class="segmented-item"
      :class="{ active: option.value === modelValue }"
      :aria-selected="option.value === modelValue"
      :disabled="disabled"
      @click="$emit('update:model-value', option.value)"
    >
      {{ option.label }}
    </button>
  </div>
</template>

<style scoped>
.segmented {
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: 12px;
  display: inline-flex;
  gap: 2px;
  padding: 3px;
}
.segmented-item {
  background: transparent;
  border: 0;
  border-radius: 9px;
  color: var(--text-secondary);
  cursor: pointer;
  font: inherit;
  font-size: 13px;
  font-weight: 500;
  padding: 7px 14px;
  transition: background 0.15s, color 0.15s, box-shadow 0.15s;
  white-space: nowrap;
}
.segmented-item:hover:not(.active):not(:disabled) { color: var(--text-primary); }
.segmented-item.active {
  background: var(--surface);
  box-shadow: var(--shadow-xs);
  color: var(--text-primary);
  font-weight: 600;
}
.segmented-item:disabled { opacity: 0.6; cursor: not-allowed; }
</style>