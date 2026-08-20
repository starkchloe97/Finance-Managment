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
ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Tooltip, Legend)
const props = defineProps({ series: { type: Array, default: () => [] } })
const chartData = computed(() => ({
  labels: props.series.map((item) => item.period),
  datasets: [
    {
      label: 'Revenue',
      data: props.series.map((item) => item.revenue),
      borderColor: '#2563eb',
      backgroundColor: '#2563eb',
    },
    {
      label: 'Cost',
      data: props.series.map((item) => item.cost),
      borderColor: '#d97706',
      backgroundColor: '#d97706',
    },
    {
      label: 'Profit',
      data: props.series.map((item) => item.profit),
      borderColor: '#16a34a',
      backgroundColor: '#16a34a',
    },
  ],
}))
const options = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { position: 'bottom' } },
}
</script>
<template>
  <section class="card dashboard-chart">
    <div class="section-head">
      <div>
        <h2>Financial overview</h2>
        <p class="hint">Revenue, cost, and final profit from the selected period.</p>
      </div>
    </div>
    <div v-if="series.length" class="chart-canvas">
      <Line :data="chartData" :options="options" />
    </div>
    <p v-else class="empty">No financial activity for this period.</p>
  </section>
</template>
