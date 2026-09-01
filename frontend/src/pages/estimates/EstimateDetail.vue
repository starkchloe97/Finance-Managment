<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { getEstimate } from '@/services/estimateService'
import { money } from '@/utils/money'
import EstimateStatus from '@/components/estimates/EstimateStatus.vue'
import EstimateItems from '@/components/estimates/EstimateItems.vue'
import EstimateConversionDialog from '@/components/estimates/EstimateConversionDialog.vue'
import InfoTip from '@/components/ui/InfoTip.vue'
import { avatarStyle, initialOf } from '@/utils/avatar'
import EstimateVehicles from '@/components/estimates/EstimateVehicles.vue'
const route = useRoute()
const estimate = ref(null)
const loading = ref(true)
const error = ref('')
const converting = ref(false)
const activeTab = ref('items')

const vehicleCount = computed(() => {
  return (
    estimate.value?.items?.reduce(
      (total, item) => total + (item.vehicles?.length || 0),
      0
    ) || 0
  )
})

const load = async () => {
  loading.value = true
  error.value = ''
  try {
    const { data } = await getEstimate(route.params.id)
    estimate.value = data.data
  } catch (e) {
    error.value = e.response?.data?.message || 'Could not load estimate.'
  } finally {
    loading.value = false
  }
}

onMounted(load)

const converted = computed(() => Boolean(estimate.value?.transport_job))

// Expiry awareness — only meaningful while the customer hasn't answered.
const OPEN_STATUSES = ['draft', 'sent']
const isOpen = computed(() => OPEN_STATUSES.includes(estimate.value?.status))

const daysLeft = computed(() => {
  const until = estimate.value?.valid_until
  if (!until || !isOpen.value) return null
  const diff = new Date(until).setHours(0, 0, 0, 0) - new Date().setHours(0, 0, 0, 0)
  return Math.round(diff / 86400000)
})

const expired = computed(() => daysLeft.value !== null && daysLeft.value < 0)
const expiringSoon = computed(() => daysLeft.value !== null && daysLeft.value >= 0 && daysLeft.value <= 7)

const validUntilText = computed(() => {
  if (expired.value) return `Expired ${Math.abs(daysLeft.value)}d ago`
  if (daysLeft.value === 0) return 'Expires today'
  if (daysLeft.value !== null) return `${daysLeft.value}d left`
  return '—'
})

const when = (value) => (value ? String(value).slice(0, 10) : '—')
</script>

