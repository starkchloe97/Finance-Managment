<script setup>
import { onMounted, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useRouter } from 'vue-router'
import { useInvestmentStore } from '@/stores/investmentStore'
import { money } from '@/utils/money'
import Pagination from '@/components/ui/Pagination.vue'
import StatePanel from '@/components/ui/StatePanel.vue'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'

const router = useRouter()
const investmentStore = useInvestmentStore()
const { investments, loading, error, pagination } = storeToRefs(investmentStore)
const deleting = ref(null)

onMounted(() => investmentStore.fetchInvestments())

const createInvestment = () => router.push({ name: 'investments.create' })
const statusClass = (status) => {
  if (status === 'active') return 'status-success'
  if (status === 'cancelled') return 'status-danger'
  if (status === 'matured' || status === 'withdrawn') return 'status-warning'
  return 'status-info'
}

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
        <p class="page-subtitle">Track principal, return terms, and capital committed to jobs.</p>
      </div>
      <button type="button" @click="createInvestment">Add investment</button>
    </div>

    <div v-if="error" class="page-error" role="alert">
      <p>{{ error }}</p>
      <button class="btn-light" type="button" @click="investmentStore.fetchInvestments()">
        Try again
      </button>
    </div>

    <section class="card list-card">
      <div class="list-card-header">
        <div>
          <h2>Capital placements</h2>
          <p class="hint">
            Open an investment to review allocation, returns, and lifecycle actions.
          </p>
        </div>
        <span v-if="pagination.total" class="result-count">{{ pagination.total }} total</span>
      </div>

      <StatePanel
        :loading="loading && !investments.length"
        :error="''"
        :empty="!loading && !investments.length"
        empty-title="No investments yet. Add a placement to start tracking capital."
        empty-action="Add investment"
        empty-to="/investments/create"
      >
        <div class="table-wrap">
          <table class="investment-table">
            <thead>
              <tr>
                <th>Investment</th>
                <th>Investor</th>
                <th>Category</th>
                <th class="right">Principal</th>
                <th>Return terms</th>
                <th>Maturity</th>
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
                  <RouterLink
                    v-if="investment.investor"
                    :to="`/investors/${investment.investor.id}`"
                  >
                    {{ investment.investor.name }}
                  </RouterLink>
                  <span v-else>—</span>
                </td>
                <td class="capitalize">{{ investment.investment_category }}</td>
                <td class="right money">{{ money(investment.amount) }}</td>
                <td>
                  <span v-if="investment.return_type === 'percentage'">
                    {{ investment.return_percentage }}% percentage
                  </span>
                  <span v-else>{{ money(investment.fixed_return_amount) }} fixed</span>
                </td>
                <td>{{ investment.maturity_date || '—' }}</td>
                <td>
                  <span class="status" :class="statusClass(investment.status)">
                    {{ investment.status }}
                  </span>
                </td>
                <td class="right">
                  <div class="row-actions">
                    <RouterLink class="btn-light btn-sm" :to="`/investments/${investment.id}`">
                      Open
                    </RouterLink>
                    <RouterLink
                      v-if="investment.status === 'active'"
                      class="btn-light btn-sm"
                      :to="`/investments/${investment.id}/edit`"
                    >
                      Edit
                    </RouterLink>
                    <button
                      v-if="investment.status === 'active'"
                      class="btn-danger btn-sm"
                      type="button"
                      @click="deleting = investment"
                    >
                      Delete
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

.page-subtitle {
  color: var(--text-secondary);
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

.page-error p {
  color: var(--danger);
}

.list-card-header {
  align-items: flex-start;
  display: flex;
  justify-content: space-between;
  margin-bottom: var(--space-4);
}

.list-card-header h2 {
  font-size: var(--text-lg);
}

.result-count,
.record-code {
  color: var(--text-muted);
  font-size: var(--text-xs);
}

.result-count {
  white-space: nowrap;
}

.record-link,
.record-code {
  display: block;
}

.record-link {
  font-weight: var(--font-weight-semibold);
}

.record-code {
  margin-top: var(--space-1);
}

.row-actions {
  align-items: center;
  display: inline-flex;
  flex-wrap: wrap;
  gap: var(--space-2);
  justify-content: flex-end;
}

.capitalize {
  text-transform: capitalize;
}

@media (max-width: 700px) {
  .page-error,
  .list-card-header {
    align-items: stretch;
    flex-direction: column;
    gap: var(--space-3);
  }

  .result-count {
    align-self: flex-start;
  }

  .investment-table {
    min-width: 980px;
  }
}
</style>
