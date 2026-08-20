<script setup>
defineProps({ modelValue: String })
defineEmits(['update:modelValue'])
const periods = [
  ['today', 'Today'],
  ['this_week', 'This Week'],
  ['this_month', 'This Month'],
  ['last_month', 'Last Month'],
  ['this_quarter', 'This Quarter'],
  ['this_year', 'This Year'],
  ['custom', 'Custom'],
]
</script>
<template>
  <select
    :value="modelValue"
    aria-label="Dashboard period"
    @change="$emit('update:modelValue', $event.target.value)"
  >
    <option v-for="item in periods" :key="item[0]" :value="item[0]">{{ item[1] }}</option>
  </select>
</template>
