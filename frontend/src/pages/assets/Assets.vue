<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { storeToRefs } from 'pinia'
import { useRouter } from 'vue-router'
import { useAssetStore } from '@/stores/assetStore'
import { money } from '@/utils/money'
import Pagination from '@/components/ui/Pagination.vue'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
import InfoTip from '@/components/ui/InfoTip.vue'
import AppToast from '@/components/ui/AppToast.vue'
import { useToast } from '@/composables/useToast'
import { avatarStyle } from '@/utils/avatar'

const router = useRouter()
const assetStore = useAssetStore()
const { assets, pagination, loading } = storeToRefs(assetStore)
const { show: showToast } = useToast()

const search = ref('')
const status = ref('')
const page = ref(1)
let searchTimer = null

/* ---- delete confirmation ---- */
const pendingDelete = ref(null)
const deleteBusy = ref(false)

const statusOptions = [
  { value: '', label: 'All' },
  { value: 'active', label: 'Active' },
  { value: 'maintenance', label: 'Maintenance' },
  { value: 'inactive', label: 'Inactive' },
]

// Maps onto the global status classes: active → green, maintenance → amber,
// inactive → gray (parked/retired, not an alarm).
const STATUS_META = {
  active: { klass: 'status-success', label: 'Active', tip: 'In service and available for jobs.' },
  maintenance: {
    klass: 'status-warning',
    label: 'Maintenance',
    tip: 'Under repair or inspection — temporarily unavailable for jobs.',
  },
  inactive: { klass: 'status-draft', label: 'Inactive', tip: 'Retired or out of service.' },
}

const hasActiveFilters = computed(() => Boolean(search.value || status.value))

const totalLabel = computed(() => {
  const total = pagination.value?.total ?? 0
  return `${total} ${total === 1 ? 'vehicle' : 'vehicles'}`
})

const initialsOf = (source) => {
  const parts = String(source).split(/\s+/).filter(Boolean)
  if (!parts.length) return '·'
  return parts
    .slice(0, 2)
    .map((part) => part[0])
    .join('')
    .toUpperCase()
}

// Display rows: store data stays untouched; view helpers ride along.
const rows = computed(() =>
  assets.value.map((asset) => {
    const meta = STATUS_META[asset.status] || {
      klass: 'status-draft',
      label: asset.status_label || asset.status,
      tip: '',
    }
    const vehicle = [asset.make, asset.model].filter(Boolean).join(' ')
    const source = vehicle || asset.name || asset.asset_code || ''
    return {
      ...asset,
      statusClass: meta.klass,
      statusLabel: meta.label,
      statusTip: meta.tip,
      vehicle: vehicle || '—',
      initials: initialsOf(source),
      monogramTone: avatarStyle(source),
    }
  }),
)

const fmtValue = (value) =>
  value === null || value === undefined || value === '' ? '—' : money(value)

/* ---- loading / filters ---- */
const loadAssets = async () => {
  await assetStore.fetchAssets({
    search: search.value || undefined,
    status: status.value || undefined,
    page: page.value,
    per_page: 15,
  })
}

const handleSearch = () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    page.value = 1
    loadAssets()
  }, 300)
}

const clearSearch = () => {
  clearTimeout(searchTimer)
  search.value = ''
  page.value = 1
  loadAssets()
}

const resetFilters = () => {
  clearTimeout(searchTimer)
  search.value = ''

  const statusChanged = status.value !== ''
  status.value = ''
  page.value = 1

  // status watcher already reloads when it changed
  if (!statusChanged) {
    loadAssets()
  }
}

const changePage = (newPage) => {
  const p = pagination.value
  const total = p?.last_page || 1
  const current = p?.current_page || page.value
  const target = Math.min(Math.max(1, newPage), total)

  if (target === current) return

  page.value = target
  loadAssets()
}

/* ---- row interactions ---- */
const openAsset = (asset) => {
  router.push({ name: 'assets.show', params: { id: asset.id } })
}

const editAsset = (asset) => {
  router.push({ name: 'assets.edit', params: { id: asset.id } })
}

const onRowKeydown = (event, asset) => {
  if (event.key !== 'Enter') return
  if (event.target !== event.currentTarget) return
  openAsset(asset)
}

