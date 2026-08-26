<script setup>
import { ref, reactive, computed } from 'vue'
import { addExpense, updateExpense, deleteExpense } from '@/services/transportJobExpenseService'
import { money } from '@/utils/money'
import { EXPENSE_CATEGORIES, categoryLabel } from '@/utils/expenseCategories'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
import InfoTip from '@/components/ui/InfoTip.vue'
import { toneFor } from '@/utils/tone'

const props = defineProps({
  job: { type: Object, required: true },
})
const emit = defineEmits(['updated'])

const apply = (job) => emit('updated', job)

const today = () => new Date().toISOString().slice(0, 10)

const blankExpense = () => ({
  title: '',
  category: '',
  amount: '',
  expense_date: today(),
  notes: '',
})

const expense = reactive(blankExpense())
const editingId = ref(null)
const saving = ref(false)
const errors = ref({})
const notice = ref('')
const deleting = ref(null)

const byCategory = computed(() => {
  const groups = new Map()
  for (const item of props.job?.expenses ?? []) {
    if (!groups.has(item.category))
      groups.set(item.category, { category: item.category, items: [], subtotal: 0 })
    const group = groups.get(item.category)
    group.items.push(item)
    group.subtotal += Number(item.amount || 0)
  }
  return [...groups.values()].sort((a, b) => b.subtotal - a.subtotal)
})

const startEdit = (item) => {
  editingId.value = item.id
  errors.value = {}
  notice.value = ''
  Object.assign(expense, {
    title: item.title,
    category: item.category,
    amount: Number(item.amount),
    expense_date: String(item.expense_date).slice(0, 10),
    notes: item.notes || '',
  })
}

const cancelEdit = () => {
  editingId.value = null
  errors.value = {}
  Object.assign(expense, blankExpense())
}

const saveExpense = async () => {
  if (saving.value) return

  saving.value = true
  errors.value = {}
  notice.value = ''

  const wasEditing = Boolean(editingId.value)

  try {
    const { data } = wasEditing
      ? await updateExpense(props.job.id, editingId.value, expense)
      : await addExpense(props.job.id, expense)

    apply(data.data)
    cancelEdit()
    notice.value = wasEditing ? 'Expense updated.' : 'Expense added — profit recalculated.'
  } catch (error) {
    errors.value = error.response?.data?.errors || {}
    if (!Object.keys(errors.value).length) {
      notice.value = error.response?.data?.message || 'Could not save the expense.'
    }
  } finally {
    saving.value = false
  }
}

