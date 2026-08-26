<script setup>
defineProps({
  label: { type: String, required: true },
})
</script>

<template>
  <span class="info-tip">
    <svg
      class="info-tip-icon"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      stroke-width="2"
      stroke-linecap="round"
      stroke-linejoin="round"
      tabindex="0"
      role="img"
      :aria-label="label"
    >
      <circle cx="12" cy="12" r="10" />
      <path d="M12 16v-4" />
      <path d="M12 8h.01" />
    </svg>
    <span class="info-tip-bubble" role="tooltip">{{ label }}</span>
  </span>
</template>

<style scoped>
.info-tip {
  display: inline-flex;
  position: relative;
  vertical-align: middle;
}

.info-tip-icon {
  color: var(--text-muted);
  cursor: help;
  height: 14px;
  transition: color 0.15s ease;
  width: 14px;
}

.info-tip:hover .info-tip-icon,
.info-tip-icon:focus-visible {
  color: var(--accent);
}

.info-tip-bubble {
  background: #101828;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgb(16 24 40 / 18%);
  color: #fff;
  font-size: 12px;
  font-weight: 400;
  left: 50%;
  line-height: 1.5;
  max-width: 220px;
  opacity: 0;
  padding: 7px 10px;
  pointer-events: none;
  position: absolute;
  text-align: left;
  top: calc(100% + 8px);
  transform: translateX(-50%) translateY(-2px);
  transition: opacity 0.15s ease, transform 0.15s ease;
  white-space: normal;
  width: max-content;
  z-index: 30;
}

.info-tip:hover .info-tip-bubble,
.info-tip-icon:focus-visible + .info-tip-bubble {
  opacity: 1;
  transform: translateX(-50%) translateY(0);
}
</style>