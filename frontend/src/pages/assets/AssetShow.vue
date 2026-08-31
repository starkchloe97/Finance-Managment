<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useAssetStore } from '@/stores/assetStore'
import { money } from '@/utils/money'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
import InfoTip from '@/components/ui/InfoTip.vue'
import { avatarStyle, initialOf } from '@/utils/avatar'
import { useToast } from '@/composables/useToast'

const route = useRoute()
const router = useRouter()
const assetStore = useAssetStore()
const { currentAsset: asset, detailLoading } = storeToRefs(assetStore)
const { show: showToast } = useToast()

/* ---- states the original page never handled ---- */
const error = ref('')
const deleteOpen = ref(false)
const deleteBusy = ref(false)

const STATUS_META = {
  active: { klass: 'status-success', label: 'Active', tip: 'In service and available for jobs.' },
  maintenance: {
    klass: 'status-warning',
    label: 'Maintenance',
    tip: 'Under repair or inspection — temporarily unavailable for jobs.',
  },
  inactive: { klass: 'status-draft', label: 'Inactive', tip: 'Retired or out of service.' },
}

const status = computed(() => {
  const meta = STATUS_META[asset.value?.status] || {
    klass: 'status-draft',
    label: asset.value?.status_label || asset.value?.status || '—',
    tip: '',
  }
  return meta
})

const vehicleLabel = computed(
  () => [asset.value?.make, asset.value?.model, asset.value?.model_year].filter(Boolean).join(' ') || 'Vehicle',
)

const monogramSeed = computed(
  () => `${asset.value?.make || ''} ${asset.value?.model || ''}`.trim() || asset.value?.name || '·',
)

const purchase = computed(() => Number(asset.value?.purchase_price || 0))
const currentValue = computed(() => Number(asset.value?.current_value || 0))

/* The number the old page made you calculate yourself */
const valueChange = computed(() => currentValue.value - purchase.value)
const valueChangePct = computed(() => {
  if (purchase.value <= 0) return null
  return Math.round((valueChange.value / purchase.value) * 100)
})
const hasValueData = computed(() => Boolean(asset.value?.purchase_price || asset.value?.current_value))

const fmtMoney = (value) => (value === null || value === undefined || value === '' ? '—' : money(value))

const fmtDate = (value) => {
  if (!value) return '—'
  return new Intl.DateTimeFormat('en-GB', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  }).format(new Date(value))
}

const load = async () => {
  error.value = ''
  try {
    await assetStore.fetchAsset(route.params.id)
  } catch (e) {
    error.value = e.response?.data?.message || 'Could not load this vehicle.'
  }
}

const editAsset = () => {
  router.push({ name: 'assets.edit', params: { id: asset.value.id } })
}

const deleteAsset = async () => {
  if (!asset.value || deleteBusy.value) return

  deleteBusy.value = true
  try {
    const name = asset.value.name
    await assetStore.deleteAsset(asset.value.id)
    showToast(`${name || 'Vehicle'} deleted`)
    router.push({ name: 'assets.index' })
  } catch {
    showToast('Could not delete this vehicle. Please try again.', 'error')
    deleteBusy.value = false
  }
}

onMounted(load)
</script>

