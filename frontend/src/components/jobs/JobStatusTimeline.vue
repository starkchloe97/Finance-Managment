<script setup>
import { computed } from 'vue'
import { statusLabel } from '@/utils/jobStatus'

const props = defineProps({
  status: { type: String, required: true },
})

// The real lifecycle, mirroring TransportJobService::TRANSITIONS. The server
// enforces the order; this only draws it.
const STAGES = ['draft', 'confirmed', 'assigned', 'in_transit', 'delivered', 'completed']

const currentIndex = computed(() => STAGES.indexOf(props.status))

// A stage is filled if it is at or before the current one. 'completed' is the
// terminal stage; the timeline is complete when the job is there.
const states = computed(() =>
  STAGES.map((stage, index) => ({
    key: stage,
    label: statusLabel(stage),
    filled: index <= currentIndex.value,
  })),
)
</script>

<template>
  <ol class="job-timeline" :class="{ complete: status === 'completed' }">
    <li v-for="stage in states" :key="stage.key" :class="{ filled: stage.filled }">
      <span class="timeline-dot"></span>
      <span class="timeline-label">{{ stage.label }}</span>
    </li>
  </ol>
</template>

<style scoped>
.job-timeline {
  align-items: flex-start;
  display: flex;
  justify-content: space-between;
  list-style: none;
  margin: var(--space-4) var(--space-0) var(--space-0);
  padding: var(--space-0);
}

.job-timeline li {
  align-items: center;
  display: flex;
  flex: 1;
  flex-direction: column;
  gap: var(--space-2);
  position: relative;
  text-align: center;
}

.job-timeline li::before {
  background: var(--border-strong);
  content: '';
  height: 2px;
  left: -50%;
  position: absolute;
  top: var(--space-3);
  width: 100%;
}

.job-timeline li:first-child::before {
  display: none;
}

.timeline-dot {
  background: var(--border-strong);
  border-radius: 50%;
  height: var(--space-3);
  position: relative;
  width: var(--space-3);
  z-index: 1;
}

.job-timeline li.filled::before,
.job-timeline li.filled .timeline-dot {
  background: var(--accent);
}

.job-timeline li.filled .timeline-dot {
  box-shadow: 0 0 0 var(--space-1) var(--accent-soft);
}

.job-timeline li.filled .timeline-label {
  color: var(--text-primary);
  font-weight: var(--font-weight-medium);
}

.timeline-label {
  color: var(--text-muted);
  font-size: var(--text-xs);
  text-transform: capitalize;
}
</style>
