<script setup>
import { money } from '@/utils/money'

const props = defineProps({
  job: { type: Object, required: true },
})

// The quoted plan lives on the linked estimate's line items (the backend has
// no separate job_budget_items table — the plan is the quote, and the running
// planned-cost total is the job's own cost_price). Display-only grouping; the
// total comes from the backend, never summed here.
const planItems = () => props.job?.estimate?.items || []
</script>

<template>
  <div>
    <div class="table-wrap" v-if="planItems().length">
      <table>
        <thead>
          <tr>
            <th>Item</th>
            <th>Category</th>
            <th class="right">Qty</th>
            <th class="right">Unit Cost</th>
            <th class="right">Total</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in planItems()" :key="item.id">
            <td>{{ item.title }}</td>
            <td>{{ item.category || '—' }}</td>
            <td class="right">{{ Number(item.quantity) }}</td>
            <td class="right">{{ money(item.cost_price) }}</td>
            <td class="right">{{ money(item.cost_total) }}</td>
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

    <p v-else class="empty">No quoted plan — this job has no linked estimate.</p>

    <p class="hint">
      The plan is the quote the job was taken on. Actual costs land in the Expenses workspace.
    </p>
  </div>
</template>
