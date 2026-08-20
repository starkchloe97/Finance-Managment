<script setup>
import { money } from '@/utils/money'
import EstimateStatus from './EstimateStatus.vue'

defineProps({
  estimates: { type: Array, default: () => [] },
})
</script>

<template>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Code</th>
          <th>Customer</th>
          <th>Route</th>
          <th class="right">Amount</th>
          <th>Status</th>
          <th>Date</th>
          <th class="right">Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="estimate in estimates" :key="estimate.id">
          <td>
            <RouterLink :to="`/estimates/${estimate.id}`">{{ estimate.code }}</RouterLink>
          </td>
          <td>{{ estimate.customer?.name || '—' }}</td>
          <td class="hint">{{ estimate.pickup }} → {{ estimate.destination }}</td>
          <td class="right money-revenue">{{ money(estimate.estimated_sell) }}</td>
          <td><EstimateStatus :status="estimate.status" /></td>
          <td>{{ String(estimate.estimate_date).slice(0, 10) }}</td>
          <td class="right">
            <RouterLink
              v-if="estimate.transport_job"
              class="btn-light btn-sm"
              :to="`/jobs/${estimate.transport_job.id}`"
              >View job</RouterLink
            >
            <RouterLink v-else class="btn-light btn-sm" :to="`/estimates/${estimate.id}`"
              >Open</RouterLink
            >
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
