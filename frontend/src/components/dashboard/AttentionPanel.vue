<script setup>
defineProps({ alerts: { type: Array, default: () => [] } })
</script>

<template>
  <section class="card queue-card">
    <div class="section-head">
      <div>
        <span class="section-kicker">Needs review</span>
        <h2>Attention</h2>
        <p class="hint">Financial and timing signals from current records.</p>
      </div>
    </div>

    <ul v-if="alerts.length" class="attention-list">
      <li v-for="alert in alerts" :key="`${alert.type}-${alert.id}`">
        <span
          class="attention-marker"
          :class="`attention-${alert.severity}`"
          aria-hidden="true"
        ></span>
        <div>
          <strong>{{ alert.title }}</strong>
          <p>{{ alert.description }}</p>
        </div>
        <RouterLink class="btn-light btn-sm" :to="alert.href">Open</RouterLink>
      </li>
    </ul>
    <p v-else class="empty">No loss, cost-overrun, or expiring-estimate alerts.</p>
  </section>
</template>

<style scoped>
.queue-card {
  min-width: 0;
}

.attention-list {
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
  list-style: none;
  margin: var(--space-4) var(--space-0) var(--space-0);
  padding: var(--space-0);
}

.attention-list li {
  align-items: center;
  border-bottom: 1px solid var(--border);
  display: grid;
  gap: var(--space-3);
  grid-template-columns: 10px 1fr auto;
  padding: var(--space-3) var(--space-0);
}

.attention-list li:last-child {
  border-bottom: 0;
}

.attention-marker {
  border-radius: 50%;
  height: 10px;
  width: 10px;
}

.attention-warning {
  background: var(--warning);
}

.attention-danger {
  background: var(--danger);
}

.attention-list strong {
  color: var(--text-primary);
  display: block;
  font-size: var(--text-sm);
}

.attention-list p {
  color: var(--text-muted);
  font-size: var(--text-sm);
  margin-top: var(--space-1);
}

@media (max-width: 560px) {
  .attention-list li {
    align-items: flex-start;
    grid-template-columns: 10px 1fr;
  }

  .attention-list .btn-light {
    grid-column: 2;
    justify-self: start;
  }
}
</style>
