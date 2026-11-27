<script setup>
import { onMounted, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useRouter } from 'vue-router'
import { useInvestmentStore } from '@/stores/investmentStore'
import { ensureMyInvestor } from '@/services/myInvestorService'
import { money } from '@/utils/money'
import AllocationForm from '@/components/AllocationForm.vue'
import FinanceStatus from '@/components/ui/FinanceStatus.vue'
import Pagination from '@/components/ui/Pagination.vue'
import StatePanel from '@/components/ui/StatePanel.vue'

const router = useRouter()
const investmentStore = useInvestmentStore()
const { investments, investorInvestmentTotals, loading, error, pagination } =
  storeToRefs(investmentStore)

const investor = ref(null)
const profileError = ref('')
const allocatingId = ref(null)

const load = async (page = 1) => {
  profileError.value = ''

  try {
    if (!investor.value) {
      const response = await ensureMyInvestor()
      investor.value = response.data
    }

    await investmentStore.fetchInvestorInvestments(investor.value.id, { page })
  } catch (requestError) {
    profileError.value =
      requestError.response?.data?.message || 'Could not load your investment profile.'
  }
}

const createInvestment = () => {
  router.push({
    name: 'investments.create',
    query: { investor_id: investor.value.id, owner: '1' },
  })
}

const allocationCreated = async () => {
  allocatingId.value = null
  await load(pagination.value.current_page)
}

const canAllocate = (investment) =>
  investment.status === 'active' &&
  investment.investment_category === 'normal' &&
  Number(investment.remaining_capital) > 0

onMounted(() => load())
</script>

<template>
  <div class="entity-list-page">
    <div class="page-head">
      <div>
        <span class="section-kicker">Capital</span>
        <h1>My investments</h1>
        <p class="page-sub">
          Your owner investments, returns, and capital allocated to transport jobs.
        </p>
      </div>
      <button v-if="investor" type="button" @click="createInvestment">
        + Create investment
      </button>
    </div>

    <div v-if="profileError || error" class="page-error" role="alert">
      <p>{{ profileError || error }}</p>
      <button class="btn-light" type="button" @click="load(pagination.current_page)">
        Try again
      </button>
    </div>

    <div v-if="investor" class="summary-grid">
      <article class="card summary-card">
        <span>Total owner capital</span>
        <strong>{{ money(investorInvestmentTotals.total) }}</strong>
      </article>
      <article class="card summary-card">
        <span>Normal investments</span>
        <strong>{{ money(investorInvestmentTotals.normal) }}</strong>
      </article>
      <article class="card summary-card">
        <span>Pool investments</span>
        <strong>{{ money(investorInvestmentTotals.pool) }}</strong>
      </article>
    </div>

    <section class="card list-card">
      <StatePanel
        :loading="loading && !investments.length"
        :error="''"
        :empty="!loading && !profileError && !error && !investments.length"
        empty-title="No owner investments yet. Create a custom investment to get started."
      >
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Investment</th>
                <th>Category</th>
                <th class="right">Principal</th>
                <th class="right">Allocated</th>
                <th class="right">Remaining</th>
                <th>Status</th>
                <th class="right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <template v-for="investment in investments" :key="investment.id">
                <tr>
                  <td>
                    <RouterLink class="record-link" :to="`/investments/${investment.id}`">
                      {{ investment.investment_code }}
                    </RouterLink>
                    <span class="record-code">{{ investment.investment_date || 'No date' }}</span>
                  </td>
                  <td>{{ investment.investment_category }}</td>
                  <td class="right record-amount">{{ money(investment.amount) }}</td>
                  <td class="right">{{ money(investment.allocated_amount) }}</td>
                  <td class="right">{{ money(investment.remaining_capital) }}</td>
                  <td><FinanceStatus :status="investment.status" kind="investment" /></td>
                  <td class="right row-actions">
                    <RouterLink class="btn-light" :to="`/investments/${investment.id}`">
                      Details
                    </RouterLink>
                    <button
                      v-if="canAllocate(investment)"
                      class="btn-light"
                      type="button"
                      @click="allocatingId = allocatingId === investment.id ? null : investment.id"
                    >
                      {{ allocatingId === investment.id ? 'Close' : 'Allocate to job' }}
                    </button>
                  </td>
                </tr>
                <tr v-if="allocatingId === investment.id" class="allocation-row">
                  <td colspan="7">
                    <div class="allocation-panel">
                      <strong>Allocate {{ investment.investment_code }} to a job</strong>
                      <p>Choose an eligible job and the amount of capital to commit.</p>
                      <AllocationForm :investment="investment" @created="allocationCreated" />
                    </div>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>

        <Pagination
          :page="pagination.current_page"
          :last-page="pagination.last_page"
          :total="pagination.total"
          :per-page="pagination.per_page"
          @update:page="load"
        />
      </StatePanel>
    </section>
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

.page-error p {
  color: var(--danger);
}

.summary-grid {
  display: grid;
  gap: var(--space-3);
  grid-template-columns: repeat(3, minmax(0, 1fr));
  margin-bottom: var(--space-4);
}

.summary-card {
  display: grid;
  gap: var(--space-2);
  padding: var(--space-4);
}

.summary-card span {
  color: var(--text-secondary);
  font-size: 13px;
}

.summary-card strong {
  color: var(--text-primary);
  font-size: 20px;
}

.record-link {
  color: var(--text-primary);
  display: block;
  font-weight: 600;
  text-decoration: none;
}

.record-link:hover {
  color: var(--accent);
}

.record-code {
  color: var(--text-muted);
  display: block;
  font-size: 12px;
  margin-top: 1px;
}

.record-amount {
  color: var(--text-primary);
  font-weight: 600;
}

.row-actions {
  display: flex;
  gap: var(--space-2);
  justify-content: flex-end;
}

.allocation-row td {
  background: var(--surface-muted);
}

.allocation-panel {
  display: grid;
  gap: var(--space-2);
  padding: var(--space-2);
}

.allocation-panel p {
  color: var(--text-secondary);
  font-size: 13px;
}

@media (max-width: 700px) {
  .summary-grid {
    grid-template-columns: 1fr;
  }

  .page-error {
    align-items: flex-start;
    flex-direction: column;
  }

  .row-actions {
    align-items: flex-end;
    flex-direction: column;
  }
}
</style>
