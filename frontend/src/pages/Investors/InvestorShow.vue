<script setup>
import { computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useInvestorStore } from '@/stores/investorStore'
import { useInvestmentStore } from '@/stores/investmentStore'
import { useLoanStore } from '@/stores/loanStore'
import { money } from '@/utils/money'
import Pagination from '@/components/ui/Pagination.vue'
import FinanceStatus from '@/components/ui/FinanceStatus.vue'
import InfoTip from '@/components/ui/InfoTip.vue'
import { avatarStyle, initialOf } from '@/utils/avatar'
import { toneFor } from '@/utils/tone'

const route = useRoute()
const investorStore = useInvestorStore()
const investmentStore = useInvestmentStore()
const loanStore = useLoanStore()

const { investor, loading, error } = storeToRefs(investorStore)
const {
  investments,
  investorInvestmentTotals,
  loading: investmentsLoading,
  error: investmentsError,
} = storeToRefs(investmentStore)
const {
  investorLoans,
  investorLoanTotals,
  investorPagination,
  investorLoansLoading,
  investorLoansError,
} = storeToRefs(loanStore)

const load = async () => {
  await Promise.all([
    investorStore.fetchInvestor(route.params.id),
    investmentStore.fetchInvestorInvestments(route.params.id),
    loanStore.fetchInvestorLoans(route.params.id),
  ])
}

const loadLoanPage = (page) => loanStore.fetchInvestorLoans(route.params.id, { page })

const totalInvested = computed(() => Number(investorInvestmentTotals.value.total || 0))

const poolShare = computed(() => {
  const pool = Number(investorInvestmentTotals.value.pool || 0)
  if (totalInvested.value <= 0) return 0
  return Math.round((pool / totalInvested.value) * 100)
})

const hasOverdueLoans = computed(() => Number(investorLoanTotals.value.overdue || 0) > 0)

const returnText = (investment) =>
  investment.return_type === 'percentage'
    ? `${investment.return_percentage}%`
    : money(investment.fixed_return_amount)

const contactRows = computed(() => {
  const inv = investor.value || {}
  return [
    { key: 'email', label: 'Email', value: inv.email, href: inv.email ? `mailto:${inv.email}` : null, icon: '<rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>' },
    { key: 'phone', label: 'Phone', value: inv.phone, href: inv.phone ? `tel:${inv.phone}` : null, icon: '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>' },
    { key: 'address', label: 'Address', value: inv.address, icon: '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>' },
    { key: 'code', label: 'Investor code', value: inv.investor_code, icon: '<path d="M9 9h.01"/><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M9 15h.01"/><path d="M15 9h.01"/><path d="M15 15h.01"/><path d="M12 12h.01"/>' },
  ]
})

onMounted(() => {
  load().catch(() => {})
})
</script>

