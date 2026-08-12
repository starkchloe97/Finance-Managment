<script setup>
import { useAuthStore } from "@/stores/authStore";

const auth = useAuthStore();
</script>

<template>

    <h1>Dashboard</h1>

    <p>{{ auth.user }}</p>

</template>