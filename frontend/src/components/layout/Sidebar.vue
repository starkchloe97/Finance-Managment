<script setup>
import { onUnmounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'

const props = defineProps({
    collapsed: Boolean,
    mobileOpen: Boolean,
})
const emit = defineEmits(['close', 'toggle-collapse'])
const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

const navigation = [
    { label: 'Overview', links: [{ label: 'Dashboard', to: '/', icon: 'D' }] },
    { label: 'Operations', links: [{ label: 'Customers', to: '/customers', icon: 'C' }, { label: 'Estimates', to: '/estimates', icon: 'E' }, { label: 'Transport Jobs', to: '/jobs', icon: 'J' }] },
    { label: 'Finance', links: [{ label: 'Investors', to: '/investors', icon: 'I' }, { label: 'Expenses', to: '/jobs', icon: '$' }] },
    { label: 'Analytics', links: [{ label: 'Reports', to: '/reports', icon: 'R' }] },
]

const close = () => emit('close')
const logout = async () => {
    await auth.logout()
    close()
    router.push('/login')
}

watch(() => route.fullPath, close)
watch(() => props.mobileOpen, (open) => {
    document.body.classList.toggle('nav-drawer-open', open)
}, { immediate: true })
onUnmounted(() => document.body.classList.remove('nav-drawer-open'))
</script>

<template>

    <div v-if="mobileOpen" class="sidebar-backdrop" @click="close"></div>
    <aside class="sidebar" :class="{ 'sidebar-collapsed': collapsed, 'sidebar-open': mobileOpen }">
        <div class="sidebar-top">
            <RouterLink class="brand" to="/" @click="close"><span class="nav-mark">T</span><span class="nav-label">Transport ERP</span></RouterLink>
            <button class="nav-icon collapse-toggle" type="button" :aria-label="collapsed ? 'Expand navigation' : 'Collapse navigation'" @click="emit('toggle-collapse')">‹</button>
        </div>

        <nav class="sidebar-nav" aria-label="Main navigation">
            <section v-for="group in navigation" :key="group.label" class="nav-group">
                <h2 class="nav-group-label">{{ group.label }}</h2>
                <RouterLink v-for="link in group.links" :key="link.label" :to="link.to" @click="close">
                    <span class="nav-icon-text">{{ link.icon }}</span><span class="nav-label">{{ link.label }}</span>
                </RouterLink>
            </section>
        </nav>

        <div class="sidebar-user">
            <div class="sidebar-user-name"><span class="nav-icon-text">●</span><span class="nav-label">{{ auth.user?.name || 'User' }}</span></div>
            <RouterLink to="/settings" @click="close"><span class="nav-icon-text">⚙</span><span class="nav-label">Settings</span></RouterLink>
            <button class="sidebar-logout" type="button" @click="logout"><span class="nav-icon-text">↪</span><span class="nav-label">Logout</span></button>
        </div>
    </aside>

</template>