/* ---- delete flow ---- */
const confirmDelete = (asset) => {
  pendingDelete.value = asset
}

const deleteAsset = async () => {
  if (!pendingDelete.value || deleteBusy.value) return

  deleteBusy.value = true

  try {
    const name = pendingDelete.value.name

    await assetStore.deleteAsset(pendingDelete.value.id)
    pendingDelete.value = null
    showToast(`${name || 'Vehicle'} deleted`)

    await loadAssets()

    // deleted the last row on the last page → step back
    if (!assets.value.length && page.value > 1) {
      page.value -= 1
      await loadAssets()
    }
  } catch {
    showToast('Could not delete this vehicle. Please try again.', 'error')
  } finally {
    deleteBusy.value = false
  }
}

onMounted(loadAssets)

onBeforeUnmount(() => {
  clearTimeout(searchTimer)
})

watch(status, () => {
  page.value = 1
  loadAssets()
})
</script>

<template>
  <div class="assets-page">
    <!-- Header -->
    <div class="page-head">
      <div>
        <span class="section-kicker">Operations / Assets</span>
        <h1>Assets</h1>
        <p class="page-sub">{{ totalLabel }} — company vehicles and operational equipment.</p>
      </div>

      <RouterLink class="btn" :to="{ name: 'assets.create' }">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true">
          <path d="M12 5v14M5 12h14" />
        </svg>
        Add vehicle
      </RouterLink>
    </div>

    <!-- Toolbar -->
    <section class="card toolbar-card">
      <div class="search-field">
        <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
          <circle cx="11" cy="11" r="7" />
          <path d="m20 20-4-4" />
        </svg>

        <input
          v-model="search"
          type="search"
          placeholder="Search by name, code, registration…"
          aria-label="Search assets"
          @input="handleSearch"
        />

        <button
          v-if="search && !loading"
          type="button"
          class="search-clear"
          aria-label="Clear search"
          @click="clearSearch"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
            <path d="M18 6 6 18M6 6l12 12" />
          </svg>
        </button>

        <span v-if="loading" class="search-spinner" aria-hidden="true"></span>
      </div>

      <div class="seg" role="group" aria-label="Filter by status">
        <button
          v-for="option in statusOptions"
          :key="option.value || 'all'"
          type="button"
          class="seg-option"
          :class="{ 'is-active': status === option.value }"
          :aria-pressed="status === option.value"
          @click="status = option.value"
        >
          <span
            class="seg-dot"
            :class="option.value ? `dot-${option.value}` : 'dot-all'"
            aria-hidden="true"
          ></span>
          {{ option.label }}
        </button>
      </div>

      <button
        v-if="hasActiveFilters"
        type="button"
        class="btn-light btn-sm"
        @click="resetFilters"
      >
        Clear filters
      </button>
    </section>

    <!-- Table / skeleton -->
    <section v-if="rows.length || loading" class="card table-card" :aria-busy="loading">
      <span v-if="loading" class="sr-only">Loading assets…</span>

      <div v-if="loading" class="progress-line" aria-hidden="true">
        <span></span>
      </div>

      <div class="table-wrap" :class="{ 'is-refreshing': loading && rows.length }">
        <table>
          <caption class="sr-only">Company vehicles and operational assets</caption>

          <thead>
            <tr>
              <th>Asset</th>
              <th>Vehicle</th>
              <th>
                Registration
                <InfoTip label="The vehicle's official plate number." />
              </th>
              <th class="col-type">Type</th>
              <th class="right">
                Value
                <InfoTip label="Current book value — what the vehicle is worth today." />
              </th>
              <th>Status</th>
              <th class="right"></th>
            </tr>
          </thead>

          <!-- Data rows -->
          <tbody v-if="rows.length">
            <tr
              v-for="asset in rows"
              :key="asset.id"
              tabindex="0"
              :aria-label="`Open ${asset.name}`"
              @click="openAsset(asset)"
              @keydown="onRowKeydown($event, asset)"
            >
              <td>
                <div class="asset-primary">
                  <span class="monogram" :style="asset.monogramTone" aria-hidden="true">
                    {{ asset.initials }}
                  </span>
                  <span class="asset-meta">
                    <strong>{{ asset.name }}</strong>
                    <span class="asset-code">{{ asset.asset_code }}</span>
                  </span>
                </div>
              </td>

              <td>
                <div class="vehicle-info">
                  <strong>{{ asset.vehicle }}</strong>
                  <span v-if="asset.model_year">{{ asset.model_year }}</span>
                </div>
              </td>

              <td>
                <span v-if="asset.registration_number" class="reg-chip">
                  {{ asset.registration_number }}
                </span>
                <template v-else>—</template>
              </td>

              <td class="col-type">{{ asset.vehicle_type || '—' }}</td>

              <td class="right cell-value">{{ fmtValue(asset.current_value) }}</td>

              <td>
                <span class="status-row">
                  <span class="status" :class="asset.statusClass">{{ asset.statusLabel }}</span>
                  <InfoTip v-if="asset.statusTip" :label="asset.statusTip" />
                </span>
              </td>

              <td class="right" @click.stop>
                <div class="row-actions">
                  <button
                    type="button"
                    class="icon-action"
                    title="View vehicle"
                    aria-label="View vehicle"
                    @click="openAsset(asset)"
                  >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                      <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" /><circle cx="12" cy="12" r="3" />
                    </svg>
                  </button>

                  <button
                    type="button"
                    class="icon-action"
                    title="Edit vehicle"
                    aria-label="Edit vehicle"
                    @click="editAsset(asset)"
                  >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                      <path d="M17 3a2.83 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z" />
                    </svg>
                  </button>

                  <button
                    type="button"
                    class="icon-action danger"
                    title="Delete vehicle"
                    aria-label="Delete vehicle"
                    @click="confirmDelete(asset)"
                  >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                      <path d="M3 6h18" /><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" /><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" /><path d="M10 11v6M14 11v6" />
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>

          <!-- Skeleton rows (first load) -->
          <tbody v-else aria-hidden="true">
            <tr v-for="i in 8" :key="`sk-${i}`">
              <td>
                <div class="asset-primary">
                  <span class="skeleton skel-avatar"></span>
                  <span class="skeleton w-55"></span>
                </div>
              </td>
              <td><span class="skeleton w-70"></span></td>
              <td><span class="skeleton w-55"></span></td>
              <td class="col-type"><span class="skeleton w-45"></span></td>
              <td><span class="skeleton w-55"></span></td>
              <td><span class="skeleton skel-badge"></span></td>
              <td><span class="skeleton w-45"></span></td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="pagination && rows.length" class="table-pagination">
        <Pagination
          :page="pagination.current_page || 1"
          :last-page="pagination.last_page || 1"
          :total="pagination.total || 0"
          :per-page="pagination.per_page || 15"
          @update:page="changePage"
        />
      </div>
    </section>

    <!-- Empty -->
    <section v-else class="card empty-state">
      <div class="empty-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
          <path d="M5 18H3c-.6 0-1-.4-1-1V7c0-.6.4-1 1-1h10c.6 0 1 .4 1 1v11" />
          <path d="M14 9h4l4 4v4c0 .6-.4 1-1 1h-2" />
          <circle cx="7.5" cy="17.5" r="2.5" />
          <circle cx="17.5" cy="17.5" r="2.5" />
        </svg>
      </div>

      <h2>{{ hasActiveFilters ? 'No matching vehicles' : 'No vehicles yet' }}</h2>

      <p>
        {{
          hasActiveFilters
            ? 'Nothing matches the current filters. Adjust your search or start over.'
            : 'Add your first company vehicle to start managing assets.'
        }}
      </p>

      <div class="empty-actions">
        <button v-if="hasActiveFilters" type="button" class="btn-light" @click="resetFilters">
          Clear filters
        </button>
        <RouterLink v-else class="btn" :to="{ name: 'assets.create' }">Add vehicle</RouterLink>
      </div>
    </section>

    <!-- Delete confirmation -->
    <ConfirmDialog
      :open="Boolean(pendingDelete)"
      title="Delete vehicle?"
      :message="
        pendingDelete
          ? `Delete ${pendingDelete.name}${pendingDelete.registration_number ? ` (${pendingDelete.registration_number})` : ''}? This cannot be undone.`
          : ''
      "
      confirm-label="Delete vehicle"
      variant="danger"
      :loading="deleteBusy"
      @confirm="deleteAsset"
      @cancel="pendingDelete = null"
    />

    <AppToast />
  </div>