<template>
  <div class="estimate-detail">
    <!-- Error -->
    <div v-if="error" class="card detail-error" role="alert">
      <div>
        <strong>Couldn't load this estimate.</strong>
        <p>{{ error }}</p>
      </div>
      <button type="button" class="btn" @click="load">Try again</button>
    </div>

    <!-- Skeleton -->
    <div v-else-if="loading && !estimate" class="detail-skeleton" aria-hidden="true">
      <div class="sk" style="height: 200px"></div>
      <div class="sk" style="height: 340px"></div>
    </div>

    <template v-else-if="estimate">
      <!-- ============ HERO ============ -->
      <header class="card hero-card">
        <div class="hero-top">
          <div class="hero-id">
            <span class="hero-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z" /><path d="M14 2v4a2 2 0 0 0 2 2h4" /><path d="M16 13H8" /><path d="M16 17H8" />
              </svg>
            </span>
            <div class="hero-copy">
              <span class="section-kicker">Operations / Estimates</span>
              <div class="hero-title-row">
                <h1>{{ estimate.code }}</h1>
                <EstimateStatus :status="estimate.status" />
              </div>
              <div v-if="estimate.customer?.name" class="hero-customer">
                <span
                  class="customer-avatar"
                  :style="avatarStyle(estimate.customer.name)"
                  aria-hidden="true"
                >
                  {{ initialOf(estimate.customer.name) }}
                </span>
                <RouterLink class="hero-customer-link" :to="`/customers/${estimate.customer.id}`">
                  {{ estimate.customer.name }}
                </RouterLink>
                <span class="hero-sep" aria-hidden="true">·</span>
                <span class="hero-date">{{ when(estimate.estimate_date) }}</span>
              </div>
              <p v-else class="hero-date">No customer · {{ when(estimate.estimate_date) }}</p>
            </div>
          </div>

          <div class="hero-actions">
            <RouterLink v-if="converted" class="btn" :to="`/jobs/${estimate.transport_job.id}`">
              View job
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M5 12h14" /><path d="m12 5 7 7-7 7" />
              </svg>
            </RouterLink>
            <template v-else>
              <RouterLink class="btn-light" :to="`/estimates/${estimate.id}/edit`">
                Edit quote
              </RouterLink>
              <button class="btn" type="button" @click="converting = true">
                Convert to job
              </button>
            </template>
          </div>
        </div>

        <div class="hero-stats">
          <div class="hero-stat">
            <span>
              Quoted amount
              <InfoTip label="The price shown to the customer. Internal costs are tracked on the job, never on the quote." />
            </span>
            <strong>{{ money(estimate.estimated_sell) }}</strong>
          </div>
          <div class="hero-stat">
            <span>
              Line items
              <InfoTip label="How many priced lines make up this quote." />
            </span>
            <strong>{{ estimate.items?.length || 0 }}</strong>
          </div>
          <div class="hero-stat">
            <span>
              Valid until
              <InfoTip label="The customer can accept this price until this date. After that the quote expires." />
            </span>
            <strong>{{ when(estimate.valid_until) }}</strong>
            <span v-if="expired" class="expiry-chip is-expired">Expired</span>
            <span v-else-if="expiringSoon" class="expiry-chip is-soon">{{ validUntilText }}</span>
          </div>
          <div class="hero-stat">
            <span>Quote date</span>
            <strong>{{ when(estimate.estimate_date) }}</strong>
          </div>
        </div>

        <p v-if="expired" class="hero-warning" role="status">
          ⚠ This quote expired on {{ when(estimate.valid_until) }} — the customer can no longer
          accept this price. Duplicate it into a new estimate to re-quote.
        </p>
      </header>

      <!-- ============ Grid ============ -->
      <div class="detail-grid">
        <aside class="detail-side">
          <section class="card side-card">
            <h2 class="side-title">Quote details</h2>
            <ul class="fact-list">
              <li>
                <span class="fact-label">Customer</span>
                <span class="fact-value">{{ estimate.customer?.name || '—' }}</span>
              </li>
              <li>
                <span class="fact-label">Route</span>
                <span class="fact-value">
                  {{ estimate.pickup || '—' }} → {{ estimate.destination || '—' }}
                </span>
              </li>
              <li>
                <span class="fact-label">
                  Service type
                  <InfoTip label="What is being moved — goods or a vehicle. It affects how the job is planned." />
                </span>
                <span class="fact-value fact-cap">{{ estimate.service_type || '—' }}</span>
              </li>
              <li>
                <span class="fact-label">Estimate date</span>
                <span class="fact-value">{{ when(estimate.estimate_date) }}</span>
              </li>
              <li>
                <span class="fact-label">Valid until</span>
                <span class="fact-value">{{ when(estimate.valid_until) }}</span>
              </li>
              <li v-if="converted">
                <span class="fact-label">Became job</span>
                <span class="fact-value">
                  <RouterLink class="fact-link" :to="`/jobs/${estimate.transport_job.id}`">
                    {{ estimate.transport_job.code }} →
                  </RouterLink>
                </span>
              </li>
            </ul>
          </section>

          <section v-if="estimate.remarks" class="card side-card">
            <h2 class="side-title">Customer-facing remarks</h2>
            <p class="remarks-text">{{ estimate.remarks }}</p>
          </section>
        </aside>

        <div class="detail-main">
          <section class="card block-card">
            <header class="block-head">
              <div class="block-title">
                <span class="block-icon icon-accent" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M8 6h13" /><path d="M8 12h13" /><path d="M8 18h13" /><path d="M3 6h.01" /><path d="M3 12h.01" /><path d="M3 18h.01" />
                  </svg>
                </span>
                <div>
                  <h2>Estimate breakdown</h2>
                  <p class="block-hint">Quote items and the vehicles required to fulfil this estimate.</p>
                </div>
              </div>
              <span class="block-total">{{ money(estimate.estimated_sell) }}</span>
            </header>

            <div class="detail-tabs">
              <button
                type="button"
                class="detail-tab"
                :class="{ 'is-active': activeTab === 'items' }"
                @click="activeTab = 'items'"
              >
                <span>Quote items</span>
                <span class="tab-count">
                  {{ estimate.items?.length || 0 }}
                </span>
              </button>

              <button
                v-if="vehicleCount > 0"
                type="button"
                class="detail-tab"
                :class="{ 'is-active': activeTab === 'vehicles' }"
                @click="activeTab = 'vehicles'"
              >
                <span>Vehicles</span>
                <span class="tab-count">
                  {{ vehicleCount }}
                </span>
              </button>
            </div>

            <div v-if="activeTab === 'items'">
              <EstimateItems
                v-if="estimate.items?.length"
                :items="estimate.items"
              />

              <div v-else class="block-empty">
                <p>No line items on this quote.</p>
              </div>
            </div>

            <EstimateVehicles
              v-else-if="activeTab === 'vehicles'"
              :items="estimate.items"
            />
          </section>
        </div>
      </div>

      <div class="detail-footer">
        <RouterLink class="btn-light" to="/estimates">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M19 12H5" /><path d="m12 19-7-7 7-7" />
          </svg>
          Back to estimates
        </RouterLink>
      </div>

      <EstimateConversionDialog
        :open="converting"
        :estimate="estimate"
        @close="converting = false"
      />
    </template>
  </div>
