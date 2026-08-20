<script setup>
import { money } from '@/utils/money'
import { statusLabel } from '@/utils/jobStatus'

defineProps({
  jobs: { type: Array, default: () => [] },
})
</script>

<template>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Job</th>
          <th>Customer</th>
          <th>Status</th>
          <th class="right">Revenue</th>
          <th class="right">Cost</th>
          <th class="right">Profit</th>
          <th class="right">Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="job in jobs" :key="job.id">
          <td>
            <RouterLink :to="`/jobs/${job.id}`">{{ job.code }}</RouterLink>
          </td>
          <td>{{ job.customer?.name || '—' }}</td>
          <td>
            <span class="status" :class="`status-${job.status}`">{{
              statusLabel(job.status)
            }}</span>
          </td>
          <td class="right money-revenue">{{ money(job.sell_price) }}</td>
          <td class="right money-cost">{{ money(job.cost_price) }}</td>
          <td class="right" :class="Number(job.final_profit) < 0 ? 'money-loss' : 'money-profit'">
            {{ money(job.final_profit) }}
          </td>
          <td class="right">
            <RouterLink class="btn-light btn-sm" :to="`/jobs/${job.id}`">View</RouterLink>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
