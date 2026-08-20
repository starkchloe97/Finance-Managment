<script setup>
import { money } from '@/utils/money'

const props = defineProps({
  // Backend-sourced aggregates from CustomerResource
  jobCount: { type: Number, default: 0 },
  revenue: { type: [String, Number], default: 0 },
  cost: { type: [String, Number], default: 0 },
  profit: { type: [String, Number], default: 0 },
})

const stats = [
  { label: 'Jobs', value: String(props.jobCount), tone: 'neutral' },
  { label: 'Revenue', value: money(props.revenue), tone: 'revenue' },
  { label: 'Cost', value: money(props.cost), tone: 'cost' },
  {
    label: 'Profit',
    value: money(props.profit),
    tone: Number(props.profit) < 0 ? 'loss' : 'profit',
  },
]
</script>

<template>
  <div class="entity-stats">
    <div v-for="stat in stats" :key="stat.label" class="entity-stat" :class="`stat-${stat.tone}`">
      <span class="entity-stat-label">{{ stat.label }}</span>
      <strong>{{ stat.value }}</strong>
    </div>
  </div>
</template>
