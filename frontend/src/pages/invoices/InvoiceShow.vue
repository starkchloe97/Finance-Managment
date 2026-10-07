<script setup>
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useInvoiceStore } from '@/stores/invoiceStore'
import InvoicePaymentForm from '@/components/invoices/InvoicePaymentForm.vue'
import FinanceStatus from '@/components/ui/FinanceStatus.vue'
import StatePanel from '@/components/ui/StatePanel.vue'
import { amountInWords } from '@/utils/numberToWords'
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
          <RouterLink
            v-if="!store.invoice.transport_job_id && Number(store.invoice.paid_amount) === 0"
            class="btn-light"
            :to="`/invoices/${store.invoice.id}/edit`"
          >Edit</RouterLink>
          <button class="btn-light" type="button" @click="print">Print</button>
          <RouterLink class="btn-light" to="/invoices">Back to invoices</RouterLink>
        </div>
      </header>

      <section
        class="invoice-paper"
        :class="store.invoice.category === 'job' ? 'transport-invoice' : 'tax-invoice'"
      >
        <template v-if="store.invoice.category === 'job'">
          <header class="transport-header">
            <img
              v-if="store.invoice.company_logo_url"
              class="company-logo"
              :src="store.invoice.company_logo_url"
              alt=""
            />
            <div class="company-heading">
              <h1>{{ store.invoice.company_name || 'AWAN GOODS TRANSPORT SERVICES' }}</h1>
              <p v-if="store.invoice.company_address">{{ store.invoice.company_address }}</p>
              <p v-if="store.invoice.company_phone">{{ store.invoice.company_phone }}</p>
            </div>
          </header>

          <div class="transport-meta">
            <div class="client-details">
              <p><strong>Client Name</strong><span>{{ store.invoice.party_company || store.invoice.party_name }}</span></p>
              <p><strong>Address</strong><span>{{ store.invoice.party_address }}</span></p>
              <p><strong>Contact Person</strong><span>{{ store.invoice.party_contact }}</span></p>
            </div>
            <dl>
              <div><dt>Invoice</dt><dd>{{ store.invoice.invoice_no }}</dd></div>
              <div><dt>Date</dt><dd>{{ store.invoice.invoice_date }}</dd></div>
            </dl>
          </div>

          <table class="invoice-table transport-table">
            <thead>
              <tr>
                <th>SR</th>
                <th>Details</th>
                <th>Truck No</th>
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
                <td>{{ item.details?.truck_no }}</td>
                <td>{{ item.details?.pickup }}</td>
                <td>{{ item.details?.drop }}</td>
                <td>{{ item.details?.date }}</td>
                <td>{{ item.details?.type }}</td>
                <td>{{ item.details?.ton }}</td>
                <td class="amount">{{ money(item.amount) }}</td>
              </tr>
              <tr class="total-row">
                <td colspan="8">total</td>
                <td class="amount">{{ money(store.invoice.total) }}</td>
              </tr>
            </tbody>
          </table>

          <div class="transport-footer">
            <div class="bank-details"><strong>Bank Details</strong><span>{{ store.invoice.company_bank_details }}</span></div>
            <div class="signature"><span></span><strong>Signature</strong></div>
          </div>
        </template>

        <template v-else>
          <header class="tax-header">
            <div>
              <h1>SALES TAX INVOICE</h1>
              <p>STR NO: {{ store.invoice.company_str_no }}</p>
              <p>NTN NO: {{ store.invoice.company_ntn_no }}</p>
              <p>STNT NO: {{ store.invoice.company_stnt_no }}</p>
            </div>
            <strong class="original">Original</strong>
          </header>

          <div class="tax-meta">
            <div>
              <p><strong>Invoice No</strong><span>{{ store.invoice.invoice_no }}</span></p>
              <p><strong>Date</strong><span>{{ store.invoice.invoice_date }}</span></p>
              <p><strong>Client Name</strong><span>{{ store.invoice.party_company || store.invoice.party_name }}</span></p>
              <p><strong>Address</strong><span>{{ store.invoice.party_address }}</span></p>
            </div>
            <div class="client-tax">
              <p><strong>NTN No</strong><span>{{ store.invoice.party_ntn_no }}</span></p>
              <p><strong>STR No</strong><span>{{ store.invoice.party_str_no }}</span></p>
            </div>
          </div>

          <table class="invoice-table rental-table">
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
                <td>{{ item.quantity }}</td>
                <td>{{ item.description }}</td>
                <td class="amount">{{ money(item.unit_price) }}</td>
                <td class="amount">{{ money(item.amount) }}</td>
              </tr>
            </tbody>
          </table>

          <div class="tax-total"><strong>Total Amount</strong><span>{{ money(store.invoice.total) }}</span></div>
          <div class="amount-words"><strong>Amount in Word</strong><span>{{ amountInWords(store.invoice.total) }}</span></div>
          <div class="signature"><span></span><strong>Signature</strong></div>
        </template>
      </section>

      <section class="summary-grid no-print">
        <div class="card"><span>Total</span><strong>{{ money(store.invoice.total) }}</strong></div>
        <div class="card">
          <span>{{ store.invoice.direction === 'receivable' ? 'Received' : 'Paid' }}</span>
          <strong>{{ money(store.invoice.paid_amount) }}</strong>
        </div>
        <div class="card"><span>Outstanding</span><strong>{{ money(store.invoice.outstanding_amount) }}</strong></div>
        <div class="card"><span>Status</span><FinanceStatus :status="store.invoice.status" /></div>
      </section>

      <section class="card history no-print">
        <h2>Payment history</h2>
        <p v-if="!store.invoice.payments?.length" class="muted">No payments recorded.</p>
        <div v-for="payment in store.invoice.payments" :key="payment.id" class="payment-row">
          <div>
            <strong>{{ payment.payment_no }}</strong>
            <span>{{ payment.payment_date }} · {{ payment.payment_method || 'unspecified method' }}<span v-if="payment.reference"> · {{ payment.reference }}</span></span>
          </div>
          <strong>{{ money(payment.amount) }}</strong>
        </div>
      </section>
      <InvoicePaymentForm class="no-print" :invoice="store.invoice" @saved="load" />
    </article>
  </StatePanel>
