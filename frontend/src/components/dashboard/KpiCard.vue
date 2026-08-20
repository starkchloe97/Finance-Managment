<script setup>
defineProps({ title: String, value: String, subtitle: String, icon: String, trend: [String, Number], trendDirection: String, variant: String })
</script>
<template><article class="kpi-card" :class="`kpi-${variant}`"><div class="kpi-icon">{{ icon }}</div><div><span class="kpi-title">{{ title }}</span><strong>{{ value }}</strong><p><span :class="`trend-${trendDirection}`">{{ trend }}</span> {{ subtitle }}</p></div></article></template>