</template>

<style scoped>
.assets-page {
  display: flex;
  flex-direction: column;
  gap: 18px;
  min-width: 0;
}

/* Flex gap owns the spacing — neutralize any global .card margin */
.assets-page > .card {
  margin-bottom: 0;
}

.page-sub {
  color: var(--text-secondary);
  font-size: 14px;
  margin-top: var(--space-2);
}

/* ---------- Toolbar ---------- */

.toolbar-card {
  align-items: center;
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  padding: 14px 16px;
}

.search-field {
  flex: 1 1 220px;
  min-width: 0;
  position: relative;
}

.search-field .search-icon {
  color: var(--text-muted);
  height: 15px;
  left: 12px;
  pointer-events: none;
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  width: 15px;
}

.search-field input {
  border-radius: 10px;
  padding-left: 36px;
  padding-right: 40px;
}

.search-field input[type='search']::-webkit-search-cancel-button {
  -webkit-appearance: none;
  appearance: none;
  display: none;
}

.search-clear {
  align-items: center;
  background: transparent;
  border: 0;
  border-radius: 7px;
  color: var(--text-muted);
  cursor: pointer;
  display: flex;
  height: 26px;
  justify-content: center;
  position: absolute;
  right: 8px;
  top: 50%;
  transform: translateY(-50%);
  width: 26px;
}