const removeExpense = async () => {
  if (!deleting.value || saving.value) return
  saving.value = true
  notice.value = ''

  try {
    const { data } = await deleteExpense(props.job.id, deleting.value.id)
    apply(data.data)
    if (editingId.value === deleting.value.id) cancelEdit()
    deleting.value = null
    notice.value = 'Expense removed.'
  } catch (error) {
    notice.value = error.response?.data?.message || 'Could not delete the expense.'
    deleting.value = null
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <section class="card block-card">
    <header class="block-head">
      <div class="block-title">
        <span class="block-icon icon-warning" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z" /><path d="M14 2v4a2 2 0 0 0 2 2h4" /><path d="M16 13H8" /><path d="M16 17H8" />
          </svg>
        </span>
        <div>
          <h2>Unexpected expenses</h2>
          <p class="block-hint">
            Only costs that were
            <em>not</em> in the quote
            <InfoTip
              label="Every expense here lowers the final profit automatically — the totals update the moment you save."
            />
          </p>
        </div>
      </div>
      <span v-if="job.expenses?.length" class="expense-total">
        {{ money(job.extra_costs) }}
      </span>
    </header>

    <div v-if="notice" class="notice">{{ notice }}</div>

    <!-- Category breakdown -->
    <div v-if="byCategory.length" class="cat-summary">
      <span v-for="group in byCategory" :key="group.category" class="cat-chip">
        <b>{{ categoryLabel(group.category) }}</b>
        {{ money(group.subtotal) }}
      </span>
    </div>

    <div v-if="job.expenses?.length" class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Expense</th>
            <th>Category</th>
            <th>Date</th>
            <th class="right">Amount</th>
            <th class="right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="item in job.expenses"
            :key="item.id"
            :class="{ editing: editingId === item.id }"
          >
            <td>
              <span class="expense-title">{{ item.title }}</span>
              <span v-if="item.notes" class="hint">{{ item.notes }}</span>
            </td>
            <td>
              <span class="badge" :class="`tone-${toneFor(item.category)}`">
                {{ categoryLabel(item.category) }}
              </span>
            </td>
            <td>{{ String(item.expense_date).slice(0, 10) }}</td>
            <td class="right expense-amount">{{ money(item.amount) }}</td>
            <td class="right">
              <div class="row-actions">
                <button
                  class="icon-action"
                  type="button"
                  :disabled="saving"
                  title="Edit expense"
                  aria-label="Edit expense"
                  @click="startEdit(item)"
                >
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z" />
                  </svg>
                </button>
                <button
                  class="icon-action danger"
                  type="button"
                  :disabled="saving"
                  title="Delete expense"
                  aria-label="Delete expense"
                  @click="deleting = item"
                >
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 6h18" /><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" /><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                  </svg>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
        <tfoot>
          <tr class="grand-total">
            <td colspan="3">Total unexpected costs</td>
            <td class="right">{{ money(job.extra_costs) }}</td>
            <td></td>
          </tr>
        </tfoot>
      </table>
    </div>

    <div v-else class="block-empty">
      <p>Nothing unexpected so far — the job is running to plan. 👍</p>
    </div>

    <form class="grid expense-form" @submit.prevent="saveExpense">
      <div class="field">
        <label>Title</label>
        <input
          v-model="expense.title"
          maxlength="255"
          placeholder="Engine repair"
          :class="{ invalid: errors.title }"
          :disabled="saving"
        />
        <small v-if="errors.title" class="error">{{ errors.title[0] }}</small>
      </div>

      <div class="field">
        <label>Category</label>
        <select v-model="expense.category" :class="{ invalid: errors.category }" :disabled="saving">
          <option value="" disabled>Choose one…</option>
          <option v-for="option in EXPENSE_CATEGORIES" :key="option.value" :value="option.value">
            {{ option.label }}
          </option>
        </select>
        <small v-if="errors.category" class="error">{{ errors.category[0] }}</small>
      </div>

      <div class="field">
        <label>Amount</label>
        <input
          type="number"
          min="0.01"
          step="0.01"
          v-model="expense.amount"
          :class="{ invalid: errors.amount }"
          :disabled="saving"
          placeholder="0.00"
        />
        <small v-if="errors.amount" class="error">{{ errors.amount[0] }}</small>
      </div>

      <div class="field">
        <label>Date</label>
        <input
          type="date"
          v-model="expense.expense_date"
          :class="{ invalid: errors.expense_date }"
          :disabled="saving"
        />
        <small v-if="errors.expense_date" class="error">{{ errors.expense_date[0] }}</small>
      </div>

      <div class="field">
        <label>Notes</label>
        <input
          v-model="expense.notes"
          placeholder="Engine overheated during the Lahore run"
          :class="{ invalid: errors.notes }"
          :disabled="saving"
        />
        <small v-if="errors.notes" class="error">{{ errors.notes[0] }}</small>
      </div>

      <div class="field actions" style="align-self: end">
        <button type="submit" :disabled="saving">
          {{ saving ? 'Saving…' : editingId ? 'Save changes' : '+ Add expense' }}
        </button>
        <button
          v-if="editingId"
          type="button"
          class="btn-light"
          :disabled="saving"
          @click="cancelEdit"
        >
          Cancel
        </button>
      </div>
    </form>

    <ConfirmDialog
      :open="Boolean(deleting)"
      title="Delete expense?"
      :message="
        deleting
          ? `Delete \`${deleting.title}\` (${money(deleting.amount)})? This cannot be undone.`
          : ''
      "
      confirm-label="Delete"
      variant="danger"
      @confirm="removeExpense"
      @cancel="deleting = null"
    />
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
.block-hint {
  align-items: center;
  color: var(--text-muted);
  display: flex;
  flex-wrap: wrap;
  font-size: 13px;
  gap: 4px;
  margin: 2px 0 0;
}
.block-hint em { font-style: normal; font-weight: 600; }

.block-icon {
  align-items: center;
  background: var(--warning-soft);
  border-radius: 9px;
  color: var(--warning);
  display: flex;
  flex: 0 0 32px;
  height: 32px;
  justify-content: center;
  width: 32px;
}
.block-icon svg { height: 15px; width: 15px; }

.expense-total {
  color: var(--text-primary);
  font-size: 16px;
  font-variant-numeric: tabular-nums;
  font-weight: 700;
  white-space: nowrap;
}

.cat-summary {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 14px;
}

.cat-chip {
  background: var(--surface-2);
  border-radius: 999px;
  color: var(--text-secondary);
  display: inline-flex;
  font-size: 12px;
  gap: 6px;
  padding: 5px 12px;
}
.cat-chip b { color: var(--text-primary); }

.expense-title { color: var(--text-primary); font-weight: 500; }
.expense-amount { color: var(--text-primary); font-weight: 600; }

.row-actions {
  display: inline-flex;
  gap: 4px;
  justify-content: flex-end;
}

.icon-action {
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
.icon-action svg { height: 15px; width: 15px; }
.icon-action:hover:not(:disabled) { background: var(--accent-soft); color: var(--accent); }
.icon-action.danger:hover:not(:disabled) { background: var(--danger-soft); color: var(--danger); }

tr.editing td { background: var(--warning-soft); }

.block-empty {
  border: 1px dashed var(--border-strong);
  border-radius: var(--radius-md);
  color: var(--text-muted);
  font-size: 13px;
  padding: 18px 16px;
  text-align: center;
}
.block-empty p { margin: 0; }

.expense-form { margin-top: 16px; }
</style>