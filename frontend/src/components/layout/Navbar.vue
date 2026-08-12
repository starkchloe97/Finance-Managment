<script setup>
import { useRouter } from "vue-router";
import { useAuthStore } from "@/stores/authStore";

const auth = useAuthStore();
const router = useRouter();

const logout = async () => {
    await auth.logout();
    router.push("/login");
};
</script>

<template>

<header>

    <span class="user">

        {{ auth.user?.name }}

    </span>

    <button class="btn-light btn-sm" @click="logout">

        Logout

    </button>

</header>

</template>
