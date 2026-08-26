<script setup>
import { computed, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { money } from '@/utils/money'
import { useDashboardStore } from '@/stores/dashboard'
import DashboardHeader from '@/components/dashboard/DashboardHeader.vue'
import KpiGrid from '@/components/dashboard/KpiGrid.vue'
import FinancialOverview from '@/components/dashboard/FinancialOverview.vue'
import JobStatusChart from '@/components/dashboard/JobStatusChart.vue'
import RecentJobs from '@/components/dashboard/RecentJobs.vue'
import PendingEstimates from '@/components/dashboard/PendingEstimates.vue'
import AttentionPanel from '@/components/dashboard/AttentionPanel.vue'
import DashboardSkeleton from '@/components/dashboard/DashboardSkeleton.vue'

const store = useDashboardStore()
const { dashboard, period, fromDate, toDate, loading, refreshing, error, statusMessage } =
  storeToRefs(store)

const updatePeriod = (value) => store.setPeriod(value)
const applyCustom = () => store.setCustomRange(fromDate.value, toDate.value)
const updateFromDate = (value) => (fromDate.value = value)
const updateToDate = (value) => (toDate.value = value)

const percentChange = (metric) => {
  if (!metric || metric.previous === null || metric.previous === undefined) return null
  const current = Number(metric.value || 0)
  const previous = Number(metric.previous || 0)
  if (previous === 0) return current === 0 ? 'No change' : 'New'
  const change = Math.round(((current - previous) / Math.abs(previous)) * 100)
  return `${change > 0 ? '+' : ''}${change}%`
}

const trendDirection = (metric, favorableWhenUp = true) => {
  if (!metric || metric.previous === null || metric.previous === undefined) return 'neutral'
  const delta = Number(metric.value || 0) - Number(metric.previous || 0)
  if (delta === 0) return 'neutral'
  return delta > 0 === favorableWhenUp ? 'up' : 'down'
}

const kpis = computed(() => {
  const data = dashboard.value?.kpis || {}
  const plannedCost = data.planned_cost || data.cost
  const actualCost = data.actual_cost || plannedCost
  const profit = data.profit
  const margin = data.profit_margin

  return [
    {
      title: 'Revenue',
      value: money(data.revenue?.value),
      subtitle: 'Selected period',
      icon: 'revenue',
      trend: percentChange(data.revenue),
      trendDirection: trendDirection(data.revenue),
      trendLabel: 'change versus previous period',
      variant: 'revenue',
    },
    {
      title: 'Actual cost',
      value: money(actualCost?.value),
      subtitle: `Planned ${money(plannedCost?.value)}`,
      icon: 'cost',
      trend: percentChange(actualCost),
      trendDirection: trendDirection(actualCost, false),
      trendLabel: 'cost change versus previous period',
      variant: 'cost',
    },
    {
      title: 'Final profit',
      value: money(profit?.value),
      subtitle: `${money(data.extra_costs?.value)} unexpected costs`,
      icon: 'profit',
      trend: percentChange(profit),
      trendDirection: Number(profit?.value || 0) < 0 ? 'down' : trendDirection(profit),
      trendLabel: 'profit change versus previous period',
      variant: 'profit',
    },
    margin
      ? {
          title: 'Profit margin',
          value: `${Number(margin.value || 0).toFixed(1)}%`,
          subtitle: 'Final profit / revenue',
          icon: 'margin',
          trend: percentChange(margin),
          trendDirection: trendDirection(margin),
          trendLabel: 'margin change versus previous period',
          variant: 'margin',
        }
      : {
          title: 'Active jobs',
          value: data.active_jobs?.value ?? 0,
          subtitle: 'Open in selected period',
          icon: 'jobs',
          trend: percentChange(data.active_jobs),
          trendDirection: trendDirection(data.active_jobs),
          trendLabel: 'active job change versus previous period',
          variant: 'jobs',
        },
  ]
})

const periodLabel = computed(() => {
  const meta = dashboard.value?.meta
  if (!meta?.from || !meta?.to) return 'Selected reporting period'
  return `${meta.from} to ${meta.to}`
})

const lastUpdated = computed(() => {
  const value = dashboard.value?.meta?.generated_at
  return value
    ? new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'short' }).format(
        new Date(value),
      )
    : ''
})

