<script setup>
import { onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useRouter } from 'vue-router'
import { useInvestmentStore } from '@/stores/investmentStore'

const router = useRouter()

const investmentStore = useInvestmentStore()

const { investments, loading, error, pagination } = storeToRefs(investmentStore)

onMounted(() => {
  investmentStore.fetchInvestments()
})

const createInvestment = () => {
  router.push({
    name: 'investments.create',
  })
}

const viewInvestment = (id) => {
  router.push({
    name: 'investments.show',
    params: { id },
  })
}

const editInvestment = (id) => {
  router.push({
    name: 'investments.edit',
    params: { id },
  })
}

const deleteInvestment = async (id) => {
  const confirmed = window.confirm('Are you sure you want to delete this investment?')

  if (!confirmed) {
    return
  }

  await investmentStore.deleteInvestment(id)
}
</script>

<template>
  <div class="page-container">
    <div class="page-header">
      <div>
        <h1>Investments</h1>

        <p>Manage investor capital placements</p>
      </div>

      <button type="button" @click="createInvestment">Add Investment</button>
    </div>

    <div v-if="loading" class="loading-state">Loading investments...</div>

    <div v-else-if="error" class="error-state">
      {{ error }}
    </div>

    <div v-else class="table-container">
      <table>
        <thead>
          <tr>
            <th>Code</th>
            <th>Investor</th>
            <th>Amount</th>
            <th>Date</th>
            <th>Period</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>

        <tbody>
          <tr v-for="investment in investments" :key="investment.id">
            <td>
              {{ investment.investment_code }}
            </td>

            <td>
              {{ investment.investor?.name || '-' }}
            </td>

            <td>
              {{ investment.amount }}
            </td>

            <td>
              {{ investment.investment_date }}
            </td>

            <td>
              <span v-if="investment.period_months">
                {{ investment.period_months }}
                months
              </span>

              <span v-else> - </span>
            </td>

            <td>
              {{ investment.status }}
            </td>

            <td>
              <button type="button" @click="viewInvestment(investment.id)">View</button>

              <button
                v-if="investment.status === 'active'"
                type="button"
                @click="editInvestment(investment.id)"
              >
                Edit
              </button>

              <button
                v-if="investment.status === 'active'"
                type="button"
                @click="deleteInvestment(investment.id)"
              >
                Delete
              </button>
            </td>
          </tr>

          <tr v-if="investments.length === 0">
            <td colspan="7">No investments found.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
