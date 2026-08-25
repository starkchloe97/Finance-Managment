<script setup>
import { computed } from 'vue'

const props = defineProps({
  page: { type: Number, default: 1 },
  lastPage: { type: Number, default: 1 },
  total: { type: Number, default: 0 },
  perPage: { type: Number, default: 10 },
})
const emit = defineEmits(['update:page'])

const from = computed(() => (props.total === 0 ? 0 : (props.page - 1) * props.perPage + 1))
const to = computed(() => Math.min(props.page * props.perPage, props.total))

const pages = computed(() => {
  const count = props.lastPage
  if (count <= 7) return Array.from({ length: count }, (_, i) => i + 1)

  const current = props.page
  const set = new Set([1, count, current - 1, current, current + 1])
  return [...set].filter((p) => p >= 1 && p <= count).sort((a, b) => a - b)
})
</script>

<template>
  <div v-if="lastPage > 1" class="pagination">
    <button
      type="button"
      class="page-btn"
      :disabled="page <= 1"
      @click="emit('update:page', page - 1)"
    >
      ‹
    </button>

    <template v-for="(p, index) in pages" :key="p">
      <span v-if="index > 0 && p - pages[index - 1] > 1" class="page-ellipsis">…</span>
      <button
        type="button"
        class="page-btn"
        :class="{ active: p === page }"
        @click="emit('update:page', p)"
      >
        {{ p }}
      </button>
    </template>

    <button
      type="button"
      class="page-btn"
      :disabled="page >= lastPage"
      @click="emit('update:page', page + 1)"
    >
      ›
    </button>
  </div>

  <p v-if="total > 0" class="pagination-summary">{{ from }}–{{ to }} of {{ total }}</p>
</template>

<style scoped>
.pagination {
  align-items: center;
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-1);
  margin-top: var(--space-4);
}

.page-btn {
  align-items: center;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  color: var(--text-secondary);
  display: inline-flex;
  height: var(--control-height-sm);
  justify-content: center;
  min-height: var(--control-height-sm);
  min-width: var(--control-height-sm);
  padding: var(--space-0) var(--space-2);
}

.page-btn:hover:not(:disabled) {
  background: var(--surface-hover);
  border-color: var(--border-strong);
  color: var(--text-primary);
}

.page-btn.active {
  background: var(--accent);
  border-color: var(--accent);
  color: var(--text-inverse);
}

.page-btn:disabled {
  opacity: 0.4;
}

.page-ellipsis {
  color: var(--text-muted);
  padding: var(--space-0) var(--space-1);
}

.pagination-summary {
  color: var(--text-muted);
  font-size: var(--text-sm);
  margin: var(--space-3) var(--space-0) var(--space-0);
}
</style>
