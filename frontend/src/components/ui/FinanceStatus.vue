<script setup>
import { computed } from 'vue'
import InfoTip from '@/components/ui/InfoTip.vue'

const props = defineProps({
  status: { type: String, required: true },
  // 'investor' | 'investment' | 'loan' | 'allocation' — picks tooltip wording
  kind: { type: String, default: 'generic' },
})

const TIPS = {
  investor: {
    active: 'This investor is active — you can record new investments and loans for them.',
    inactive: 'Paused — no new activity can be recorded, but everything existing is untouched.',
  },
  investment: {
    active: 'Capital is placed and earning its agreed return.',
    matured: 'The term has ended — settle the payout or arrange a renewal.',
    withdrawn: 'Capital was pulled out — settle the remaining payout.',
    closed: 'Fully settled — capital and return have been paid out.',
    cancelled: 'Cancelled — it never earned a return.',
  },
  loan: {
    active: 'Issued and being repaid on schedule.',
    paid: 'Fully repaid — nothing left to collect.',
    overdue: 'Past its due date with money still outstanding. Needs attention.',
    cancelled: 'Written off — no further repayments expected.',
  },
  allocation: {
    active: 'This capital is committed to a job and earning from it.',
    released: 'Returned to the investment — available to place on another job.',
  },
}

const CLASSES = {
  active: 'status-success',
  paid: 'status-success',
  closed: 'status-success',
  settled: 'status-success',
  matured: 'status-warning',
  withdrawn: 'status-warning',
  inactive: 'status-draft',
  overdue: 'status-danger',
  cancelled: 'status-danger',
  released: 'status-info',
}

const label = computed(() => (props.status || '—').replace(/_/g, ' '))
const klass = computed(() => CLASSES[props.status] || 'status-info')
const tip = computed(() => TIPS[props.kind]?.[props.status] || '')
</script>

<template>
  <span class="finance-status">
    <span class="status capitalize" :class="klass">{{ label }}</span>
    <InfoTip v-if="tip" :label="tip" />
  </span>
</template>

<style scoped>
.finance-status {
  align-items: center;
  display: inline-flex;
  gap: 5px;
}
.capitalize { text-transform: capitalize; }
</style>