<template>
  <div class="dashboard-skeleton">
    <div v-for="item in 4" :key="item" class="skeleton-block"></div>
  </div>
</template>

<style scoped>
.dashboard-skeleton {
  display: grid;
  gap: var(--space-3);
  grid-template-columns: repeat(4, minmax(150px, 1fr));
}

.dashboard-skeleton .skeleton-block {
  height: 140px;
}

@media (max-width: 1024px) {
  .dashboard-skeleton {
    grid-template-columns: repeat(2, minmax(150px, 1fr));
  }
}

@media (max-width: 560px) {
  .dashboard-skeleton {
    grid-template-columns: 1fr;
  }
}
</style>