<template>
  <div class="asset-detail">
    <!-- Error — the original page had none -->
    <div v-if="error" class="card detail-error" role="alert">
      <div>
        <strong>Couldn't load this vehicle.</strong>
        <p>{{ error }}</p>
      </div>
      <button type="button" class="btn" @click="load">Try again</button>
    </div>

    <!-- Skeleton -->
    <div v-else-if="detailLoading && !asset" class="detail-skeleton" aria-hidden="true">
      <div class="sk" style="height: 210px"></div>
      <div class="sk" style="height: 220px"></div>
    </div>

    <template v-else-if="asset">
      <!-- ============ HERO ============ -->
      <header class="card hero-card">
        <div class="hero-top">
          <div class="hero-id">
            <span class="hero-monogram" :style="avatarStyle(monogramSeed)" aria-hidden="true">
              {{ initialOf(monogramSeed) }}
            </span>
            <div class="hero-copy">
              <span class="section-kicker">{{ asset.asset_code }}</span>
              <div class="hero-title-row">
                <h1>{{ asset.name }}</h1>
                <span class="status-row">
                  <span class="status" :class="status.klass">{{ status.label }}</span>
                  <InfoTip v-if="status.tip" :label="status.tip" />
                </span>
              </div>
              <p class="hero-sub">{{ vehicleLabel }}</p>
            </div>
          </div>

          <div class="hero-actions">
            <button type="button" class="btn-light" @click="editAsset">Edit vehicle</button>
            <button type="button" class="btn-light danger-text" @click="deleteOpen = true">
              Delete
            </button>
          </div>
        </div>

        <div class="hero-stats">
          <div class="hero-stat">
            <span>
              Purchase price
              <InfoTip label="What the company paid when it acquired this vehicle." />
            </span>
            <strong>{{ fmtMoney(asset.purchase_price) }}</strong>
          </div>
          <div class="hero-stat">
            <span>
              Current value
              <InfoTip label="What it's worth today, after depreciation. Used on the assets list." />
            </span>
            <strong>{{ fmtMoney(asset.current_value) }}</strong>
          </div>
          <div class="hero-stat">
            <span>
              Value change
              <InfoTip label="Current value minus purchase price — what the vehicle gained or lost in value since it was bought." />
            </span>
            <strong
              v-if="hasValueData"
              :class="valueChange < 0 ? 'is-red' : valueChange > 0 ? 'is-green' : ''"
            >
              {{ valueChange > 0 ? '+' : '' }}{{ money(valueChange) }}
            </strong>
            <strong v-else>—</strong>
          </div>
          <div class="hero-stat">
            <span>Purchased</span>
            <strong>{{ fmtDate(asset.purchase_date) }}</strong>
          </div>
        </div>

        <p v-if="asset.status === 'maintenance'" class="notice-banner" role="status">
          Under maintenance — this vehicle is temporarily unavailable for job assignment.
        </p>
      </header>

      <!-- ============ Grid ============ -->
      <div class="detail-grid">
        <aside class="detail-side">
          <!-- Vehicle facts -->
          <section class="card side-card">
            <h2 class="side-title">Vehicle</h2>
            <ul class="fact-list">
              <li>
                <span class="fact-label">
                  Type
                  <InfoTip label="Free-text category — Truck, Pickup, Van." />
                </span>
                <span class="fact-value">{{ asset.vehicle_type || '—' }}</span>
              </li>
              <li>
                <span class="fact-label">Make</span>
                <span class="fact-value">{{ asset.make || '—' }}</span>
              </li>
              <li>
                <span class="fact-label">Model</span>
                <span class="fact-value">{{ asset.model || '—' }}</span>
              </li>
              <li>
                <span class="fact-label">Model year</span>
                <span class="fact-value">{{ asset.model_year || '—' }}</span>
              </li>
              <li>
                <span class="fact-label">Color</span>
                <span class="fact-value">{{ asset.color || '—' }}</span>
              </li>
            </ul>
          </section>

          <!-- Identification -->
          <section class="card side-card">
            <h2 class="side-title">
              Identification
              <InfoTip label="Official identifiers — used for job paperwork, insurance, and maintenance records." />
            </h2>
            <ul class="fact-list">
              <li>
                <span class="fact-label">Registration</span>
                <span class="fact-value mono">
                  {{ asset.registration_number || '—' }}
                </span>
              </li>
              <li>
                <span class="fact-label">
                  VIN / Chassis
                  <InfoTip label="The manufacturer's unique vehicle identification number." />
                </span>
                <span class="fact-value mono">{{ asset.vin || '—' }}</span>
              </li>
              <li>
                <span class="fact-label">
                  Engine number
                  <InfoTip label="The engine block's serial number — different from the chassis number." />
                </span>
                <span class="fact-value mono">{{ asset.engine_number || '—' }}</span>
              </li>
            </ul>
          </section>

          <section v-if="asset.notes" class="card side-card">
            <h2 class="side-title">Notes</h2>
            <p class="notes-text">{{ asset.notes }}</p>
          </section>
        </aside>

        <div class="detail-main">
          <!-- Value story -->
          <section v-if="hasValueData" class="card block-card">
            <header class="block-head">
              <div class="block-title">
                <span class="block-icon icon-accent" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="9" /><path d="M12 7v10" /><path d="M15 9.5c0-1.1-1.3-2-3-2s-3 .9-3 2 1.3 2 3 2 3 .9 3 2-1.3 2-3 2-3-.9-3-2" />
                  </svg>
                </span>
                <div>
                  <h2>Value over time</h2>
                  <p class="block-hint">
                    What the company paid versus what it's worth today.
                    <InfoTip label="A negative change is normal — vehicles depreciate as they age and rack up mileage." />
                  </p>
                </div>
              </div>
              <span
                v-if="valueChangePct !== null"
                class="change-badge"
                :class="valueChange < 0 ? 'is-loss' : 'is-gain'"
              >
                {{ valueChange > 0 ? '+' : '' }}{{ valueChangePct }}%
              </span>
            </header>

            <div class="value-compare">
              <div class="value-step">
                <span class="flow-label">
                  Purchase price
                  <InfoTip :label="`Paid on ${fmtDate(asset.purchase_date)}.`" />
                </span>
                <strong>{{ fmtMoney(asset.purchase_price) }}</strong>
              </div>
              <svg class="value-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M5 12h14" /><path d="m12 5 7 7-7 7" />
              </svg>
              <div class="value-step value-now">
                <span class="flow-label">Current value</span>
                <strong>{{ fmtMoney(asset.current_value) }}</strong>
              </div>
            </div>
          </section>

          <!-- Assignment (future) -->
          <section class="card block-card placeholder-card">
            <header class="block-head">
              <div class="block-title">
                <span class="block-icon icon-neutral" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="M22 21v-2a4 4 0 0 0-3-3.87" /><path d="M16 3.13a4 4 0 0 1 0 7.75" />
                  </svg>
                </span>
                <div>
                  <h2>Job assignment</h2>
                  <p class="block-hint">
                    Where this vehicle is used.
                    <InfoTip label="Customer and transport-job assignments land here in the next phase. For now, this vehicle isn't linked to any job." />
                  </p>
                </div>
              </div>
              <span class="badge">Not assigned</span>
            </header>
          </section>
        </div>
      </div>

      <div class="detail-footer">
        <RouterLink class="btn-light" :to="{ name: 'assets.index' }">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M19 12H5" /><path d="m12 19-7-7 7-7" />
          </svg>
          Back to assets
        </RouterLink>
      </div>

      <!-- Delete confirmation — replaces window.confirm -->
      <ConfirmDialog
        :open="deleteOpen"
        title="Delete vehicle?"
        :message="
          asset
            ? `Delete ${asset.name}${asset.registration_number ? ` (${asset.registration_number})` : ''}? This cannot be undone.`
            : ''
        "
        confirm-label="Delete vehicle"
        variant="danger"
        :loading="deleteBusy"
        @confirm="deleteAsset"
        @cancel="deleteOpen = false"
      />
    </template>
  </div>