</template>

<style scoped>
.invoice-detail { display: grid; gap: var(--space-4); }
.page-heading, .actions, .transport-header, .transport-meta, .transport-footer, .payment-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-4);
}
.section-kicker { color: var(--text-muted); display: block; font-size: var(--text-xs); text-transform: uppercase; }
.invoice-paper {
  background: #fff;
  border: 1px solid #222;
  color: #111;
  font-family: Arial, Helvetica, sans-serif;
  margin: 0 auto;
  max-width: 1100px;
  padding: 30px 36px;
}
.invoice-paper p { margin: 0; }
.transport-header { border-bottom: 1px solid #222; justify-content: center; min-height: 90px; padding-bottom: 14px; text-align: center; }
.company-heading h1 { font-size: 25px; letter-spacing: 1px; margin: 0 0 6px; }
.company-heading p { font-size: 12px; margin-top: 3px; }
.company-logo { max-height: 76px; max-width: 110px; object-fit: contain; }
.transport-meta { align-items: flex-start; margin: 22px 0; }
.client-details { display: grid; gap: 8px; }
.client-details p, .tax-meta p { display: grid; gap: 4px; }
.transport-meta dl { margin: 0; min-width: 180px; }
.transport-meta dl div { display: flex; gap: 18px; justify-content: space-between; padding: 3px 0; }
.transport-meta dt { font-weight: 700; }
.transport-meta dd { margin: 0; }
.invoice-table { border-collapse: collapse; color: #111; table-layout: fixed; width: 100%; }
.invoice-table th, .invoice-table td { border: 1px solid #222; padding: 7px 5px; text-align: left; vertical-align: top; overflow-wrap: anywhere; }
.invoice-table th { font-weight: 700; text-align: center; }
.transport-table { font-size: 10px; }
.transport-table th:nth-child(1) { width: 5%; }
.transport-table th:nth-child(2) { width: 21%; }
.transport-table th:nth-child(3) { width: 11%; }
.transport-table th:nth-child(4), .transport-table th:nth-child(5) { width: 12%; }
.transport-table th:nth-child(6) { width: 10%; }
.transport-table th:nth-child(7) { width: 9%; }
.transport-table th:nth-child(8) { width: 7%; }
.transport-table th:nth-child(9) { width: 13%; }
.amount { text-align: right !important; white-space: nowrap; }
.total-row td { font-weight: 700; }
.total-row td:first-child { text-align: right; text-transform: lowercase; }
.transport-footer { align-items: flex-end; margin-top: 34px; }
.bank-details { display: grid; gap: 5px; max-width: 65%; white-space: pre-line; }
.signature { display: grid; gap: 7px; justify-items: center; margin: 50px 10px 0 auto; min-width: 150px; text-align: center; }
.signature > span { border-top: 1px solid #222; width: 100%; }
.tax-header { display: flex; justify-content: space-between; }
.tax-header h1 { font-size: 21px; margin: 0 0 13px; }
.tax-header p { font-size: 12px; margin-top: 4px; }
.original { border: 1px solid #222; font-size: 12px; height: fit-content; padding: 4px 12px; }
.tax-meta { display: grid; gap: 20px; grid-template-columns: 2fr 1fr; margin: 24px 0; }
.tax-meta > div { display: grid; gap: 10px; }
.tax-meta p { grid-template-columns: 110px 1fr; }
.client-tax { align-content: start; }
.rental-table th:first-child { width: 16%; }
.rental-table th:nth-child(2) { width: 46%; }
.rental-table th:nth-child(3), .rental-table th:nth-child(4) { width: 19%; }
.rental-table td { height: 31px; }
.tax-total, .amount-words { display: flex; gap: 16px; margin-top: 14px; }
.tax-total { justify-content: flex-end; }
.amount-words { border-bottom: 1px solid #222; padding-bottom: 9px; }
.amount-words span { text-transform: capitalize; }
.summary-grid { display: grid; gap: var(--space-3); grid-template-columns: repeat(4, minmax(0, 1fr)); }
.summary-grid .card { display: grid; gap: var(--space-2); }
.summary-grid span, .muted, .payment-row span { color: var(--text-muted); font-size: var(--text-sm); }
.summary-grid strong { font-size: var(--text-lg); }
.history { display: grid; gap: var(--space-3); }
.payment-row { border-bottom: 1px solid var(--border); padding: 10px 0; }
.payment-row div { display: grid; gap: 3px; }

@page { size: A4 portrait; margin: 12mm; }
@media print {
  :global(html), :global(body), :global(#app) { background: #fff !important; height: 0 !important; margin: 0 !important; min-height: 0 !important; }
  :global(body *) { visibility: hidden !important; }
  :global(.app), :global(.content), :global(.app-content), .invoice-detail {
    display: block !important;
    flex: none !important;
    height: 0 !important;
    margin: 0 !important;
    min-height: 0 !important;
    overflow: visible !important;
    padding: 0 !important;
  }
  .invoice-paper, .invoice-paper * { visibility: visible !important; }
  .invoice-paper {
    border: 0;
    box-shadow: none;
    left: 0;
    margin: 0;
    max-width: none;
    padding: 0;
    position: absolute;
    top: 0;
    width: 100%;
  }
  .transport-table { font-size: 8px; }
  .transport-table th, .transport-table td { padding: 5px 3px; }
  .invoice-table thead { display: table-header-group; }
  .transport-footer, .tax-total, .amount-words, .signature { break-inside: avoid; }
  .invoice-table tr { break-inside: avoid; }
}
@media (max-width: 700px) {
  .invoice-paper { overflow-x: auto; padding: 18px; }
  .transport-meta { flex-direction: column; }
  .transport-table { min-width: 720px; }
  .summary-grid { grid-template-columns: repeat(2, 1fr); }
  .tax-meta { grid-template-columns: 1fr; }
}
</style>
