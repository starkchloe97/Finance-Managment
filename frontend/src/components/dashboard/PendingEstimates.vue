<script setup>
import { money } from '@/utils/money'
import { estimateStatusLabel, estimateStatusClass } from '@/utils/estimateStatus'

defineProps({ estimates: { type: Array, default: () => [] } })
</script>

<template>
  <section class="card queue-card">
    <div class="section-head">
      <div>
        <span class="section-kicker">Outstanding work</span>
        <h2>Open estimates</h2>
        <p class="hint">Quotes waiting for a decision across the business.</p>
      </div>
      <RouterLink class="btn-light btn-sm" to="/estimates">View all</RouterLink>
    </div>

    <ul v-if="estimates.length" class="estimate-list">
      <li v-for="estimate in estimates" :key="estimate.id">
        <div class="queue-primary">
          <RouterLink :to="`/estimates/${estimate.id}`">{{ estimate.code }}</RouterLink>
          <strong>{{ estimate.customer?.name || 'No customer' }}</strong>
        </div>
        <div class="queue-secondary">
          <span>{{ money(estimate.estimated_sell) }}</span>
          <span v-if="estimate.valid_until">Valid {{ estimate.valid_until }}</span>
          <span class="status" :class="estimateStatusClass(estimate.status)">
            {{ estimateStatusLabel(estimate.status) }}
          </span>
        </div>
      </li>
    </ul>
    <p v-else class="empty">No open estimates need a decision.</p>
  </section>
</template>

<style scoped>
.queue-card {
  min-width: 0;
}

.estimate-list {
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
  list-style: none;
  margin: var(--space-4) var(--space-0) var(--space-0);
  padding: var(--space-0);
}

.estimate-list li {
  align-items: center;
  border-bottom: 1px solid var(--border);
  display: flex;
  gap: var(--space-4);
  justify-content: space-between;
  padding: var(--space-3) var(--space-0);
}

.estimate-list li:last-child {
  border-bottom: 0;
}

.queue-primary,
.queue-secondary {
  align-items: center;
  display: flex;
  gap: var(--space-2);
  min-width: 0;
}

.queue-primary {
  flex: 1;
}

.queue-primary a,
.queue-primary strong {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.queue-primary strong {
  color: var(--text-secondary);
  font-size: var(--text-sm);
  font-weight: var(--font-weight-medium);
}

.queue-secondary {
  color: var(--text-muted);
  flex-wrap: wrap;
  font-size: var(--text-sm);
  justify-content: flex-end;
}

@media (max-width: 560px) {
  .estimate-list li,
  .queue-primary,
  .queue-secondary {
    align-items: flex-start;
    flex-direction: column;
  }

  .estimate-list li {
    gap: var(--space-2);
  }

  .queue-secondary {
    align-items: flex-start;
  }
}
</style>
