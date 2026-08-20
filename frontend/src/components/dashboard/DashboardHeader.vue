<script setup>
import DashboardFilters from './DashboardFilters.vue'
defineProps({ period: String })
defineEmits(['update:period'])
</script>
<template>
  <div class="dashboard-header">
    <div>
      <h1>Dashboard</h1>
      <p>Operations and financial performance at a glance.</p>
    </div>
    <DashboardFilters :model-value="period" @update:model-value="$emit('update:period', $event)" />
  </div>
</template>
