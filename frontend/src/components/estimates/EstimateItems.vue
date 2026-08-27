<script setup>
import { money } from '@/utils/money'
import { toneFor } from '@/utils/tone'

defineProps({
  items: { type: Array, default: () => [] },
})
</script>

<template>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Description</th>
          <th>Category</th>
          <th class="right">Qty</th>
          <th class="right">Amount</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="item in items" :key="item.id">
          <td>
            <span class="item-title">{{ item.title }}</span>
            <span v-if="item.remarks" class="hint">{{ item.remarks }}</span>
          </td>
          <td>
            <span v-if="item.category" class="badge" :class="`tone-${toneFor(item.category)}`">
              {{ item.category }}
            </span>
            <span v-else>—</span>
          </td>
          <td class="right">{{ Number(item.quantity) }}</td>
          <td class="right item-amount">{{ money(item.sell_total ?? item.sell_price) }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<style scoped>
.item-title {
  color: var(--text-primary);
  display: block;
  font-weight: 500;
}
.item-amount { color: var(--text-primary); font-weight: 600; }
</style>