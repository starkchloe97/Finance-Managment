<script setup>
const iconPaths = {
  revenue: '<path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H7" />',
  cost: '<path d="M6 2h9l3 3v17H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Z" /><path d="M14 2v4h4M8 11h8M8 15h5" />',
  profit: '<path d="m3 17 6-6 4 4 8-8" /><path d="M15 7h6v6" />',
  jobs: '<path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2" /><path d="M15 18H9M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.62l-3.48-4.35A1 1 0 0 0 17.52 8H14" /><circle cx="17" cy="18" r="2" /><circle cx="7" cy="18" r="2" />',
}

defineProps({
  title: String,
  value: [String, Number],
  subtitle: String,
  icon: String,
  trend: [String, Number],
  trendDirection: String,
  variant: String,
})
</script>
<template>
  <article class="kpi-card" :class="`kpi-${variant}`">
    <div class="kpi-card-top">
      <span class="kpi-title">{{ title }}</span>
      <span class="kpi-icon">
        <svg
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="1.8"
          stroke-linecap="round"
          stroke-linejoin="round"
          aria-hidden="true"
          v-html="iconPaths[icon]"
        />
      </span>
    </div>
    <strong class="kpi-value">{{ value }}</strong>
    <p class="kpi-meta">
      <span class="kpi-trend" :class="`trend-${trendDirection}`">{{ trend }}</span>
      <span class="kpi-subtitle">{{ subtitle }}</span>
    </p>
  </article>
</template>