<template>
  <div class="investor-detail">
    <!-- Error -->
    <div v-if="error" class="card detail-error" role="alert">
      <div>
        <strong>Couldn't load this investor.</strong>
        <p>{{ error }}</p>
      </div>
      <button type="button" class="btn" @click="load">Try again</button>
    </div>

    <!-- Skeleton -->
    <div v-else-if="loading && !investor" class="detail-skeleton" aria-hidden="true">
      <div class="sk" style="height: 230px"></div>
      <div class="sk" style="height: 420px"></div>
    </div>

    <template v-else-if="investor">
      <!-- ============ HERO ============ -->
      <header class="card hero-card">
        <div class="hero-top">
          <div class="hero-id">
            <span class="hero-avatar" :style="avatarStyle(investor.name)" aria-hidden="true">
              {{ initialOf(investor.name) }}
            </span>
            <div class="hero-copy">
              <span class="section-kicker">Capital / Investors</span>
              <div class="hero-title-row">
                <h1>{{ investor.name }}</h1>
                <FinanceStatus v-if="investor.status" :status="investor.status" kind="investor" />
              </div>
              <p class="hero-code">{{ investor.investor_code || '—' }}</p>
            </div>
          </div>

          <div class="hero-actions">
            <RouterLink class="btn-light" :to="`/investors/${investor.id}/edit`">Edit</RouterLink>
            <RouterLink class="btn-light" :to="`/loans/create?investor_id=${investor.id}`">
              Add loan
            </RouterLink>
            <RouterLink class="btn" :to="`/investments/create?investor_id=${investor.id}`">
              Add investment
            </RouterLink>
          </div>
        </div>

        <div class="hero-stats">
          <div class="hero-stat">
            <span>
              Total invested
              <InfoTip label="All capital this investor has placed with the company." />
            </span>
            <strong>{{ money(investorInvestmentTotals.total) }}</strong>
          </div>
          <div class="hero-stat">
            <span>
              Pool capital
              <InfoTip label="Capital in the shared pool — it gets spread across transport jobs automatically." />
            </span>
            <strong>{{ money(investorInvestmentTotals.pool) }}</strong>
          </div>
          <div class="hero-stat">
            <span>
              Direct capital
              <InfoTip label="Standalone placements, each tracked with its own return and maturity." />
            </span>
            <strong>{{ money(investorInvestmentTotals.normal) }}</strong>
          </div>
          <div class="hero-stat">
            <span>Investments</span>
            <strong>{{ investments.length }}</strong>
          </div>
          <div class="hero-stat">
            <span>
              Loans outstanding
              <InfoTip label="Money still to repay on this investor's loans. Loans stay separate from investment capital." />
            </span>
            <strong :class="hasOverdueLoans ? 'money-loss' : undefined">
              {{ money(investorLoanTotals.outstanding) }}
            </strong>
          </div>
        </div>
      </header>

      <!-- ============ Grid ============ -->
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
            <h2 class="side-title">Capital mix</h2>
            <template v-if="totalInvested > 0">
              <div
                class="mix-bar"
                role="img"
                :aria-label="`Pool ${poolShare}%, direct ${100 - poolShare}%`"
              >
                <span class="mix-pool" :style="{ width: `${poolShare}%` }"></span>
                <span class="mix-direct" :style="{ width: `${100 - poolShare}%` }"></span>
              </div>
              <ul class="mix-legend">
                <li>
                  <span class="mix-dot pool" aria-hidden="true"></span>
                  <span class="mix-label">
                    Pool
                    <InfoTip label="Capital spread automatically across jobs." />
                  </span>
                  <strong>{{ money(investorInvestmentTotals.pool) }}</strong>
                </li>
                <li>
                  <span class="mix-dot direct" aria-hidden="true"></span>
                  <span class="mix-label">
                    Direct
                    <InfoTip label="Standalone placements with their own terms." />
                  </span>
                  <strong>{{ money(investorInvestmentTotals.normal) }}</strong>
                </li>
              </ul>
            </template>
            <p v-else class="mix-empty">No capital placed yet.</p>
          </section>

          <section v-if="investor.notes" class="card side-card">
            <h2 class="side-title">Notes</h2>
            <p class="notes-text">{{ investor.notes }}</p>
          </section>
        </aside>

        <div class="detail-main">
          <!-- ===== Investments block ===== -->
          <section class="card block-card">
            <header class="block-head">
              <div class="block-title">
                <span class="block-icon icon-accent" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="22 7 13.5 15.5 8.5 10.5 2 17" /><polyline points="16 7 22 7 22 13" />
                  </svg>
                </span>
                <div>
                  <h2>Investments</h2>
                  <p class="block-hint">Capital placements earning a return.</p>
                </div>
              </div>
              <div class="block-tools">
                <span v-if="investments.length" class="count-badge">{{ investments.length }}</span>
                <RouterLink
                  class="btn-light btn-sm"
                  :to="`/investments/create?investor_id=${investor.id}`"
                >
                  + Add
                </RouterLink>
              </div>
            </header>

            <div v-if="investmentsLoading" class="block-loading">
              <div class="sk" style="height: 140px"></div>
            </div>
            <div v-else-if="investmentsError" class="block-error">
              <p>{{ investmentsError }}</p>
              <button type="button" class="btn-light btn-sm" @click="load">Try again</button>
            </div>
            <div v-else-if="!investments.length" class="block-empty">
              <p>No investments yet.</p>
              <RouterLink
                class="block-link"
                :to="`/investments/create?investor_id=${investor.id}`"
              >
                Add the first investment →
              </RouterLink>
            </div>
            <div v-else class="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th>Code</th>
                    <th>Category</th>
                    <th class="right">
                      Principal
                      <InfoTip label="The amount of capital originally placed." />
                    </th>
                    <th>
                      Return
                      <InfoTip label="How the investment earns — a percentage share of profit, or a fixed amount." />
                    </th>
                    <th>
                      Maturity
                      <InfoTip label="When the investment term ends." />
                    </th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="investment in investments" :key="investment.id">
                    <td>
                      <RouterLink
                        class="row-code"
                        :to="`/investments/${investment.id}`"
                      >
                        {{ investment.investment_code }}
                      </RouterLink>
                    </td>
                    <td>
                      <span
                        v-if="investment.investment_category"
                        class="badge"
                        :class="`tone-${toneFor(investment.investment_category)}`"
                      >
                        {{ investment.investment_category }}
                      </span>
                      <span v-else>—</span>
                    </td>
                    <td class="right row-amount">{{ money(investment.amount) }}</td>
                    <td>{{ returnText(investment) }}</td>
                    <td>{{ investment.maturity_date || '—' }}</td>
                    <td>
                      <FinanceStatus :status="investment.status" kind="investment" />
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </section>

          <!-- ===== Loans block ===== -->
          <section class="card block-card">
            <header class="block-head">
              <div class="block-title">
                <span class="block-icon icon-warning" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="18" height="12" x="3" y="8" rx="2" /><path d="M7 12h.01M11 12h2m4 0h.01" /><circle cx="12" cy="14" r="4" />
                  </svg>
                </span>
                <div>
                  <h2>Loans</h2>
                  <p class="block-hint">
                    Company loans to this investor — separate from investment capital.
                    <InfoTip label="Loans are money the investor owes the company. They never count toward invested capital or returns." />
                  </p>
                </div>
              </div>
              <div class="block-tools">
                <RouterLink class="btn-light btn-sm" :to="`/loans/create?investor_id=${investor.id}`">
                  + Add
                </RouterLink>
              </div>
            </header>

            <div class="loan-chips">
              <span class="loan-chip">
                <b>Outstanding</b>
                <strong :class="hasOverdueLoans ? 'money-loss' : ''">
                  {{ money(investorLoanTotals.outstanding) }}
                </strong>
              </span>
              <span class="loan-chip">
                <b>Active</b>
                <strong>{{ investorLoanTotals.active }}</strong>
              </span>
              <span class="loan-chip" :class="{ 'is-danger': hasOverdueLoans }">
                <b>Overdue</b>
                <strong>{{ investorLoanTotals.overdue }}</strong>
              </span>
              <span class="loan-chip">
                <b>Paid</b>
                <strong>{{ investorLoanTotals.paid }}</strong>
              </span>
            </div>

            <div v-if="investorLoansLoading" class="block-loading">
              <div class="sk" style="height: 140px"></div>
            </div>
            <div v-else-if="investorLoansError" class="block-error">
              <p>{{ investorLoansError }}</p>
              <button
                type="button"
                class="btn-light btn-sm"
                @click="loadLoanPage(investorPagination.current_page)"
              >
                Try again
              </button>
            </div>
            <div v-else-if="!investorLoans.length" class="block-empty">
              <p>No loans linked to this investor.</p>
              <RouterLink class="block-link" :to="`/loans/create?investor_id=${investor.id}`">
                Add a loan →
              </RouterLink>
            </div>
            <template v-else>
              <div class="table-wrap">
                <table class="loan-table">
                  <thead>
                    <tr>
                      <th>Loan</th>
                      <th class="right">Principal</th>
                      <th class="right">Repaid</th>
                      <th class="right">
                        Outstanding
                        <InfoTip label="Principal minus everything repaid so far." />
                      </th>
                      <th>Due date</th>
                      <th>Status</th>
                      <th class="right">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="loan in investorLoans" :key="loan.id">
                      <td>
                        <RouterLink class="row-code" :to="`/loans/${loan.id}`">
                          {{ loan.loan_code }}
                        </RouterLink>
                      </td>
                      <td class="right">{{ money(loan.amount) }}</td>
                      <td class="right">{{ money(loan.total_repaid) }}</td>
                      <td class="right row-amount">{{ money(loan.outstanding_amount) }}</td>
                      <td :class="{ 'due-overdue': loan.status === 'overdue' }">
                        {{ loan.due_date }}
                      </td>
                      <td>
                        <FinanceStatus :status="loan.status" kind="loan" />
                      </td>
                      <td class="right">
                        <RouterLink
                          class="icon-action"
                          :to="`/loans/${loan.id}`"
                          title="Open loan"
                          aria-label="Open loan"
                        >
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M5 12h14" /><path d="m12 5 7 7-7 7" />
                          </svg>
                        </RouterLink>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <Pagination
                :page="investorPagination.current_page"
                :last-page="investorPagination.last_page"
                :total="investorPagination.total"
                :per-page="investorPagination.per_page"
                @update:page="loadLoanPage"
              />
            </template>
          </section>
        </div>
      </div>

      <div class="detail-footer">
        <RouterLink class="btn-light" to="/investors">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M19 12H5" /><path d="m12 19-7-7 7-7" />
          </svg>
          Back to investors
        </RouterLink>
      </div>
    </template>
  </div>