.search-clear svg { height: 13px; width: 13px; }
.search-clear:hover { background: var(--surface-2); color: var(--text-primary); }

.search-spinner {
  animation: spin 0.7s linear infinite;
  border: 2px solid var(--border);
  border-radius: 50%;
  border-top-color: var(--text-muted);
  height: 15px;
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  width: 15px;
}

/* Segmented status filter */
.seg {
  align-items: center;
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: 10px;
  display: inline-flex;
  gap: 2px;
  padding: 3px;
}

.seg-option {
  align-items: center;
  background: transparent;
  border: 0;
  border-radius: 8px;
  color: var(--text-secondary);
  cursor: pointer;
  display: inline-flex;
  font-size: 13px;
  font-weight: 600;
  gap: 7px;
  padding: 6px 12px;
  transition: background 0.15s ease, color 0.15s ease, box-shadow 0.15s ease;
  white-space: nowrap;
}

.seg-option:hover { color: var(--text-primary); }

.seg-option.is-active {
  background: var(--surface);
  box-shadow: var(--shadow-xs);
  color: var(--text-primary);
}

.seg-option:focus-visible {
  outline: 2px solid var(--accent);
  outline-offset: 1px;
}

.seg-dot {
  border-radius: 50%;
  height: 7px;
  width: 7px;
}

.seg-dot.dot-all {
  background: transparent;
  border: 1.5px solid var(--text-muted);
  box-sizing: border-box;
}
.seg-dot.dot-active { background: var(--success); }
.seg-dot.dot-maintenance { background: var(--warning); }
.seg-dot.dot-inactive { background: var(--text-muted); }

/* ---------- Table ---------- */

.table-card {
  overflow: hidden;
  padding: 0;
}

.progress-line {
  height: 2px;
  overflow: hidden;
  position: relative;
}

.progress-line span {
  animation: progress-slide 1.1s ease-in-out infinite;
  background: var(--accent);
  border-radius: 2px;
  height: 100%;
  left: 0;
  position: absolute;
  top: 0;
  width: 40%;
}

.table-card .table-wrap { padding: 0; }
.table-card table { min-width: 860px; }
.table-card th,
.table-card td { white-space: nowrap; }

.table-wrap.is-refreshing tbody {
  opacity: 0.55;
  pointer-events: none;
  transition: opacity 0.15s ease;
}

.data-table tbody tr { cursor: pointer; }

.data-table tbody tr:focus-visible {
  background: var(--surface-2);
  outline: 2px solid var(--accent);
  outline-offset: -2px;
}

.asset-primary {
  align-items: center;
  display: flex;
  gap: 11px;
}

