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
            <th class="right">Action</th>
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
            <td class="right" :class="Number(job.final_profit) < 0 ? 'money-loss' : 'money-profit'">
              {{ money(job.final_profit) }}
            </td>
            <td class="right">
              <RouterLink class="btn-light btn-sm" :to="`/jobs/${job.id}`">Open</RouterLink>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-else class="empty">
      No jobs yet. <RouterLink to="/estimates/create">Create an estimate</RouterLink> to start one.
    </div>

    <div v-if="jobs.length" class="mobile-jobs">
      <article v-for="job in jobs" :key="job.id" class="job-row-card">
        <div class="job-row-top">
          <div>
            <RouterLink :to="`/jobs/${job.id}`">{{ job.code }}</RouterLink>
            <strong>{{ job.customer?.name || '—' }}</strong>
          </div>
          <span class="status" :class="`status-${job.status}`">{{ statusLabel(job.status) }}</span>
        </div>
        <div class="job-row-values">
          <span
            >Revenue <strong class="money-revenue">{{ money(job.sell_price) }}</strong></span
          >
          <span
            >Profit
            <strong :class="Number(job.final_profit) < 0 ? 'money-loss' : 'money-profit'">{{
              money(job.final_profit)
            }}</strong></span
          >
        </div>
      </article>
    </div>
  </section>
</template>

<style scoped>
.latest-jobs-card {
  min-width: 0;
}

.desktop-jobs {
  display: block;
}

.mobile-jobs {
  display: none;
}

.job-row-card {
  border-bottom: 1px solid var(--border);
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
  padding: var(--space-3) var(--space-0);
}

.job-row-card:last-child {
  border-bottom: 0;
}

.job-row-top,
.job-row-values {
  align-items: center;
  display: flex;
  gap: var(--space-3);
  justify-content: space-between;
}

.job-row-top div,
.job-row-values span {
  display: flex;
  flex-direction: column;
  gap: var(--space-1);
}

.job-row-top strong,
.job-row-values {
  color: var(--text-secondary);
  font-size: var(--text-sm);
  font-weight: var(--font-weight-medium);
}

.job-row-values strong {
  margin-left: var(--space-1);
}

@media (max-width: 820px) {
  .desktop-jobs {
    display: none;
  }

  .mobile-jobs {
    display: block;
  }
}
</style>
