<script setup>
import { computed } from 'vue'
import { statusLabel } from '@/utils/jobStatus'
import InfoTip from '@/components/ui/InfoTip.vue'

const props = defineProps({
  status: { type: String, required: true },
})

// Mirrors TransportJobService::TRANSITIONS — the server enforces the order.
const STAGES = [
  { key: 'draft', tip: 'Being prepared — the job is not confirmed yet.' },
  { key: 'confirmed', tip: 'The customer accepted the quote.' },
  { key: 'assigned', tip: 'A driver and vehicle have been allocated.' },
  { key: 'in_transit', tip: 'The vehicle is on the road.' },
  { key: 'delivered', tip: 'The goods arrived at the destination.' },
  { key: 'completed', tip: 'Job finished — finances are closed and distributed.' },
]

const currentIndex = computed(() => STAGES.findIndex((stage) => stage.key === props.status))

const states = computed(() =>
  STAGES.map((stage, index) => ({
    ...stage,
    label: statusLabel(stage.key),
    done: index < currentIndex.value,
    current: index === currentIndex.value,
  })),
)

const progress = computed(() =>
  currentIndex.value <= 0 ? 0 : Math.round((currentIndex.value / (STAGES.length - 1)) * 100),
)
</script>

<template>
  <div class="stage-track">
    <ol class="stage-list" :aria-label="`Job stage: ${statusLabel(status)}`">
      <li
        v-for="stage in states"
        :key="stage.key"
        class="stage"
        :class="{ done: stage.done, current: stage.current }"
      >
        <span class="stage-dot" aria-hidden="true">
          <svg
            v-if="stage.done"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="3"
            stroke-linecap="round"
            stroke-linejoin="round"
          >
            <path d="M20 6 9 17l-5-5" />
          </svg>
          <span v-else-if="stage.current" class="stage-pulse"></span>
        </span>
        <span class="stage-label">
          {{ stage.label }}
          <InfoTip :label="stage.tip" />
        </span>
      </li>
    </ol>
    <p class="stage-progress" aria-hidden="true">{{ progress }}% through the workflow</p>
  </div>
</template>

<style scoped>
.stage-track {
  min-width: 0;
}

.stage-list {
  align-items: flex-start;
  display: flex;
  justify-content: space-between;
  list-style: none;
  margin: 0;
  padding: 0;
}

.stage {
  align-items: center;
  display: flex;
  flex: 1;
  flex-direction: column;
  gap: 8px;
  min-width: 0;
  padding: 0 4px;
  position: relative;
  text-align: center;
}

/* Connector */
.stage::before {
  background: var(--border);
  content: '';
  height: 2px;
  left: -50%;
  position: absolute;
  top: 13px;
  width: 100%;
  transition: background 0.3s ease;
}

.stage:first-child::before { display: none; }
.stage.done::before,
.stage.current::before { background: var(--accent); }

.stage-dot {
  align-items: center;
  background: var(--surface);
  border: 2px solid var(--border-strong);
  border-radius: 50%;
  color: #fff;
  display: flex;
  height: 26px;
  justify-content: center;
  position: relative;
  transition: background 0.2s ease, border-color 0.2s ease;
  width: 26px;
  z-index: 1;
}

.stage.done .stage-dot {
  background: var(--accent);
  border-color: var(--accent);
}

.stage.current .stage-dot {
  border-color: var(--accent);
  box-shadow: 0 0 0 4px var(--accent-soft);
}

.stage-dot svg { height: 12px; width: 12px; }

.stage-pulse {
  animation: pulse 1.8s ease-out infinite;
  background: var(--accent);
  border-radius: 50%;
  height: 8px;
  width: 8px;
}

@keyframes pulse {
  0% { box-shadow: 0 0 0 0 rgb(37 99 235 / 40%); }
  70% { box-shadow: 0 0 0 8px rgb(37 99 235 / 0%); }
  100% { box-shadow: 0 0 0 0 rgb(37 99 235 / 0%); }
}

.stage-label {
  align-items: center;
  color: var(--text-muted);
  display: inline-flex;
  font-size: 12px;
  gap: 4px;
}

.stage.done .stage-label,
.stage.current .stage-label {
  color: var(--text-primary);
  font-weight: 600;
}

.stage-progress {
  color: var(--text-muted);
  font-size: 11px;
  margin: 10px 0 0;
  text-align: right;
}

@media (max-width: 640px) {
  .stage { padding: 0 2px; }
  .stage-label { font-size: 10px; }
  .stage-label :deep(.info-tip) { display: none; } /* tooltips are desktop affordances */
}
</style>