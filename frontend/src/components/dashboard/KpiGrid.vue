<script setup>import KpiCard from './KpiCard.vue'; defineProps({ items: Array })</script>
<template><section class="kpi-grid"><KpiCard v-for="item in items" :key="item.title" v-bind="item" /></section></template>
