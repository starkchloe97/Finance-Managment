<template>
  <a class="skip-link" href="#main-content">Skip to main content</a>
  <RouterView />
  <AppToast />
</template>

<script setup>
import AppToast from '@/components/ui/AppToast.vue'
</script>
