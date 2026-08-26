<script setup>
import KpiCard from './KpiCard.vue'

defineProps({ items: { type: Array, default: () => [] } })
</script>

<template>
  <section class="kpi-grid" aria-label="Financial overview">
    <KpiCard v-for="item in items" :key="item.title" v-bind="item" />
  </section>
</template>

<style scoped>
.kpi-grid {
  display: grid;
  gap: 14px;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  margin-bottom: var(--space-4);
}

@media (max-width: 1024px) { .kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 560px) { .kpi-grid { grid-template-columns: 1fr; } }
</style>