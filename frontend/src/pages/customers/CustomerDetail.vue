<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { getCustomer } from '@/services/customerService'
import { money } from '@/utils/money'
import { statusLabel } from '@/utils/jobStatus'
import { estimateStatusLabel, estimateStatusClass } from '@/utils/estimateStatus'
import { avatarStyle, initialOf } from '@/utils/avatar'

const route = useRoute()
const customer = ref(null)
const loading = ref(true)
const error = ref('')

const load = async () => {
  loading.value = true
  error.value = ''
  try {
    const { data } = await getCustomer(route.params.id)
    customer.value = data.data
  } catch (e) {
    error.value = e.response?.data?.message || 'Could not load customer.'
  } finally {
    loading.value = false
  }
}
onMounted(load)

const icons = {
  phone: '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>',
  mail: '<rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>',
  building: '<path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/><path d="M10 18h4"/>',
  pin: '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
  file: '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M16 13H8"/><path d="M16 17H8"/>',
  truck: '<path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.62l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/>',
  activity: '<path d="M22 12h-2.48a2 2 0 0 0-1.93 1.46l-2.35 8.36a.25.25 0 0 1-.48 0L9.24 2.18a.25.25 0 0 0-.48 0l-2.35 8.36A2 2 0 0 1 4.49 12H2"/>',
}

const MAX_TABLE_ROWS = 5
const MAX_ACTIVITY = 8

const initial = computed(() => initialOf(customer.value?.name))

const estimates = computed(() => customer.value?.estimates || [])
const jobs = computed(() => customer.value?.jobs || [])
const activities = computed(() => customer.value?.activities || [])

const shownEstimates = computed(() => estimates.value.slice(0, MAX_TABLE_ROWS))
const shownJobs = computed(() => jobs.value.slice(0, MAX_TABLE_ROWS))
const shownActivities = computed(() => activities.value.slice(0, MAX_ACTIVITY))

const contactRows = computed(() => {
  const c = customer.value || {}
  return [
    {
      key: 'phone',
      label: 'Phone',
      icon: icons.phone,
      value: c.phone,
      href: c.phone ? `tel:${c.phone}` : null,
    },
    {
      key: 'email',
      label: 'Email',
      icon: icons.mail,
      value: c.email,
      href: c.email ? `mailto:${c.email}` : null,
    },
    { key: 'company', label: 'Company', icon: icons.building, value: c.company },
    { key: 'address', label: 'Address', icon: icons.pin, value: c.address },
  ]
})

const jobCost = (job) => Number(job.cost_price || 0) + Number(job.extra_costs || 0)
const when = (value) => (value ? new Date(value).toLocaleDateString() : '—')
const whenTime = (value) => (value ? new Date(value).toLocaleString() : '—')
</script>

