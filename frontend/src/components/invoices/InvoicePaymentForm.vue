<script setup>
import { computed, reactive, ref } from 'vue'
import { useInvoiceStore } from '@/stores/invoiceStore'

const props = defineProps({ invoice: { type: Object, required: true } })
const emit = defineEmits(['saved'])
const store = useInvoiceStore()
const saving = ref(false)
const error = ref('')
const form = reactive({
  payment_date: new Date().toISOString().slice(0, 10),
  amount: '',
  payment_method: 'bank_transfer',
  reference: '',
  notes: '',
})
const direction = computed(() => (props.invoice.direction === 'receivable' ? 'received' : 'paid'))

const save = async () => {
  error.value = ''
  const amount = Number(form.amount)
  if (!amount || amount <= 0 || amount > Number(props.invoice.outstanding_amount)) {
    error.value = 'Enter a positive amount no greater than the outstanding balance.'
    return
  }
  saving.value = true
  try {
    await store.savePayment({
      ...form,
      amount,
      direction: direction.value,
      allocations: [{ invoice_id: props.invoice.id, amount }],
    })
    form.amount = ''
    form.reference = ''
    form.notes = ''
    emit('saved')
  } catch (e) {
    error.value =
      e.response?.data?.errors?.allocations?.[0] ||
      e.response?.data?.errors?.amount?.[0] ||
      e.response?.data?.message ||
      'Could not record the payment.'
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <form
    v-if="Number(invoice.outstanding_amount) > 0"
    class="card payment-form"
    @submit.prevent="save"
  >
    <h3>{{ direction === 'received' ? 'Receive payment' : 'Make payment' }}</h3>
    <p class="muted">Outstanding: Rs {{ Number(invoice.outstanding_amount).toLocaleString() }}</p>
    <div v-if="error" class="form-error" role="alert">{{ error }}</div>
    <div class="form-grid">
      <label>Payment date <input v-model="form.payment_date" type="date" required /></label>
      <label
        >Amount
        <input
          v-model="form.amount"
          type="number"
          min="0.01"
          step="0.01"
          :max="invoice.outstanding_amount"
          required
      /></label>
      <label
        >Method
        <select v-model="form.payment_method">
          <option value="cash">Cash</option>
          <option value="bank_transfer">Bank transfer</option>
          <option value="cheque">Cheque</option>
          <option value="other">Other</option>
        </select>
      </label>
      <label>Reference <input v-model="form.reference" maxlength="255" /></label>
    </div>
    <label>Notes <textarea v-model="form.notes" rows="2"></textarea></label>
    <button class="btn" type="submit" :disabled="saving">
      {{ saving ? 'Saving…' : 'Record payment' }}
    </button>
  </form>
</template>

<style scoped>
.payment-form {
  display: grid;
  gap: var(--space-3);
}
.payment-form h3 {
  margin: 0;
}
.form-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: var(--space-3);
}
label {
  display: grid;
  gap: var(--space-1);
  color: var(--text-secondary);
  font-size: var(--text-sm);
}
input,
select,
textarea {
  border: 1px solid var(--border-strong);
  border-radius: var(--radius-sm);
  padding: 9px 10px;
  width: 100%;
}
.form-error {
  color: var(--danger);
}
@media (max-width: 560px) {
  .form-grid {
    grid-template-columns: 1fr;
  }
}
</style>
