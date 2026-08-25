<script setup>
defineProps({
  modelValue: [String, Number],
  options: { type: Array, default: () => [] }, // [{ value, label }]
  placeholder: { type: String, default: 'All' },
})
const emit = defineEmits(['update:modelValue'])
</script>

<template>
  <select
    class="filter-select"
    :value="modelValue"
    @change="emit('update:modelValue', $event.target.value)"
  >
    <option value="">{{ placeholder }}</option>
    <option v-for="option in options" :key="option.value" :value="option.value">
      {{ option.label }}
    </option>
  </select>
</template>

<style scoped>
.filter-select {
  min-width: 140px;
  width: auto;
}
</style>
