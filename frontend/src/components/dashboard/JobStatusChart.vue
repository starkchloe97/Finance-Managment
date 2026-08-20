<script setup>
import { computed } from 'vue'
import { Doughnut } from 'vue-chartjs'
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js'
ChartJS.register(ArcElement, Tooltip, Legend)
const props = defineProps({ statuses: { type: Object, default: () => ({}) } })
const data = computed(() => ({ labels: Object.keys(props.statuses).map((item) => item.replaceAll('_', ' ')), datasets: [{ data: Object.values(props.statuses), backgroundColor: ['#2563eb', '#0891b2', '#d97706', '#16a34a', '#4b5563', '#dc2626'] }] }))
const options = { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
</script>
<template><section class="card dashboard-chart"><h2>Job status</h2><p class="hint">Current distribution across the transport workflow.</p><div v-if="Object.keys(statuses).length" class="chart-canvas"><Doughnut :data="data" :options="options" /></div><p v-else class="empty">No jobs in this period.</p></section></template>
