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
