<script setup>
import { money } from '@/utils/money'
import InfoTip from '@/components/ui/InfoTip.vue'

const props = defineProps({
  form: Object,
})

const CATEGORIES = ['Labor', 'Transport', 'Vehicle', 'Fuel', 'Machinery', 'Agent', 'Other']

const addRow = () => {
  props.form.items.push({
    title: '',
    category: '',
    quantity: 1,
    cost_price: 0,
    sell_price: 0,
    cost_total: 0,
    sell_total: 0,
    profit: 0,
    remarks: '',
  })
}

const removeRow = (index) => {
  if (props.form.items.length === 1) return
  props.form.items.splice(index, 1)
}

// Live preview only — the backend recomputes all three from quantity and the
// two prices (EstimateService::buildLine). This just saves a round trip.
const calculateRow = (item) => {
  const quantity = Number(item.quantity || 0)
  const costPrice = Number(item.cost_price || 0)
  const sellPrice = Number(item.sell_price || 0)

  item.cost_total = quantity * costPrice
  item.sell_total = quantity * sellPrice
  item.profit = item.sell_total - item.cost_total
}
</script>

<template>
  <div class="estimate-items">
    <div class="section-heading">
      <h3>Quoted items</h3>
      <p>
        Each line prices one piece of the job. Line profit updates as you type — the running totals
        sit in the panel on the right.
      </p>
    </div>

    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th class="col-title">Title</th>
            <th class="col-cat">Category</th>
            <th class="col-num">Qty</th>
            <th class="col-num">
              Cost / unit
              <InfoTip label="Your internal cost for one unit. Never shown to the customer." />
            </th>
            <th class="col-num">
              Sell / unit
              <InfoTip label="What the customer pays for one unit." />
            </th>
            <th class="col-num right">Line total</th>
            <th class="col-num right">
              Profit
              <InfoTip label="Line total minus your cost for the whole line." />
            </th>
            <th class="col-del"></th>
          </tr>
        </thead>

        <tbody>
          <tr v-for="(item, index) in form.items" :key="index">
            <td class="col-title">
              <input v-model="item.title" class="cell-input" placeholder="Freight" />
            </td>
            <td class="col-cat">
              <select v-model="item.category" class="cell-input">
                <option value="">Category</option>
                <option v-for="category in CATEGORIES" :key="category" :value="category">
                  {{ category }}
                </option>
              </select>
            </td>
            <td class="col-num">
              <input
                type="number"
                min="1"
                v-model="item.quantity"
                class="cell-input num"
                @input="calculateRow(item)"
              />
            </td>
            <td class="col-num">
              <input
                type="number"
                min="0"
                step="0.01"
                v-model="item.cost_price"
                class="cell-input num"
                placeholder="0.00"
                @input="calculateRow(item)"
              />
            </td>
            <td class="col-num">
              <input
                type="number"
                min="0"
                step="0.01"
                v-model="item.sell_price"
                class="cell-input num"
                placeholder="0.00"
                @input="calculateRow(item)"
              />
            </td>
            <td class="col-num right cell-total">{{ money(item.sell_total) }}</td>
            <td
              class="col-num right cell-profit"
              :class="Number(item.profit) < 0 ? 'money-loss' : 'money-profit'"
            >
              {{ money(item.profit) }}
            </td>
            <td class="col-del">
              <button
                type="button"
                class="icon-remove"
                :disabled="form.items.length === 1"
                title="Remove line"
                aria-label="Remove line"
                @click="removeRow(index)"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M3 6h18" /><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" /><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                </svg>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="items-actions">
      <button type="button" class="btn-light btn-sm" @click="addRow">+ Add item</button>
    </div>
  </div>
</template>

<style scoped>
.section-heading { margin-bottom: var(--space-4); }
.section-heading h3 { font-size: 15px; font-weight: 600; margin: 0; }
.section-heading p { color: var(--text-muted); font-size: 13px; margin: 2px 0 0; }

.table-wrap table { min-width: 860px; }

th.col-title { min-width: 180px; }
th.col-cat { min-width: 130px; }
th.col-num,
td.col-num { min-width: 110px; }

th.right { text-align: right; }

.cell-input {
  border-radius: 8px;
  font-size: 13px;
  min-height: 36px;
  padding: 6px 9px;
}
.cell-input.num {
  font-variant-numeric: tabular-nums;
  text-align: right;
}

.cell-total {
  color: var(--text-primary);
  font-weight: 600;
}
.cell-profit { font-weight: 600; }

.col-del { width: 44px; }

.icon-remove {
  align-items: center;
  background: transparent;
  border: 1px solid transparent;
  border-radius: 8px;
  color: var(--text-muted);
  cursor: pointer;
  display: inline-flex;
  height: 32px;
  justify-content: center;
  transition: background 0.15s ease, color 0.15s ease;
  width: 32px;
}
.icon-remove svg { height: 14px; width: 14px; }
.icon-remove:hover:not(:disabled) { background: var(--danger-soft); color: var(--danger); }
.icon-remove:disabled { cursor: not-allowed; opacity: 0.35; }

.items-actions {
  display: flex;
  margin-top: var(--space-3);
}
</style>