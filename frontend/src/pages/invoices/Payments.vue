<script setup>
import { onMounted } from 'vue'
import { useInvoiceStore } from '@/stores/invoiceStore'
import StatePanel from '@/components/ui/StatePanel.vue'
import { money } from '@/utils/money'

const store = useInvoiceStore()
const load = () => store.fetchPayments()
onMounted(load)
</script>

<template>
  <div class="payments-page">
    <header>
      <span class="section-kicker">Finance / Accounting</span>
      <h1>Payments</h1>
      <p class="muted">
        Recorded receipts and outgoing settlements; payments remain separate from company capital.
      </p>
    </header>
    <StatePanel
      :loading="store.loading"
      :error="store.error"
      :empty="!store.loading && !store.error && !store.payments.length"
      empty-title="No payments recorded yet."
    >
      <template #error-action><button class="btn" @click="load">Try again</button></template>
      <div class="card table-wrap">
        <table>
          <thead>
            <tr>
              <th>Payment</th>
              <th>Date</th>
              <th>Direction</th>
              <th>Amount</th>
              <th>Method</th>
              <th>Reference</th>
              <th>Invoices</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="payment in store.payments" :key="payment.id">
              <td>{{ payment.payment_no }}</td>
              <td>{{ payment.payment_date }}</td>
              <td>{{ payment.direction === 'received' ? 'Received' : 'Paid' }}</td>
              <td>{{ money(payment.amount) }}</td>
              <td>{{ payment.payment_method || '—' }}</td>
              <td>{{ payment.reference || '—' }}</td>
              <td>
                <RouterLink
                  v-for="allocation in payment.allocations"
                  :key="allocation.invoice_id"
                  :to="`/invoices/${allocation.invoice_id}`"
                  class="invoice-link"
                  >{{ allocation.invoice_no }} ({{ money(allocation.amount) }})</RouterLink
                >
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </StatePanel>
  </div>
</template>

<style scoped>
.payments-page {
  display: grid;
  gap: var(--space-4);
}
.section-kicker {
  color: var(--text-muted);
  display: block;
  font-size: var(--text-xs);
  text-transform: uppercase;
}
.table-wrap {
  overflow-x: auto;
  padding: 0;
}
table {
  width: 100%;
  min-width: 800px;
}
th,
td {
  border-bottom: 1px solid var(--border);
  padding: 12px;
  text-align: left;
}
th {
  color: var(--text-muted);
  font-size: var(--text-sm);
}
.invoice-link {
  display: block;
}
</style>
