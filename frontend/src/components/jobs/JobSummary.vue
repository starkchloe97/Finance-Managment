<script setup>
import { computed } from 'vue'
import { money } from '@/utils/money'
import JobStatusTimeline from './JobStatusTimeline.vue'

defineProps({
  job: { type: Object, required: true },
})
</script>

<template>
  <div class="job-summary">
    <div class="entity-stats">
      <div class="entity-stat stat-revenue">
        <span class="entity-stat-label">Revenue</span>
        <strong>{{ money(job.sell_price) }}</strong>
      </div>
      <div class="entity-stat stat-cost">
        <span class="entity-stat-label">Planned cost</span>
        <strong>{{ money(job.cost_price) }}</strong>
      </div>
      <div class="entity-stat stat-cost">
        <span class="entity-stat-label">Actual cost</span>
        <strong>{{
          money(job.actual_cost ?? Number(job.cost_price) + Number(job.extra_costs))
        }}</strong>
      </div>
      <div class="entity-stat" :class="Number(job.final_profit) < 0 ? 'stat-loss' : 'stat-profit'">
        <span class="entity-stat-label">Profit</span>
        <strong>{{ money(job.final_profit) }}</strong>
      </div>
      <div class="entity-stat stat-neutral">
        <span class="entity-stat-label">Margin</span>
        <strong>{{ job.margin != null ? `${job.margin}%` : '—' }}</strong>
      </div>
    </div>

    <JobStatusTimeline :status="job.status" />
  </div>
</template>
