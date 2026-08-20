<script setup>
import { money } from '@/utils/money'

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
            {{ item.title }}
            <span v-if="item.remarks" class="hint">{{ item.remarks }}</span>
          </td>
          <td>{{ item.category || '—' }}</td>
          <td class="right">{{ Number(item.quantity) }}</td>
          <td class="right">{{ money(item.sell_total ?? item.sell_price) }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
