<script setup>
defineProps({
  loading: Boolean,
  error: String,
  empty: Boolean,
  emptyTitle: String,
  emptyAction: String,
  emptyTo: String,
})
</script>

<template>
  <!-- Error state — never render a blank page on failure -->
  <div v-if="error" class="state-panel state-error">
    <p>{{ error }}</p>
    <slot name="error-action"></slot>
  </div>

  <!-- Loading skeleton while first data is in flight -->
  <div v-else-if="loading" class="state-panel state-loading">
    <slot name="skeleton"><div class="skeleton-block"></div></slot>
  </div>

  <!-- Friendly empty state with a primary action, not a bare "No data" -->
  <div v-else-if="empty" class="state-panel state-empty">
    <p>{{ emptyTitle || 'Nothing here yet.' }}</p>
    <RouterLink v-if="emptyTo" class="btn" :to="emptyTo">{{
      emptyAction || 'Get started'
    }}</RouterLink>
    <slot name="empty-action"></slot>
  </div>

  <slot v-else />
</template>
