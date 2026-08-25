<script setup>
import { computed, onUnmounted, watch } from 'vue'
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

// lucide-style 24x24 stroke icons, rendered as inline SVG (fill="none", currentColor)
const navigation = [
  {
    label: 'Overview',
    links: [
      {
        label: 'Dashboard',
        to: '/',
        icon: '<rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/>',
      },
    ],
  },
  {
    label: 'Operations',
    links: [
      {
        label: 'Customers',
        to: '/customers',
        icon: '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
      },
      {
        label: 'Estimates',
        to: '/estimates',
        icon: '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/>',
      },
      {
        label: 'Transport Jobs',
        to: '/jobs',
        icon: '<path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.62l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/>',
      },
    ],
  },
  {
    label: 'Finance',
    links: [
      {
        label: 'Investors',
        to: '/investors',
        icon: '<rect width="20" height="12" x="2" y="6" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/>',
      },
      {
        label: 'Investments',
        to: { name: 'investments.index' },
        icon: '<polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/>',
      },
    ],
  },
]

const userInitial = computed(() => (auth.user?.name || 'U').charAt(0).toUpperCase())
const close = () => emit('close')
const logout = async () => {
  await auth.logout()
  close()
  router.push('/login')
}

watch(() => route.fullPath, close)
watch(
  () => props.mobileOpen,
  (open) => {
    document.body.classList.toggle('nav-drawer-open', open)
  },
  { immediate: true },
)
onUnmounted(() => document.body.classList.remove('nav-drawer-open'))
</script>

<template>
  <div v-if="mobileOpen" class="sidebar-backdrop" @click="close"></div>
  <aside class="sidebar" :class="{ 'sidebar-collapsed': collapsed, 'sidebar-open': mobileOpen }">
    <div class="sidebar-top">
      <RouterLink class="brand" to="/" @click="close">
        <span class="brand-mark">A</span>
        <span class="brand-name">ABC Company</span>
      </RouterLink>
      <button
        class="collapse-toggle"
        type="button"
        :aria-label="collapsed ? 'Expand navigation' : 'Collapse navigation'"
        @click="emit('toggle-collapse')"
      >
        <svg
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
          aria-hidden="true"
        >
          <path d="m15 18-6-6 6-6" />
        </svg>
      </button>
    </div>

    <nav class="sidebar-nav" aria-label="Main navigation">
      <section v-for="group in navigation" :key="group.label" class="nav-group">
        <p class="nav-group-label">{{ group.label }}</p>
        <RouterLink
          v-for="link in group.links"
          :key="link.label"
          :to="link.to"
          class="nav-item"
          @click="close"
        >
          <svg
            class="nav-item-icon"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
            aria-hidden="true"
            v-html="link.icon"
          />
          <span class="nav-label">{{ link.label }}</span>
        </RouterLink>
      </section>
    </nav>

    <div class="sidebar-user">
      <span class="user-avatar">{{ userInitial }}</span>
      <span class="user-name">{{ auth.user?.name || 'User' }}</span>
      <button class="logout-btn" type="button" title="Logout" aria-label="Logout" @click="logout">
        <svg
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
          aria-hidden="true"
        >
          <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
          <polyline points="16 17 21 12 16 7" />
          <line x1="21" x2="9" y1="12" y2="12" />
        </svg>
      </button>
    </div>
  </aside>
</template>

<style scoped>
.sidebar {
  background: var(--sidebar);
  display: flex;
  flex: 0 0 var(--sidebar-width);
  flex-direction: column;
  min-height: 100vh;
  padding: var(--space-5) var(--space-3);
  position: sticky;
  top: 0;
  width: var(--sidebar-width);
  z-index: 5;
}

.sidebar-top {
  align-items: center;
  display: flex;
  justify-content: space-between;
  margin-bottom: var(--space-6);
}

.brand {
  align-items: center;
  color: var(--text-inverse);
  display: flex;
  gap: var(--space-3);
  text-decoration: none;
}

