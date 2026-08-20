<script setup>
import { ref, reactive, computed } from 'vue'
import { addExpense, updateExpense, deleteExpense } from '@/services/transportJobExpenseService'
import { money } from '@/utils/money'
import { EXPENSE_CATEGORIES, categoryLabel } from '@/utils/expenseCategories'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'

const props = defineProps({
  job: { type: Object, required: true },
})
const emit = defineEmits(['updated'])

// The server returns the recalculated job after every mutation. This component
// only surfaces it.
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
    notice.value = wasEditing ? 'Expense updated.' : 'Expense added.'
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
  <div class="card">
    <h3>Unexpected Expenses</h3>
    <p class="hint">
      Only costs that were not in the quote. Each one lowers the final profit — the job's totals are
      recalculated by the server.
    </p>

    <div v-if="notice" class="notice">{{ notice }}</div>

    <div class="table-wrap" v-if="job.expenses?.length">
      <table>
        <thead>
          <tr>
            <th>Expense</th>
            <th>Category</th>
            <th>Date</th>
            <th class="right">Amount</th>
            <th width="140"></th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="item in job.expenses"
            :key="item.id"
            :class="{ editing: editingId === item.id }"
          >
            <td>
              {{ item.title }}
              <span v-if="item.notes" class="hint">{{ item.notes }}</span>
            </td>
            <td>
              <span class="badge">{{ categoryLabel(item.category) }}</span>
            </td>
            <td>{{ String(item.expense_date).slice(0, 10) }}</td>
            <td class="right">{{ money(item.amount) }}</td>
            <td class="right">
              <button class="btn-light btn-sm" :disabled="saving" @click="startEdit(item)">
                Edit
              </button>
              <button class="btn-danger btn-sm" :disabled="saving" @click="deleting = item">
                Delete
              </button>
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

    <p v-else class="empty">Nothing unexpected so far — the job is running to plan.</p>

    <form class="grid" style="margin-top: 14px" @submit.prevent="saveExpense">
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
          {{ saving ? 'Saving…' : editingId ? 'Save Changes' : '+ Add Expense' }}
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
  </div>
</template>