</template>

<style scoped>
.investor-detail {
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

.hero-code {
  color: var(--text-muted);
  font-size: 13px;
  font-weight: 500;
  letter-spacing: 0.02em;
  margin: 4px 0 0;
}

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
  grid-template-columns: repeat(5, minmax(0, 1fr));
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

/* Capital mix */
.mix-bar {
  border-radius: 999px;
  display: flex;
  height: 10px;
  overflow: hidden;
}
.mix-pool { background: var(--accent); display: block; transition: width 0.4s ease; }
.mix-direct { background: var(--violet); display: block; transition: width 0.4s ease; }

.mix-legend {
  display: flex;
  flex-direction: column;
  gap: 12px;
  list-style: none;
  margin: 14px 0 0;
  padding: 0;
}
.mix-legend li {
  align-items: center;
  display: flex;
  gap: 8px;
}
.mix-dot { border-radius: 50%; flex: 0 0 8px; height: 8px; width: 8px; }
.mix-dot.pool { background: var(--accent); }
.mix-dot.direct { background: var(--violet); }
.mix-label {
  align-items: center;
  color: var(--text-secondary);
  display: flex;
  font-size: 13px;
  flex: 1;
  gap: 5px;
}
.mix-legend strong {
  color: var(--text-primary);
  font-size: 13px;
  font-variant-numeric: tabular-nums;
  font-weight: 600;
}
.mix-empty { color: var(--text-muted); font-size: 13px; margin: 0; }

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
  margin-bottom: 14px;
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
.icon-warning { background: var(--warning-soft); color: var(--warning); }

.block-tools {
  align-items: center;
  display: flex;
  flex: 0 0 auto;
  gap: 8px;
}

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

.row-code { color: var(--text-primary); font-weight: 600; text-decoration: none; }
.row-code:hover { color: var(--accent); }
.row-amount { color: var(--text-primary); font-weight: 600; }

.block-loading,
.block-error,
.block-empty { margin-top: 4px; }

.block-error {
  align-items: center;
  background: var(--danger-soft);
  border-radius: var(--radius-md);
  color: var(--danger);
  display: flex;
  font-size: 13px;
  gap: 12px;
  justify-content: space-between;
  padding: 12px 14px;
}
.block-error p { margin: 0; }

.block-empty {
  border: 1px dashed var(--border-strong);
  border-radius: var(--radius-md);
  color: var(--text-muted);
  display: flex;
  flex-direction: column;
  font-size: 13px;
  gap: 6px;
  padding: 18px 16px;
  text-align: center;
}
.block-empty p { margin: 0; }

.block-link {
  color: var(--accent);
  font-size: 13px;
  font-weight: 600;
  text-decoration: none;
}
.block-link:hover { text-decoration: underline; }

/* ---------- Loan chips ---------- */
.loan-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 14px;
}

