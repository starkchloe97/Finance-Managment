<script setup>
import { RouterLink } from "vue-router";
</script>

<template>

    <aside class="sidebar">

        <div class="brand">Transport ERP</div>

        <RouterLink to="/">Dashboard</RouterLink>

        <RouterLink to="/customers">Customers</RouterLink>

        <RouterLink to="/estimates">Estimates</RouterLink>

        <RouterLink to="/jobs">Jobs</RouterLink>
        
        <RouterLink to="/investors">Investors</RouterLink>

        <RouterLink :to="{ name: 'investments.index' }">Investments</RouterLink>

    </aside>

</template>
