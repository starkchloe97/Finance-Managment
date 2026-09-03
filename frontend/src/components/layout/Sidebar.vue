<script setup>
import { computed, onMounted, onUnmounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'

const props = defineProps({
  collapsed: Boolean,
  mobileOpen: Boolean,
  // e.g. { Estimates: 3, 'Transport Jobs': 12 } — key = link label
  badges: { type: Object, default: () => ({}) },
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
      {
        label:'Assets',
        to: '/assets',
        icon: '<path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.62l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/>',
      },
      {
        label: 'Vehicle Contracts',
        to: '/vehicle-contracts',
        icon: '<path d="M7 18c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zM1 6v9h15V6H1zm16 9.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z"/>',
      },
      {
        label: 'On Road Vehicles',
        to: '/on-road-vehicles',
        icon: '<path d="M3 9l9-7v7H7v10a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9h-4L3 9z"/><circle cx="12" cy="12" r="3"/>',
      },
    ],
  },
  {
    label: 'Capital',
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
      {
        label: 'Loans',
        to: { name: 'loans.index' },
        icon: '<path d="M7 7h10v10H7z"/><path d="M4 10V5a1 1 0 0 1 1-1h5"/><path d="M20 14v5a1 1 0 0 1-1 1h-5"/>',
      },
    ],
  },
]

const isActive = (link) => {
  if (typeof link.to === 'string') {
    if (link.to === '/') return route.path === '/'
    return route.path === link.to || route.path.startsWith(`${link.to}/`)
  }
  const prefix = link.to.name.split('.')[0]
  return route.name === link.to.name || route.name?.startsWith(`${prefix}.`)
}

const badgeFor = (link) => {
  const count = Number(props.badges?.[link.label] ?? 0)
  if (!count) return null
  return count > 99 ? '99+' : String(count)
}

const userInitial = computed(() => (auth.user?.name || 'U').charAt(0).toUpperCase())
const userSubtitle = computed(() => auth.user?.email || auth.user?.role || 'Signed in')

const close = () => emit('close')
const logout = async () => {
  await auth.logout()
  close()
  router.push('/login')
}

const onKeydown = (event) => {
  if (event.key === 'Escape' && props.mobileOpen) close()
}

watch(() => route.fullPath, close)
watch(
  () => props.mobileOpen,
  (open) => {
    document.body.classList.toggle('nav-drawer-open', open)
  },
  { immediate: true },
)
onMounted(() => document.addEventListener('keydown', onKeydown))
onUnmounted(() => {
  document.body.classList.remove('nav-drawer-open')
  document.removeEventListener('keydown', onKeydown)
})
</script>

<template>
  <div v-if="mobileOpen" class="sidebar-backdrop" @click="close"></div>

  <aside class="sidebar" :class="{ 'sidebar-collapsed': collapsed, 'sidebar-open': mobileOpen }">
    <div class="sidebar-top">
      <RouterLink
        class="brand"
        to="/"
        :title="collapsed ? 'ABC Company' : undefined"
        @click="close"
      >
        <span class="brand-mark" aria-hidden="true">A</span>
        <span class="brand-copy">
          <span class="brand-name">ABC Company</span>
          <span class="brand-sub">Finance suite</span>
        </span>
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
          :class="{ 'is-active': isActive(link) }"
          :data-tooltip="link.label"
          :title="collapsed ? link.label : undefined"
          :aria-current="isActive(link) ? 'page' : undefined"
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
          <span v-if="badgeFor(link)" class="nav-badge">{{ badgeFor(link) }}</span>
        </RouterLink>
      </section>
    </nav>

    <div class="sidebar-user">
      <span class="user-avatar" aria-hidden="true">{{ userInitial }}</span>
      <span class="user-copy">
        <span class="user-name">{{ auth.user?.name || 'User' }}</span>
        <span class="user-sub">{{ userSubtitle }}</span>
      </span>
      <button
        class="logout-btn"
        type="button"
        title="Logout"
        aria-label="Logout"
        @click="logout"
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
          <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
          <polyline points="16 17 21 12 16 7" />
          <line x1="21" x2="9" y1="12" y2="12" />
        </svg>
      </button>
    </div>
  </aside>
</template>

