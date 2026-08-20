<script setup>
import { money } from '@/utils/money'
defineProps({ jobs: { type: Array, default: () => [] } })
</script>
<template>
  <section class="card">
    <div class="section-head">
      <h2>Recent jobs</h2>
      <RouterLink to="/jobs">View all →</RouterLink>
    </div>
    <div v-if="jobs.length" class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Job</th>
            <th>Customer</th>
            <th>Status</th>
            <th class="right">Revenue</th>
            <th class="right">Cost</th>
            <th class="right">Profit</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="job in jobs" :key="job.id">
            <td>
              <RouterLink :to="`/jobs/${job.id}`">{{ job.code }}</RouterLink>
            </td>
            <td>{{ job.customer?.name }}</td>
            <td>
              <span class="status" :class="`status-${job.status}`">{{
                job.status.replaceAll('_', ' ')
              }}</span>
            </td>
            <td class="right money-revenue">{{ money(job.sell_price) }}</td>
            <td class="right money-cost">{{ money(job.cost_price) }}</td>
            <td class="right" :class="Number(job.final_profit) < 0 ? 'money-loss' : 'money-profit'">
              {{ money(job.final_profit) }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <div v-else class="empty">
      No jobs yet. <RouterLink to="/estimates/create">Create an estimate</RouterLink> to start one.
    </div>
  </section>
</template>
