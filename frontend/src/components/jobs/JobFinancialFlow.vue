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
      <span v-if="isLoss" class="badge badge-loss">Loss</span>
    </div>
  </div>
</template>

<style scoped>
.chain {
  align-items: stretch;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-2);
  padding: var(--space-5);
}

.chain .step {
  flex: 1 1 var(--chain-min);
  min-width: var(--chain-min);
}

.chain .step span {
  color: var(--text-muted);
  display: block;
  font-size: var(--text-xs);
  margin-bottom: var(--space-1);
}

.chain .step b {
  font-size: var(--text-xl);
  font-variant-numeric: tabular-nums;
}

.chain .op {
  align-items: center;
  color: var(--text-muted);
  display: flex;
  font-size: var(--text-lg);
  padding-top: var(--space-4);
}

.chain .final b {
  color: var(--success);
}

.chain .final b.money-loss {
  color: var(--danger);
}

.financial-flow {
  margin: var(--space-4) var(--space-0) var(--space-5);
}

.badge-loss {
  background: var(--danger);
  color: var(--text-inverse);
}

@media (max-width: 560px) {
  .chain {
    padding: var(--space-4);
  }

  .chain .op {
    display: none;
  }

  .chain .step {
    flex-basis: calc(50% - var(--space-2));
  }
}
</style>
