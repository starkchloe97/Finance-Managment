<script setup>
import { useAuthStore } from "@/stores/authStore";

const auth = useAuthStore();
</script>

<template>

<header>

    <span>

        {{ auth.user?.name }}

    </span>

</header>

</template>