<script setup>
import { ref } from 'vue'
import Sidebar from '@/components/layout/Sidebar.vue'
import Navbar from '@/components/layout/Navbar.vue'

const collapsed = ref(false)
const mobileOpen = ref(false)
</script>

<template>
  <div class="app">
    <Sidebar
      :collapsed="collapsed"
      :mobile-open="mobileOpen"
      @close="mobileOpen = false"
      @toggle-collapse="collapsed = !collapsed"
    />

    <div class="content" :class="{ 'content-collapsed': collapsed }">
      <Navbar @open-menu="mobileOpen = true" />

      <main class="app-content">
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
  padding: var(--space-6);
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