<style scoped>
/* ------------------------------------------------------------------
   Sidebar theme — override these globally (on :root or .sidebar)
   to re-theme without touching this component.
------------------------------------------------------------------ */
.sidebar {
  --sb-surface: var(--sidebar-surface, #ffffff);
  --sb-line: var(--sidebar-border, #e7ebf1);
  --sb-ink: var(--sidebar-ink, #475467);
  --sb-ink-strong: var(--sidebar-ink-strong, #101828);
  --sb-ink-muted: var(--sidebar-ink-muted, #98a2b3);
  --sb-hover-bg: var(--sidebar-hover-bg, #f5f7fa);
  --sb-active-bg: var(--sidebar-active-bg, #eef4ff);
  --sb-active-ink: var(--sidebar-active-ink, #2563eb);
  --sb-width: var(--sidebar-width, 264px);
  --sb-collapsed-width: var(--header-height, 76px);
}

/* ---------- Shell ---------- */
.sidebar {
  background: var(--sb-surface);
  border-right: 1px solid var(--sb-line);
  display: flex;
  flex: 0 0 var(--sb-width);
  flex-direction: column;
  height: 100vh;
  padding: 16px 12px;
  position: sticky;
  top: 0;
  width: var(--sb-width);
  z-index: 5;
}

/* ---------- Brand ---------- */
.sidebar-top {
  align-items: center;
  display: flex;
  gap: 8px;
  justify-content: space-between;
  margin-bottom: 20px;
}

.brand {
  align-items: center;
  border-radius: 10px;
  color: var(--sb-ink-strong);
  display: flex;
  gap: 10px;
  min-width: 0;
  padding: 4px;
  margin: -4px;
  text-decoration: none;
  transition: background 0.15s ease;
}

.brand:hover {
  background: var(--sb-hover-bg);
  color: var(--sb-ink-strong);
  text-decoration: none;
}

.brand-mark {
  align-items: center;
  background: linear-gradient(135deg, #2563eb, #60a5fa);
  border-radius: 10px;
  box-shadow: 0 4px 12px rgb(37 99 235 / 28%);
  color: #fff;
  display: inline-flex;
  flex: 0 0 36px;
  font-size: 15px;
  font-weight: 700;
  height: 36px;
  justify-content: center;
  width: 36px;
}

.brand-copy {
  display: flex;
  flex-direction: column;
  gap: 1px;
  min-width: 0;
}

.brand-name {
  color: var(--sb-ink-strong);
  font-size: 15px;
  font-weight: 700;
  letter-spacing: -0.01em;
  white-space: nowrap;
}

.brand-sub {
  color: var(--sb-ink-muted);
  font-size: 11px;
  white-space: nowrap;
}

.collapse-toggle {
  align-items: center;
  background: transparent;
  border: 1px solid transparent;
  border-radius: 8px;
  color: var(--sb-ink);
  cursor: pointer;
  display: inline-flex;
  flex: 0 0 32px;
  height: 32px;
  justify-content: center;
  padding: 0;
  transition: background 0.15s ease, color 0.15s ease;
  width: 32px;
}

.collapse-toggle svg {
  height: 16px;
  width: 16px;
  transition: transform 0.2s ease;
}

.collapse-toggle:hover {
  background: var(--sb-hover-bg);
  color: var(--sb-ink-strong);
}

/* ---------- Nav ---------- */
.sidebar-nav {
  display: flex;
  flex: 1;
  flex-direction: column;
  gap: 20px;
  min-height: 0;
  overflow-y: auto;
  overscroll-behavior: contain;
  scrollbar-width: thin;
  scrollbar-color: var(--sb-line) transparent;
}

.sidebar-nav::-webkit-scrollbar {
  width: 4px;
}

.sidebar-nav::-webkit-scrollbar-thumb {
  background: var(--sb-line);
  border-radius: 999px;
}

.nav-group {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.nav-group-label {
  color: var(--sb-ink-muted);
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.08em;
  margin: 0 10px 4px;
  text-transform: uppercase;
  white-space: nowrap;
}

.nav-item {
  align-items: center;
  border-radius: 10px;
  color: var(--sb-ink);
  display: flex;
  font-size: 14px;
  font-weight: 500;
  gap: 12px;
  height: 40px;
  padding: 0 10px;
  position: relative;
  text-decoration: none;
  transition:
    background 0.15s ease,
    color 0.15s ease;
}

.nav-item:hover {
  background: var(--sb-hover-bg);
  color: var(--sb-ink-strong);
  text-decoration: none;
}

.nav-item.is-active {
  background: var(--sb-active-bg);
  color: var(--sb-active-ink);
  font-weight: 600;
}

.nav-item-icon {
  flex: 0 0 20px;
  height: 20px;
  width: 20px;
}

.nav-label {
  flex: 1;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.nav-badge {
  align-items: center;
  background: var(--sb-active-bg);
  border-radius: 999px;
  color: var(--sb-active-ink);
  display: inline-flex;
  flex: 0 0 auto;
  font-size: 11px;
  font-weight: 600;
  height: 20px;
  justify-content: center;
  min-width: 20px;
  padding: 0 6px;
}

.nav-item.is-active .nav-badge {
  background: var(--sb-active-ink);
  color: #fff;
}

.nav-item:focus-visible,
.collapse-toggle:focus-visible,
.logout-btn:focus-visible,
.brand:focus-visible {
  outline: 2px solid var(--sb-active-ink);
  outline-offset: 2px;
}

/* ---------- User ---------- */
.sidebar-user {
  align-items: center;
  border-top: 1px solid var(--sb-line);
  display: flex;
  gap: 10px;
  margin-top: 12px;
  padding: 14px 6px 2px;
}

.user-avatar {
  align-items: center;
  background: linear-gradient(135deg, #2563eb, #7c3aed);
  border-radius: 50%;
  color: #fff;
  display: inline-flex;
  flex: 0 0 36px;
  font-size: 13px;
  font-weight: 600;
  height: 36px;
  justify-content: center;
  width: 36px;
}

.user-copy {
  display: flex;
  flex: 1;
  flex-direction: column;
  gap: 1px;
  min-width: 0;
}

.user-name {
  color: var(--sb-ink-strong);
  font-size: 13px;
  font-weight: 600;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.user-sub {
  color: var(--sb-ink-muted);
  font-size: 11px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.logout-btn {
  align-items: center;
  background: transparent;
  border: 1px solid transparent;
  border-radius: 8px;
  color: var(--sb-ink);
  cursor: pointer;
  display: inline-flex;
  flex: 0 0 32px;
  height: 32px;
  justify-content: center;
  padding: 0;
  transition: background 0.15s ease, color 0.15s ease;
  width: 32px;
}

.logout-btn svg {
  height: 15px;
  width: 15px;
}

.logout-btn:hover {
  background: var(--danger-soft, #fdeeee);
  color: var(--danger, #dc2626);
}

/* ---------- Collapsed (desktop) ---------- */
.sidebar-collapsed {
  flex-basis: var(--sb-collapsed-width);
  padding: 16px 10px;
  width: var(--sb-collapsed-width);
}

.sidebar-collapsed .brand-copy,
.sidebar-collapsed .nav-label,
.sidebar-collapsed .nav-group-label,
.sidebar-collapsed .nav-badge,
.sidebar-collapsed .user-copy {
  display: none;
}

.sidebar-collapsed .sidebar-top {
  flex-direction: column;
  gap: 12px;
  margin-bottom: 16px;
}

.sidebar-collapsed .brand,
.sidebar-collapsed .nav-item {
  justify-content: center;
  padding: 0;
  margin: 0;
}

.sidebar-collapsed .nav-item {
  width: 100%;
}

.sidebar-collapsed .collapse-toggle svg {
  transform: rotate(180deg);
}

.sidebar-collapsed .sidebar-user {
  flex-direction: column;
  gap: 10px;
  padding: 14px 0 2px;
}

/* Tooltips while collapsed */
@media (min-width: 821px) {
  .sidebar-collapsed .sidebar-nav {
    overflow: visible;
  }

  .sidebar-collapsed .nav-item::after {
    background: #101828;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgb(16 24 40 / 16%);
    color: #fff;
    content: attr(data-tooltip);
    font-size: 12px;
    font-weight: 500;
    left: calc(100% + 12px);
    opacity: 0;
    padding: 5px 10px;
    pointer-events: none;
    position: absolute;
    top: 50%;
    transform: translateY(-50%) translateX(-4px);
    transition:
      opacity 0.15s ease,
      transform 0.15s ease;
    white-space: nowrap;
    z-index: 30;
  }

  .sidebar-collapsed .nav-item:hover::after {
    opacity: 1;
    transform: translateY(-50%) translateX(0);
  }
}

@media (min-width: 821px) {
  .sidebar {
    transition:
      flex-basis 0.2s ease,
      width 0.2s ease,
      padding 0.2s ease;
  }
}

/* ---------- Mobile drawer ---------- */
.sidebar-backdrop {
  display: none;
}

@media (max-width: 820px) {
  :global(body.nav-drawer-open) {
    overflow: hidden;
  }

  .sidebar {
    box-shadow: 0 8px 30px rgb(16 24 40 / 18%);
    height: 100vh;
    left: 0;
    max-width: var(--sb-width);
    position: fixed;
    top: 0;
    transform: translateX(-100%);
    transition: transform 0.25s ease;
    z-index: 3;
  }

  .sidebar.sidebar-open {
    transform: translateX(0);
  }

  /* Drawer is always expanded on mobile */
  .sidebar-collapsed {
    flex-basis: var(--sb-width);
    padding: 16px 12px;
    width: var(--sb-width);
  }

  .sidebar-collapsed .brand-copy,
  .sidebar-collapsed .nav-label,
  .sidebar-collapsed .nav-group-label,
  .sidebar-collapsed .nav-badge,
  .sidebar-collapsed .user-copy {
    display: flex;
  }

  .sidebar-collapsed .sidebar-top {
    flex-direction: row;
    gap: 8px;
    margin-bottom: 20px;
  }

  .sidebar-collapsed .brand {
    justify-content: flex-start;
    padding: 4px;
    margin: -4px;
  }

  .sidebar-collapsed .nav-item {
    height: 40px;
    justify-content: flex-start;
    padding: 0 10px;
    width: auto;
  }

  .sidebar-collapsed .sidebar-user {
    flex-direction: row;
    gap: 10px;
    padding: 14px 6px 2px;
  }

  .sidebar-backdrop {
    animation: backdrop-in 0.2s ease;
    background: rgb(16 24 40 / 45%);
    backdrop-filter: blur(2px);
    display: block;
    inset: 0;
    position: fixed;
    z-index: 2;
  }

  .collapse-toggle {
    display: none;
  }
}

@keyframes backdrop-in {
  from {
    opacity: 0;
  }
}
</style>