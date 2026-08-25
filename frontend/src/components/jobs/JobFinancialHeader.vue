<script setup>
import { money } from '@/utils/money'

defineProps({
  job: { type: Object, required: true },
})
</script>

<template>
  <div class="financial-header">
    <div class="financial-cell">
      <span>Sell / quoted price</span>
      <strong class="money-revenue">{{ money(job.sell_price) }}</strong>
    </div>
    <div class="financial-cell">
      <span>Planned cost</span>
      <strong class="money-cost">{{ money(job.cost_price) }}</strong>
    </div>
    <div class="financial-cell">
      <span>Actual cost</span>
      <strong class="money-cost">{{
        money(job.actual_cost ?? Number(job.cost_price) + Number(job.extra_costs))
      }}</strong>
    </div>
    <div class="financial-cell">
      <span>Profit</span>
      <strong :class="Number(job.final_profit) < 0 ? 'money-loss' : 'money-profit'">{{
        money(job.final_profit)
      }}</strong>
    </div>
    <div class="financial-cell">
      <span>Margin</span>
      <strong>{{ job.margin != null ? `${job.margin}%` : '—' }}</strong>
    </div>
  </div>
</template>

<style scoped>
.financial-header {
  display: grid;
  gap: var(--space-3);
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
}

.financial-cell {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  padding: var(--space-4);
}

.financial-cell span {
  color: var(--text-muted);
  display: block;
  font-size: var(--text-sm);
  margin-bottom: var(--space-1);
}

.financial-cell strong {
  font-size: var(--text-lg);
  font-variant-numeric: tabular-nums;
}
</style>
