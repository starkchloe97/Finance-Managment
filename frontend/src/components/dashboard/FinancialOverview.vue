<script setup>
import { computed } from 'vue'
import { Line } from 'vue-chartjs'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Tooltip,
  Legend,
} from 'chart.js'
import { money } from '@/utils/money'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Tooltip, Legend)

const props = defineProps({
  series: { type: Array, default: () => [] },
  kpis: { type: Object, default: () => ({}) },
})

const chartData = computed(() => ({
  labels: props.series.map((item) => item.period),
  datasets: [
    {
      label: 'Revenue',
      data: props.series.map((item) => item.revenue),
      borderColor: '#2563eb',
      backgroundColor: 'rgb(37 99 235 / 8%)',
      borderWidth: 2,
      pointRadius: 2,
      tension: 0.3,
    },
    {
      label: 'Actual cost',
      data: props.series.map((item) => item.actual_cost ?? item.cost),
      borderColor: '#d97706',
      backgroundColor: 'transparent',
      borderWidth: 2,
      pointRadius: 2,
      tension: 0.3,
    },
    {
      label: 'Final profit',
      data: props.series.map((item) => item.profit),
      borderColor: '#16a34a',
      backgroundColor: 'rgb(22 163 74 / 10%)',
      borderWidth: 3,
      pointRadius: 2,
      tension: 0.3,
      fill: true,
    },
  ],
}))

const options = {
  responsive: true,
  maintainAspectRatio: false,
  interaction: { intersect: false, mode: 'index' },
  plugins: {
    legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } },
    tooltip: {
      callbacks: {
        label: (context) => `${context.dataset.label}: ${money(context.parsed.y)}`,
      },
    },
  },
  scales: {
    y: {
      ticks: { callback: (value) => money(value) },
      grid: { color: 'rgb(229 231 235 / 70%)' },
    },
    x: { grid: { display: false } },
  },
}
</script>

<template>
  <section class="card dashboard-chart">
    <div class="section-head">
      <div>
        <span class="section-kicker">Selected period</span>
        <h2>Financial performance</h2>
        <p class="hint">Revenue, actual cost, and final profit by reporting interval.</p>
      </div>
    </div>

    <div class="chart-summary" aria-label="Financial totals">
      <div>
        <span>Revenue</span>
        <strong class="money-revenue">{{ money(kpis.revenue?.value) }}</strong>
      </div>
      <div>
        <span>Actual cost</span>
        <strong class="money-cost">{{ money(kpis.actual_cost?.value ?? kpis.cost?.value) }}</strong>
      </div>
      <div>
        <span>Final profit</span>
        <strong :class="Number(kpis.profit?.value) < 0 ? 'money-loss' : 'money-profit'">
          {{ money(kpis.profit?.value) }}
        </strong>
      </div>
    </div>

    <div v-if="series.length" class="chart-canvas">
      <Line :data="chartData" :options="options" aria-label="Financial performance chart" />
    </div>
    <p v-else class="empty">No financial activity in this period.</p>

    <p class="chart-note">
      Cost includes planned cost and unexpected expenses. Profit is the backend-calculated final
      profit.
    </p>
  </section>
</template>

<style scoped>
.dashboard-chart {
  min-width: 0;
}

.chart-summary {
  display: grid;
  gap: var(--space-3);
  grid-template-columns: repeat(3, minmax(0, 1fr));
  margin: var(--space-4) var(--space-0);
}

.chart-summary > div {
  border-left: 2px solid var(--border-strong);
  display: flex;
  flex-direction: column;
  gap: var(--space-1);
  padding-left: var(--space-3);
}

.chart-summary > div:first-child {
  border-left-color: var(--accent);
}

.chart-summary > div:nth-child(2) {
  border-left-color: var(--warning);
}

.chart-summary > div:nth-child(3) {
  border-left-color: var(--success);
}

.chart-summary span {
  color: var(--text-muted);
  font-size: var(--text-xs);
}

.chart-summary strong {
  font-size: var(--text-lg);
}

.chart-canvas {
  height: var(--chart-height);
  position: relative;
}

.chart-note {
  color: var(--text-muted);
  font-size: var(--text-xs);
  margin-top: var(--space-3);
}

@media (max-width: 560px) {
  .chart-summary {
    grid-template-columns: 1fr;
  }

  .chart-canvas {
    height: 220px;
  }
}
</style>
