<script setup>
import { onMounted, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useRouter } from 'vue-router'
import { useInvestorStore } from '@/stores/investorStore'
import Pagination from '@/components/ui/Pagination.vue'
import StatePanel from '@/components/ui/StatePanel.vue'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'

const router = useRouter()
const investorStore = useInvestorStore()
const { investors, loading, error, pagination } = storeToRefs(investorStore)
const deleting = ref(null)

onMounted(() => investorStore.fetchInvestors())

const createInvestor = () => router.push('/investors/create')
const viewInvestor = (id) => router.push(`/investors/${id}`)
const editInvestor = (id) => router.push(`/investors/${id}/edit`)
const statusClass = (status) => (status === 'active' ? 'status-success' : 'status-neutral')

const deleteInvestor = async () => {
  if (!deleting.value) return
  try {
    await investorStore.deleteInvestor(deleting.value.id)
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
        <h1>Investors</h1>
        <p class="page-subtitle">Manage investor profiles and the capital they place with you.</p>
      </div>
      <button type="button" @click="createInvestor">Add investor</button>
    </div>

    <div v-if="error" class="page-error" role="alert">
      <p>{{ error }}</p>
      <button class="btn-light" type="button" @click="investorStore.fetchInvestors()">
        Try again
      </button>
    </div>

    <section class="card list-card">
      <div class="list-card-header">
        <div>
          <h2>Investor directory</h2>
          <p class="hint">Use an investor profile to review their investment mix and activity.</p>
        </div>
        <span v-if="pagination.total" class="result-count">{{ pagination.total }} total</span>
      </div>

      <StatePanel
        :loading="loading && !investors.length"
        :error="''"
        :empty="!loading && !investors.length"
        empty-title="No investors yet. Create an investor to start tracking capital."
        empty-action="Add investor"
        empty-to="/investors/create"
      >
        <div class="table-wrap">
          <table class="investor-table">
            <thead>
              <tr>
                <th>Investor</th>
                <th>Contact</th>
                <th>Status</th>
                <th class="right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="investor in investors" :key="investor.id">
                <td>
                  <RouterLink class="record-link" :to="`/investors/${investor.id}`">
                    {{ investor.name }}
                  </RouterLink>
                  <span class="record-code">{{ investor.investor_code }}</span>
                </td>
                <td>
                  <span>{{ investor.email || 'No email' }}</span>
                  <span class="record-code">{{ investor.phone || 'No phone' }}</span>
                </td>
                <td>
                  <span class="status" :class="statusClass(investor.status)">
                    {{ investor.status }}
                  </span>
                </td>
                <td class="right">
                  <div class="row-actions">
                    <RouterLink class="btn-light btn-sm" :to="`/investors/${investor.id}`">
                      Open
                    </RouterLink>
                    <RouterLink class="btn-light btn-sm" :to="`/investors/${investor.id}/edit`">
                      Edit
                    </RouterLink>
                    <button class="btn-danger btn-sm" type="button" @click="deleting = investor">
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
          @update:page="investorStore.setPage"
        />
      </StatePanel>
    </section>

    <ConfirmDialog
      :open="Boolean(deleting)"
      title="Delete investor?"
      :message="deleting ? `Delete ${deleting.name}? This cannot be undone.` : ''"
      confirm-label="Delete investor"
      variant="danger"
      @confirm="deleteInvestor"
      @cancel="deleting = null"
    />
  </div>
</template>

<style scoped>
.entity-list-page {
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

.list-card {
  min-width: 0;
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

.investor-table td > span,
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

  .investor-table {
    min-width: 580px;
  }
}
</style>
