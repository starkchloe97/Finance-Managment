<script setup>
import { computed } from 'vue'

const props = defineProps({
  title: { type: String, required: true },
  value: { type: [String, Number], default: '—' },
  subtitle: { type: String, default: '' },
  // Plain-language explanation for the tooltip
  tip: { type: String, default: '' },
  icon: { type: String, default: 'revenue' },
  trend: { type: String, default: '' },
  trendDirection: { type: String, default: 'neutral' },
  trendLabel: { type: String, default: '' },
  variant: { type: String, default: 'revenue' },
  spark: { type: Array, default: () => [] },
})

const icons = {
  revenue: '<path d="M12 2v20"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',
  cost: '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>',
  profit: '<path d="M22 7l-8.5 8.5-5-5L2 17"/><path d="M16 7h6v6"/>',
  margin: '<path d="M19 5 5 19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/>',
  jobs: '<rect x="2" y="7" width="20" height="13" rx="2"/><path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2"/>',
  wallet:
    '<rect x="3" y="5" width="18" height="15" rx="2"/><path d="M3 9h18"/><path d="M16 14h2"/>',
  receivables: '<path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 19h14"/>',
  payables: '<path d="M12 21V9"/><path d="m17 14-5-5-5 5"/><path d="M5 5h14"/>',
  overdueReceivables: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
  overduePayables:
    '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/><path d="m9 15 2 2 4-4"/>',
  commission:
    '<path d="M12 3 14.8 8.7 21 9.6l-4.5 4.4 1.1 6.2L12 17.3 6.4 20.2l1.1-6.2L3 9.6l6.2-.9L12 3Z"/>',
}
const icon = computed(() => icons[props.icon] || icons.revenue)

// Avoid repeating the subtitle when it is already included in the explanation.
const tooltipLines = computed(() => {
  return [...new Set([props.tip, props.subtitle].filter(Boolean))]
})
const hasTooltip = computed(() => tooltipLines.value.length > 0)

// No visible title anymore — the card needs a programmatic label.
const ariaLabel = computed(() => {
  const bits = [props.title, String(props.value)]
  if (props.trend) bits.push(`${props.trend}${props.trendLabel ? ` ${props.trendLabel}` : ''}`)
  if (props.subtitle) bits.push(props.subtitle)
  return bits.join(', ')
})

const uid = `spark-${Math.random().toString(36).slice(2)}`

const points = computed(() => {
  const data = props.spark.map(Number).filter((n) => Number.isFinite(n))
  if (data.length < 2) return []
  const max = Math.max(...data)
  const min = Math.min(...data)
  const range = max - min || 1
  const step = 100 / (data.length - 1)
  return data.map((value, i) => [i * step, 29 - ((value - min) / range) * 26])
})
const linePath = computed(() =>
  points.value.length
    ? `M${points.value.map(([x, y]) => `${x.toFixed(2)} ${y.toFixed(2)}`).join(' L')}`
    : '',
)
const areaPath = computed(() => (linePath.value ? `${linePath.value} L100 32 L0 32 Z` : ''))
</script>

<template>
  <article
    class="kpi-card"
    :class="[`kpi-${variant}`, { 'has-tip': hasTooltip }]"
    :aria-label="ariaLabel"
    :tabindex="hasTooltip ? 0 : undefined"
  >
    <div class="kpi-head">
      <span class="kpi-icon" aria-hidden="true">
        <svg
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
          v-html="icon"
        />
      </span>
      <span class="kpi-title">{{ title }}</span>
    </div>

    <div class="kpi-main">
      <strong class="kpi-value">{{ value }}</strong>

      <p
        v-if="trend"
        class="kpi-trend"
        :class="`trend-${trendDirection}`"
      >
        <svg v-if="trendDirection === 'up'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5"/><path d="M5 12l7-7 7 7"/></svg>
        <svg v-else-if="trendDirection === 'down'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M19 12l-7 7-7-7"/></svg>
        <span>{{ trend }}</span>
        <span class="sr-only"> — {{ trendLabel }}</span>
      </p>
    </div>

    <span v-if="subtitle" class="kpi-sub">{{ subtitle }}</span>

    <svg
      v-if="areaPath"
      class="kpi-spark"
      :class="`kpi-spark-${variant}`"
      viewBox="0 0 100 32"
      preserveAspectRatio="none"
      aria-hidden="true"
    >
      <defs>
        <linearGradient :id="uid" x1="0" y1="0" x2="0" y2="1">
          <stop offset="0%" class="spark-stop-a" />
          <stop offset="100%" class="spark-stop-b" />
        </linearGradient>
      </defs>
      <path :d="areaPath" :fill="`url(#${uid})`" />
      <path :d="linePath" class="spark-line" fill="none" stroke-width="2" />
    </svg>

    <!-- Tooltip: hover or focus anywhere on the card -->
    <span v-if="hasTooltip" class="kpi-tip" aria-hidden="true">
      <b>{{ title }}</b>
      <span v-for="(line, i) in tooltipLines" :key="i">{{ line }}</span>
    </span>
  </article>
