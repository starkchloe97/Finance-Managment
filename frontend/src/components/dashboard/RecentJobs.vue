<script setup>
import { money } from '@/utils/money'
import { statusLabel } from '@/utils/jobStatus'

defineProps({ jobs: { type: Array, default: () => [] } })
</script>

<template>
  <section class="card latest-jobs-card">
    <div class="section-head">
      <div>
        <span class="section-kicker">Latest activity</span>
        <h2>Latest jobs</h2>
        <p class="hint">The newest jobs recorded across the business.</p>
      </div>
      <RouterLink class="btn-light btn-sm" to="/jobs">View all</RouterLink>
    </div>

    <div v-if="jobs.length" class="table-wrap desktop-jobs">
      <table>
        <thead>
          <tr>
            <th>Job</th>
            <th>Customer</th>
            <th>Status</th>
            <th class="right">Revenue</th>
            <th class="right">Final profit</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="job in jobs" :key="job.id">
            <td>
              <RouterLink class="job-code" :to="`/jobs/${job.id}`">{{ job.code }}</RouterLink>
            </td>
            <td>{{ job.customer?.name || '—' }}</td>
            <td>
              <span class="status" :class="`status-${job.status}`">
                {{ statusLabel(job.status) }}
              </span>
            </td>
            <td class="right money-revenue">{{ money(job.sell_price) }}</td>
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

    <div v-if="jobs.length" class="mobile-jobs">
      <!-- keep your existing mobile card markup -->
    </div>
  </section>
</template>

<style scoped>
.latest-jobs-card { min-width: 0; }
.job-code { color: var(--text-primary); font-weight: 600; }
.job-code:hover { color: var(--accent); }
.desktop-jobs { display: block; margin-top: 8px; }
.mobile-jobs { display: none; }
/* keep your existing mobile-jobs styles */
@media (max-width: 820px) {
  .desktop-jobs { display: none; }
  .mobile-jobs { display: block; }
}
</style>