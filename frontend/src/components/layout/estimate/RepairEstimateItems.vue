<script setup>
import { money } from '@/utils/money'

const props = defineProps({
  form: { type: Object, required: true },
})

const updateLine = (item) => {
  const costPerUnit = Number(item.parts_cost || 0) + Number(item.labor_cost || 0)
  item.category = 'Repair'
  item.quantity = Math.max(1, Number(item.quantity || 1))
  item.cost_price = costPerUnit
  item.sell_price = costPerUnit
  item.cost_total = item.quantity * costPerUnit
  item.sell_total = item.cost_total
  item.profit = 0
}

const addLine = () => {
  props.form.items.push({
    title: '',
    category: 'Repair',
    quantity: 1,
    cost_price: 0,
    sell_price: 0,
    parts_cost: 0,
    labor_cost: 0,
    cost_total: 0,
    sell_total: 0,
    profit: 0,
    remarks: '',
    vehicles: [],
  })
}

const removeLine = (index) => {
  if (props.form.items.length > 1) props.form.items.splice(index, 1)
}

props.form.items.forEach(updateLine)
</script>

<template>
  <div class="repair-lines">
    <div class="section-heading">
      <h3>Repair / maintenance details</h3>
      <p>Enter parts and labour for each work item; line totals update automatically.</p>
    </div>

    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Work description</th>
            <th class="amount">Parts cost (PKR)</th>
            <th class="amount">Labour cost (PKR)</th>
            <th class="amount">Total (PKR)</th>
            <th><span class="sr-only">Actions</span></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(item, index) in form.items" :key="index">
            <td>
              <input
                v-model="item.title"
                aria-label="Work description"
                placeholder="Describe the repair"
              />
            </td>
            <td>
              <input
                v-model.number="item.parts_cost"
                aria-label="Parts cost"
                class="numeric"
                type="number"
                min="0"
                step="0.01"
                @input="updateLine(item)"
              />
            </td>
            <td>
              <input
                v-model.number="item.labor_cost"
                aria-label="Labour cost"
                class="numeric"
                type="number"
                min="0"
                step="0.01"
                @input="updateLine(item)"
              />
            </td>
            <td class="amount total">{{ money(item.sell_total) }}</td>
            <td>
              <button
                type="button"
                class="remove-line"
                :disabled="form.items.length === 1"
                :aria-label="`Remove ${item.title || `repair line ${index + 1}`}`"
                @click="removeLine(index)"
              >
                Remove
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <button class="add-line" type="button" @click="addLine">+ Add repair item</button>
  </div>
</template>

<style scoped>
.section-heading {
  margin-bottom: var(--space-4);
}
.section-heading h3 {
  font-size: 15px;
  font-weight: 600;
  margin: 0;
}
.section-heading p {
  color: var(--text-muted);
  font-size: 13px;
  margin: 2px 0 0;
}
.table-wrap {
  overflow-x: auto;
}
table {
  border-collapse: collapse;
  min-width: 650px;
  width: 100%;
}
th,
td {
  border-bottom: 1px solid var(--border);
  padding: 10px 8px;
  text-align: left;
}
th {
  color: var(--text-muted);
  font-size: 11px;
  font-weight: 600;
}
td {
  color: var(--text-primary);
  font-size: 13px;
}
td input {
  min-width: 90px;
  width: 100%;
}
td:first-child input {
  min-width: 180px;
}
.amount {
  text-align: right;
  white-space: nowrap;
}
.numeric {
  text-align: right;
}
.total {
  font-variant-numeric: tabular-nums;
  font-weight: 600;
}
.remove-line,
.add-line {
  background: transparent;
  border: 0;
  color: var(--accent);
  cursor: pointer;
  font: inherit;
}
.remove-line {
  font-size: 12px;
}
.remove-line:disabled {
  color: var(--text-muted);
  cursor: not-allowed;
}
.add-line {
  margin-top: 12px;
  padding: 4px 0;
  font-size: 13px;
  font-weight: 600;
}
.sr-only {
  height: 1px;
  margin: -1px;
  overflow: hidden;
  position: absolute;
  width: 1px;
  clip: rect(0, 0, 0, 0);
}
</style>
