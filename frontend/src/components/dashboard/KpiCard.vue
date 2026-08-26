<script setup>
const iconPaths = {
  revenue: '<path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H7" />',
  cost: '<path d="M6 2h9l3 3v17H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Z" /><path d="M14 2v4h4M8 11h8M8 15h5" />',
  profit: '<path d="m3 17 6-6 4 4 8-8" /><path d="M15 7h6v6" />',
  margin: '<path d="M4 19V5M4 19h16" /><path d="m7 15 3-4 3 2 5-7" />',
  jobs: '<path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2" /><path d="M15 18H9M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.62l-3.48-4.35A1 1 0 0 0 17.52 8H14" /><circle cx="17" cy="18" r="2" /><circle cx="7" cy="18" r="2" />',
}

defineProps({
  title: String,
  value: [String, Number],
  subtitle: String,
  icon: String,
  trend: [String, Number],
  trendDirection: { type: String, default: 'neutral' },
  variant: { type: String, default: 'neutral' },
  trendLabel: String,
})
</script>

<template>
  <article class="kpi-card" :class="`kpi-${variant}`">
    <div class="kpi-card-top">
      <span class="kpi-title">{{ title }}</span>
      <span class="kpi-icon" aria-hidden="true">
        <svg
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="1.8"
          stroke-linecap="round"
          stroke-linejoin="round"
          v-html="iconPaths[icon]"
        />
      </span>
    </div>
    <strong class="kpi-value">{{ value }}</strong>
    <div class="kpi-meta">
      <span
        v-if="trend !== null && trend !== undefined"
        class="kpi-trend"
        :class="`trend-${trendDirection}`"
      >
        <span aria-hidden="true">{{
          trendDirection === 'up' ? '↑' : trendDirection === 'down' ? '↓' : '•'
        }}</span>
        {{ trend }}
        <span class="sr-only">{{ trendLabel || trendDirection }}</span>
      </span>
      <span class="kpi-subtitle">{{ subtitle }}</span>
    </div>
  </article>
</template>

<style scoped>
.kpi-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-sm);
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
  min-height: 132px;
  overflow: hidden;
  padding: var(--space-4);
  position: relative;
}

.kpi-card::before {
  background: var(--accent);
  content: '';
  inset-block: var(--space-0);
  inset-inline-start: var(--space-0);
  position: absolute;
  width: var(--space-1);
}

.kpi-card-top,
.kpi-meta {
  align-items: center;
  display: flex;
  gap: var(--space-2);
}

.kpi-card-top {
  justify-content: space-between;
  min-width: var(--space-0);
}

.kpi-icon {
  align-items: center;
  background: var(--accent-soft);
  border-radius: var(--radius-md);
  color: var(--accent);
  display: inline-flex;
  flex: 0 0 var(--space-8);
  height: var(--space-8);
  justify-content: center;
  width: var(--space-8);
}

.kpi-icon svg {
  height: var(--space-4);
  width: var(--space-4);
}

.kpi-revenue::before {
  background: var(--accent);
}

.kpi-cost::before {
  background: var(--warning);
}

.kpi-cost .kpi-icon {
  background: var(--warning-soft);
  color: var(--warning);
}

.kpi-profit::before {
  background: var(--success);
}

.kpi-profit .kpi-icon {
  background: var(--success-soft);
  color: var(--success);
}

.kpi-margin::before,
.kpi-jobs::before {
  background: var(--info);
}

.kpi-margin .kpi-icon,
.kpi-jobs .kpi-icon {
  background: var(--info-soft);
  color: var(--info);
}

.kpi-title {
  color: var(--text-secondary);
  display: block;
  font-size: var(--text-xs);
  font-weight: var(--font-weight-semibold);
  letter-spacing: 0.04em;
  min-width: var(--space-0);
  overflow: hidden;
  text-overflow: ellipsis;
  text-transform: uppercase;
  white-space: nowrap;
}

.kpi-value {
  display: block;
  font-size: var(--text-2xl);
  font-variant-numeric: tabular-nums;
  font-weight: var(--font-weight-semibold);
  letter-spacing: -0.02em;
  line-height: var(--line-height-tight);
  min-width: var(--space-0);
  overflow-wrap: anywhere;
}

.kpi-meta {
  flex-wrap: wrap;
  margin: auto var(--space-0) var(--space-0);
  min-width: var(--space-0);
}

.kpi-trend {
  border-radius: var(--radius-pill);
  display: inline-flex;
  font-size: var(--text-xs);
  font-weight: var(--font-weight-semibold);
  gap: var(--space-1);
  line-height: var(--line-height-tight);
  padding: var(--space-1) var(--space-2);
}

.kpi-subtitle {
  color: var(--text-muted);
  font-size: var(--text-xs);
  line-height: var(--line-height-tight);
  min-width: var(--space-0);
  overflow-wrap: anywhere;
}

.trend-up {
  background: var(--success-soft);
  color: var(--success);
}

.trend-down {
  background: var(--warning-soft);
  color: var(--warning);
}

.trend-neutral {
  background: var(--surface-muted);
  color: var(--text-secondary);
}
</style>
