<script setup>
import { onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useInvestorStore } from '@/stores/investorStore'
import { useInvestmentStore } from '@/stores/investmentStore'
import { money } from '@/utils/money'

const route = useRoute()
const router = useRouter()

const investmentStore = useInvestmentStore()

const {
  investments,
  loading: investmentsLoading,
  error: investmentsError,
} = storeToRefs(investmentStore)

const investorStore = useInvestorStore()

const { investor, loading, error } = storeToRefs(investorStore)

onMounted(() => {
  investorStore.fetchInvestor(route.params.id)
  investmentStore.fetchInvestorInvestments(route.params.id)
})

function editInvestor() {
  router.push(`/investors/${route.params.id}/edit`)
}

function goBack() {
  router.push('/investors')
}

function viewInvestment(id) {
  router.push({
    name: 'investments.show',
    params: { id },
  })
}
</script>

<template>
  <div class="page">
    <div v-if="loading">Loading investor...</div>

    <div v-else-if="error" class="card">
      {{ error }}
    </div>

    <template v-else-if="investor">
      <div class="page-head">
        <div>
          <h1>{{ investor.name }}</h1>

          <p>
            {{ investor.investor_code }}
          </p>
        </div>

        <div class="actions">
          <button type="button" @click="goBack">Back</button>

          <button type="button" @click="editInvestor">Edit</button>
        </div>
      </div>

      <div class="card">
        <h2>Investor Information</h2>

        <div class="grid">
          <div>
            <strong>Investor Code</strong>
            <p>{{ investor.investor_code }}</p>
          </div>

          <div>
            <strong>Name</strong>
            <p>{{ investor.name }}</p>
          </div>

          <div>
            <strong>Email</strong>
            <p>{{ investor.email || '—' }}</p>
          </div>

          <div>
            <strong>Phone</strong>
            <p>{{ investor.phone || '—' }}</p>
          </div>

          <div>
            <strong>Status</strong>
            <p>{{ investor.status }}</p>
          </div>

          <div>
            <strong>Address</strong>
            <p>{{ investor.address || '—' }}</p>
          </div>

          <div>
            <strong>Notes</strong>
            <p>{{ investor.notes || '—' }}</p>
          </div>
        </div>
      </div>

      <div class="card">
        <h2>Investments</h2>

        <div v-if="investmentsLoading" class="empty">Loading investments...</div>

        <div v-else-if="investmentsError" class="error-state">
          {{ investmentsError }}
        </div>

        <div v-else-if="investments.length === 0" class="empty">No investments found.</div>

        <div v-else class="table-container">
          <table>
            <thead>
              <tr>
                <th>Code</th>
                <th>Amount</th>
                <th>Date</th>
                <th>Period</th>
                <th>Status</th>
              </tr>
            </thead>

            <tbody>
              <tr v-for="investment in investments" :key="investment.id">
                <td>
                  <a href="#" @click.prevent="viewInvestment(investment.id)">
                    {{ investment.investment_code }}
                  </a>
                </td>

                <td>{{ money(investment.amount) }}</td>
                <td>{{ investment.investment_date }}</td>
                <td>
                  {{ investment.period_months ? `${investment.period_months} months` : '-' }}
                </td>
                <td>{{ investment.status }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>
  </div>
</template>
