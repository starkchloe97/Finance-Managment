<script setup>
import { onMounted, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useRouter } from 'vue-router'
import { useInvestmentStore } from '@/stores/investmentStore'
import { money } from '@/utils/money'
import Pagination from '@/components/ui/Pagination.vue'
import StatePanel from '@/components/ui/StatePanel.vue'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
import FinanceStatus from '@/components/ui/FinanceStatus.vue'
import InfoTip from '@/components/ui/InfoTip.vue'
import { avatarStyle, initialOf } from '@/utils/avatar'
import { toneFor } from '@/utils/tone'

const router = useRouter()
const investmentStore = useInvestmentStore()
const { investments, loading, error, pagination } = storeToRefs(investmentStore)
const deleting = ref(null)

onMounted(() => investmentStore.fetchInvestments())

const createInvestment = () => router.push({ name: 'investments.create' })

// An active investment whose maturity date has passed is ready to mature —
// worth flagging visually without blocking anything.
const isMaturityDue = (investment) => {
  if (investment.status !== 'active' || !investment.maturity_date) return false
  return investment.maturity_date <= new Date().toISOString().slice(0, 10)
}

const returnText = (investment) =>
  investment.return_type === 'percentage'
    ? `${investment.return_percentage ?? 0}%`
    : money(investment.fixed_return_amount)

const deleteInvestment = async () => {
  if (!deleting.value) return
  try {
    await investmentStore.deleteInvestment(deleting.value.id)
    deleting.value = null
  } catch {
    deleting.value = null
  }
}
</script>

<template>
  <div class="entity-list-page">
    <div class="page-head">
      <div>
        <span class="section-kicker">Capital</span>
        <h1>Investments</h1>
        <p class="page-sub">
          {{ pagination.total }}
          {{ pagination.total === 1 ? 'placement' : 'placements' }} — principal, return terms, and
          capital committed to jobs.
        </p>
      </div>
      <button type="button" @click="createInvestment">+ Add investment</button>
    </div>

    <div v-if="error" class="page-error" role="alert">
      <p>{{ error }}</p>
      <button class="btn-light" type="button" @click="investmentStore.fetchInvestments()">
        Try again
      </button>
    </div>

    <section class="card list-card">
      <StatePanel
        :loading="loading && !investments.length"
        :error="''"
        :empty="!loading && !error && !investments.length"
        empty-title="No investments yet. Add a placement to start tracking capital."
        empty-action="Add investment"
        empty-to="/investments/create"
      >
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Investment</th>
                <th>Investor</th>
                <th>Category</th>
                <th class="right">
                  Principal
                  <InfoTip label="The amount of capital originally placed." />
                </th>
                <th>
                  Return
                  <InfoTip label="How the investment earns — a share of profit, or a fixed amount." />
                </th>
                <th>
                  Maturity
                  <InfoTip label="When the term ends. Amber means the date has passed and it can be matured." />
                </th>
                <th>Status</th>
                <th class="right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="investment in investments" :key="investment.id">
                <td>
                  <RouterLink class="record-link" :to="`/investments/${investment.id}`">
                    {{ investment.investment_code }}
                  </RouterLink>
                  <span class="record-code">{{ investment.investment_date || 'No date' }}</span>
                </td>
                <td>
                  <div v-if="investment.investor" class="investor-cell">
                    <span
                      class="investor-avatar"
                      :style="avatarStyle(investment.investor.name)"
                      aria-hidden="true"
                    >
                      {{ initialOf(investment.investor.name) }}
                    </span>
                    <RouterLink
                      class="investor-link"
                      :to="`/investors/${investment.investor.id}`"
                      title="View investor"
                    >
                      {{ investment.investor.name }}
                    </RouterLink>
                  </div>
                  <span v-else>—</span>
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
                <td class="right record-amount">{{ money(investment.amount) }}</td>
                <td>{{ returnText(investment) }}</td>
                <td :class="{ 'maturity-due': isMaturityDue(investment) }">
                  {{ investment.maturity_date || '—' }}
                </td>
                <td>
                  <FinanceStatus :status="investment.status" kind="investment" />
                </td>
                <td class="right">
                  <div class="row-actions">
                    <RouterLink
                      class="icon-action"
                      :to="`/investments/${investment.id}`"
                      title="Open investment"
                      aria-label="Open investment"
                    >
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M5 12h14" /><path d="m12 5 7 7-7 7" />
                      </svg>
                    </RouterLink>
                    <RouterLink
                      v-if="investment.status === 'active'"
                      class="icon-action"
                      :to="`/investments/${investment.id}/edit`"
                      title="Edit investment"
                      aria-label="Edit investment"
                    >
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z" />
                      </svg>
                    </RouterLink>
                    <button
                      v-if="investment.status === 'active'"
                      class="icon-action danger"
                      type="button"
                      title="Delete investment"
                      aria-label="Delete investment"
                      @click="deleting = investment"
                    >
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M3 6h18" /><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" /><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <Pagination
          :page="pagination.current_page"
          :last-page="pagination.last_page"
          :total="pagination.total"
          :per-page="pagination.per_page"
          @update:page="investmentStore.setPage"
        />
      </StatePanel>
    </section>

    <ConfirmDialog
      :open="Boolean(deleting)"
      title="Delete investment?"
      :message="deleting ? `Delete ${deleting.investment_code}? This cannot be undone.` : ''"
      confirm-label="Delete investment"
      variant="danger"
      @confirm="deleteInvestment"
      @cancel="deleting = null"
    />
  </div>
</template>

<style scoped>
.entity-list-page,
.list-card {
  min-width: 0;
}

.page-sub {
  color: var(--text-secondary);
  font-size: 14px;
  margin-top: var(--space-2);
}

.page-error {
  align-items: center;
  background: var(--danger-soft);
  border: 1px solid var(--danger);
  border-radius: var(--radius-md);
  display: flex;
  gap: var(--space-3);
  justify-content: space-between;
  margin-bottom: var(--space-4);
  padding: var(--space-3) var(--space-4);
}

.page-error p { color: var(--danger); }

.record-link {
  color: var(--text-primary);
  display: block;
  font-weight: 600;
  text-decoration: none;
}
.record-link:hover { color: var(--accent); }

.record-code {
  color: var(--text-muted);
  display: block;
  font-size: 12px;
  margin-top: 1px;
}

.record-amount { color: var(--text-primary); font-weight: 600; }

.maturity-due {
  color: var(--warning);
  font-weight: 600;
}

.investor-cell {
  align-items: center;
  display: flex;
  gap: 10px;
}

.investor-avatar {
  align-items: center;
  border-radius: 9px;
  color: #fff;
  display: inline-flex;
  flex: 0 0 28px;
  font-size: 11px;
  font-weight: 600;
  height: 28px;
  justify-content: center;
  width: 28px;
}

.investor-link {
  color: var(--text-secondary);
  font-size: 14px;
  text-decoration: none;
}
.investor-link:hover { color: var(--accent); }

.row-actions {
  display: inline-flex;
  gap: 4px;
  justify-content: flex-end;
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

@media (max-width: 700px) {
  .page-error {
    align-items: flex-start;
    flex-direction: column;
  }
}
</style>