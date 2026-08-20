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
