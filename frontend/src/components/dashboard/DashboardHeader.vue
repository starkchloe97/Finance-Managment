<script setup>
import DashboardFilters from './DashboardFilters.vue'

defineProps({
  period: String,
  fromDate: String,
  toDate: String,
  refreshing: Boolean,
  lastUpdated: String,
})

defineEmits(['update:period', 'update:from-date', 'update:to-date', 'refresh', 'apply-custom'])
</script>

<template>
  <header class="dashboard-header">
    <div class="dashboard-title">
      <h1>Overview</h1>
      <p class="dashboard-subtitle">Revenue, costs, profit, and live workload — at a glance.</p>
    </div>

    <div class="dashboard-controls">
      <DashboardFilters
        :model-value="period"
        :disabled="refreshing"
        @update:model-value="$emit('update:period', $event)"
      />

      <div v-if="period === 'custom'" class="custom-range">
        <input
          type="date"
          aria-label="From date"
          :value="fromDate"
          :disabled="refreshing"
          @input="$emit('update:from-date', $event.target.value)"
        />
        <span class="range-sep" aria-hidden="true">–</span>
        <input
          type="date"
          aria-label="To date"
          :value="toDate"
          :disabled="refreshing"
          @input="$emit('update:to-date', $event.target.value)"
        />
        <button
          class="btn-light btn-sm"
          type="button"
          :disabled="refreshing"
          @click="$emit('apply-custom')"
        >
          Apply
        </button>
      </div>

      <button
        class="icon-button"
        :class="{ spinning: refreshing }"
        type="button"
        :disabled="refreshing"
        :title="refreshing ? 'Refreshing…' : 'Refresh data'"
        @click="$emit('refresh')"
      >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M21 12a9 9 0 1 1-2.64-6.36" />
          <path d="M21 3v6h-6" />
        </svg>
      </button>

      <span v-if="lastUpdated" class="last-updated">Updated {{ lastUpdated }}</span>
    </div>
  </header>
</template>

<style scoped>
.dashboard-header {
  align-items: center;
  display: flex;
  gap: var(--space-5);
  justify-content: space-between;
  margin-bottom: var(--space-5);
  flex-wrap: wrap;
}

.dashboard-title h1 {
  font-size: 24px;
  font-weight: 700;
  letter-spacing: -0.02em;
}

.dashboard-subtitle {
  color: var(--text-secondary);
  font-size: 14px;
  margin-top: 4px;
}

.dashboard-controls {
  align-items: center;
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-3);
}

.custom-range { align-items: center; display: flex; gap: 8px; }
.custom-range input {
  background: var(--surface);
  border: 1px solid var(--border-strong);
  border-radius: 10px;
  color: var(--text-primary);
  font: inherit;
  font-size: 13px;
  padding: 7px 10px;
}
.custom-range input:focus-visible { border-color: var(--accent); outline: 2px solid var(--accent-soft); }
.range-sep { color: var(--text-muted); }

.icon-button {
  align-items: center;
  background: var(--surface);
  border: 1px solid var(--border-strong);
  border-radius: 10px;
  color: var(--text-secondary);
  cursor: pointer;
  display: inline-flex;
  height: 38px;
  justify-content: center;
  width: 45px;
  transition: background 0.15s, color 0.15s;
}
.icon-button:hover:not(:disabled) { background: var(--surface-2); color: var(--text-primary); }
.icon-button svg { height: 16px; width: 16px; }
.icon-button.spinning svg { animation: spin 0.9s linear infinite; }

@keyframes spin { to { transform: rotate(360deg); } }

.last-updated { color: var(--text-muted); font-size: 12px; white-space: nowrap; }

@media (max-width: 820px) {
  .dashboard-controls { width: 100%; }
}

@media (max-width: 560px) {
  .dashboard-controls,
  .custom-range { align-items: stretch; flex-direction: column; }
  .custom-range input { width: 100%; }
}
</style>