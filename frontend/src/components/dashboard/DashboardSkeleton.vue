<template>
  <div class="dashboard-skeleton" aria-hidden="true">
    <div class="skeleton-kpis">
      <div v-for="n in 4" :key="`kpi-${n}`" class="sk" style="height: 148px"></div>
    </div>
    <div class="skeleton-main">
      <div class="sk" style="height: 320px"></div>
      <div class="sk" style="height: 320px"></div>
    </div>
  </div>
</template>

<style scoped>
.dashboard-skeleton { display: flex; flex-direction: column; gap: 16px; }
.skeleton-kpis { display: grid; gap: 16px; grid-template-columns: repeat(4, minmax(0, 1fr)); }
.skeleton-main { display: grid; gap: 16px; grid-template-columns: minmax(0, 1.7fr) minmax(0, 0.8fr); }

.sk {
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  overflow: hidden;
  position: relative;
}
.sk::after {
  animation: shimmer 1.6s infinite;
  background: linear-gradient(90deg, transparent, rgb(255 255 255 / 70%), transparent);
  content: '';
  inset: 0;
  position: absolute;
  transform: translateX(-100%);
}
@keyframes shimmer { 100% { transform: translateX(100%); } }

@media (max-width: 1024px) {
  .skeleton-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .skeleton-main { grid-template-columns: 1fr; }
}
@media (max-width: 560px) {
  .skeleton-kpis { grid-template-columns: 1fr; }
}
</style>