</template>

<style scoped>
.asset-detail {
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

.hero-id { align-items: center; display: flex; gap: 16px; min-width: 0; }

.hero-monogram {
  align-items: center;
  border-radius: 14px;
  box-shadow: 0 4px 12px rgb(16 24 40 / 14%);
  color: #fff;
  display: inline-flex;
  flex: 0 0 52px;
  font-size: 19px;
  font-weight: 700;
  height: 52px;
  justify-content: center;
  width: 52px;
}

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

.status-row {
  align-items: center;
  display: inline-flex;
  gap: 5px;
}

.hero-sub {
  color: var(--text-secondary);
  font-size: 14px;
  margin: 5px 0 0;
}

.hero-actions {
  align-items: center;
  display: flex;
  flex: 0 0 auto;
  gap: 10px;
}

.danger-text { color: var(--danger); }
.danger-text:hover { border-color: var(--danger); }

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
  color: var(--text-primary);
  font-size: 19px;
  font-variant-numeric: tabular-nums;
  font-weight: 700;
  letter-spacing: -0.01em;
}
.hero-stat strong.is-green { color: var(--success); }
.hero-stat strong.is-red { color: var(--danger); }

.notice-banner {
  background: var(--warning-soft);
  border-radius: var(--radius-md);
  color: var(--warning);
  font-size: 13px;
  margin: 16px 0 0;
  padding: 10px 14px;
}

