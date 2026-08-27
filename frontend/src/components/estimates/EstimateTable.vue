<script setup>
import { money } from '@/utils/money'
import { avatarStyle, initialOf } from '@/utils/avatar'
import EstimateStatus from './EstimateStatus.vue'
import InfoTip from '@/components/ui/InfoTip.vue'

defineProps({
  estimates: { type: Array, default: () => [] },
})
</script>

<template>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Quote</th>
          <th>Customer</th>
          <th>Route</th>
          <th class="right">
            Amount
            <InfoTip label="The price the customer was quoted for this job." />
          </th>
          <th>Status</th>
          <th>
            Date
            <InfoTip label="The day the quote was written." />
          </th>
          <th class="right">Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="estimate in estimates" :key="estimate.id">
          <td>
            <RouterLink class="row-code" :to="`/estimates/${estimate.id}`">
              {{ estimate.code }}
            </RouterLink>
          </td>
          <td>
            <div v-if="estimate.customer?.name" class="customer-cell">
              <span
                class="customer-avatar"
                :style="avatarStyle(estimate.customer.name)"
                aria-hidden="true"
              >
                {{ initialOf(estimate.customer.name) }}
              </span>
              <span>{{ estimate.customer.name }}</span>
            </div>
            <span v-else>—</span>
          </td>
          <td>
            <span v-if="estimate.pickup || estimate.destination" class="route-cell">
              <span>{{ estimate.pickup || '?' }}</span>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M5 12h14" /><path d="m12 5 7 7-7 7" />
              </svg>
              <span>{{ estimate.destination || '?' }}</span>
            </span>
            <span v-else>—</span>
          </td>
          <td class="right row-amount">{{ money(estimate.estimated_sell) }}</td>
          <td>
            <EstimateStatus :status="estimate.status" />
          </td>
          <td>{{ String(estimate.estimate_date).slice(0, 10) }}</td>
          <td class="right">
            <div class="row-actions">
              <RouterLink
                v-if="estimate.transport_job"
                class="job-chip"
                :to="`/jobs/${estimate.transport_job.id}`"
                title="This quote became a job — open it"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2" /><path d="M15 18H9" /><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.62l-3.48-4.35A1 1 0 0 0 17.52 8H14" /><circle cx="17" cy="18" r="2" /><circle cx="7" cy="18" r="2" />
                </svg>
                Job
              </RouterLink>
              <RouterLink
                class="icon-action"
                :to="`/estimates/${estimate.id}`"
                title="Open quote"
                aria-label="Open quote"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M5 12h14" /><path d="m12 5 7 7-7 7" />
                </svg>
              </RouterLink>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<style scoped>
.row-code {
  color: var(--text-primary);
  font-weight: 600;
  text-decoration: none;
}
.row-code:hover { color: var(--accent); }

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

.route-cell {
  align-items: center;
  color: var(--text-secondary);
  display: inline-flex;
  font-size: 13px;
  gap: 6px;
}
.route-cell svg { color: var(--text-muted); height: 13px; width: 13px; }

.row-amount { color: var(--text-primary); font-weight: 600; }

.row-actions {
  display: inline-flex;
  gap: 6px;
  justify-content: flex-end;
}

.job-chip {
  align-items: center;
  background: var(--violet-soft);
  border-radius: 999px;
  color: var(--violet);
  display: inline-flex;
  font-size: 11px;
  font-weight: 600;
  gap: 5px;
  padding: 4px 10px;
  text-decoration: none;
  transition: background 0.15s ease, color 0.15s ease;
}
.job-chip svg { height: 12px; width: 12px; }
.job-chip:hover { background: var(--violet); color: #fff; }

.icon-action {
  align-items: center;
  background: transparent;
  border: 1px solid transparent;
  border-radius: 8px;
  color: var(--text-muted);
  display: inline-flex;
  height: 32px;
  justify-content: center;
  transition: background 0.15s ease, color 0.15s ease;
  width: 32px;
}
.icon-action svg { height: 15px; width: 15px; }
.icon-action:hover { background: var(--accent-soft); color: var(--accent); }
</style>