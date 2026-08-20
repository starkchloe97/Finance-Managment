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
