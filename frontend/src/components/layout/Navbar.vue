<script setup>
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'

const emit = defineEmits(['open-menu'])

const auth = useAuthStore();
const router = useRouter();
const route = useRoute()
const profileOpen = ref(false)

const breadcrumb = computed(() => route.meta.breadcrumb || 'Dashboard')

const logout = async () => {
    await auth.logout();
    profileOpen.value = false
    router.push('/login')
};
</script>

<template>

<header class="navbar">
    <div class="navbar-left">
        <button class="nav-icon mobile-menu" type="button" aria-label="Open navigation" @click="emit('open-menu')">☰</button>
        <nav class="breadcrumb" aria-label="Breadcrumb">{{ breadcrumb }}</nav>
    </div>

    <div class="navbar-actions">
        <button class="nav-icon" type="button" aria-label="Notifications">♢</button>
        <div class="profile-menu">
            <button class="profile-trigger" type="button" :aria-expanded="profileOpen" @click="profileOpen = !profileOpen">
                <span class="profile-avatar">{{ auth.user?.name?.slice(0, 1) || 'U' }}</span>
                <span class="user">{{ auth.user?.name || 'User' }}</span>
            </button>
            <div v-if="profileOpen" class="profile-dropdown">
                <p class="profile-name">{{ auth.user?.name || 'User' }}</p>
                <p class="profile-role">Authenticated user</p>
                <RouterLink to="/settings" @click="profileOpen = false">Settings</RouterLink>
                <button class="profile-logout" type="button" @click="logout">Logout</button>
            </div>
        </div>
    </div>
</header>

</template>
