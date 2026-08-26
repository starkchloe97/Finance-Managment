<script setup>
import { nextTick, ref } from 'vue'
import Sidebar from '@/components/layout/Sidebar.vue'
import Navbar from '@/components/layout/Navbar.vue'

const collapsed = ref(false)
const mobileOpen = ref(false)
const navbar = ref(null)

const openMobileMenu = () => {
  mobileOpen.value = true
}

const closeMobileMenu = () => {
  const wasOpen = mobileOpen.value
  mobileOpen.value = false
  if (wasOpen) nextTick(() => navbar.value?.focusMenuButton())
}
</script>

<template>
  <div class="app">
    <Sidebar
      :collapsed="collapsed"
      :mobile-open="mobileOpen"
      @close="closeMobileMenu"
      @toggle-collapse="collapsed = !collapsed"
    />

    <div class="content" :class="{ 'content-collapsed': collapsed }">
      <Navbar ref="navbar" :mobile-open="mobileOpen" @open-menu="openMobileMenu" />

      <main id="main-content" class="app-content" tabindex="-1">
        <RouterView />
      </main>
    </div>
  </div>
</template>

<style scoped>
.app {
  display: flex;
  min-height: 100vh;
}

.content {
  display: flex;
  flex: 1;
  flex-direction: column;
  min-width: var(--space-0);
}

.app-content {
  flex: 1;
  margin: var(--space-0) auto;
  max-width: var(--content-max-width);
  padding: var(--content-padding);
  width: 100%;
}

@media (max-width: 1024px) {
  .app-content {
    padding: var(--space-5);
  }
}

@media (max-width: 820px) {
  .app-content {
    padding: var(--space-4);
  }
}
</style>
