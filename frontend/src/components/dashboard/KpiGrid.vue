<script setup>
import KpiCard from './KpiCard.vue'
defineProps({ items: Array })
</script>
<template>
  <section class="kpi-grid">
    <KpiCard v-for="item in items" :key="item.title" v-bind="item" />
  </section>
</template>

<style scoped>
.kpi-grid {
  display: grid;
  gap: var(--space-3);
  grid-template-columns: repeat(4, minmax(150px, 1fr));
  margin-bottom: var(--space-5);
}

@media (max-width: 1024px) {
  .kpi-grid {
    grid-template-columns: repeat(2, minmax(150px, 1fr));
  }
}

@media (max-width: 560px) {
  .kpi-grid {
    grid-template-columns: 1fr;
  }
}
</style>
