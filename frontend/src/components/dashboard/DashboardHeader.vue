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
      <p class="eyebrow">Finance management</p>
      <h1>Operations overview</h1>
      <p class="dashboard-subtitle">A compact view of performance, workload, and next actions.</p>
    </div>

    <div class="dashboard-controls">
      <div class="period-control">
        <span class="control-label">Reporting period</span>
        <DashboardFilters
          :model-value="period"
          :disabled="refreshing"
          @update:model-value="$emit('update:period', $event)"
        />
      </div>
      <div v-if="period === 'custom'" class="custom-range">
        <label>
          <span class="sr-only">From date</span>
          <input
            type="date"
            :value="fromDate"
            :disabled="refreshing"
            @input="$emit('update:from-date', $event.target.value)"
          />
        </label>
        <span aria-hidden="true">to</span>
        <label>
          <span class="sr-only">To date</span>
          <input
            type="date"
            :value="toDate"
            :disabled="refreshing"
            @input="$emit('update:to-date', $event.target.value)"
          />
        </label>
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
        class="btn-light refresh-button"
        type="button"
        :disabled="refreshing"
        @click="$emit('refresh')"
      >
        <span aria-hidden="true">↻</span>
        {{ refreshing ? 'Refreshing' : 'Refresh' }}
      </button>
      <span v-if="lastUpdated" class="last-updated">Updated {{ lastUpdated }}</span>
    </div>
  </header>
</template>

<style scoped>
.dashboard-header {
  align-items: flex-end;
  display: flex;
  gap: var(--space-6);
  justify-content: space-between;
  margin-bottom: var(--space-5);
}

.dashboard-title {
  min-width: 0;
}

.eyebrow,
.control-label,
.last-updated {
  color: var(--text-muted);
  font-size: var(--text-xs);
  letter-spacing: 0.06em;
  text-transform: uppercase;
}

.eyebrow {
  font-weight: var(--font-weight-semibold);
  margin-bottom: var(--space-2);
}

.dashboard-subtitle {
  color: var(--text-secondary);
  margin-top: var(--space-2);
  max-width: 60ch;
}

.dashboard-controls {
  align-items: flex-end;
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-3);
  justify-content: flex-end;
}

.period-control {
  display: flex;
  flex-direction: column;
  gap: var(--space-1);
}

.period-control :deep(select) {
  max-width: 180px;
}

.custom-range {
  align-items: center;
  display: flex;
  gap: var(--space-2);
}

.custom-range input {
  min-width: 140px;
  width: auto;
}

.refresh-button {
  gap: var(--space-2);
}

.last-updated {
  align-self: center;
  letter-spacing: normal;
  text-transform: none;
  white-space: nowrap;
}

@media (max-width: 820px) {
  .dashboard-header {
    align-items: flex-start;
    flex-direction: column;
  }

  .dashboard-controls {
    align-items: stretch;
    justify-content: flex-start;
    width: 100%;
  }

  .period-control {
    flex: 1;
  }

  .period-control :deep(select) {
    max-width: none;
    width: 100%;
  }
}

@media (max-width: 560px) {
  .dashboard-controls,
  .custom-range {
    align-items: stretch;
    flex-direction: column;
  }

  .custom-range input,
  .refresh-button {
    width: 100%;
  }
}
</style>
