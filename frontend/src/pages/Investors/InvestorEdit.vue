<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useInvestorStore } from '@/stores/investorStore'

const route = useRoute()
const router = useRouter()

const investorStore = useInvestorStore()

const { investor, loading } =
  storeToRefs(investorStore)

const submitting = ref(false)
const validationErrors = ref({})

const form = reactive({
  name: '',
  email: '',
  phone: '',
  address: '',
  status: 'active',
  notes: '',
})

onMounted(async () => {
  await investorStore.fetchInvestor(route.params.id)

  if (!investor.value) {
    return
  }

  form.name = investor.value.name || ''
  form.email = investor.value.email || ''
  form.phone = investor.value.phone || ''
  form.address = investor.value.address || ''
  form.status = investor.value.status || 'active'
  form.notes = investor.value.notes || ''
})

async function submit() {
  submitting.value = true
  validationErrors.value = {}

  try {
    await investorStore.updateInvestor(
      route.params.id,
      form,
    )

    router.push(`/investors/${route.params.id}`)
  } catch (error) {
    if (error.response?.status === 422) {
      validationErrors.value =
        error.response.data.errors || {}
    }
  } finally {
    submitting.value = false
  }
}

function cancel() {
  router.push(`/investors/${route.params.id}`)
}
</script>

<template>
  <div class="page">
    <div v-if="loading && !investor">
      Loading investor...
    </div>

    <template v-else-if="investor">
      <div class="page-head">
        <div>
          <h1>Edit Investor</h1>
          <p>{{ investor.investor_code }}</p>
        </div>
      </div>

      <form
        class="card"
        @submit.prevent="submit"
      >
        <div class="grid">
          <div class="field">
            <label for="name">Name</label>

            <input
              id="name"
              v-model="form.name"
              type="text"
              required
            />

            <small v-if="validationErrors.name">
              {{ validationErrors.name[0] }}
            </small>
          </div>

          <div class="field">
            <label for="email">Email</label>

            <input
              id="email"
              v-model="form.email"
              type="email"
            />

            <small v-if="validationErrors.email">
              {{ validationErrors.email[0] }}
            </small>
          </div>

          <div class="field">
            <label for="phone">Phone</label>

            <input
              id="phone"
              v-model="form.phone"
              type="text"
            />
          </div>

          <div class="field">
            <label for="status">Status</label>

            <select
              id="status"
              v-model="form.status"
            >
              <option value="active">
                Active
              </option>

              <option value="inactive">
                Inactive
              </option>
            </select>
          </div>

          <div class="field">
            <label for="address">Address</label>

            <textarea
              id="address"
              v-model="form.address"
              rows="3"
            />
          </div>

          <div class="field">
            <label for="notes">Notes</label>

            <textarea
              id="notes"
              v-model="form.notes"
              rows="3"
            />
          </div>
        </div>

        <div class="actions">
          <button
            type="button"
            @click="cancel"
          >
            Cancel
          </button>

          <button
            type="submit"
            :disabled="submitting"
          >
            {{ submitting ? 'Saving...' : 'Save Changes' }}
          </button>
        </div>
      </form>
    </template>
  </div>
</template>