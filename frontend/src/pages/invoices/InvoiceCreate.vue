<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useInvoiceStore } from '@/stores/invoiceStore'
import { getInvoiceCompanyDetails } from '@/services/invoiceService'

const router = useRouter()
const route = useRoute()
const store = useInvoiceStore()
const saving = ref(false)
const error = ref('')
const isEditing = computed(() => route.name === 'invoices.edit')
const detailFields = computed(() =>
  form.category === 'job'
    ? [
        ['truck_no', 'Truck number', 'text', 'Enter the truck or vehicle registration number'],
        ['pickup', 'Pickup location', 'text', 'Enter the pickup address or location'],
        ['drop', 'Drop location', 'text', 'Enter the delivery address or location'],
        ['date', 'Service date', 'date'],
        ['type', 'Vehicle type', 'text', 'e.g. Truck, trailer, or van'],
        ['ton', 'Ton', 'number'],
      ]
    : form.category === 'vehicle_rental'
      ? [
          ['vehicle_make', 'Vehicle', 'text', 'Enter the vehicle make or model'],
          ['registration', 'Registration number', 'text', 'Vehicle plate number, e.g. ABC-123'],
          ['delivery_date', 'Delivery date', 'date'],
          ['period', 'Rental period', 'text', 'e.g. 1 month or 15 days'],
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
  party_ntn_no: '',
  party_str_no: '',
  company_name: '',
  company_phone: '',
  company_address: '',
  company_str_no: '',
  company_ntn_no: '',
  company_stnt_no: '',
  company_bank_details: '',
  company_logo_url: '',
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
onMounted(async () => {
  if (isEditing.value) {
    try {
      const invoice = await store.fetchInvoice(route.params.id)
      Object.assign(form, {
        ...invoice,
        items: invoice.items.map((item) => ({
          description: item.description,
          quantity: Number(item.quantity),
          unit: item.unit || '',
          unit_price: Number(item.unit_price),
          details: item.details || {},
        })),
      })
    } catch (e) {
      error.value = e.response?.data?.message || 'Could not load invoice for editing.'
    }
    return
  }

  try {
    const { data } = await getInvoiceCompanyDetails()
    Object.assign(form, data.data)
  } catch (e) {
    error.value = e.response?.data?.message || 'Could not load saved company details.'
  }
})
const save = async () => {
  saving.value = true
  error.value = ''
  try {
    const invoice = isEditing.value
      ? await store.updateInvoice(route.params.id, form)
      : await store.saveInvoice(form)
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
        <h1>{{ isEditing ? 'Edit invoice' : 'Create invoice' }}</h1>
      </div>
      <RouterLink class="btn-light" :to="isEditing ? `/invoices/${route.params.id}` : '/invoices'"
        >Cancel</RouterLink
      >
    </header>
    <div v-if="error" class="form-error" role="alert">{{ error }}</div>
    <section class="card form-grid">
      <label
        >Direction<select v-model="form.direction" :disabled="isEditing">
          <option value="receivable">Receivable (customer invoice)</option>
          <option value="payable">Payable (vendor bill)</option>
        </select></label
      >
      <label
        >Category<select v-model="form.category" :disabled="isEditing">
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
      <label
        >Party name<input
          v-model="form.party_name"
          :placeholder="
            form.direction === 'receivable' ? 'Customer or client name' : 'Supplier or payee name'
          "
          required
          maxlength="255"
      /></label>
      <label
        >Company<input
          v-model="form.party_company"
          placeholder="Party's company name"
          maxlength="255"
      /></label>
      <label
        >Contact person<input
          v-model="form.party_contact"
          placeholder="Name of the person to contact"
          maxlength="255"
      /></label>
      <label
        >Phone<input v-model="form.party_phone" placeholder="e.g. +92 300 1234567" maxlength="40"
      /></label>
      <label>Invoice date<input v-model="form.invoice_date" type="date" required /></label>
      <label>Due date<input v-model="form.due_date" type="date" /></label>
      <label class="wide"
        >Party address<textarea
          v-model="form.party_address"
          placeholder="Street, city, and other address details"
          rows="2"
        ></textarea>
      </label>
      <label
        >Client NTN (tax ID)<input
          v-model="form.party_ntn_no"
          placeholder="Client's National Tax Number"
      /></label>
      <label
        >Client STR (sales tax registration)<input
          v-model="form.party_str_no"
          placeholder="Client's Sales Tax Registration number"
      /></label>
      <label
        >Issuer/company name<input
          v-model="form.company_name"
          placeholder="Your business's registered or trading name"
          maxlength="255"
      /></label>
      <label
        >Issuer phone<input
          v-model="form.company_phone"
          placeholder="Business phone number"
          maxlength="40"
      /></label>
      <label class="wide"
        >Issuer address<textarea
          v-model="form.company_address"
          placeholder="Your business address shown on the invoice"
          rows="2"
        ></textarea>
      </label>
      <label
        >Issuer STR (sales tax registration)<input
          v-model="form.company_str_no"
          placeholder="Your business's Sales Tax Registration number"
      /></label>
      <label
        >Issuer NTN (tax ID)<input
          v-model="form.company_ntn_no"
          placeholder="Your business's National Tax Number"
      /></label>
      <label
        >Issuer STNT No<input
          v-model="form.company_stnt_no"
          placeholder="Your business's sales tax number, if applicable"
      /></label>
      <label class="wide"
        >Bank details<textarea
          v-model="form.company_bank_details"
          placeholder="Bank name, account title, and account number shown for payment"
          rows="2"
        ></textarea>
      </label>
    </section>
    <section class="card">
      <div class="section-header">
        <h2>Line items</h2>
        <button class="btn-light" type="button" @click="addLine">Add line</button>
      </div>
      <div v-for="(item, index) in form.items" :key="index" class="item-grid">
        <label class="wide"
          >Description<input v-model="item.description" placeholder="What is being billed" required
        /></label>
        <label
          >Quantity<input
            v-model.number="item.quantity"
            type="number"
            min="0.001"
            step="0.001"
            placeholder="e.g. 1"
            required
        /></label>
        <label>Unit<input v-model="item.unit" placeholder="e.g. job, vehicle, month" /></label>
        <label
          >Unit price<input
            v-model.number="item.unit_price"
            type="number"
            min="0"
            step="0.01"
            placeholder="e.g. 2500.00"
            required
        /></label>
        <label v-for="[key, label, type, placeholder] in detailFields" :key="key"
          >{{ label
          }}<input v-model="item.details[key]" :type="type || 'text'" :placeholder="placeholder"
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
          placeholder="e.g. 0.00"
      /></label>
      <label>Tax label<input v-model="form.tax_label" placeholder="e.g. Sales tax or VAT" /></label>
      <label
        >Tax registration number<input
          v-model="form.tax_number"
          placeholder="Registration number for the tax above"
      /></label>
      <label class="wide"
        >Notes<textarea
          v-model="form.notes"
          placeholder="Optional payment terms or other invoice notes"
          rows="2"
        ></textarea>
      </label>
    </section>
    <button class="btn" type="submit" :disabled="saving">
      {{ saving ? 'Saving…' : isEditing ? 'Save invoice' : 'Create invoice' }}
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
