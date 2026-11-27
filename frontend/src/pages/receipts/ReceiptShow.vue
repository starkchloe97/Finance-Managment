<script setup>
import { computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useReceiptStore } from '@/stores/receiptStore'
import { money } from '@/utils/money'

const route = useRoute()
const store = useReceiptStore()
const receipt = computed(() => store.receipt)
const heading = computed(() => receipt.value?.direction === 'received' ? 'Receipt' : 'Payment voucher')
const print = () => window.print()
onMounted(() => store.fetchReceipt(route.params.id).catch(() => {}))
</script>

<template>
  <div v-if="store.loading" class="state-panel state-loading"><div class="skeleton-block"></div></div>
  <div v-else-if="store.error" class="card state-panel state-error">{{ store.error }}</div>
  <main v-else-if="receipt" class="receipt-page">
    <div class="print-actions no-print">
      <RouterLink class="btn-light" :to="{ name: 'receipts.index' }">Back to receipts</RouterLink>
      <button class="btn" type="button" @click="print">Print / Save PDF</button>
    </div>
    <article class="receipt-sheet">
      <header class="receipt-header">
        <div><span class="section-kicker">Awan Goods & Services</span><h1>{{ heading }}</h1></div>
        <div class="receipt-id"><b>{{ receipt.receipt_no }}</b><span>{{ receipt.receipt_date }}</span></div>
      </header>
      <section class="amount-block" :class="receipt.direction"><span>{{ receipt.direction === 'received' ? 'Amount received' : 'Amount paid' }}</span><strong>Rs {{ money(receipt.amount) }}</strong></section>
      <dl>
        <div><dt>Transaction</dt><dd>{{ receipt.receipt_type.replaceAll('_', ' ') }}</dd></div>
        <div><dt>Party</dt><dd>{{ receipt.party_name || '—' }}</dd></div>
        <div><dt>Description</dt><dd>{{ receipt.description }}</dd></div>
        <div v-if="receipt.notes"><dt>Notes</dt><dd>{{ receipt.notes }}</dd></div>
      </dl>
      <footer>System-generated financial record · Keep this receipt for your records.</footer>
    </article>
  </main>
</template>

<style scoped>
.receipt-page { margin: 0 auto; max-width: 760px; }
.print-actions { display: flex; gap: 10px; justify-content: flex-end; margin-bottom: 16px; }
.receipt-sheet { background: #fff; border: 1px solid var(--border); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); color: #172033; padding: 42px; }
.receipt-header { border-bottom: 2px solid #172033; display: flex; justify-content: space-between; padding-bottom: 24px; }
.section-kicker { color: #526072; font-size: 12px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
h1 { font-size: 32px; margin: 5px 0 0; }
.receipt-id { align-items: flex-end; display: flex; flex-direction: column; gap: 5px; }
.receipt-id span { color: #526072; }
.amount-block { border-radius: 10px; display: flex; flex-direction: column; gap: 5px; margin: 26px 0; padding: 20px; }
.amount-block.received { background: #ecfdf3; color: #057a55; }
.amount-block.paid { background: #fef2f2; color: #b42318; }
.amount-block strong { font-size: 30px; }
dl { display: grid; gap: 16px; margin: 0; }
dl div { border-bottom: 1px solid #e2e8f0; display: grid; gap: 4px; padding-bottom: 14px; }
dt { color: #526072; font-size: 12px; font-weight: 700; text-transform: uppercase; }
dd { margin: 0; white-space: pre-line; }
footer { color: #718096; font-size: 12px; margin-top: 32px; text-align: center; }
@media print {
  :global(.sidebar), :global(.app-header), :global(.no-print) { display: none !important; }
  :global(body) { background: #fff !important; }
  .receipt-page { max-width: none; }
  .receipt-sheet { border: 0; box-shadow: none; padding: 0; }
}
</style>
