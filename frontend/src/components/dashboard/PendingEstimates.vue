<script setup>
defineProps({ estimates: { type: Array, default: () => [] } })
</script>
<template>
  <section class="card">
    <div class="section-head">
      <h2>Pending estimates</h2>
      <RouterLink to="/estimates">View all →</RouterLink>
    </div>
    <ul v-if="estimates.length" class="dashboard-list">
      <li v-for="estimate in estimates" :key="estimate.id">
        <RouterLink to="/estimates">{{ estimate.code }}</RouterLink
        ><span>{{ estimate.customer?.name }}</span
        ><span class="status" :class="`status-${estimate.status}`">{{ estimate.status }}</span>
      </li>
    </ul>
    <p v-else class="empty">No estimates need attention.</p>
  </section>
</template>
