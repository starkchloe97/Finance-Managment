<script setup>
import { computed } from 'vue'

const props = defineProps({
  title: { type: String, required: true },
  value: { type: [String, Number], default: '—' },
  subtitle: { type: String, default: '' },
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
}
const icon = computed(() => icons[props.icon] || icons.revenue)

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
  <article class="kpi-card" :class="`kpi-${variant}`">
    <header class="kpi-head">
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
      <h3>{{ title }}</h3>
    </header>

    <div class="kpi-body">
      <strong class="kpi-value">{{ value }}</strong>
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
    </div>

    <footer class="kpi-foot">
      <p v-if="trend" class="kpi-trend" :class="`trend-${trendDirection}`" :title="trendLabel">
        <svg v-if="trendDirection === 'up'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5"/><path d="M5 12l7-7 7 7"/></svg>
        <svg v-else-if="trendDirection === 'down'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14"/><path d="M19 12l-7 7-7-7"/></svg>
        <span>{{ trend }}</span>
        <span class="sr-only">{{ trendLabel }}</span>
      </p>
      <span v-if="subtitle" class="kpi-subtitle">{{ subtitle }}</span>
    </footer>
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
  gap: 14px;
  padding: 18px 20px;
  transition: box-shadow 0.15s, border-color 0.15s;
}
.kpi-card:hover { box-shadow: var(--shadow-md); border-color: var(--border-strong); }

.kpi-head { align-items: center; display: flex; gap: 10px; }
.kpi-icon {
  align-items: center;
  border-radius: 10px;
  display: flex;
  height: 36px;
  justify-content: center;
  width: 36px;
}
.kpi-icon svg { height: 18px; width: 18px; }
.kpi-head h3 { color: var(--text-secondary); font-size: 13px; font-weight: 600; }

.kpi-revenue .kpi-icon { background: var(--accent-soft); color: var(--accent); }
.kpi-cost .kpi-icon { background: var(--warning-soft); color: var(--warning); }
.kpi-profit .kpi-icon { background: var(--success-soft); color: var(--success); }
.kpi-margin .kpi-icon { background: var(--violet-soft); color: var(--violet); }
.kpi-jobs .kpi-icon { background: var(--info-soft); color: var(--info); }

.kpi-body { align-items: flex-end; display: flex; gap: 12px; justify-content: space-between; }
.kpi-value {
  color: var(--text-primary);
  font-size: 26px;
  font-variant-numeric: tabular-nums;
  font-weight: 700;
  letter-spacing: -0.02em;
}
.kpi-spark { height: 34px; width: 92px; color: var(--accent); flex-shrink: 0; }
.kpi-spark-cost { color: var(--warning); }
.kpi-spark-profit { color: var(--success); }
.spark-line { stroke: currentColor; stroke-linecap: round; vector-effect: non-scaling-stroke; }
.spark-stop-a { stop-color: currentColor; stop-opacity: 0.22; }
.spark-stop-b { stop-color: currentColor; stop-opacity: 0; }

.kpi-foot { align-items: center; display: flex; flex-wrap: wrap; gap: 8px; }
.kpi-trend {
  align-items: center;
  border-radius: 999px;
  display: inline-flex;
  font-size: 12px;
  font-weight: 600;
  gap: 4px;
  padding: 3px 9px;
}
.kpi-trend svg { height: 12px; width: 12px; }
.trend-up { background: var(--success-soft); color: var(--success); }
.trend-down { background: var(--danger-soft); color: var(--danger); }
.trend-neutral { background: var(--surface-2); color: var(--text-muted); }
.kpi-subtitle { color: var(--text-muted); font-size: 12px; }
</style>