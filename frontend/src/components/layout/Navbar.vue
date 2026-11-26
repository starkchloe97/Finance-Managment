<script setup>
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'

const props = defineProps({
  mobileOpen: Boolean,
})
const emit = defineEmits(['open-menu'])
const menuButton = ref(null)

const auth = useAuthStore()
const router = useRouter()
const route = useRoute()
const profileOpen = ref(false)

const breadcrumb = computed(() => route.meta.breadcrumb || 'Dashboard')

const logout = async () => {
  await auth.logout()
  profileOpen.value = false
  router.push('/login')
}

const focusMenuButton = () => menuButton.value?.focus()

defineExpose({ focusMenuButton })
</script>

<template>
  <header class="navbar no-print">
    <div class="navbar-left">
      <button
        ref="menuButton"
        class="nav-icon mobile-menu"
        type="button"
        aria-label="Open navigation"
        :aria-expanded="mobileOpen"
        @click="emit('open-menu')"
      >
        ☰
      </button>
      <nav class="breadcrumb" aria-label="Breadcrumb">
        <template v-for="(part, index) in breadcrumb.split(' / ')" :key="index">
          <span v-if="index > 0" class="crumb-sep" aria-hidden="true">/</span>
          <span class="crumb" :class="{ 'crumb-current': index === breadcrumb.split(' / ').length - 1 }">{{ part }}</span>
        </template>
      </nav>
    </div>

    <div class="navbar-actions">
      <div class="profile-menu">
        <button
          class="profile-trigger"
          type="button"
          aria-haspopup="true"
          :aria-expanded="profileOpen"
          @click="profileOpen = !profileOpen"
        >
          <span class="profile-avatar">{{ auth.user?.name?.slice(0, 1) || 'U' }}</span>
          <span class="user">{{ auth.user?.name || 'User' }}</span>
        </button>
        <div v-if="profileOpen" class="profile-dropdown">
          <p class="profile-name">{{ auth.user?.name || 'User' }}</p>
          <p class="profile-role">Authenticated user</p>
          <button class="profile-logout" type="button" @click="logout">Logout</button>
        </div>
      </div>
    </div>
  </header>
</template>

<style scoped>
.navbar {
  align-items: center;
  background: var(--surface);
  border-bottom: 1px solid var(--border);
  box-shadow: 0 1px 0 rgb(15 23 42 / 2%);
  display: flex;
  gap: var(--space-3);
  height: var(--header-height);
  justify-content: space-between;
  padding: var(--space-0) var(--space-6);
}

.navbar-left,
.navbar-actions,
.profile-trigger {
  align-items: center;
  display: flex;
  gap: var(--space-3);
}

.breadcrumb {
  align-items: center;
  display: flex;
  flex-wrap: wrap;
  font-size: var(--text-sm);
  gap: 6px;
}

.crumb {
  color: var(--text-muted);
}

.crumb-sep {
  color: var(--border-strong);
}

.crumb-current {
  color: var(--text-primary);
  font-weight: var(--font-weight-semibold);
}

.user {
  color: var(--text-muted);
  font-size: var(--text-sm);
}

.nav-icon {
  align-items: center;
  background: transparent;
  border: 1px solid transparent;
  border-radius: var(--radius-md);
  color: var(--text-secondary);
  display: inline-flex;
  justify-content: center;
  min-height: var(--control-height-sm);
  min-width: var(--control-height-sm);
  padding: var(--space-1);
}

.nav-icon:hover {
  background: var(--surface-hover);
  border-color: var(--border);
  color: var(--text-primary);
}

.mobile-menu {
  display: none;
}

.profile-menu {
  position: relative;
}

.profile-trigger {
  background: transparent;
  border-color: transparent;
  color: var(--text-primary);
  min-height: var(--control-height-sm);
  padding: var(--space-1) var(--space-2);
}

.profile-avatar {
  align-items: center;
  background: linear-gradient(135deg, #2563eb, #7c3aed);
  border-radius: 50%;
  color: #fff;
  display: inline-flex;
  font-size: var(--text-xs);
  font-weight: var(--font-weight-semibold);
  height: 28px;
  justify-content: center;
  text-transform: uppercase;
  width: 28px;
}

.profile-trigger {
  border-radius: var(--radius-pill);
  box-shadow: none;
  padding: 3px 12px 3px 4px;
}

.profile-trigger:hover {
  background: var(--surface-hover);
  border-color: var(--surface-hover);
  box-shadow: none;
  color: var(--text-primary);
}

.user {
  color: var(--text-secondary);
  font-weight: var(--font-weight-medium);
}

.profile-dropdown {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-md);
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
  min-width: 200px;
  padding: var(--space-3);
  position: absolute;
  right: var(--space-0);
  top: calc(100% + var(--space-2));
  z-index: 2;
}

.profile-name {
  color: var(--text-primary);
  font-weight: var(--font-weight-semibold);
}

.profile-role {
  color: var(--text-muted);
  font-size: var(--text-xs);
}

.profile-logout {
  box-shadow: none;
  background: var(--danger-soft);
  border-color: var(--danger-soft);
  color: var(--danger);
  justify-content: flex-start;
  min-height: var(--control-height-sm);
}

.profile-logout:hover {
  background: var(--danger);
  border-color: var(--danger);
  color: var(--text-inverse);
}

@media (max-width: 820px) {
  .navbar {
    padding: var(--space-0) var(--space-4);
  }

  .mobile-menu {
    display: inline-flex;
  }
}

@media print {
  .navbar {
    display: none;
  }
}
</style>
