<script setup>
import { computed } from 'vue'
import { statusLabel } from '@/utils/jobStatus'

defineOptions({ name: 'JobPipeline' })

const props = defineProps({ statuses: { type: Object, default: () => ({}) } })

const stages = ['draft', 'confirmed', 'assigned', 'in_transit', 'delivered', 'completed']
const stageColors = {
  draft: 'var(--text-muted)',
  confirmed: 'var(--accent)',
  assigned: 'var(--violet)',
  in_transit: 'var(--warning)',
  delivered: 'var(--info)',
  completed: 'var(--success)',
}

const rows = computed(() => {
  const total = stages.reduce((sum, s) => sum + Number(props.statuses[s] || 0), 0) || 1
  return stages.map((stage) => {
    const count = Number(props.statuses[stage] || 0)
    return {
      stage,
      label: statusLabel(stage),
      count,
      share: Math.round((count / total) * 100),
      color: stageColors[stage],
    }
  })
})

const activeTotal = computed(() =>
  rows.value.filter((r) => r.stage !== 'completed').reduce((sum, r) => sum + r.count, 0),
)
const completedTotal = computed(() => rows.value.find((r) => r.stage === 'completed')?.count ?? 0)
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
      <div class="pipeline-total-copy">
        <span>jobs in progress</span>
        <span class="pipeline-sub">{{ completedTotal }} completed</span>
      </div>
    </div>

    <ul class="pipeline-list" aria-label="Current job pipeline">
      <li v-for="row in rows" :key="row.stage">
        <div class="pipeline-row-head">
          <span class="pipeline-label">
            <span class="pipeline-dot" :style="{ background: row.color }" aria-hidden="true" />
            {{ row.label }}
          </span>
          <span class="pipeline-meta">
            <strong>{{ row.count }}</strong>
            <span class="pipeline-share">{{ row.share }}%</span>
          </span>
        </div>
        <div class="pipeline-track">
          <span class="pipeline-fill" :style="{ width: `${row.share}%`, background: row.color }" />
        </div>
      </li>
    </ul>
  </section>
</template>

<style scoped>
.pipeline-card { min-width: 0; }

.pipeline-total {
  align-items: center;
  display: flex;
  gap: var(--space-3);
  margin: 18px 0 20px;
}
.pipeline-total strong {
  font-size: 30px;
  font-weight: 700;
  font-variant-numeric: tabular-nums;
  letter-spacing: -0.02em;
  line-height: 1;
}
.pipeline-total-copy { display: flex; flex-direction: column; gap: 2px; }
.pipeline-total-copy span { color: var(--text-muted); font-size: 13px; }
.pipeline-sub { font-size: 12px; }

.pipeline-list {
  display: flex;
  flex-direction: column;
  gap: 14px;
  list-style: none;
  margin: 0;
  padding: 0;
}

.pipeline-row-head {
  align-items: center;
  display: flex;
  justify-content: space-between;
  margin-bottom: 6px;
}
.pipeline-label {
  align-items: center;
  color: var(--text-secondary);
  display: flex;
  font-size: 13px;
  gap: 8px;
}
.pipeline-dot { border-radius: 50%; height: 8px; width: 8px; flex-shrink: 0; }
.pipeline-meta { align-items: baseline; display: flex; gap: 6px; }
.pipeline-meta strong { font-size: 14px; font-variant-numeric: tabular-nums; }
.pipeline-share { color: var(--text-muted); font-size: 11px; }

.pipeline-track {
  background: var(--surface-2);
  border-radius: 999px;
  height: 6px;
  overflow: hidden;
}
.pipeline-fill { border-radius: 999px; display: block; height: 100%; transition: width 0.4s ease; }
</style>