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
