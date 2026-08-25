<script setup>
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'

const emit = defineEmits(['open-menu'])

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
</script>

<template>
  <header class="navbar">
    <div class="navbar-left">
      <button
        class="nav-icon mobile-menu"
        type="button"
        aria-label="Open navigation"
        @click="emit('open-menu')"
      >
        ☰
      </button>
      <nav class="breadcrumb" aria-label="Breadcrumb">{{ breadcrumb }}</nav>
    </div>

    <div class="navbar-actions">
      <button class="nav-icon" type="button" aria-label="Notifications">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          width="16"
          height="16"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
          class="lucide lucide-bell-icon lucide-bell"
        >
          <path d="M10.268 21a2 2 0 0 0 3.464 0" />
          <path
            d="M3.262 15.326A1 1 0 0 0 4 17h16a1 1 0 0 0 .74-1.673C19.41 13.956 18 12.499 18 8A6 6 0 0 0 6 8c0 4.499-1.411 5.956-2.738 7.326"
          />
        </svg>
      </button>
      <div class="profile-menu">
        <button
          class="profile-trigger"
          type="button"
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
  color: var(--text-secondary);
  font-size: var(--text-sm);
}

.user {
  color: var(--text-muted);
  font-size: var(--text-sm);
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
  padding: var(--space-1);
}

.profile-trigger:hover {
  background: var(--surface-hover);
  border-color: var(--surface-hover);
  color: var(--text-primary);
}

.profile-dropdown {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-md);
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
  min-width: var(--timeline-width);
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
</style>
