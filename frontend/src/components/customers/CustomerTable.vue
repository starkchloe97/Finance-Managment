<script setup>
import { money } from '@/utils/money'

defineProps({
  customers: { type: Array, default: () => [] },
})
const emit = defineEmits(['delete'])
</script>

<template>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Customer</th>
          <th>Contact</th>
          <th class="right">Jobs</th>
          <th class="right">Revenue</th>
          <th class="right">Profit</th>
          <th class="right">Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="customer in customers" :key="customer.id">
          <td>
            <RouterLink :to="`/customers/${customer.id}`">{{ customer.name }}</RouterLink>
            <span v-if="customer.company" class="hint">{{ customer.company }}</span>
          </td>
          <td>
            {{ customer.phone || '—' }}
            <span v-if="customer.email" class="hint">{{ customer.email }}</span>
          </td>
          <td class="right">{{ customer.job_count ?? 0 }}</td>
          <td class="right money-revenue">{{ money(customer.revenue) }}</td>
          <td class="right" :class="Number(customer.profit) < 0 ? 'money-loss' : 'money-profit'">
            {{ money(customer.profit) }}
          </td>
          <td class="right">
            <RouterLink class="btn-light btn-sm" :to="`/customers/${customer.id}`">View</RouterLink>
            <RouterLink class="btn-light btn-sm" :to="`/customers/${customer.id}/edit`"
              >Edit</RouterLink
            >
            <button class="btn-danger btn-sm" @click="emit('delete', customer)">Delete</button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
