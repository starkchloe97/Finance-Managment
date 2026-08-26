<script setup>
import { money } from '@/utils/money'
import { avatarStyle, initialOf } from '@/utils/avatar'

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
            <div class="customer-cell">
              <span class="customer-avatar" :style="avatarStyle(customer.name)" aria-hidden="true">
                {{ initialOf(customer.name) }}
              </span>
              <div class="customer-id">
                <RouterLink :to="`/customers/${customer.id}`">{{ customer.name }}</RouterLink>
                <span v-if="customer.company" class="hint">{{ customer.company }}</span>
              </div>
            </div>
          </td>
          <td>
            <div class="contact-cell">
              <a v-if="customer.phone" class="contact-phone" :href="`tel:${customer.phone}`">
                {{ customer.phone }}
              </a>
              <span v-else>—</span>
              <span v-if="customer.email" class="hint">{{ customer.email }}</span>
            </div>
          </td>
          <td class="right">
            <span class="jobs-badge">{{ customer.job_count ?? 0 }}</span>
          </td>
          <td class="right money-revenue">{{ money(customer.revenue) }}</td>
          <td class="right" :class="Number(customer.profit) < 0 ? 'money-loss' : 'money-profit'">
            {{ money(customer.profit) }}
          </td>
          <td class="right">
            <div class="row-actions">
              <RouterLink
                class="icon-action"
                :to="`/customers/${customer.id}`"
                title="View customer"
                aria-label="View customer"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z" /><circle cx="12" cy="12" r="3" />
                </svg>
              </RouterLink>
              <RouterLink
                class="icon-action"
                :to="`/customers/${customer.id}/edit`"
                title="Edit customer"
                aria-label="Edit customer"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z" />
                </svg>
              </RouterLink>
              <button
                class="icon-action danger"
                type="button"
                title="Delete customer"
                aria-label="Delete customer"
                @click="emit('delete', customer)"
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M3 6h18" /><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" /><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                </svg>
              </button>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<style scoped>
.customer-cell {
  align-items: center;
  display: flex;
  gap: 12px;
}

.customer-avatar {
  align-items: center;
  border-radius: 10px;
  color: #fff;
  display: inline-flex;
  flex: 0 0 36px;
  font-size: 13px;
  font-weight: 600;
  height: 36px;
  justify-content: center;
  width: 36px;
}

.customer-id {
  display: flex;
  flex-direction: column;
  gap: 1px;
  min-width: 0;
}
.customer-id a { color: var(--text-primary); font-weight: 600; text-decoration: none; }
.customer-id a:hover { color: var(--accent); }

.contact-cell { display: flex; flex-direction: column; gap: 1px; }
.contact-phone {
  color: var(--text-secondary);
  font-size: 14px;
  text-decoration: none;
}
.contact-phone:hover { color: var(--accent); }

.jobs-badge {
  align-items: center;
  background: var(--surface-2);
  border-radius: 999px;
  color: var(--text-secondary);
  display: inline-flex;
  font-size: 12px;
  font-weight: 600;
  height: 22px;
  justify-content: center;
  min-width: 28px;
  padding: 0 8px;
}

.row-actions {
  display: inline-flex;
  gap: 4px;
  justify-content: flex-end;
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
  transition: background 0.15s, color 0.15s;
  width: 32px;
}
.icon-action svg { height: 16px; width: 16px; }
.icon-action:hover {
  background: var(--accent-soft);
  color: var(--accent);
}
.icon-action.danger:hover {
  background: var(--danger-soft);
  color: var(--danger);
}
</style>