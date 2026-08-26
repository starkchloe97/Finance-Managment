<script setup>
defineProps({ modelValue: String, disabled: Boolean })
defineEmits(['update:modelValue'])
const periods = [
  ['today', 'Today'],
  ['this_week', 'This week'],
  ['this_month', 'This month'],
  ['last_month', 'Last month'],
  ['this_quarter', 'This quarter'],
  ['this_year', 'This year'],
  ['custom', 'Custom range'],
]
</script>

<template>
  <select
    :value="modelValue"
    aria-label="Dashboard reporting period"
    :disabled="disabled"
    @change="$emit('update:modelValue', $event.target.value)"
  >
    <option v-for="item in periods" :key="item[0]" :value="item[0]">{{ item[1] }}</option>
  </select>
</template>

<style scoped>
select {
  max-width: 180px;
}

@media (max-width: 560px) {
  select {
    max-width: none;
    width: 100%;
  }
}
</style>
