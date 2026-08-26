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
        <span class="alert-icon" :class="`alert-${alert.severity}`" aria-hidden="true">
          <svg v-if="alert.severity === 'danger'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10" /><path d="M12 8v4" /><path d="M12 16h.01" />
          </svg>
          <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" /><path d="M12 9v4" /><path d="M12 17h.01" />
          </svg>
        </span>
        <div class="alert-body">
          <strong>{{ alert.title }}</strong>
          <p>{{ alert.description }}</p>
        </div>
        <RouterLink class="alert-link" :to="alert.href">
          Open <span aria-hidden="true">→</span>
        </RouterLink>
      </li>
    </ul>
    <p v-else class="empty">No loss, cost-overrun, or expiring-estimate alerts.</p>
  </section>
</template>

<style scoped>
.queue-card { min-width: 0; }

.attention-list {
  display: flex;
  flex-direction: column;
  list-style: none;
  margin-top: 16px;
  padding: 0;
}
.attention-list li {
  align-items: center;
  border-bottom: 1px solid var(--border);
  display: grid;
  gap: 12px;
  grid-template-columns: 36px minmax(0, 1fr) auto;
  padding: 12px 0;
}
.attention-list li:last-child { border-bottom: 0; }

.alert-icon {
  align-items: center;
  border-radius: 10px;
  display: flex;
  height: 36px;
  justify-content: center;
  width: 36px;
}
.alert-icon svg { height: 16px; width: 16px; }
.alert-warning { background: var(--warning-soft); color: var(--warning); }
.alert-danger { background: var(--danger-soft); color: var(--danger); }

.alert-body strong { display: block; font-size: 14px; color: var(--text-primary); }
.alert-body p { color: var(--text-muted); font-size: 13px; margin-top: 2px; }

.alert-link {
  color: var(--accent);
  font-size: 13px;
  font-weight: 600;
  white-space: nowrap;
}
.alert-link:hover { text-decoration: underline; }

@media (max-width: 560px) {
  .attention-list li { align-items: flex-start; grid-template-columns: 36px minmax(0, 1fr); }
  .attention-list .alert-link { grid-column: 2; justify-self: start; }
}
</style>