.brand:hover {
  color: var(--text-inverse);
  text-decoration: none;
}

.brand-mark {
  align-items: center;
  background: linear-gradient(135deg, var(--accent), #60a5fa);
  border-radius: var(--radius-md);
  color: var(--text-inverse);
  display: inline-flex;
  flex: 0 0 var(--space-8);
  font-size: var(--text-base);
  font-weight: var(--font-weight-semibold);
  height: var(--space-8);
  justify-content: center;
  letter-spacing: 0.02em;
  width: var(--space-8);
}

.brand-name {
  font-size: var(--text-lg);
  font-weight: var(--font-weight-semibold);
  letter-spacing: 0.01em;
  white-space: nowrap;
}

.collapse-toggle {
  align-items: center;
  background: transparent;
  border: 1px solid transparent;
  border-radius: var(--radius-md);
  color: var(--sidebar-text);
  display: inline-flex;
  flex: 0 0 var(--space-8);
  height: var(--space-8);
  justify-content: center;
  min-height: var(--space-8);
  padding: var(--space-0);
  transition:
    transform var(--transition-base),
    background var(--transition-fast),
    color var(--transition-fast);
  width: var(--space-8);
}

.collapse-toggle svg {
  height: var(--space-4);
  width: var(--space-4);
}

.collapse-toggle:hover {
  background: var(--sidebar-hover);
  color: var(--text-inverse);
}

.sidebar-nav {
  display: flex;
  flex: 1;
  flex-direction: column;
  gap: var(--space-5);
  margin-top: var(--space-2);
  overflow-y: auto;
}

.nav-group {
  display: flex;
  flex-direction: column;
  gap: var(--space-1);
}

.nav-group-label {
  color: var(--text-disabled);
  font-size: var(--text-xs);
  font-weight: var(--font-weight-semibold);
  letter-spacing: 0.08em;
  margin: var(--space-0) var(--space-3) var(--space-1);
  text-transform: uppercase;
}

.nav-item {
  align-items: center;
  border-radius: var(--radius-md);
  color: var(--sidebar-text);
  display: flex;
  font-size: var(--text-sm);
  font-weight: var(--font-weight-medium);
  gap: var(--space-3);
  height: var(--control-height);
  padding: var(--space-0) var(--space-3);
  position: relative;
  text-decoration: none;
  transition:
    background var(--transition-fast),
    color var(--transition-fast);
}

.nav-item:hover {
  background: var(--sidebar-hover);
  color: var(--text-inverse);
  text-decoration: none;
}

.nav-item-icon {
  flex: 0 0 var(--space-5);
  height: var(--space-5);
  width: var(--space-5);
}

.nav-label {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.nav-item .nav-label {
  flex: 1;
}

.nav-item.router-link-exact-active,
.nav-item.router-link-active:not([href='/']) {
  background: var(--sidebar-active);
  color: var(--text-inverse);
}

.nav-item.router-link-exact-active .nav-item-icon,
.nav-item.router-link-active:not([href='/']) .nav-item-icon {
  color: var(--text-inverse);
}

.sidebar-user {
  align-items: center;
  border-top: 1px solid var(--sidebar-hover);
  display: flex;
  gap: var(--space-2);
  margin-top: var(--space-4);
  padding: var(--space-4) var(--space-3) var(--space-1);
}

.user-avatar {
  align-items: center;
  background: var(--sidebar-hover);
  border-radius: var(--radius-pill);
  color: var(--text-inverse);
  display: inline-flex;
  flex: 0 0 var(--space-6);
  font-size: var(--text-xs);
  font-weight: var(--font-weight-semibold);
  height: var(--space-6);
  justify-content: center;
  width: var(--space-6);
}

.user-name {
  color: var(--sidebar-text);
  flex: 1;
  font-size: var(--text-sm);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.logout-btn {
  align-items: center;
  background: transparent;
  border: 1px solid transparent;
  border-radius: var(--radius-md);
  color: var(--sidebar-text);
  display: inline-flex;
  flex: 0 0 var(--space-6);
  height: var(--space-6);
  justify-content: center;
  min-height: var(--space-6);
  padding: var(--space-0);
  transition:
    background var(--transition-fast),
    color var(--transition-fast);
  width: var(--space-6);
}

.logout-btn svg {
  height: var(--space-4);
  width: var(--space-4);
}

.logout-btn:hover {
  background: var(--danger-soft);
  color: var(--danger);
}

.sidebar-collapsed {
  flex-basis: var(--header-height);
  width: var(--header-height);
}

.sidebar-collapsed .brand-name,
.sidebar-collapsed .nav-label,
.sidebar-collapsed .nav-group-label,
.sidebar-collapsed .user-name {
  display: none;
}

.sidebar-collapsed .sidebar-top,
.sidebar-collapsed .nav-item,
.sidebar-collapsed .sidebar-user {
  justify-content: center;
}

.sidebar-collapsed .sidebar-top {
  flex-direction: column;
  gap: var(--space-3);
  margin-bottom: var(--space-4);
}

.sidebar-collapsed .sidebar-nav {
  align-items: center;
}

.sidebar-collapsed .nav-group {
  align-items: center;
}

.sidebar-collapsed .nav-item {
  height: var(--space-8);
  padding: var(--space-0);
  width: var(--space-8);
}

.sidebar-collapsed .nav-group-label {
  height: var(--space-3);
}

.sidebar-collapsed .collapse-toggle {
  transform: rotate(180deg);
}

.sidebar-collapsed .sidebar-user {
  flex-direction: column;
  padding: var(--space-3) var(--space-0) var(--space-1);
}

.sidebar-collapsed .logout-btn {
  flex-basis: var(--space-8);
  height: var(--space-8);
  width: var(--space-8);
}

.sidebar-backdrop {
  display: none;
}

@media (min-width: 821px) {
  .sidebar {
    transition:
      flex-basis var(--transition-base),
      width var(--transition-base);
  }
}

@media (max-width: 820px) {
  :global(body.nav-drawer-open) {
    overflow: hidden;
  }

  .sidebar {
    box-shadow: var(--shadow-lg);
    height: 100vh;
    left: var(--space-0);
    max-width: var(--sidebar-width);
    position: fixed;
    top: var(--space-0);
    transform: translateX(-100%);
    transition: transform var(--transition-base);
    z-index: 3;
  }

  .sidebar.sidebar-open {
    transform: translateX(var(--space-0));
  }

  .sidebar-collapsed {
    flex-basis: var(--sidebar-width);
    width: var(--sidebar-width);
  }

  .sidebar-collapsed .brand-name,
  .sidebar-collapsed .nav-label,
  .sidebar-collapsed .nav-group-label,
  .sidebar-collapsed .user-name {
    display: inline;
  }

  .sidebar-collapsed .sidebar-top,
  .sidebar-collapsed .sidebar-user {
    flex-direction: row;
  }

  .sidebar-collapsed .sidebar-top {
    gap: var(--space-3);
    margin-bottom: var(--space-6);
  }

  .sidebar-collapsed .sidebar-nav,
  .sidebar-collapsed .nav-group {
    align-items: stretch;
  }

  .sidebar-collapsed .nav-item {
    height: var(--control-height);
    padding: var(--space-0) var(--space-3);
    width: auto;
  }

  .sidebar-collapsed .nav-group-label {
    height: auto;
  }

  .sidebar-collapsed .logout-btn {
    flex-basis: var(--space-6);
    height: var(--space-6);
    width: var(--space-6);
  }

  .sidebar-backdrop {
    background: var(--text-primary);
    display: block;
    inset: var(--space-0);
    opacity: var(--overlay-opacity);
    position: fixed;
    z-index: 2;
  }

  .collapse-toggle {
    display: none;
  }
}
</style>
