<script setup>
import { money } from '@/utils/money'
import { toneFor } from '@/utils/tone'

const props = defineProps({
  job: { type: Object, required: true },
})

// The quoted plan lives on the linked estimate's line items. Display-only
// grouping; the total comes from the backend, never summed here.
const planItems = () => props.job?.estimate?.items || []
</script>

<template>
  <section class="card block-card">
    <header class="block-head">
      <div class="block-title">
        <span class="block-icon icon-violet" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 3v18" /><path d="M17 7H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
          </svg>
        </span>
        <div>
          <h2>The plan (budget)</h2>
          <p class="block-hint">What this job was expected to cost, item by item, from the quote.</p>
        </div>
      </div>
      <span v-if="planItems().length" class="plan-total">{{ money(job.cost_price) }}</span>
    </header>

    <div v-if="planItems().length" class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Item</th>
            <th>Category</th>
            <th class="right">Qty</th>
            <th class="right">Unit cost</th>
            <th class="right">Total</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in planItems()" :key="item.id">
            <td class="item-title">{{ item.title }}</td>
            <td>
              <span v-if="item.category" class="badge" :class="`tone-${toneFor(item.category)}`">
                {{ item.category }}
              </span>
              <span v-else>—</span>
            </td>
            <td class="right">{{ Number(item.quantity) }}</td>
            <td class="right">{{ money(item.cost_price) }}</td>
            <td class="right item-total">{{ money(item.cost_total) }}</td>
          </tr>
        </tbody>
        <tfoot>
          <tr class="grand-total">
            <td colspan="4">Planned cost</td>
            <td class="right">{{ money(job.cost_price) }}</td>
          </tr>
        </tfoot>
      </table>
    </div>

    <div v-else class="block-empty">
      <p>No plan to show — this job has no linked estimate.</p>
    </div>

    <p class="plan-note">
      Actual spending that wasn't in this plan is tracked under Expenses above.
    </p>
  </section>
</template>

<style scoped>
.block-card { padding: 20px; }

.block-head {
  align-items: flex-start;
  display: flex;
  gap: 12px;
  justify-content: space-between;
  margin-bottom: 14px;
}

.block-title { align-items: flex-start; display: flex; gap: 12px; }
.block-title h2 { font-size: 15px; font-weight: 600; margin: 0; }
.block-hint { color: var(--text-muted); font-size: 13px; margin: 2px 0 0; }

.block-icon {
  align-items: center;
  background: var(--violet-soft);
  border-radius: 9px;
  color: var(--violet);
  display: flex;
  flex: 0 0 32px;
  height: 32px;
  justify-content: center;
  width: 32px;
}
.block-icon svg { height: 15px; width: 15px; }

.plan-total {
  color: var(--text-primary);
  font-size: 16px;
  font-variant-numeric: tabular-nums;
  font-weight: 700;
  white-space: nowrap;
}

.item-title { color: var(--text-primary); font-weight: 500; }
.item-total { color: var(--text-primary); font-weight: 600; }

.block-empty {
  border: 1px dashed var(--border-strong);
  border-radius: var(--radius-md);
  color: var(--text-muted);
  font-size: 13px;
  padding: 18px 16px;
  text-align: center;
}
.block-empty p { margin: 0; }

.plan-note { color: var(--text-muted); font-size: 12px; margin: 14px 0 0; }
</style>