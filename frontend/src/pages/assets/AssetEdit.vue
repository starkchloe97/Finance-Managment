<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAssetStore } from '@/stores/assetStore'
import { money } from '@/utils/money'
import AssetFormFields from '@/components/assets/AssetFormFields.vue'
import InfoTip from '@/components/ui/InfoTip.vue'
import { avatarStyle, initialOf } from '@/utils/avatar'
import { useToast } from '@/composables/useToast'

const route = useRoute()
const router = useRouter()
const assetStore = useAssetStore()
const { show: showToast } = useToast()

const loading = ref(true)
const submitting = ref(false)
const loadError = ref('')
const errors = ref({})
const generalError = ref('')
const assetCode = ref('')

const form = reactive({
  asset_type: 'vehicle',
  name: '',
  make: '',
  model: '',
  model_year: '',
  registration_number: '',
  vin: '',
  engine_number: '',
  vehicle_type: '',
  color: '',
  purchase_date: '',
  purchase_price: '',
  current_value: '',
  status: 'active',
  notes: '',
})

const STATUS_PREVIEW = {
  active: { klass: 'status-success', label: 'Active' },
  maintenance: { klass: 'status-warning', label: 'Maintenance' },
  inactive: { klass: 'status-draft', label: 'Inactive' },
}

/* ---- live preview ---- */
const previewIdentity = () =>
  [form.make, form.model, form.model_year].filter(Boolean).join(' ') || 'Make and model'

const previewName = () => form.name || 'Vehicle name'

const previewMonogram = () =>
  initialOf(`${form.make || ''} ${form.model || ''}`.trim() || form.name || '·')

const previewTone = () =>
  avatarStyle(`${form.make || ''} ${form.model || ''}`.trim() || form.name || '·')

const previewStatus = computed(
  () => STATUS_PREVIEW[form.status] || { klass: 'status-draft', label: form.status },
)

const fmtMoney = (value) => (value === '' || value === null ? '—' : money(value))

/* ---- load ---- */
const loadAsset = async () => {
  loading.value = true
  loadError.value = ''
  try {
    const asset = await assetStore.fetchAsset(route.params.id)

    if (!asset) {
      loadError.value = 'Vehicle not found.'
      return
    }

    assetCode.value = asset.asset_code || ''

    Object.assign(form, {
      asset_type: asset.asset_type || 'vehicle',
      name: asset.name || '',
      make: asset.make || '',
      model: asset.model || '',
      model_year: asset.model_year || '',
      registration_number: asset.registration_number || '',
      vin: asset.vin || '',
      engine_number: asset.engine_number || '',
      vehicle_type: asset.vehicle_type || '',
      color: asset.color || '',
      purchase_date: asset.purchase_date || '',
      purchase_price: asset.purchase_price || '',
      current_value: asset.current_value || '',
      status: asset.status || 'active',
      notes: asset.notes || '',
    })
  } catch (error) {
    loadError.value = error.response?.data?.message || 'Unable to load vehicle.'
  } finally {
    loading.value = false
  }
}

/* ---- validation helpers ---- */
const fieldError = (field) => errors.value[field]?.[0] || ''

const clearFieldError = (field) => {
  if (errors.value[field]) {
    delete errors.value[field]
  }
}

/* ---- submit ---- */
const submit = async () => {
  errors.value = {}
  generalError.value = ''
  submitting.value = true

  try {
    const payload = {
      ...form,

      model_year: form.model_year === '' ? null : Number(form.model_year),
      purchase_price: form.purchase_price === '' ? null : Number(form.purchase_price),
      current_value: form.current_value === '' ? null : Number(form.current_value),

      make: form.make || null,
      model: form.model || null,
      registration_number: form.registration_number || null,
      vin: form.vin || null,
      engine_number: form.engine_number || null,
      vehicle_type: form.vehicle_type || null,
      color: form.color || null,
      purchase_date: form.purchase_date || null,
      notes: form.notes || null,
    }

    const asset = await assetStore.updateAsset(route.params.id, payload)

    showToast(`${form.name || 'Vehicle'} updated`)

    await router.push({
      name: 'assets.show',
      params: { id: asset.id },
    })
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {}
    } else {
      generalError.value = error.response?.data?.message || 'Unable to update vehicle.'
    }
  } finally {
    submitting.value = false
  }
}

const cancel = () => {
  router.push({ name: 'assets.show', params: { id: route.params.id } })
}

onMounted(loadAsset)
</script>