.monogram {
  align-items: center;
  border-radius: 9px;
  color: #fff;
  display: inline-flex;
  flex: 0 0 34px;
  font-size: 12px;
  font-weight: 700;
  height: 34px;
  justify-content: center;
  letter-spacing: 0.02em;
  width: 34px;
}

.asset-meta {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.asset-meta strong { color: var(--text-primary); font-size: 13px; }
.asset-code {
  color: var(--text-muted);
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.05em;
  text-transform: uppercase;
}

.vehicle-info {
  display: flex;
  flex-direction: column;
  gap: 3px;
}
.vehicle-info strong { color: var(--text-primary); font-size: 13px; }
.vehicle-info span { color: var(--text-muted); font-size: 12px; }

.reg-chip {
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: 6px;
  display: inline-block;
  font-family: ui-monospace, SFMono-Regular, 'SF Mono', Menlo, Consolas, monospace;
  font-size: 12px;
  letter-spacing: 0.03em;
  padding: 3px 8px;
}

.status-row {
  align-items: center;
  display: inline-flex;
  gap: 5px;
}

.cell-value { font-weight: 600; }

.row-actions {
  display: inline-flex;
  gap: 4px;
}

.icon-action {
  align-items: center;
  background: transparent;
  border: 1px solid transparent;
  border-radius: 8px;
  color: var(--text-muted);
  cursor: pointer;
  display: inline-flex;
  height: 32px;
  justify-content: center;
  transition: background 0.15s ease, color 0.15s ease;
  width: 32px;
}
.icon-action svg { height: 16px; width: 16px; }
.icon-action:hover { background: var(--accent-soft); color: var(--accent); }
.icon-action.danger:hover { background: var(--danger-soft); color: var(--danger); }
.icon-action:focus-visible {
  outline: 2px solid var(--accent);
  outline-offset: 1px;
}

/* Skeletons */
.skeleton {
  background: var(--surface-2);
  border-radius: 6px;
  display: block;
  height: 11px;
  overflow: hidden;
  position: relative;
}

.skeleton::after {
  animation: shimmer 1.4s infinite;
  background: linear-gradient(90deg, transparent, rgb(255 255 255 / 70%), transparent);
  content: '';
  inset: 0;
  position: absolute;
  transform: translateX(-100%);
}

.skel-avatar {
  border-radius: 9px;
  flex: 0 0 auto;
  height: 34px;
  width: 34px;
}
.skel-badge {
  border-radius: 999px;
  height: 20px;
  width: 84px;
}
.w-45 { width: 45%; }
.w-55 { width: 55%; }
.w-70 { width: 70%; }

/* Pagination footer */
.table-pagination {
  border-top: 1px solid var(--border);
  padding: 12px 16px;
}

/* ---------- Empty state ---------- */

.empty-state {
  padding: 56px 24px;
  text-align: center;
}

.empty-icon {
  align-items: center;
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: 18px;
  color: var(--text-muted);
  display: flex;
  height: 64px;
  justify-content: center;
  margin: 0 auto 14px;
  width: 64px;
}
.empty-icon svg { height: 28px; width: 28px; }

.empty-state h2 { font-size: 17px; margin: 0 0 8px; }
.empty-state p {
  color: var(--text-muted);
  margin: 0 auto 22px;
  max-width: 380px;
}

.empty-actions {
  align-items: center;
  display: flex;
  gap: 10px;
  justify-content: center;
  flex-wrap: wrap;
}

/* ---------- Animations ---------- */

@keyframes spin { to { transform: rotate(360deg); } }
@keyframes shimmer { to { transform: translateX(100%); } }
@keyframes progress-slide {
  0% { transform: translateX(-100%); }
  100% { transform: translateX(350%); }
}

/* ---------- Responsive ---------- */

@media (max-width: 900px) {
  .toolbar-card { align-items: stretch; flex-direction: column; }
  .seg { overflow-x: auto; scrollbar-width: none; width: 100%; }
  .toolbar-card .btn-light { align-self: flex-start; }
}

@media (max-width: 640px) {
  .table-card .col-type { display: none; }
  .table-card table { min-width: 720px; }
  .empty-state { padding: 40px 16px; }
}
</style>