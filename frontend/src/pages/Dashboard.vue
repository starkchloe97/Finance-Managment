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
const { dashboard, period, loading, error } = storeToRefs(store)
const changePeriod = (value) => store.setPeriod(value)
const percent = (current, previous) =>
  previous
    ? `${Math.round(((Number(current) - Number(previous)) / Math.abs(Number(previous))) * 100)}%`
    : '—'
const kpis = computed(() =>
  dashboard.value
    ? [
        {
          title: 'Revenue',
          value: money(dashboard.value.kpis.revenue.value),
          subtitle: 'vs. previous period',
          icon: 'revenue',
          trend: percent(dashboard.value.kpis.revenue.value, dashboard.value.kpis.revenue.previous),
          trendDirection: 'up',
          variant: 'revenue',
        },
        {
          title: 'Cost',
          value: money(dashboard.value.kpis.cost.value),
          subtitle: 'vs. previous period',
          icon: 'cost',
          trend: percent(dashboard.value.kpis.cost.value, dashboard.value.kpis.cost.previous),
          trendDirection: 'down',
          variant: 'cost',
        },
        {
          title: 'Profit',
          value: money(dashboard.value.kpis.profit.value),
          subtitle: 'final profit',
          icon: 'profit',
          trend: percent(dashboard.value.kpis.profit.value, dashboard.value.kpis.profit.previous),
          trendDirection: 'up',
          variant: 'profit',
        },
        {
          title: 'Active jobs',
          value: dashboard.value.kpis.active_jobs.value,
          subtitle: 'in selected period',
          icon: 'jobs',
          trend: 'Live',
          trendDirection: 'neutral',
          variant: 'jobs',
        },
      ]
    : [],
)
onMounted(() => store.fetchDashboard())
</script>
<template>
  <div class="dashboard-page">
    <DashboardHeader :period="period" @update:period="changePeriod" />
    <div v-if="error" class="dashboard-error">
      <p>Could not load dashboard data.</p>
      <button type="button" @click="store.refresh">Try again</button>
    </div>
    <template v-else>
      <DashboardSkeleton v-if="loading && !dashboard" /><template v-else-if="dashboard">
        <KpiGrid :items="kpis" />
        <div class="dashboard-visuals">
          <FinancialOverview :series="dashboard.financial_overview" />
          <JobStatusChart :statuses="dashboard.job_status" />
        </div>
        <div class="dashboard-lists">
          <PendingEstimates :estimates="dashboard.pending_estimates" />
          <AttentionPanel :alerts="dashboard.alerts" />
        </div>
        <RecentJobs :jobs="dashboard.recent_jobs" />
      </template>
    </template>
  </div>
</template>

<style scoped>
.dashboard-visuals {
  display: grid;
  gap: var(--space-5);
  grid-template-columns: minmax(var(--space-0), 2fr) minmax(var(--space-0), 1fr);
}

.dashboard-lists {
  display: grid;
  gap: var(--space-5);
  grid-template-columns: repeat(2, minmax(var(--space-0), 1fr));
}

.dashboard-error {
  align-items: center;
  background: var(--danger-soft);
  border: 1px solid var(--danger);
  border-radius: var(--radius-lg);
  color: var(--danger);
  display: flex;
  gap: var(--space-4);
  justify-content: space-between;
  padding: var(--space-5);
}

.dashboard-page :deep(.section-head) {
  align-items: center;
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-3);
  justify-content: space-between;
  margin: var(--space-8) var(--space-0) var(--space-3);
}

.dashboard-page :deep(.dashboard-chart) {
  min-height: var(--login-max-width);
}

.dashboard-page :deep(.chart-canvas) {
  height: var(--login-max-width);
  position: relative;
}

.dashboard-page :deep(.dashboard-list) {
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
  list-style: none;
  padding: var(--space-0);
}

.dashboard-page :deep(.dashboard-list li) {
  align-items: center;
  border-bottom: 1px solid var(--border);
  display: flex;
  gap: var(--space-3);
  justify-content: space-between;
  padding-bottom: var(--space-3);
}

@media (max-width: 1024px) {
  .dashboard-visuals {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 560px) {
  .dashboard-error {
    align-items: stretch;
    flex-direction: column;
  }

  .dashboard-lists {
    grid-template-columns: 1fr;
  }

  .dashboard-page :deep(.dashboard-list li) {
    align-items: flex-start;
    flex-direction: column;
    gap: var(--space-1);
  }
}
</style>
