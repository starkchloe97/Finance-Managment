<script setup>
import { money } from '@/utils/money'

defineProps({
  customer: { type: Object, required: true },
})
const emit = defineEmits(['delete'])
</script>

<template>
  <article class="entity-card">
    <div class="entity-card-top">
      <div>
        <h3>
          <RouterLink :to="`/customers/${customer.id}`">{{ customer.name }}</RouterLink>
        </h3>
        <p class="hint">{{ customer.company || '—' }}</p>
      </div>
      <span class="entity-card-count">{{ customer.job_count ?? 0 }} jobs</span>
    </div>

    <div class="entity-card-meta">
      <span>{{ customer.phone || '—' }}</span>
      <span v-if="customer.email">{{ customer.email }}</span>
    </div>

    <div class="entity-card-stats">
      <div>
        <span class="hint">Revenue</span>
        <strong class="money-revenue">{{ money(customer.revenue) }}</strong>
      </div>
      <div>
        <span class="hint">Profit</span>
        <strong :class="Number(customer.profit) < 0 ? 'money-loss' : 'money-profit'">{{
          money(customer.profit)
        }}</strong>
      </div>
    </div>

    <div class="entity-card-actions">
      <RouterLink class="btn-light btn-sm" :to="`/customers/${customer.id}`">View →</RouterLink>
      <RouterLink class="btn-light btn-sm" :to="`/customers/${customer.id}/edit`">Edit</RouterLink>
      <button class="btn-danger btn-sm" @click="emit('delete', customer)">Delete</button>
    </div>
  </article>
</template>
