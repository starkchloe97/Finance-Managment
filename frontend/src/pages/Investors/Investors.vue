<script setup>
import { onMounted, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { useRouter } from 'vue-router'
import { useInvestorStore } from '@/stores/investorStore'
import Pagination from '@/components/ui/Pagination.vue'
import StatePanel from '@/components/ui/StatePanel.vue'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
import FinanceStatus from '@/components/ui/FinanceStatus.vue'
import { avatarStyle, initialOf } from '@/utils/avatar'

const router = useRouter()
const investorStore = useInvestorStore()
const { investors, loading, error, pagination } = storeToRefs(investorStore)
const deleting = ref(null)

onMounted(() => investorStore.fetchInvestors())

const createInvestor = () => router.push('/investors/create')
const viewInvestor = (id) => router.push(`/investors/${id}`)
const editInvestor = (id) => router.push(`/investors/${id}/edit`)

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
        <p class="page-sub">
          {{ pagination.total }}
          {{ pagination.total === 1 ? 'investor' : 'investors' }} — profiles, capital placed, and
          loan balances.
        </p>
      </div>
      <button type="button" @click="createInvestor">+ Add investor</button>
    </div>

    <div v-if="error" class="page-error" role="alert">
      <p>{{ error }}</p>
      <button class="btn-light" type="button" @click="investorStore.fetchInvestors()">
        Try again
      </button>
    </div>

    <section class="card list-card">
      <StatePanel
        :loading="loading && !investors.length"
        :error="''"
        :empty="!loading && !error && !investors.length"
        empty-title="No investors yet. Create an investor to start tracking capital."
        empty-action="Add investor"
        empty-to="/investors/create"
      >
        <div class="table-wrap">
          <table>
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
                  <div class="investor-cell">
                    <span
                      class="investor-avatar"
                      :style="avatarStyle(investor.name)"
                      aria-hidden="true"
                    >
                      {{ initialOf(investor.name) }}
                    </span>
                    <div class="investor-id">
                      <RouterLink class="record-link" :to="`/investors/${investor.id}`">
                        {{ investor.name }}
                      </RouterLink>
                      <span class="record-code">{{ investor.investor_code }}</span>
                    </div>
                  </div>
                </td>
                <td>
                  <div class="contact-cell">
                    <a v-if="investor.email" class="contact-link" :href="`mailto:${investor.email}`">
                      {{ investor.email }}
                    </a>
                    <span v-else class="record-code">No email</span>
                    <a v-if="investor.phone" class="record-code contact-link" :href="`tel:${investor.phone}`">
                      {{ investor.phone }}
                    </a>
                    <span v-else class="record-code">No phone</span>
                  </div>
                </td>
                <td>
                  <FinanceStatus :status="investor.status" kind="investor" />
                </td>
                <td class="right">
                  <div class="row-actions">
                    <RouterLink
                      class="icon-action"
                      :to="`/investors/${investor.id}`"
                      title="View investor"
                      aria-label="View investor"
                    >
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z" /><circle cx="12" cy="12" r="3" />
                      </svg>
                    </RouterLink>
                    <RouterLink
                      class="icon-action"
                      :to="`/investors/${investor.id}/edit`"
                      title="Edit investor"
                      aria-label="Edit investor"
                    >
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z" />
                      </svg>
                    </RouterLink>
                    <button
                      class="icon-action danger"
                      type="button"
                      title="Delete investor"
                      aria-label="Delete investor"
                      @click="deleting = investor"
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

.investor-cell {
  align-items: center;
  display: flex;
  gap: 12px;
}

.investor-avatar {
  align-items: center;
  border-radius: 10px;
  color: #fff;
  display: inline-flex;
  flex: 0 0 36px;
  font-size: 13px;
  font-weight: 600;
  height: 36px;
  justify-content: center;
  width: 36px;
}

.investor-id {
  display: flex;
  flex-direction: column;
  gap: 1px;
  min-width: 0;
}

.record-link {
  color: var(--text-primary);
  font-size: 14px;
  font-weight: 600;
  text-decoration: none;
}
.record-link:hover { color: var(--accent); }

.record-code {
  color: var(--text-muted);
  font-size: 12px;
}

.contact-cell { display: flex; flex-direction: column; gap: 1px; }

.contact-link {
  color: var(--text-secondary);
  font-size: 14px;
  text-decoration: none;
}
.contact-link:hover { color: var(--accent); }
.contact-link.record-code { font-size: 12px; }

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

button.icon-action.danger {
  padding-left: 0;
  padding-right: 0;
}
button.icon-action.danger svg {
  flex-shrink: 0;
}
td a + button{
  margin-left: 0px;
}

button{
  min-height: auto;
}

@media (max-width: 700px) {
  .page-error {
    align-items: flex-start;
    flex-direction: column;
  }
}
</style>