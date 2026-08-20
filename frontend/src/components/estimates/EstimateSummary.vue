<script setup>
import { money } from '@/utils/money'

defineProps({
  // Quote-only figures. No cost/profit data belongs here — the estimate is
  // customer-facing and the internal figures are the job's business.
  amount: { type: [String, Number], default: 0 },
  date: String,
  validUntil: String,
  items: { type: Number, default: 0 },
})
</script>

<template>
  <div class="entity-stats">
    <div class="entity-stat stat-revenue">
      <span class="entity-stat-label">Quoted amount</span>
      <strong>{{ money(amount) }}</strong>
    </div>
    <div class="entity-stat stat-neutral">
      <span class="entity-stat-label">Date</span>
      <strong>{{ date || '—' }}</strong>
    </div>
    <div class="entity-stat stat-neutral">
      <span class="entity-stat-label">Valid until</span>
      <strong>{{ validUntil || '—' }}</strong>
    </div>
    <div class="entity-stat stat-neutral">
      <span class="entity-stat-label">Line items</span>
      <strong>{{ items }}</strong>
    </div>
  </div>
</template>