<template>
  <div class="customer-detail">
    <!-- Error -->
    <div v-if="error" class="card detail-error" role="alert">
      <div>
        <strong>Couldn't load this customer.</strong>
        <p>{{ error }}</p>
      </div>
      <button type="button" class="btn" @click="load">Try again</button>
    </div>

    <!-- Skeleton -->
    <div v-else-if="loading && !customer" class="detail-skeleton" aria-hidden="true">
      <div class="sk" style="height: 170px"></div>
      <div class="sk" style="height: 420px"></div>
    </div>

    <template v-else-if="customer">
      <!-- ============ HERO: identity + stats in one card ============ -->
      <header class="card hero-card">
        <div class="hero-top">
          <div class="hero-id">
            <span class="hero-avatar" :style="avatarStyle(customer.name)" aria-hidden="true">
              {{ initial }}
            </span>
            <div class="hero-copy">
              <span class="section-kicker">Customer</span>
              <h1>{{ customer.name }}</h1>
              <p v-if="customer.company" class="hero-company">{{ customer.company }}</p>
            </div>
          </div>

          <div class="hero-actions">
            <RouterLink class="btn-light" :to="`/customers/${customer.id}/edit`">
              Edit customer
            </RouterLink>
            <RouterLink class="btn" :to="`/estimates/create?customer_id=${customer.id}`">
              New estimate
            </RouterLink>
          </div>
        </div>

        <div class="hero-stats">
          <div class="hero-stat">
            <span>Jobs</span>
            <strong>{{ customer.job_count ?? 0 }}</strong>
          </div>
          <div class="hero-stat">
            <span>Revenue</span>
            <strong>{{ money(customer.revenue) }}</strong>
          </div>
          <div class="hero-stat">
            <span>Actual cost</span>
            <strong>{{ money(customer.cost) }}</strong>
          </div>
          <div class="hero-stat">
            <span>Profit</span>
            <strong :class="Number(customer.profit) < 0 ? 'money-loss' : 'money-profit'">
              {{ money(customer.profit) }}
            </strong>
          </div>
        </div>
      </header>

      <!-- ============ Two-column: sidebar + blocks ============ -->
      <div class="detail-grid">
        <aside class="detail-side">
          <section class="card side-card">
            <h2 class="side-title">Contact</h2>
            <ul class="contact-list">
              <li v-for="row in contactRows" :key="row.key">
                <span class="contact-icon" aria-hidden="true">
                  <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    v-html="row.icon"
                  />
                </span>
                <div class="contact-body">
                  <span class="contact-label">{{ row.label }}</span>
                  <a v-if="row.href" class="contact-value is-link" :href="row.href">
                    {{ row.value }}
                  </a>
                  <span v-else-if="row.value" class="contact-value">{{ row.value }}</span>
                  <span v-else class="contact-value is-empty">—</span>
                </div>
              </li>
            </ul>
          </section>

          <section class="card side-card">
            <h2 class="side-title">Notes</h2>
            <p v-if="customer.notes" class="notes-text">{{ customer.notes }}</p>
            <p v-else class="notes-empty">No notes for this customer yet.</p>
          </section>
        </aside>

        <div class="detail-main">
          <!-- ===== Estimates block ===== -->
          <section class="card block-card">
            <header class="block-head">
              <div class="block-title">
                <span class="block-icon icon-accent" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" v-html="icons.file" />
                </span>
                <h2>Estimates</h2>
                <span v-if="estimates.length" class="count-badge">{{ estimates.length }}</span>
              </div>
              <RouterLink v-if="estimates.length" class="block-link" to="/estimates">
                View all →
              </RouterLink>
            </header>

            <div v-if="estimates.length" class="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th>Code</th>
                    <th>Date</th>
                    <th class="right">Amount</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="estimate in shownEstimates" :key="estimate.id">
                    <td>
                      <RouterLink class="row-code" :to="`/estimates/${estimate.id}`">
                        {{ estimate.code }}
                      </RouterLink>
                    </td>
                    <td>{{ when(estimate.estimate_date) }}</td>
                    <td class="right">{{ money(estimate.estimated_sell) }}</td>
                    <td>
                      <span class="status" :class="estimateStatusClass(estimate.status)">
                        {{ estimateStatusLabel(estimate.status) }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
              <p v-if="estimates.length > shownEstimates.length" class="block-more">
                Showing {{ shownEstimates.length }} of {{ estimates.length }}
                <RouterLink class="block-link" to="/estimates">View all</RouterLink>
              </p>
            </div>

            <div v-else class="block-empty">
              <p>No estimates for this customer yet.</p>
              <RouterLink
                class="block-link"
                :to="`/estimates/create?customer_id=${customer.id}`"
              >
                Create estimate →
              </RouterLink>
            </div>
          </section>

          <!-- ===== Jobs block ===== -->
          <section class="card block-card">
            <header class="block-head">
              <div class="block-title">
                <span class="block-icon icon-violet" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" v-html="icons.truck" />
                </span>
                <h2>Jobs</h2>
                <span v-if="jobs.length" class="count-badge">{{ jobs.length }}</span>
              </div>
              <RouterLink v-if="jobs.length" class="block-link" to="/jobs">View all →</RouterLink>
            </header>

            <div v-if="jobs.length" class="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th>Job</th>
                    <th>Status</th>
                    <th class="right">Revenue</th>
                    <th class="right">Cost</th>
                    <th class="right">Profit</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="job in shownJobs" :key="job.id">
                    <td>
                      <RouterLink class="row-code" :to="`/jobs/${job.id}`">{{ job.code }}</RouterLink>
                    </td>
                    <td>
                      <span class="status" :class="`status-${job.status}`">
                        {{ statusLabel(job.status) }}
                      </span>
                    </td>
                    <td class="right">{{ money(job.sell_price) }}</td>
                    <td class="right">{{ money(jobCost(job)) }}</td>
                    <td
                      class="right"
                      :class="Number(job.final_profit) < 0 ? 'money-loss' : 'money-profit'"
                    >
                      {{ money(job.final_profit) }}
                    </td>
                  </tr>
                </tbody>
              </table>
              <p v-if="jobs.length > shownJobs.length" class="block-more">
                Showing {{ shownJobs.length }} of {{ jobs.length }}
                <RouterLink class="block-link" to="/jobs">View all</RouterLink>
              </p>
            </div>

            <div v-else class="block-empty">
              <p>No jobs yet — convert an estimate to start one.</p>
              <RouterLink
                class="block-link"
                :to="`/estimates/create?customer_id=${customer.id}`"
              >
                Create estimate →
              </RouterLink>
            </div>
          </section>

          <!-- ===== Activity block ===== -->
          <section class="card block-card">
            <header class="block-head">
              <div class="block-title">
                <span class="block-icon icon-info" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" v-html="icons.activity" />
                </span>
                <h2>Activity</h2>
                <span v-if="activities.length" class="count-badge">{{ activities.length }}</span>
              </div>
            </header>

            <ul v-if="activities.length" class="activity-timeline">
              <li v-for="item in shownActivities" :key="item.id">
                <span class="timeline-dot" aria-hidden="true"></span>
                <div class="timeline-body">
                  <b>{{ item.description }}</b>
                  <span class="timeline-meta">
                    {{ item.author || 'system' }} · {{ whenTime(item.created_at) }}
                  </span>
                </div>
              </li>
            </ul>
            <div v-else class="block-empty">
              <p>No activity recorded for this customer yet.</p>
            </div>
          </section>
        </div>
      </div>
    </template>
  </div>
</template>

<style scoped>
.customer-detail {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

/* ---------- Hero ---------- */
.hero-card { padding: 24px; }

.hero-top {
  align-items: center;
  display: flex;
  gap: 16px;
  justify-content: space-between;
}

.hero-id {
  align-items: center;
  display: flex;
  gap: 16px;
  min-width: 0;
}

.hero-avatar {
  align-items: center;
  border-radius: 50%;
  box-shadow: 0 4px 12px rgb(16 24 40 / 14%);
  color: #fff;
  display: inline-flex;
  flex: 0 0 56px;
  font-size: 21px;
  font-weight: 700;
  height: 56px;
  justify-content: center;
  width: 56px;
}

.hero-copy { min-width: 0; }
.hero-copy h1 {
  font-size: 22px;
  font-weight: 700;
  letter-spacing: -0.02em;
  margin: 2px 0 0;
}
.hero-company { color: var(--text-secondary); font-size: 14px; margin: 2px 0 0; }

.hero-actions {
  align-items: center;
  display: flex;
  flex: 0 0 auto;
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
  padding: 0 20px;
}
.hero-stat:first-child { border-left: 0; padding-left: 0; }
.hero-stat span {
  color: var(--text-muted);
  font-size: 12px;
  font-weight: 500;
}
.hero-stat strong {
  font-size: 20px;
  font-variant-numeric: tabular-nums;
  font-weight: 700;
  letter-spacing: -0.01em;
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

/* ---------- Sidebar cards ---------- */
.side-card { padding: 20px; }
.side-title {
  color: var(--text-muted);
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.08em;
  margin: 0 0 14px;
  text-transform: uppercase;
}

.contact-list {
  display: flex;
  flex-direction: column;
  gap: 14px;
  list-style: none;
  margin: 0;
  padding: 0;
}

.contact-list li { display: flex; gap: 12px; }

.contact-icon {
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
.contact-icon svg { height: 15px; width: 15px; }

.contact-body { display: flex; flex-direction: column; gap: 1px; min-width: 0; }
.contact-label { color: var(--text-muted); font-size: 11px; font-weight: 500; }
.contact-value {
  color: var(--text-primary);
  font-size: 14px;
  font-weight: 500;
  overflow-wrap: anywhere;
}
.contact-value.is-link { color: var(--accent); text-decoration: none; }
.contact-value.is-link:hover { text-decoration: underline; }
.contact-value.is-empty { color: var(--text-muted); font-weight: 400; }

.notes-text {
  color: var(--text-secondary);
  font-size: 14px;
  line-height: 1.6;
  margin: 0;
  white-space: pre-line;
}
.notes-empty { color: var(--text-muted); font-size: 13px; margin: 0; }

/* ---------- Blocks ---------- */
.block-card { padding: 20px; }

.block-head {
  align-items: center;
  display: flex;
  justify-content: space-between;
  margin-bottom: 14px;
}

.block-title { align-items: center; display: flex; gap: 10px; }
.block-title h2 { font-size: 15px; font-weight: 600; margin: 0; }

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
.icon-violet { background: var(--violet-soft); color: var(--violet); }
.icon-info { background: var(--info-soft); color: var(--info); }

.count-badge {
  align-items: center;
  background: var(--surface-2);
  border-radius: 999px;
  color: var(--text-secondary);
  display: inline-flex;
  font-size: 11px;
  font-weight: 600;
  height: 20px;
  justify-content: center;
  min-width: 20px;
  padding: 0 7px;
}

.block-link {
  color: var(--accent);
  font-size: 13px;
  font-weight: 600;
  text-decoration: none;
  white-space: nowrap;
}
.block-link:hover { text-decoration: underline; }

.row-code { color: var(--text-primary); font-weight: 600; text-decoration: none; }
.row-code:hover { color: var(--accent); }

.block-more {
  color: var(--text-muted);
  display: flex;
  font-size: 12px;
  gap: 8px;
  margin: 12px 0 0;
}

.block-empty {
  border: 1px dashed var(--border-strong);
  border-radius: var(--radius-md);
  color: var(--text-muted);
  display: flex;
  flex-direction: column;
  font-size: 13px;
  gap: 6px;
  padding: 18px 16px;
}
.block-empty p { margin: 0; }

/* ---------- Activity timeline ---------- */
.activity-timeline {
  list-style: none;
  margin: 4px 0 0;
  padding: 0;
}

.activity-timeline li {
  display: grid;
  gap: 14px;
  grid-template-columns: 10px minmax(0, 1fr);
  padding-bottom: 18px;
  position: relative;
}
.activity-timeline li:last-child { padding-bottom: 0; }

.activity-timeline li:not(:last-child)::before {
  background: var(--border);
  bottom: 2px;
  content: '';
  left: 4px;
  position: absolute;
  top: 14px;
  width: 2px;
}

.timeline-dot {
  background: var(--accent);
  border: 3px solid var(--accent-soft);
  border-radius: 50%;
  height: 12px;
  margin-top: 3px;
  width: 12px;
}

.timeline-body { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
.timeline-body b { color: var(--text-primary); font-size: 13.5px; font-weight: 600; }
.timeline-meta { color: var(--text-muted); font-size: 12px; }

/* ---------- Error & skeleton ---------- */
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
  .detail-grid { grid-template-columns: 1fr; }
  .detail-side { position: static; }

  .hero-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); row-gap: 18px; }
  .hero-stat:nth-child(odd) { border-left: 0; padding-left: 0; }
  .hero-stat:nth-child(n + 3) { padding-top: 0; }
}

@media (max-width: 640px) {
  .hero-top {
    align-items: flex-start;
    flex-direction: column;
  }

  .hero-actions { width: 100%; }
  .hero-actions .btn,
  .hero-actions .btn-light { flex: 1; justify-content: center; }

  .hero-avatar { flex-basis: 48px; font-size: 18px; height: 48px; width: 48px; }
  .hero-copy h1 { font-size: 20px; }
  .hero-stat strong { font-size: 18px; }

  .detail-error { align-items: flex-start; flex-direction: column; }
}
</style>