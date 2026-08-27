<script setup>
import { computed } from 'vue'
import { estimateStatusLabel, estimateStatusClass } from '@/utils/estimateStatus'
import InfoTip from '@/components/ui/InfoTip.vue'

const props = defineProps({
  status: { type: String, required: true },
})

const TIPS = {
  draft: 'Being written — not sent to the customer yet. Safe to edit.',
  sent: 'With the customer, waiting for their answer.',
  accepted: 'The customer said yes — ready to become a transport job.',
  rejected: 'The customer declined this quote.',
  expired: 'The valid-until date passed without an answer.',
}

const tip = computed(() => TIPS[props.status] || '')
</script>

<template>
  <span class="status-row">
    <span class="status" :class="estimateStatusClass(status)">
      {{ estimateStatusLabel(status) }}
    </span>
    <InfoTip v-if="tip" :label="tip" />
  </span>
</template>

<style scoped>
.status-row {
  align-items: center;
  display: inline-flex;
  gap: 5px;
}
</style>