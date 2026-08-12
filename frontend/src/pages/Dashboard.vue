<script setup>
import { ref, onMounted } from "vue";
import { useAuthStore } from "@/stores/authStore";
import { getCustomers } from "@/services/customerService";

const auth = useAuthStore();

const customerCount = ref("—");

onMounted(async () => {
    const { data } = await getCustomers();
    customerCount.value = data.meta?.total ?? 0;
});
</script>

<template>

<div class="page-head">
    <h1>Dashboard</h1>
</div>

<div class="stats">

    <div class="stat">
        <div class="label">Customers</div>
        <div class="value">{{ customerCount }}</div>
    </div>

</div>

<div class="card" style="margin-top: 18px">

    <h3>Quick actions</h3>

    <div class="actions">
        <RouterLink class="btn" to="/customers/create">New Customer</RouterLink>
        <RouterLink class="btn btn-light" to="/estimates/create">New Estimate</RouterLink>
    </div>

</div>

</template>