/* ---------- Grid / sidebar ---------- */
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

.side-card { padding: 20px; }

.side-title {
  align-items: center;
  color: var(--text-muted);
  display: flex;
  font-size: 11px;
  font-weight: 600;
  gap: 5px;
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
.fact-value.mono {
  font-family: ui-monospace, SFMono-Regular, 'SF Mono', Menlo, Consolas, monospace;
  font-size: 13px;
  letter-spacing: 0.02em;
}

.notes-text {
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
}

.block-title { align-items: flex-start; display: flex; gap: 12px; }
.block-title h2 { font-size: 15px; font-weight: 600; margin: 0; }
.block-hint {
  align-items: center;
  color: var(--text-muted);
  display: flex;
  flex-wrap: wrap;
  font-size: 13px;
  gap: 4px;
  margin: 2px 0 0;
}

.block-icon {
  align-items: center;
  border-radius: 9px;
  display: flex;
  flex: 0 0 32px;
  height: 32px;
  justify-content: center;
  width: 32px;
}
.block-icon svg { height: 15px; width: 15px; }
.icon-accent { background: var(--accent-soft); color: var(--accent); }
.icon-neutral { background: var(--surface-2); color: var(--text-muted); }

.change-badge {
  border-radius: 999px;
  display: inline-flex;
  font-size: 11px;
  font-weight: 600;
  padding: 3px 9px;
  white-space: nowrap;
}
.change-badge.is-gain { background: var(--success-soft); color: var(--success); }
.change-badge.is-loss { background: var(--surface-2); color: var(--text-secondary); }

/* Value compare */
.value-compare {
  align-items: stretch;
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 16px;
}

.value-step {
  background: var(--surface-2);
  border-radius: var(--radius-md);
  flex: 1 1 160px;
  padding: 14px 16px;
}
.value-step.value-now { background: var(--accent-soft); }

.flow-label {
  align-items: center;
  color: var(--text-muted);
  display: flex;
  font-size: 12px;
  font-weight: 500;
  gap: 6px;
  margin-bottom: 5px;
}

.value-step strong {
  color: var(--text-primary);
  font-size: 20px;
  font-variant-numeric: tabular-nums;
  font-weight: 700;
  letter-spacing: -0.01em;
}
.value-step.value-now strong { color: var(--accent); }

.value-arrow {
  align-items: center;
  align-self: center;
  color: var(--text-muted);
  display: flex;
  height: 20px;
  width: 20px;
}

/* Placeholder block */
.placeholder-card {
  border: 1px dashed var(--border-strong);
  box-shadow: none;
}

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
  .hero-actions .btn-light { flex: 1; justify-content: center; }
  .detail-error { align-items: flex-start; flex-direction: column; }
  .value-arrow { display: none; }
}
</style>