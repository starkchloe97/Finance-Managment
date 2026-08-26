<script setup>
import { money } from '@/utils/money'
import { avatarStyle, initialOf } from '@/utils/avatar'

defineProps({
  customer: { type: Object, required: true },
})
const emit = defineEmits(['delete'])
</script>

<template>
  <article class="entity-card">
    <div class="entity-card-top">
      <div class="card-identity">
        <span class="customer-avatar" :style="avatarStyle(customer.name)" aria-hidden="true">
          {{ initialOf(customer.name) }}
        </span>
        <div class="card-id">
          <h3>
            <RouterLink :to="`/customers/${customer.id}`">{{ customer.name }}</RouterLink>
          </h3>
          <p class="hint">{{ customer.company || '—' }}</p>
        </div>
      </div>
      <span class="entity-card-count">{{ customer.job_count ?? 0 }} jobs</span>
    </div>

    <div class="entity-card-meta">
      <a v-if="customer.phone" :href="`tel:${customer.phone}`">{{ customer.phone }}</a>
      <span v-else>—</span>
      <span v-if="customer.email">{{ customer.email }}</span>
    </div>

    <div class="entity-card-stats">
      <div>
        <span class="hint">Revenue</span>
        <strong class="money-revenue">{{ money(customer.revenue) }}</strong>
      </div>
      <div>
        <span class="hint">Profit</span>
        <strong :class="Number(customer.profit) < 0 ? 'money-loss' : 'money-profit'">
          {{ money(customer.profit) }}
        </strong>
      </div>
    </div>

    <div class="entity-card-actions">
      <RouterLink class="btn-light btn-sm" :to="`/customers/${customer.id}`">View →</RouterLink>
      <RouterLink class="btn-light btn-sm" :to="`/customers/${customer.id}/edit`">Edit</RouterLink>
      <button class="btn-danger btn-sm" @click="emit('delete', customer)">Delete</button>
    </div>
  </article>
</template>

<style scoped>
.card-identity {
  align-items: center;
  display: flex;
  gap: 12px;
  min-width: 0;
}

.customer-avatar {
  align-items: center;
  border-radius: 10px;
  color: #fff;
  display: inline-flex;
  flex: 0 0 40px;
  font-size: 14px;
  font-weight: 600;
  height: 40px;
  justify-content: center;
  width: 40px;
}

.card-id { min-width: 0; }
.card-id h3 { margin: 0; }
</style>