</template>

<style scoped>
.kpi-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-xs);
  display: flex;
  flex-direction: column;
  gap: 6px;
  min-width: 0;
  overflow: visible;
  padding: 14px 16px 12px;
  position: relative;
  transition: box-shadow 0.15s, border-color 0.15s, transform 0.15s;
}
.kpi-card:hover {
  border-color: var(--border-strong);
  box-shadow: var(--shadow-md);
  transform: translateY(-1px);
  /* The transform creates a stacking context. Keep the active card above
     neighbouring grid cards so its tooltip is never painted underneath them. */
  z-index: 10;
}
.kpi-card:focus-visible { outline: none; }

/* ---------- Header: icon · label ---------- */
.kpi-head {
  align-items: center;
  display: flex;
  gap: 8px;
  min-width: 0;
}

.kpi-title {
  color: var(--text-secondary);
  font-size: 12.5px;
  font-weight: 500;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* ---------- Value row: value · trend ---------- */
.kpi-main {
  align-items: center;
  display: flex;
  flex-wrap: wrap;
  gap: 4px 8px;
  min-height: 30px;
}

.kpi-sub {
  color: var(--text-muted);
  font-size: 12px;
  line-height: 1.35;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.kpi-icon {
  align-items: center;
  border-radius: 8px;
  display: flex;
  flex: 0 0 26px;
  height: 26px;
  justify-content: center;
  width: 26px;
}
.kpi-icon svg { height: 15px; width: 15px; }

.kpi-revenue .kpi-icon { background: var(--accent-soft); color: var(--accent); }
.kpi-cost .kpi-icon { background: var(--warning-soft); color: var(--warning); }
.kpi-profit .kpi-icon { background: var(--success-soft); color: var(--success); }
.kpi-margin .kpi-icon { background: var(--violet-soft); color: var(--violet); }
.kpi-jobs .kpi-icon { background: var(--info-soft); color: var(--info); }

.kpi-value {
  color: var(--text-primary);
  flex: 0 1 auto;
  font-size: 22px;
  font-variant-numeric: tabular-nums;
  font-weight: 700;
  letter-spacing: -0.02em;
  line-height: 1.1;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.kpi-trend {
  align-items: center;
  border-radius: 999px;
  display: inline-flex;
  flex: 0 0 auto;
  font-size: 11px;
  font-weight: 600;
  gap: 3px;
  margin: 0;
  padding: 2px 8px;
}
.kpi-trend svg { height: 11px; width: 11px; }
.trend-up { background: var(--success-soft); color: var(--success); }
.trend-down { background: var(--danger-soft); color: var(--danger); }
.trend-neutral { background: var(--surface-2); color: var(--text-muted); }

/* ---------- Full-width sparkline strip ---------- */
.kpi-spark {
  color: var(--accent);
  display: block;
  height: 24px;
  margin: 2px -4px -4px; /* bleed slightly into padding */
  width: calc(100% + 8px);
}
.kpi-spark-cost { color: var(--warning); }
.kpi-spark-profit { color: var(--success); }
.spark-line { stroke: currentColor; stroke-linecap: round; vector-effect: non-scaling-stroke; }
.spark-stop-a { stop-color: currentColor; stop-opacity: 0.18; }
.spark-stop-b { stop-color: currentColor; stop-opacity: 0; }

/* ---------- Tooltip (whole card is the trigger) ---------- */
.kpi-tip {
  background: #101828;
  border-radius: 6px;
  box-shadow: 0 4px 10px rgb(16 24 40 / 20%);
  color: #fff;
  display: flex;
  flex-direction: column;
  font-size: 11px;
  font-weight: 400;
  gap: 2px;
  left: auto;
  line-height: 1.5;
  max-width: 210px;
  opacity: 0;
  padding: 6px 9px;
  pointer-events: none;
  position: absolute;
  right: 0;
  top: calc(100% + 8px);
  transform: translateY(-3px);
  transition: opacity 0.15s ease, transform 0.15s ease;
  white-space: normal;
  width: max-content;
  z-index: 20;
}
.kpi-tip b {
  font-weight: 600;
  margin-bottom: 1px;
}
.kpi-card.has-tip:hover .kpi-tip,
.kpi-card.has-tip:focus-visible .kpi-tip {
  opacity: 1;
  transform: translateY(0);
}

/* Card focus ring — subtler than the global one since the card is a tooltip trigger */
.kpi-card.has-tip:focus-visible {
  border-color: var(--accent);
  box-shadow: 0 0 0 3px var(--focus-ring);
  z-index: 10;
}
</style>
