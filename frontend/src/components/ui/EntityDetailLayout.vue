<script setup>
import { computed } from 'vue'

const props = defineProps({
  // [{ key, label, icon }] — the tab bar
  tabs: { type: Array, default: () => [] },
  modelValue: String,
  loading: Boolean,
  error: String,
  // [{ label, value, tone }] — summary strip under the header, tone: revenue|cost|profit|neutral
  stats: { type: Array, default: () => [] },
  // When the page renders its own header (title + actions), set this to
  // false and the layout becomes a pure tabbed body container.
  showHeader: { type: Boolean, default: true },
})
const emit = defineEmits(['update:modelValue', 'retry'])

const active = computed(() => props.modelValue || props.tabs[0]?.key || '')
</script>

<template>
  <div class="entity-detail">
    <!-- Header: title + actions, then the stat strip -->
    <div v-if="showHeader" class="page-head entity-head">
      <div class="head-title">
        <slot name="title" />
      </div>
      <div class="actions">
        <slot name="actions" />
      </div>
    </div>

    <!-- Summary strip — backend-sourced values, rendered only -->
    <div v-if="stats.length" class="entity-stats">
      <div
        v-for="stat in stats"
        :key="stat.label"
        class="entity-stat"
        :class="`stat-${stat.tone || 'neutral'}`"
      >
        <span class="entity-stat-label">{{ stat.label }}</span>
        <strong>{{ stat.value }}</strong>
      </div>
    </div>

    <!-- Tab bar -->
    <div class="entity-tabs" role="tablist">
      <button
        v-for="tab in tabs"
        :key="tab.key"
        type="button"
        role="tab"
        :aria-selected="active === tab.key"
        :class="{ active: active === tab.key }"
        @click="emit('update:modelValue', tab.key)"
      >
        <svg
          v-if="tab.icon"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
          aria-hidden="true"
          v-html="tab.icon"
        />
        {{ tab.label }}
      </button>
    </div>

    <!-- Body: loading / error / slot -->
    <div v-if="error" class="state-panel state-error">
      <p>{{ error }}</p>
      <button type="button" class="btn" @click="emit('retry')">Try again</button>
    </div>
    <div v-else-if="loading" class="state-panel state-loading">
      <div class="skeleton-block"></div>
    </div>
    <div v-else class="entity-body">
      <slot />
    </div>
  </div>
</template>

<style scoped>
.entity-detail {
  margin-top: var(--space-5);
}

.entity-head {
  margin-bottom: var(--space-4);
}

.entity-tabs {
  border-bottom: 1px solid var(--border);
  display: flex;
  gap: var(--space-1);
  margin-bottom: var(--space-5);
  overflow-x: auto;
}

.entity-tabs button {
  align-items: center;
  background: transparent;
  border: var(--space-0);
  border-bottom: 2px solid transparent;
  border-radius: var(--space-0);
  color: var(--text-muted);
  display: inline-flex;
  font-weight: var(--font-weight-medium);
  gap: var(--space-2);
  margin-bottom: -1px;
  min-height: var(--control-height);
  padding: var(--space-2) var(--space-3);
  white-space: nowrap;
}

.entity-tabs button:hover {
  background: var(--surface-hover);
  color: var(--text-primary);
}

.entity-tabs button.active {
  border-bottom-color: var(--accent);
  color: var(--accent);
}

.entity-tabs button svg {
  height: var(--space-4);
  width: var(--space-4);
}

.entity-body {
  min-height: 200px;
}

:slotted(.entity-section) {
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
}
</style>
