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

<style scoped>
.dashboard-header {
  align-items: flex-end;
  display: flex;
  gap: var(--space-4);
  justify-content: space-between;
  margin-bottom: var(--space-6);
}

.dashboard-header p {
  color: var(--text-muted);
  margin-top: var(--space-2);
}

@media (max-width: 560px) {
  .dashboard-header {
    align-items: stretch;
    flex-direction: column;
  }
}
</style>