</template>

<style scoped>
.estimate-detail {
  display: flex;
  flex-direction: column;
  gap: 20px;
  min-width: 0;
}

/* ---------- Hero ---------- */
.hero-card { padding: 24px; }

.hero-top {
  align-items: flex-start;
  display: flex;
  gap: 16px;
  justify-content: space-between;
}

.hero-id { align-items: flex-start; display: flex; gap: 16px; min-width: 0; }

.hero-icon {
  align-items: center;
  background: var(--accent-soft);
  border-radius: 12px;
  color: var(--accent);
  display: flex;
  flex: 0 0 48px;
  height: 48px;
  justify-content: center;
  width: 48px;
}
.hero-icon svg { height: 22px; width: 22px; }

.hero-copy { min-width: 0; }
.hero-copy .section-kicker { margin-bottom: 4px; }

.hero-title-row {
  align-items: center;
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}
.hero-title-row h1 {
  font-size: 22px;
  font-weight: 700;
  letter-spacing: -0.02em;
  margin: 0;
}

.hero-customer {
  align-items: center;
  color: var(--text-secondary);
  display: flex;
  font-size: 14px;
  gap: 8px;
  margin-top: 6px;
}
.customer-avatar {
  align-items: center;
  border-radius: 50%;
  color: #fff;
  display: inline-flex;
  flex: 0 0 26px;
  font-size: 11px;
  font-weight: 600;
  height: 26px;
  justify-content: center;
  width: 26px;
}
.hero-customer-link { color: var(--text-secondary); font-weight: 500; }
.hero-customer-link:hover { color: var(--accent); }
.hero-sep { color: var(--text-muted); }
.hero-date { color: var(--text-muted); font-size: 14px; margin-top: 6px; }

.hero-actions {
  align-items: center;
  display: flex;
  flex: 0 0 auto;
  flex-wrap: wrap;
  gap: 10px;
}

.hero-stats {
  border-top: 1px solid var(--border);
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  margin-top: 20px;
  padding-top: 20px;
}

.hero-stat {
  border-left: 1px solid var(--border);
  display: flex;
  flex-direction: column;
  gap: 3px;
  min-width: 0;
  padding: 0 16px;
}
.hero-stat:first-child { border-left: 0; padding-left: 0; }
.hero-stat > span {
  align-items: center;
  color: var(--text-muted);
  display: flex;
  font-size: 12px;
  font-weight: 500;
  gap: 5px;
}
.hero-stat strong {
  font-size: 19px;
  font-variant-numeric: tabular-nums;
  font-weight: 700;
  letter-spacing: -0.01em;
}

.expiry-chip {
  border-radius: 999px;
  font-size: 10px;
  font-weight: 600;
  margin-top: 2px;
  padding: 2px 8px;
  width: fit-content;
}
.expiry-chip.is-expired { background: var(--danger-soft); color: var(--danger); }
.expiry-chip.is-soon { background: var(--warning-soft); color: var(--warning); }

.hero-warning {
  background: var(--warning-soft);
  border-radius: var(--radius-md);
  color: var(--warning);
  font-size: 13px;
  margin: 16px 0 0;
  padding: 10px 14px;
}

/* ---------- Grid ---------- */
.detail-grid {
  align-items: start;
  display: grid;
  gap: 20px;
  grid-template-columns: 300px minmax(0, 1fr);
}

.detail-side {
  display: flex;
  flex-direction: column;
  gap: 20px;
  position: sticky;
  top: 20px;
}

.detail-main {
  display: flex;
  flex-direction: column;
  gap: 20px;
  min-width: 0;
}

/* ---------- Sidebar ---------- */
.side-card { padding: 20px; }

