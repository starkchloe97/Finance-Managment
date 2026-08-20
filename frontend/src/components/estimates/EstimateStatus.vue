<script setup>
import { estimateStatusLabel, estimateStatusClass } from '@/utils/estimateStatus'

defineProps({
  status: { type: String, required: true },
})
</script>

<template>
  <span class="status" :class="estimateStatusClass(status)">{{ estimateStatusLabel(status) }}</span>
</template>
