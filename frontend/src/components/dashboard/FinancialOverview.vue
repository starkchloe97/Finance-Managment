<script setup>
import { computed } from 'vue'
import { Line } from 'vue-chartjs'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Filler,
  Tooltip,
  Legend,
} from 'chart.js'
import { money } from '@/utils/money'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Filler, Tooltip, Legend)

const props = defineProps({
  series: { type: Array, default: () => [] },
  kpis: { type: Object, default: () => ({}) },
})

const COLORS = { revenue: '#2563eb', cost: '#d97706', profit: '#16a34a' }

const makeGradient = (hex, alpha) => (context) => {
  const { ctx, chartArea } = context.chart
  if (!chartArea) return `${hex}14`
  const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom)
  gradient.addColorStop(0, `${hex}${alpha}`)
  gradient.addColorStop(1, `${hex}00`)
  return gradient
}

const baseLine = {
  tension: 0.4,
  pointRadius: 0,
  pointHoverRadius: 5,
  pointHoverBorderWidth: 2,
  pointHoverBorderColor: '#ffffff',
  borderJoinStyle: 'round',
  borderCapStyle: 'round',
}

const chartData = computed(() => ({
  labels: props.series.map((item) => item.period),
  datasets: [
    {
      ...baseLine,
      label: 'Revenue',
      data: props.series.map((item) => item.revenue),
      borderColor: COLORS.revenue,
      pointBackgroundColor: COLORS.revenue,
      backgroundColor: makeGradient(COLORS.revenue, '26'),
      borderWidth: 2,
      fill: true,
    },
    {
      ...baseLine,
      label: 'Actual cost',
      data: props.series.map((item) => item.actual_cost ?? item.cost),
      borderColor: COLORS.cost,
      pointBackgroundColor: COLORS.cost,
      borderDash: [6, 6],
      borderWidth: 2,
      fill: false,
    },
    {
      ...baseLine,
      label: 'Final profit',
      data: props.series.map((item) => item.profit),
      borderColor: COLORS.profit,
      pointBackgroundColor: COLORS.profit,
      backgroundColor: makeGradient(COLORS.profit, '2e'),
      borderWidth: 3,
      fill: true,
    },
  ],
}))

const compact = (value) =>
  new Intl.NumberFormat(undefined, { notation: 'compact', maximumFractionDigits: 1 }).format(value)

const options = {
  responsive: true,
  maintainAspectRatio: false,
  interaction: { intersect: false, mode: 'index' },
  layout: { padding: { top: 8 } },
  plugins: {
    legend: {
      position: 'top',
      align: 'end',
      labels: {
        usePointStyle: true,
        pointStyle: 'circle',
        boxWidth: 6,
        boxHeight: 6,
        padding: 16,
        color: '#64748b',
        font: { size: 12 },
      },
    },
    tooltip: {
      backgroundColor: '#0f172a',
      padding: 12,
      cornerRadius: 10,
      boxPadding: 6,
      usePointStyle: true,
      titleFont: { size: 12, weight: '600' },
      bodyFont: { size: 12 },
      callbacks: {
        label: (context) => ` ${context.dataset.label}: ${money(context.parsed.y)}`,
      },
    },
  },
  scales: {
    y: {
      border: { display: false },
      grid: { color: '#eef2f7' },
      ticks: {
        callback: (value) => compact(value),
        color: '#94a3b8',
        font: { size: 11 },
        maxTicksLimit: 6,
        padding: 8,
      },
    },
    x: {
      border: { display: false },
      grid: { display: false },
      ticks: { color: '#94a3b8', font: { size: 11 }, padding: 8 },
    },
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
      <div class="summary-item">
        <span class="dot dot-revenue">Revenue</span>
        <strong>{{ money(kpis.revenue?.value) }}</strong>
      </div>
      <div class="summary-item">
        <span class="dot dot-cost">Actual cost</span>
        <strong>{{ money(kpis.actual_cost?.value ?? kpis.cost?.value) }}</strong>
      </div>
      <div class="summary-item">
        <span class="dot dot-profit">Final profit</span>
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
.dashboard-chart { min-width: 0; }

.chart-summary {
  display: grid;
  gap: 12px;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  margin: 16px 0 20px;
}
.summary-item {
  background: var(--surface-2);
  border-radius: var(--radius-md);
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: 12px 14px;
}
.summary-item span {
  align-items: center;
  color: var(--text-muted);
  display: flex;
  font-size: 12px;
  font-weight: 500;
  gap: 6px;
}
.summary-item strong {
  font-size: 20px;
  font-weight: 700;
  font-variant-numeric: tabular-nums;
  letter-spacing: -0.01em;
}
.dot::before { border-radius: 50%; content: ''; display: inline-block; height: 8px; width: 8px; }
.dot-revenue::before { background: #2563eb; }
.dot-cost::before { background: #d97706; }
.dot-profit::before { background: #16a34a; }

.chart-canvas { height: var(--chart-height); position: relative; }

.chart-note { color: var(--text-muted); font-size: 12px; margin-top: 14px; }

@media (max-width: 560px) {
  .chart-summary { grid-template-columns: 1fr; }
  .chart-canvas { height: 220px; }
}
</style>