.side-title {
  color: var(--text-muted);
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.08em;
  margin: 0 0 14px;
  text-transform: uppercase;
}

.fact-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
  list-style: none;
  margin: 0;
  padding: 0;
}

.fact-list li { display: flex; flex-direction: column; gap: 2px; }
.fact-label {
  align-items: center;
  color: var(--text-muted);
  display: flex;
  font-size: 11px;
  font-weight: 500;
  gap: 5px;
}
.fact-value { color: var(--text-primary); font-size: 14px; font-weight: 500; }
.fact-cap { text-transform: capitalize; }
.fact-link { color: var(--accent); font-weight: 600; }

.remarks-text {
  color: var(--text-secondary);
  font-size: 14px;
  line-height: 1.6;
  margin: 0;
  white-space: pre-line;
}

/* ---------- Blocks ---------- */
.block-card { padding: 20px; }

.block-head {
  align-items: flex-start;
  display: flex;
  gap: 12px;
  justify-content: space-between;
  margin-bottom: 14px;
}

.block-title { align-items: flex-start; display: flex; gap: 12px; }
.block-title h2 { font-size: 15px; font-weight: 600; margin: 0; }
.block-hint { color: var(--text-muted); font-size: 13px; margin: 2px 0 0; }

.block-icon {
  align-items: center;
  background: var(--accent-soft);
  border-radius: 9px;
  color: var(--accent);
  display: flex;
  flex: 0 0 32px;
  height: 32px;
  justify-content: center;
  width: 32px;
}
.block-icon svg { height: 15px; width: 15px; }

.block-total {
  color: var(--text-primary);
  font-size: 16px;
  font-variant-numeric: tabular-nums;
  font-weight: 700;
  white-space: nowrap;
}

.block-empty {
  border: 1px dashed var(--border-strong);
  border-radius: var(--radius-md);
  color: var(--text-muted);
  font-size: 13px;
  padding: 18px 16px;
  text-align: center;
}
.block-empty p { margin: 0; }

/* ---------- Error / skeleton / footer ---------- */
.detail-error {
  align-items: center;
  border-color: var(--danger);
  color: var(--danger);
  display: flex;
  gap: 16px;
  justify-content: space-between;
}
.detail-error p { color: var(--text-secondary); margin: 4px 0 0; }

.detail-skeleton { display: flex; flex-direction: column; gap: 20px; }

.sk {
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  overflow: hidden;
  position: relative;
}
.sk::after {
  animation: shimmer 1.6s infinite;
  background: linear-gradient(90deg, transparent, rgb(255 255 255 / 70%), transparent);
  content: '';
  inset: 0;
  position: absolute;
  transform: translateX(-100%);
}
@keyframes shimmer { 100% { transform: translateX(100%); } }

.detail-footer { display: flex; }

/* ---------- Responsive ---------- */
@media (max-width: 1024px) {
  .detail-grid { grid-template-columns: 1fr; }
  .detail-side { position: static; }
  .hero-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); row-gap: 18px; }
  .hero-stat:nth-child(odd) { border-left: 0; padding-left: 0; }
}

@media (max-width: 700px) {
  .hero-top { flex-direction: column; }
  .hero-actions { width: 100%; }
  .hero-actions .btn,
  .hero-actions .btn-light { flex: 1; justify-content: center; }
  .detail-error { align-items: flex-start; flex-direction: column; }
}

.detail-tabs {
  border-bottom: 1px solid var(--border);
  display: flex;
  gap: 4px;
  padding: 0 20px;
}

.detail-tab {
  align-items: center;
  background: transparent;
  border: 0;
  border-bottom: 2px solid transparent;
  color: var(--text-muted);
  cursor: pointer;
  display: inline-flex;
  font: inherit;
  font-size: 13px;
  font-weight: 500;
  gap: 7px;
  margin-bottom: -1px;
  padding: 12px 4px 11px;
  transition:
    color 0.15s ease,
    border-color 0.15s ease;
}

.detail-tab:hover {
  color: var(--text-primary);
}

.detail-tab.is-active {
  border-bottom-color: var(--accent);
  color: var(--accent);
  font-weight: 600;
}

.tab-count {
  align-items: center;
  background: var(--surface-subtle, var(--background-secondary));
  border-radius: 999px;
  display: inline-flex;
  font-size: 10px;
  font-weight: 600;
  height: 19px;
  justify-content: center;
  min-width: 19px;
  padding: 0 5px;
}

.detail-tab.is-active .tab-count {
  background: var(--accent-soft);
  color: var(--accent);
}
</style>