onMounted(() => store.fetchDashboard().catch(() => {}))
</script>

<template>
  <div class="dashboard-page">
    <DashboardHeader
      :period="period"
      :from-date="fromDate"
      :to-date="toDate"
      :refreshing="refreshing"
      :last-updated="lastUpdated"
      @update:period="updatePeriod"
      @update:from-date="updateFromDate"
      @update:to-date="updateToDate"
      @apply-custom="applyCustom"
      @refresh="store.refresh"
    />

    <div v-if="error" class="dashboard-error" role="alert">
      <div>
        <strong>Dashboard data is unavailable.</strong>
        <p>{{ error }}</p>
      </div>
      <button type="button" @click="store.refresh">Try again</button>
    </div>

    <div v-if="statusMessage" class="sr-only" role="status">{{ statusMessage }}</div>

    <DashboardSkeleton v-if="loading && !dashboard" />

    <template v-else-if="dashboard">
      <div class="dashboard-scope-row">
        <div>
          <span class="section-kicker">Financial performance</span>
          <strong>{{ periodLabel }}</strong>
        </div>
        <span v-if="refreshing" class="refreshing-indicator" role="status">Refreshing data…</span>
      </div>

      <KpiGrid :items="kpis" />

      <div class="dashboard-primary-grid">
        <FinancialOverview :series="dashboard.financial_overview" :kpis="dashboard.kpis" />
        <JobStatusChart :statuses="dashboard.current_pipeline" />
      </div>

      <div class="dashboard-work-grid">
        <PendingEstimates :estimates="dashboard.pending_estimates" />
        <AttentionPanel :alerts="dashboard.alerts" />
      </div>

      <RecentJobs :jobs="dashboard.recent_jobs" />
    </template>
  </div>
</template>

<style scoped>
.dashboard-page {
  min-width: 0;
}

.dashboard-scope-row {
  align-items: center;
  border-bottom: 1px solid var(--border);
  display: flex;
  justify-content: space-between;
  margin-bottom: var(--space-3);
  padding-bottom: var(--space-3);
}

.section-kicker {
  color: var(--text-muted);
  display: block;
  font-size: var(--text-xs);
  font-weight: var(--font-weight-semibold);
  letter-spacing: 0.06em;
  margin-bottom: var(--space-1);
  text-transform: uppercase;
}

.dashboard-scope-row strong {
  color: var(--text-secondary);
  font-size: var(--text-sm);
  font-weight: var(--font-weight-medium);
}

.refreshing-indicator {
  color: var(--accent);
  font-size: var(--text-sm);
}

.dashboard-primary-grid,
.dashboard-work-grid {
  display: grid;
  gap: var(--space-4);
}

.dashboard-primary-grid {
  grid-template-columns: minmax(0, 1.7fr) minmax(280px, 0.8fr);
}

.dashboard-work-grid {
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

.dashboard-error {
  align-items: center;
  background: var(--danger-soft);
  border: 1px solid var(--danger);
  border-radius: var(--radius-md);
  color: var(--danger);
  display: flex;
  gap: var(--space-4);
  justify-content: space-between;
  margin-bottom: var(--space-4);
  padding: var(--space-4);
}

.dashboard-error p {
  color: var(--text-secondary);
  margin-top: var(--space-1);
}

.dashboard-error button {
  background: var(--surface);
  border-color: var(--danger);
  color: var(--danger);
}

@media (max-width: 1024px) {
  .dashboard-primary-grid,
  .dashboard-work-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 560px) {
  .dashboard-scope-row,
  .dashboard-error {
    align-items: flex-start;
    flex-direction: column;
  }

  .dashboard-error button {
    width: 100%;
  }
}
</style>
