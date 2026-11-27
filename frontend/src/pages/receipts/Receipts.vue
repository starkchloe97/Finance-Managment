<script setup>
import { computed, onMounted, ref } from 'vue'
import { useReceiptStore } from '@/stores/receiptStore'
import { money } from '@/utils/money'
import StatePanel from '@/components/ui/StatePanel.vue'

const store = useReceiptStore()
const search = ref('')
const direction = ref('')
const receipts = computed(() => store.receipts)

const load = () => store.fetchReceipts({ search: search.value || undefined, direction: direction.value || undefined })
const submit = () => load().catch(() => {})
onMounted(submit)
</script>

<template>
  <div class="receipts-page">
    <header class="page-header">
      <div>
        <span class="section-kicker">Finance / Records</span>
        <h1>Receipts</h1>
        <p>Numbered records of money received and paid. Open any receipt to print or save it as PDF.</p>
      </div>
    </header>

    <form class="card filters" @submit.prevent="submit">
      <input v-model="search" type="search" placeholder="Receipt number, party, or description" />
      <select v-model="direction">
        <option value="">All money movements</option>
        <option value="received">Money received</option>
        <option value="paid">Money paid</option>
      </select>
      <button class="btn" type="submit">Search</button>
    </form>

    <StatePanel
      :loading="store.loading"
      :error="store.error"
      :empty="!store.loading && !store.error && !receipts.length"
      empty-title="No receipts issued yet."
    >
      <template #error-action><button class="btn" @click="submit">Try again</button></template>
      <div class="card table-wrap">
        <table>
          <thead><tr><th>Receipt</th><th>Date</th><th>Type</th><th>Party</th><th>Direction</th><th>Amount</th><th></th></tr></thead>
          <tbody>
            <tr v-for="receipt in receipts" :key="receipt.id">
              <td><strong>{{ receipt.receipt_no }}</strong></td>
              <td>{{ receipt.receipt_date }}</td>
              <td>{{ receipt.receipt_type.replaceAll('_', ' ') }}</td>
              <td>{{ receipt.party_name || '—' }}</td>
              <td><span :class="receipt.direction === 'received' ? 'received' : 'paid'">{{ receipt.direction }}</span></td>
              <td>{{ money(receipt.amount) }}</td>
              <td><RouterLink class="btn-light btn-sm" :to="{ name: 'receipts.show', params: { id: receipt.id } }">View</RouterLink></td>
            </tr>
          </tbody>
        </table>
      </div>
    </StatePanel>
  </div>
</template>

<style scoped>
.receipts-page { display: grid; gap: 20px; }
.page-header h1 { margin: 4px 0; }
.page-header p, .section-kicker { color: var(--text-muted); }
.section-kicker { font-size: 12px; font-weight: 600; text-transform: uppercase; }
.filters { display: flex; gap: 10px; padding: 14px; }
.filters input { flex: 1; min-width: 220px; }
.filters input, .filters select { border: 1px solid var(--border-strong); border-radius: var(--radius-md); padding: 9px 10px; }
.table-wrap { overflow-x: auto; padding: 0; }
table { min-width: 800px; width: 100%; }
th, td { border-bottom: 1px solid var(--border); padding: 12px; text-align: left; }
th { color: var(--text-muted); font-size: 12px; }
.received, .paid { border-radius: 999px; font-size: 12px; font-weight: 600; padding: 3px 8px; text-transform: capitalize; }
.received { background: var(--success-soft); color: var(--success); }
.paid { background: var(--danger-soft); color: var(--danger); }
@media (max-width: 640px) { .filters { flex-direction: column; } }
</style>
