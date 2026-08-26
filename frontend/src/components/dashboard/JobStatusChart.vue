<script setup>
import { computed } from 'vue'
import { statusLabel } from '@/utils/jobStatus'

defineOptions({ name: 'JobPipeline' })

const props = defineProps({ statuses: { type: Object, default: () => ({}) } })

const stages = ['draft', 'confirmed', 'assigned', 'in_transit', 'delivered', 'completed']
const stageStyles = {
  draft: 'pipeline-neutral',
  confirmed: 'pipeline-info',
  assigned: 'pipeline-info',
  in_transit: 'pipeline-warning',
  delivered: 'pipeline-success',
  completed: 'pipeline-complete',
}

const rows = computed(() =>
  stages.map((stage) => ({
    stage,
    label: statusLabel(stage),
    count: Number(props.statuses[stage] || 0),
    style: stageStyles[stage],
  })),
)

const activeTotal = computed(() =>
  rows.value.filter((row) => row.stage !== 'completed').reduce((sum, row) => sum + row.count, 0),
)
</script>

<template>
  <section class="card pipeline-card">
    <div class="section-head">
      <div>
        <span class="section-kicker">All jobs</span>
        <h2>Current pipeline</h2>
        <p class="hint">Where transport work sits right now.</p>
      </div>
      <RouterLink class="btn-light btn-sm" to="/jobs">View jobs</RouterLink>
    </div>

    <div class="pipeline-total">
      <strong>{{ activeTotal }}</strong>
      <span>open jobs in progress</span>
    </div>

    <ul class="pipeline-list" aria-label="Current job pipeline">
      <li v-for="row in rows" :key="row.stage">
        <span class="pipeline-marker" :class="row.style" aria-hidden="true"></span>
        <span class="pipeline-label">{{ row.label }}</span>
        <strong>{{ row.count }}</strong>
      </li>
    </ul>
  </section>
</template>

<style scoped>
.pipeline-card {
  min-width: 0;
}

.pipeline-total {
  align-items: baseline;
  display: flex;
  gap: var(--space-2);
  margin: var(--space-5) var(--space-0) var(--space-4);
}

.pipeline-total strong {
  font-size: var(--text-3xl);
  font-variant-numeric: tabular-nums;
}

.pipeline-total span {
  color: var(--text-muted);
  font-size: var(--text-sm);
}

.pipeline-list {
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
  list-style: none;
  margin: var(--space-0);
  padding: var(--space-0);
}

.pipeline-list li {
  align-items: center;
  border-bottom: 1px solid var(--border);
  display: grid;
  gap: var(--space-2);
  grid-template-columns: 10px 1fr auto;
  padding: var(--space-2) var(--space-0);
}

.pipeline-list li:last-child {
  border-bottom: 0;
}

.pipeline-marker {
  border-radius: 50%;
  height: 10px;
  width: 10px;
}

.pipeline-neutral {
  background: var(--text-muted);
}

.pipeline-info {
  background: var(--info);
}

.pipeline-warning {
  background: var(--warning);
}

.pipeline-success {
  background: var(--success);
}

.pipeline-complete {
  background: var(--border-strong);
}

.pipeline-label {
  color: var(--text-secondary);
  font-size: var(--text-sm);
  text-transform: capitalize;
}

.pipeline-list strong {
  font-variant-numeric: tabular-nums;
}
</style>