.loan-chip {
  background: var(--surface-2);
  border-radius: 999px;
  color: var(--text-secondary);
  display: inline-flex;
  font-size: 12px;
  gap: 6px;
  padding: 5px 12px;
}
.loan-chip b { color: var(--text-muted); font-weight: 500; }
.loan-chip strong {
  color: var(--text-primary);
  font-variant-numeric: tabular-nums;
  font-weight: 600;
}
.loan-chip.is-danger {
  background: var(--danger-soft);
}
.loan-chip.is-danger b,
.loan-chip.is-danger strong { color: var(--danger); }

.due-overdue { color: var(--danger); font-weight: 600; }

.loan-table { min-width: 760px; }

.icon-action {
  align-items: center;
  background: transparent;
  border: 1px solid transparent;
  border-radius: 8px;
  color: var(--text-muted);
  display: inline-flex;
  height: 32px;
  justify-content: center;
  transition: background 0.15s ease, color 0.15s ease;
  width: 32px;
}
.icon-action svg { height: 15px; width: 15px; }
.icon-action:hover { background: var(--accent-soft); color: var(--accent); }

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
@media (max-width: 1180px) {
  .hero-stats { grid-template-columns: repeat(3, minmax(0, 1fr)); row-gap: 18px; }
  .hero-stat:nth-child(3n + 1) { border-left: 0; padding-left: 0; }
}

@media (max-width: 1024px) {
  .detail-grid { grid-template-columns: 1fr; }
  .detail-side { position: static; }
}

@media (max-width: 700px) {
  .hero-top { flex-direction: column; }
  .hero-actions { width: 100%; }
  .hero-actions .btn,
  .hero-actions .btn-light { flex: 1; justify-content: center; }
  .hero-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .hero-stat:nth-child(odd) { border-left: 0; padding-left: 0; }
  .hero-stat:nth-child(3n + 1) { border-left: 1px solid var(--border); padding-left: 16px; }
  .detail-error { align-items: flex-start; flex-direction: column; }
  .block-error { flex-direction: column; align-items: flex-start; }
}
</style>