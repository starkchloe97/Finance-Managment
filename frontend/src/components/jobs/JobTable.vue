<script setup>
import { money } from '@/utils/money'
import { statusLabel } from '@/utils/jobStatus'
import { avatarStyle, initialOf } from '@/utils/avatar'
import InfoTip from '@/components/ui/InfoTip.vue'

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
          <th class="right">
            Revenue
            <InfoTip label="The quoted price the customer agreed to pay." />
          </th>
          <th class="right">
            Cost
            <InfoTip label="Planned cost — what the job was expected to cost when quoted." />
          </th>
          <th class="right">
            Profit
            <InfoTip label="What's left after planned and unexpected costs." />
          </th>
          <th class="right">Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="job in jobs" :key="job.id">
          <td>
            <RouterLink class="job-code" :to="`/jobs/${job.id}`">{{ job.code }}</RouterLink>
          </td>
          <td>
            <div class="customer-cell">
              <span
                v-if="job.customer?.name"
                class="customer-avatar"
                :style="avatarStyle(job.customer.name)"
                aria-hidden="true"
              >
                {{ initialOf(job.customer.name) }}
              </span>
              <span>{{ job.customer?.name || '—' }}</span>
            </div>
          </td>
          <td>
            <span class="status" :class="`status-${job.status}`">
              {{ statusLabel(job.status) }}
            </span>
          </td>
          <td class="right">{{ money(job.sell_price) }}</td>
          <td class="right money-cost">{{ money(job.cost_price) }}</td>
          <td class="right" :class="Number(job.final_profit) < 0 ? 'money-loss' : 'money-profit'">
            {{ money(job.final_profit) }}
          </td>
          <td class="right">
            <RouterLink
              class="icon-action"
              :to="`/jobs/${job.id}`"
              title="Open job"
              aria-label="Open job"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M5 12h14" /><path d="m12 5 7 7-7 7" />
              </svg>
            </RouterLink>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<style scoped>
.job-code {
  color: var(--text-primary);
  font-weight: 600;
  text-decoration: none;
}
.job-code:hover { color: var(--accent); }

.customer-cell {
  align-items: center;
  display: flex;
  gap: 10px;
}

.customer-avatar {
  align-items: center;
  border-radius: 9px;
  color: #fff;
  display: inline-flex;
  flex: 0 0 28px;
  font-size: 11px;
  font-weight: 600;
  height: 28px;
  justify-content: center;
  width: 28px;
}

.icon-action {
  align-items: center;
  background: transparent;
  border: 1px solid transparent;
  border-radius: 8px;
  color: var(--text-muted);
  cursor: pointer;
  display: inline-flex;
  height: 32px;
  justify-content: center;
  transition: background 0.15s ease, color 0.15s ease;
  width: 32px;
}
.icon-action svg { height: 15px; width: 15px; }
.icon-action:hover {
  background: var(--accent-soft);
  color: var(--accent);
}
</style>