<template>
  <div class="asset-edit-page">
    <!-- Load error — no form until data exists (prevents saving blanks over the record) -->
    <div v-if="loadError" class="card detail-error" role="alert">
      <div>
        <strong>Couldn't load this vehicle.</strong>
        <p>{{ loadError }}</p>
      </div>
      <button type="button" class="btn" @click="loadAsset">Try again</button>
    </div>

    <!-- Skeleton -->
    <div v-else-if="loading" class="detail-skeleton" aria-hidden="true">
      <div class="sk" style="height: 90px"></div>
      <div class="sk" style="height: 280px"></div>
      <div class="sk" style="height: 220px"></div>
    </div>

    <template v-else>
      <!-- Header -->
      <div class="page-head">
        <div>
          <span class="section-kicker">Operations / Assets {{ assetCode ? `/ ${assetCode}` : '' }}</span>
          <h1>Edit vehicle</h1>
          <p class="page-sub">{{ previewName() }} — update the details in your asset register.</p>
        </div>
      </div>

      <form class="asset-form-layout" @submit.prevent="submit">
        <!-- ===== Main column ===== -->
        <div class="form-main">
          <AssetFormFields
            :form="form"
            :field-error="fieldError"
            :clear-field-error="clearFieldError"
            :submitting="submitting"
          />

          <div v-if="generalError" class="page-error" role="alert">
            {{ generalError }}
          </div>
        </div>

        <!-- ===== Sticky preview panel ===== -->
        <aside class="form-aside">
          <div class="card panel-card">
            <h2 class="panel-title">
              Preview
              <InfoTip label="Updates live as you edit — this is how the vehicle will appear in lists." />
            </h2>

            <div class="panel-preview">
              <span class="panel-monogram" :style="previewTone()" aria-hidden="true">
                {{ previewMonogram() }}
              </span>
              <div class="panel-id">
                <strong>{{ previewName() }}</strong>
                <span>{{ previewIdentity() }}</span>
              </div>
            </div>

            <div class="panel-rows">
              <div class="panel-row">
                <span>Registration</span>
                <strong>{{ form.registration_number || '—' }}</strong>
              </div>
              <div class="panel-row">
                <span>Type</span>
                <strong>{{ form.vehicle_type || '—' }}</strong>
              </div>
              <div class="panel-row">
                <span>Purchase price</span>
                <strong>{{ fmtMoney(form.purchase_price) }}</strong>
              </div>
              <div class="panel-row">
                <span>Current value</span>
                <strong>{{ fmtMoney(form.current_value) }}</strong>
              </div>
              <div class="panel-row panel-status">
                <span>Status</span>
                <span class="status" :class="previewStatus.klass">{{ previewStatus.label }}</span>
              </div>
            </div>

            <div class="panel-actions">
              <button type="submit" :disabled="submitting" :aria-busy="submitting">
                <span v-if="submitting" class="btn-spinner" aria-hidden="true"></span>
                {{ submitting ? 'Saving…' : 'Save changes' }}
              </button>
              <button type="button" class="btn-light" :disabled="submitting" @click="cancel">
                Cancel
              </button>
            </div>
          </div>
        </aside>
      </form>
    </template>
  </div>
</template>

<style scoped>
.asset-edit-page {
  display: flex;
  flex-direction: column;
  gap: 20px;
  min-width: 0;
}

.page-sub {
  color: var(--text-secondary);
  font-size: 14px;
  margin-top: var(--space-2);
}

/* ---------- Layout ---------- */
.asset-form-layout {
  align-items: start;
  display: grid;
  gap: 20px;
  grid-template-columns: minmax(0, 1fr) 300px;
}

.form-main {
  display: flex;
  flex-direction: column;
  gap: 20px;
  min-width: 0;
}

.form-aside {
  position: sticky;
  top: 20px;
}

/* ---------- Error banner ---------- */
.page-error {
  background: var(--danger-soft);
  border: 1px solid var(--danger);
  border-radius: var(--radius-md);
  color: var(--danger);
  font-size: 13px;
  padding: 11px 14px;
}

/* ---------- Preview panel ---------- */
.panel-card { padding: 18px; }

.panel-title {
  align-items: center;
  display: flex;
  font-size: 15px;
  font-weight: 600;
  gap: 5px;
  margin: 0 0 14px;
}

.panel-preview {
  align-items: center;
  display: flex;
  gap: 12px;
  margin-bottom: 12px;
}

.panel-monogram {
  align-items: center;
  border-radius: 10px;
  color: #fff;
  display: inline-flex;
  flex: 0 0 40px;
  font-size: 13px;
  font-weight: 700;
  height: 40px;
  justify-content: center;
  width: 40px;
}

.panel-id {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}
.panel-id strong {
  color: var(--text-primary);
  font-size: 14px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.panel-id span {
  color: var(--text-muted);
  font-size: 12px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.panel-row {
  align-items: center;
  border-bottom: 1px solid var(--border);
  display: flex;
  font-size: 13px;
  justify-content: space-between;
  padding: 8px 0;
}
.panel-row span { color: var(--text-muted); font-size: 12px; }
.panel-row strong {
  color: var(--text-primary);
  font-variant-numeric: tabular-nums;
  font-weight: 600;
}
.panel-row.panel-status { border-bottom: 0; }

.panel-actions {
  border-top: 1px solid var(--border);
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-top: 12px;
  padding-top: 14px;
}

.btn-spinner {
  animation: spin 0.7s linear infinite;
  border: 2px solid rgb(255 255 255 / 40%);
  border-radius: 50%;
  border-top-color: #fff;
  display: inline-block;
  height: 13px;
  margin-right: 6px;
  vertical-align: -2px;
  width: 13px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* ---------- Load error / skeleton ---------- */
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

/* ---------- Responsive ---------- */
@media (max-width: 1024px) {
  .asset-form-layout { grid-template-columns: 1fr; }

  /* Panel becomes a sticky bottom bar — Save always reachable */
  .form-aside {
    bottom: 0;
    position: sticky;
    top: auto;
    z-index: 10;
  }

  .panel-card {
    border-radius: var(--radius-lg) var(--radius-lg) 0 0;
    box-shadow: var(--shadow-md);
    padding: 12px 16px;
  }

  .panel-title,
  .panel-preview,
  .panel-rows { display: none; }

  .panel-actions {
    border-top: 0;
    flex-direction: row;
    margin-top: 0;
    padding-top: 0;
  }
  .panel-actions button,
  .panel-actions .btn-light { flex: 1; }
}
</style>