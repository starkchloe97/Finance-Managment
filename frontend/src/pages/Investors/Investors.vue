<script setup>
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useInvestorStore } from '@/stores/investorStore'

const router = useRouter()
const investorStore = useInvestorStore()

const { investors, loading, error, pagination } = storeToRefs(investorStore)

onMounted(() => {
  investorStore.fetchInvestors()
})

function createInvestor() {
  router.push('/investors/create')
}

function viewInvestor(id) {
  router.push(`/investors/${id}`)
}

function editInvestor(id) {
  router.push(`/investors/${id}/edit`)
}

async function deleteInvestor(id) {
  const confirmed = window.confirm('Are you sure you want to delete this investor?')

  if (!confirmed) {
    return
  }

  try {
    await investorStore.deleteInvestor(id)
  } catch {
    // Store already contains the error message.
  }
}
</script>

<template>
  <div class="page">
    <div class="page-head">
      <div>
        <h1>Investors</h1>
        <p>Manage investors and their profiles.</p>
      </div>

      <div class="actions">
        <button type="button" @click="createInvestor">Add Investor</button>
      </div>
    </div>

    <div v-if="error" class="card">
      <p>{{ error }}</p>
    </div>

    <div class="card">
      <div v-if="loading">Loading investors...</div>

      <div v-else-if="investors.length === 0">No investors found.</div>

      <table v-else>
        <thead>
          <tr>
            <th>Code</th>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>

        <tbody>
          <tr v-for="investor in investors" :key="investor.id">
            <td>{{ investor.investor_code }}</td>

            <td>{{ investor.name }}</td>

            <td>{{ investor.email || '—' }}</td>

            <td>{{ investor.phone || '—' }}</td>

            <td>
              {{ investor.status }}
            </td>

            <td>
              <div class="actions">
                <button type="button" @click="viewInvestor(investor.id)">View</button>

                <button type="button" @click="editInvestor(investor.id)">Edit</button>

                <button type="button" @click="deleteInvestor(investor.id)">Delete</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="pagination.total > 0">
        <p>Showing {{ investors.length }} of {{ pagination.total }} investors</p>
      </div>
    </div>
  </div>
</template>
