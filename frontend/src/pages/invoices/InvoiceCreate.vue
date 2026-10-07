<script setup>
import { computed, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useInvoiceStore } from '@/stores/invoiceStore'

const router = useRouter()
const store = useInvoiceStore()
const saving = ref(false)
const error = ref('')
const detailFields = computed(() =>
  form.category === 'job'
    ? [
        ['truck_no', 'Truck no'],
        ['pickup', 'Pickup'],
        ['drop', 'Drop'],
        ['date', 'Service date'],
        ['type', 'Type'],
        ['ton', 'Ton'],
      ]
    : form.category === 'vehicle_rental'
      ? [
          ['vehicle_make', 'Vehicle'],
          ['registration', 'Registration'],
          ['delivery_date', 'Delivery date'],
          ['period', 'Rental period'],
        ]
      : [],
)
const form = reactive({
  direction: 'receivable',
  category: 'sales',
  party_name: '',
  party_company: '',
  party_contact: '',
  party_phone: '',
  party_address: '',
  party_tax_number: '',
  company_name: '',
  company_phone: '',
  company_address: '',
  company_tax_number: '',
  invoice_date: new Date().toISOString().slice(0, 10),
  due_date: '',
  tax_amount: 0,
  tax_label: '',
  tax_number: '',
  notes: '',
  items: [{ description: '', quantity: 1, unit: '', unit_price: 0, details: {} }],
})
const addLine = () =>
  form.items.push({ description: '', quantity: 1, unit: '', unit_price: 0, details: {} })
const removeLine = (index) => {
  if (form.items.length > 1) form.items.splice(index, 1)
}
const save = async () => {
  saving.value = true
  error.value = ''
  try {
    const invoice = await store.saveInvoice(form)
    await router.push(`/invoices/${invoice.id}`)
  } catch (e) {
    error.value =
      e.response?.data?.message ||
      Object.values(e.response?.data?.errors || {})[0]?.[0] ||
      'Could not create invoice.'
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <form class="invoice-form" @submit.prevent="save">
    <header class="page-heading">
      <div>
        <span class="section-kicker">Finance / Invoices</span>
        <h1>Create invoice</h1>
      </div>
      <RouterLink class="btn-light" to="/invoices">Cancel</RouterLink>
    </header>
    <div v-if="error" class="form-error" role="alert">{{ error }}</div>
    <section class="card form-grid">
      <label
        >Direction<select v-model="form.direction">
          <option value="receivable">Receivable (customer invoice)</option>
          <option value="payable">Payable (vendor bill)</option>
        </select></label
      >
      <label
        >Category<select v-model="form.category">
          <option value="job">Transport job</option>
          <option value="vehicle_rental">Vehicle rental</option>
          <option value="sales">Sales</option>
          <option value="commission">Commission</option>
          <option value="contractor">Contractor</option>
          <option value="supplier">Supplier</option>
          <option value="administrative">Administrative</option>
          <option value="marketing">Marketing</option>
          <option value="other">Other</option>
        </select></label
      >
      <label>Party name<input v-model="form.party_name" required maxlength="255" /></label>
      <label>Company<input v-model="form.party_company" maxlength="255" /></label>
      <label>Contact person<input v-model="form.party_contact" maxlength="255" /></label>
      <label>Phone<input v-model="form.party_phone" maxlength="40" /></label>
      <label>Invoice date<input v-model="form.invoice_date" type="date" required /></label>
      <label>Due date<input v-model="form.due_date" type="date" /></label>
      <label class="wide"
        >Party address<textarea v-model="form.party_address" rows="2"></textarea>
      </label>
      <label>Party tax registration<input v-model="form.party_tax_number" /></label>
      <label>Issuer/company name<input v-model="form.company_name" maxlength="255" /></label>
      <label>Issuer phone<input v-model="form.company_phone" maxlength="40" /></label>
      <label class="wide"
        >Issuer address<textarea v-model="form.company_address" rows="2"></textarea>
      </label>
      <label>Issuer tax registration<input v-model="form.company_tax_number" /></label>
    </section>
    <section class="card">
      <div class="section-header">
        <h2>Line items</h2>
        <button class="btn-light" type="button" @click="addLine">Add line</button>
      </div>
      <div v-for="(item, index) in form.items" :key="index" class="item-grid">
        <label class="wide">Description<input v-model="item.description" required /></label>
        <label
          >Qty<input v-model.number="item.quantity" type="number" min="0.001" step="0.001" required
        /></label>
        <label>Unit<input v-model="item.unit" placeholder="job, vehicle, month…" /></label>
        <label
          >Unit price<input
            v-model.number="item.unit_price"
            type="number"
            min="0"
            step="0.01"
            required
        /></label>
        <label v-for="[key, label] in detailFields" :key="key"
          >{{ label }}<input v-model="item.details[key]"
        /></label>
        <button
          v-if="form.items.length > 1"
          type="button"
          class="btn-light remove"
          @click="removeLine(index)"
        >
          Remove
        </button>
      </div>
    </section>
    <section class="card form-grid">
      <label
        >Tax amount (no rate is assumed)<input
          v-model.number="form.tax_amount"
          type="number"
          min="0"
          step="0.01"
      /></label>
      <label>Tax label<input v-model="form.tax_label" placeholder="Sales tax / STR / NTN" /></label>
      <label>Tax number<input v-model="form.tax_number" /></label>
      <label class="wide">Notes<textarea v-model="form.notes" rows="2"></textarea></label>
    </section>
    <button class="btn" type="submit" :disabled="saving">
      {{ saving ? 'Saving…' : 'Create invoice' }}
    </button>
  </form>
</template>

<style scoped>
.invoice-form {
  display: grid;
  gap: var(--space-4);
}
.page-heading,
.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-3);
}
.section-kicker {
  color: var(--text-muted);
  display: block;
  font-size: var(--text-xs);
  text-transform: uppercase;
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
.wide {
  grid-column: 1 / -1;
}
.item-grid {
  align-items: end;
  border-top: 1px solid var(--border);
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: var(--space-3);
  padding: var(--space-3) 0;
}
.section-header {
  margin-bottom: var(--space-3);
}
.form-error {
  color: var(--danger);
}
.remove {
  color: var(--danger);
}
@media (max-width: 650px) {
  .form-grid,
  .item-grid {
    grid-template-columns: 1fr 1fr;
  }
}
</style>
