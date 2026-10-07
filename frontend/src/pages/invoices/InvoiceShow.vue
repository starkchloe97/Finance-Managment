<script setup>
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useInvoiceStore } from '@/stores/invoiceStore'
import InvoicePaymentForm from '@/components/invoices/InvoicePaymentForm.vue'
import FinanceStatus from '@/components/ui/FinanceStatus.vue'
import StatePanel from '@/components/ui/StatePanel.vue'
import { money } from '@/utils/money'

const route = useRoute()
const store = useInvoiceStore()
const error = ref('')
const load = async () => {
  error.value = ''
  try {
    await store.fetchInvoice(route.params.id)
  } catch (e) {
    error.value = e.response?.data?.message || 'Could not load invoice.'
  }
}
const print = () => window.print()
onMounted(load)
</script>

<template>
  <StatePanel
    :loading="store.loading"
    :error="error"
    :empty="!store.loading && !error && !store.invoice"
    empty-title="Invoice not found."
  >
    <template #error-action><button class="btn" @click="load">Try again</button></template>
    <article v-if="store.invoice" class="invoice-detail">
      <header class="page-heading no-print">
        <div>
          <span class="section-kicker">Finance / Invoice details</span>
          <h1>{{ store.invoice.invoice_no }}</h1>
        </div>
        <div class="actions">
          <button class="btn-light" type="button" @click="print">Print</button
          ><RouterLink class="btn-light" to="/invoices">Back to invoices</RouterLink>
        </div>
      </header>
      <section class="card invoice-paper">
        <header class="invoice-head">
          <div>
            <h2>{{ store.invoice.company_name || 'Invoice' }}</h2>
            <strong>{{
              store.invoice.tax_label ||
              (store.invoice.category === 'job' ? 'Transport Invoice' : 'Invoice')
            }}</strong>
            <span class="muted">{{ store.invoice.company_address }}</span
            ><span class="muted">{{ store.invoice.company_phone }}</span
            ><span v-if="store.invoice.company_tax_number" class="muted">
              Company tax registration: {{ store.invoice.company_tax_number }}
            </span>
            ><span v-if="store.invoice.tax_number" class="muted">
              {{ store.invoice.tax_label || 'Tax registration' }}: {{ store.invoice.tax_number }}
            </span>
          </div>
          <div class="invoice-no">
            <strong>{{ store.invoice.invoice_no }}</strong
            ><span>{{ store.invoice.direction === 'receivable' ? 'Receivable' : 'Payable' }}</span>
          </div>
        </header>
        <div class="party-grid">
          <div>
            <span class="muted">{{
              store.invoice.direction === 'receivable' ? 'Bill to' : 'Pay to'
            }}</span
            ><strong>{{ store.invoice.party_company || store.invoice.party_name }}</strong
            ><span v-if="store.invoice.party_contact">{{ store.invoice.party_contact }}</span
            ><span v-if="store.invoice.party_address">{{ store.invoice.party_address }}</span
            ><span v-if="store.invoice.party_phone">{{ store.invoice.party_phone }}</span
            ><span v-if="store.invoice.party_tax_number"
              >Tax registration: {{ store.invoice.party_tax_number }}</span
            >
          </div>
          <dl>
            <div>
              <dt>Invoice date</dt>
              <dd>{{ store.invoice.invoice_date }}</dd>
            </div>
            <div>
              <dt>Due date</dt>
              <dd>{{ store.invoice.due_date || '—' }}</dd>
            </div>
            <div>
              <dt>Category</dt>
              <dd>{{ store.invoice.category.replaceAll('_', ' ') }}</dd>
            </div>
            <div v-if="store.invoice.source">
              <dt>Source</dt>
              <dd>
                <RouterLink :to="`/jobs/${store.invoice.source.id}`">{{
                  store.invoice.source.code
                }}</RouterLink>
              </dd>
            </div>
          </dl>
        </div>
        <div class="table-wrap">
          <table v-if="store.invoice.category === 'job'">
            <thead>
              <tr>
                <th>Sr</th>
                <th>Details</th>
                <th>Truck no</th>
                <th>Pickup</th>
                <th>Drop</th>
                <th>Date</th>
                <th>Type</th>
                <th>Ton</th>
                <th>Amount</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(item, index) in store.invoice.items" :key="item.id">
                <td>{{ index + 1 }}</td>
                <td>{{ item.description }}</td>
                <td>{{ item.details?.truck_no || '—' }}</td>
                <td>{{ item.details?.pickup || '—' }}</td>
                <td>{{ item.details?.drop || '—' }}</td>
                <td>{{ item.details?.date || '—' }}</td>
                <td>{{ item.details?.type || '—' }}</td>
                <td>{{ item.details?.ton || '—' }}</td>
                <td>{{ money(item.amount) }}</td>
              </tr>
            </tbody>
          </table>
          <table v-else-if="store.invoice.category === 'vehicle_rental'">
            <thead>
              <tr>
                <th>Quantity</th>
                <th>Description</th>
                <th>Rental</th>
                <th>Total</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in store.invoice.items" :key="item.id">
                <td>{{ item.quantity }} {{ item.unit || 'Unit' }}</td>
                <td>
                  {{ item.description }}
                  <div class="muted">
                    {{
                      [
                        item.details?.vehicle_make,
                        item.details?.registration,
                        item.details?.delivery_date,
                        item.details?.period,
                      ]
                        .filter(Boolean)
                        .join(' · ')
                    }}
                  </div>
                </td>
                <td>{{ money(item.unit_price) }}</td>
                <td>{{ money(item.amount) }}</td>
              </tr>
            </tbody>
          </table>
          <table v-else>
            <thead>
              <tr>
                <th>Description</th>
                <th>Qty</th>
                <th>Unit</th>
                <th>Unit price</th>
                <th>Amount</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in store.invoice.items" :key="item.id">
                <td>
                  {{ item.description }}
                  <div class="muted" v-if="item.details">
                    {{
                      Object.entries(item.details)
                        .map(([key, value]) => `${key.replaceAll('_', ' ')}: ${value}`)
                        .join(' · ')
                    }}
                  </div>
                </td>
                <td>{{ item.quantity }}</td>
                <td>{{ item.unit || '—' }}</td>
                <td>{{ money(item.unit_price) }}</td>
                <td>{{ money(item.amount) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="total-box">
          <div>
            <span>Subtotal</span><strong>{{ money(store.invoice.subtotal) }}</strong>
          </div>
          <div v-if="Number(store.invoice.tax_amount)">
            <span>{{ store.invoice.tax_label || 'Tax' }}</span
            ><strong>{{ money(store.invoice.tax_amount) }}</strong>
          </div>
          <div class="grand">
            <span>Total</span><strong>{{ money(store.invoice.total) }}</strong>
          </div>
        </div>
        <p v-if="store.invoice.notes" class="invoice-notes">{{ store.invoice.notes }}</p>
      </section>
      <section class="summary-grid">
        <div class="card">
          <span>Total</span><strong>{{ money(store.invoice.total) }}</strong>
        </div>
        <div class="card">
          <span>{{ store.invoice.direction === 'receivable' ? 'Received' : 'Paid' }}</span
          ><strong>{{ money(store.invoice.paid_amount) }}</strong>
        </div>
        <div class="card">
          <span>Outstanding</span><strong>{{ money(store.invoice.outstanding_amount) }}</strong>
        </div>
        <div class="card"><span>Status</span><FinanceStatus :status="store.invoice.status" /></div>
      </section>
      <section class="card history">
        <h2>Payment history</h2>
        <p v-if="!store.invoice.payments?.length" class="muted">No payments recorded.</p>
        <div v-for="payment in store.invoice.payments" :key="payment.id" class="payment-row">
          <div>
            <strong>{{ payment.payment_no }}</strong
            ><span
              >{{ payment.payment_date }} · {{ payment.payment_method || 'unspecified method'
              }}<span v-if="payment.reference"> · {{ payment.reference }}</span></span
            >
          </div>
          <strong>{{ money(payment.amount) }}</strong>
        </div>
      </section>
      <InvoicePaymentForm :invoice="store.invoice" @saved="load" />
    </article>
  </StatePanel>
</template>

<style scoped>
.invoice-detail {
  display: grid;
  gap: var(--space-4);
}
.page-heading,
.actions,
.invoice-head,
.party-grid,
.payment-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-4);
}
.section-kicker {
  color: var(--text-muted);
  display: block;
  font-size: var(--text-xs);
  text-transform: uppercase;
}
.invoice-paper {
  padding: 32px;
}
.invoice-head {
  border-bottom: 2px solid var(--text-primary);
  padding-bottom: var(--space-4);
}
.invoice-no {
  display: grid;
  text-align: right;
}
.party-grid {
  align-items: flex-start;
  padding: var(--space-5) 0;
}
.party-grid > div {
  display: grid;
  gap: var(--space-1);
  max-width: 55%;
}
dl {
  margin: 0;
  min-width: 220px;
}
dl div,
.total-box div {
  display: flex;
  justify-content: space-between;
  gap: var(--space-4);
  padding: 5px 0;
}
dt,
.total-box span {
  color: var(--text-muted);
}
dd {
  margin: 0;
  text-align: right;
  text-transform: capitalize;
}
.table-wrap {
  overflow-x: auto;
}
table {
  border-collapse: collapse;
  width: 100%;
}
th,
td {
  border-bottom: 1px solid var(--border);
  padding: 12px 8px;
  text-align: left;
  vertical-align: top;
}
th {
  color: var(--text-muted);
  font-size: var(--text-sm);
}
.muted {
  font-size: var(--text-sm);
}
.total-box {
  margin: var(--space-4) 0 0 auto;
  max-width: 340px;
}
.total-box .grand {
  border-top: 1px solid var(--border-strong);
  font-size: var(--text-lg);
  margin-top: var(--space-2);
  padding-top: var(--space-3);
}
.invoice-notes {
  border-top: 1px solid var(--border);
  margin-top: var(--space-4);
  padding-top: var(--space-3);
  white-space: pre-wrap;
}
.summary-grid {
  display: grid;
  gap: var(--space-3);
  grid-template-columns: repeat(4, minmax(0, 1fr));
}
.summary-grid .card {
  display: grid;
  gap: var(--space-2);
}
.summary-grid span {
  color: var(--text-muted);
  font-size: var(--text-sm);
}
.summary-grid strong {
  font-size: var(--text-lg);
}
.history {
  display: grid;
  gap: var(--space-3);
}
.payment-row {
  border-bottom: 1px solid var(--border);
  padding: 10px 0;
}
.payment-row div {
  display: grid;
  gap: 3px;
}
.payment-row span {
  color: var(--text-muted);
  font-size: var(--text-sm);
}
@media print {
  :global(body) {
    background: white;
  }
  .no-print {
    display: none !important;
  }
  .invoice-detail {
    display: block;
  }
  .invoice-detail > * {
    margin-bottom: 16px;
  }
  .invoice-paper {
    border: 0;
    box-shadow: none;
    padding: 0;
  }
}
@media (max-width: 700px) {
  .party-grid {
    flex-direction: column;
  }
  .party-grid > div {
    max-width: none;
  }
  .summary-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>
