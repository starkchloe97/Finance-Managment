<script setup>
defineProps({ alerts: { type: Array, default: () => [] } })
</script>
<template>
  <section class="card">
    <h2>Attention</h2>
    <p class="hint">Items requiring follow-up will appear here.</p>
    <ul v-if="alerts.length" class="dashboard-list">
      <li v-for="alert in alerts" :key="alert.title">
        <strong>{{ alert.title }}</strong
        ><span>{{ alert.description }}</span>
      </li>
    </ul>
    <p v-else class="empty">No items need attention right now.</p>
  </section>
</template>
