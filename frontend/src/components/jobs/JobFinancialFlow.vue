<script setup>
import { computed } from 'vue'
import { money } from '@/utils/money'
import InfoTip from '@/components/ui/InfoTip.vue'

const props = defineProps({
  sellPrice: { type: [String, Number], default: 0 },
  costPrice: { type: [String, Number], default: 0 },
  extraCosts: { type: [String, Number], default: 0 },
  finalProfit: { type: [String, Number], default: 0 },
})

const isLoss = computed(() => Number(props.finalProfit) < 0)
const baseProfit = computed(() => Number(props.sellPrice) - Number(props.costPrice))
</script>

<template>
  <section class="card flow-card">
    <header class="flow-head">
      <div>
        <h2>How the money works</h2>
        <p class="hint">Follow the math from the quoted price to what the job really earned.</p>
      </div>
    </header>

    <div class="flow-chain">
      <div class="flow-step">
        <span class="flow-label">
          Quoted price
          <InfoTip label="The price the customer agreed to pay for this job." />
        </span>
        <strong>{{ money(sellPrice) }}</strong>
      </div>

      <span class="flow-op" aria-hidden="true">−</span>

      <div class="flow-step">
        <span class="flow-label">
          Planned cost
          <InfoTip
            label="What you expected to spend when the job was quoted — vehicle, fuel, driver."
          />
        </span>
        <strong>{{ money(costPrice) }}</strong>
      </div>

      <span class="flow-op" aria-hidden="true">=</span>

      <div class="flow-step">
        <span class="flow-label">
          Base profit
          <InfoTip label="Profit if the job had gone exactly to plan." />
        </span>
        <strong>{{ money(baseProfit) }}</strong>
      </div>

      <span class="flow-op" aria-hidden="true">−</span>

      <div class="flow-step">
        <span class="flow-label">
          Unexpected
          <InfoTip
            label="Extra costs that came up during the job — repairs, fines, delays. Each one is listed under Expenses below."
          />
        </span>
        <strong>{{ money(extraCosts) }}</strong>
      </div>

      <span class="flow-op" aria-hidden="true">=</span>

      <div class="flow-step flow-final" :class="{ 'flow-loss': isLoss }">
        <span class="flow-label">
          Final profit
          <InfoTip label="What the job actually earned after every cost." />
        </span>
        <strong>{{ money(finalProfit) }}</strong>
        <span v-if="isLoss" class="flow-badge">Loss</span>
      </div>
    </div>
  </section>
</template>

<style scoped>
.flow-card { min-width: 0; }

.flow-head { margin-bottom: 18px; }
.flow-head h2 { font-size: 15px; font-weight: 600; }

.flow-chain {
  align-items: stretch;
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.flow-step {
  background: var(--surface-2);
  border-radius: var(--radius-md);
  flex: 1 1 130px;
  min-width: 130px;
  padding: 14px 16px;
}

.flow-label {
  align-items: center;
  color: var(--text-muted);
  display: flex;
  font-size: 12px;
  font-weight: 500;
  gap: 6px;
  margin-bottom: 5px;
}

.flow-step strong {
  color: var(--text-primary);
  font-size: 20px;
  font-variant-numeric: tabular-nums;
  font-weight: 700;
  letter-spacing: -0.01em;
}

.flow-op {
  align-items: center;
  color: var(--text-muted);
  display: flex;
  font-size: 18px;
  font-weight: 600;
  padding: 0 2px;
}

.flow-final {
  background: var(--success-soft);
  flex: 1.4 1 150px;
}

.flow-final strong { color: var(--success); }

.flow-final.flow-loss { background: var(--danger-soft); }
.flow-final.flow-loss strong { color: var(--danger); }

.flow-badge {
  background: var(--danger);
  border-radius: 999px;
  color: #fff;
  display: inline-block;
  font-size: 11px;
  font-weight: 600;
  margin-left: 8px;
  padding: 2px 8px;
  vertical-align: middle;
}

@media (max-width: 560px) {
  .flow-op { display: none; }
  .flow-step { flex-basis: calc(50% - 5px); }
  .flow-final { flex-basis: 100%; }
}
</style>