<script setup>
import { computed } from 'vue'
import { money } from '@/utils/money'

const props = defineProps({
  // A job (or anything with these figures). Backend-sourced only.
  sellPrice: { type: [String, Number], default: 0 },
  costPrice: { type: [String, Number], default: 0 },
  extraCosts: { type: [String, Number], default: 0 },
  finalProfit: { type: [String, Number], default: 0 },
})

const isLoss = computed(() => Number(props.finalProfit) < 0)
</script>

<template>
  <div class="chain financial-flow">
    <div class="step">
      <span>Quoted</span>
      <b>{{ money(sellPrice) }}</b>
    </div>

    <div class="op">−</div>

    <div class="step">
      <span>Planned cost</span>
      <b class="money-cost">{{ money(costPrice) }}</b>
    </div>

    <div class="op">=</div>

    <div class="step">
      <span>Base profit</span>
      <b>{{ money(Number(sellPrice) - Number(costPrice)) }}</b>
    </div>

    <div class="op">−</div>

    <div class="step">
      <span>Unexpected</span>
      <b class="money-cost">{{ money(extraCosts) }}</b>
    </div>

    <div class="op">=</div>

    <div class="step final">
      <span>Final profit</span>
      <b :class="isLoss ? 'money-loss' : 'money-profit'">{{ money(finalProfit) }}</b>
      <span v-if="isLoss" class="badge badge-loss">LOSS</span>
    </div>
  </div>
</template>
