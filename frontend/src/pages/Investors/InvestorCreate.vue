<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useInvestorStore } from '@/stores/investorStore'

const router = useRouter()
const investorStore = useInvestorStore()

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

async function submit() {
  submitting.value = true
  validationErrors.value = {}

  try {
    const investor = await investorStore.createInvestor(form)

    router.push(`/investors/${investor.id}`)
  } catch (error) {
    console.log('Investor create error:', error)
    console.log('Validation errors:', error.response?.data)

    if (error.response?.status === 422) {
      validationErrors.value = error.response.data.errors || {}
    }
  } finally {
    submitting.value = false
  }
}

function cancel() {
  router.push('/investors')
}
</script>

<template>
  <div class="page">
    <div class="page-head">
      <div>
        <h1>Add Investor</h1>
        <p>Create a new investor profile.</p>
      </div>
    </div>

    <form class="card" @submit.prevent="submit">
      <div class="grid">
        <div class="field">
          <label for="name">Name</label>

          <input id="name" v-model="form.name" type="text" required />

          <small v-if="validationErrors.name">
            {{ validationErrors.name[0] }}
          </small>
        </div>

        <div class="field">
          <label for="email">Email</label>

          <input id="email" v-model="form.email" type="email" />

          <small v-if="validationErrors.email">
            {{ validationErrors.email[0] }}
          </small>
        </div>

        <div class="field">
          <label for="phone">Phone</label>

          <input id="phone" v-model="form.phone" type="text" />

          <small v-if="validationErrors.phone">
            {{ validationErrors.phone[0] }}
          </small>
        </div>

        <div class="field">
          <label for="status">Status</label>

          <select id="status" v-model="form.status">
            <option value="active">Active</option>

            <option value="inactive">Inactive</option>
          </select>
        </div>

        <div class="field">
          <label for="address">Address</label>

          <textarea id="address" v-model="form.address" rows="3" />
        </div>

        <div class="field">
          <label for="notes">Notes</label>

          <textarea id="notes" v-model="form.notes" rows="3" />
        </div>
      </div>

      <div class="actions">
        <button type="button" @click="cancel">Cancel</button>

        <button type="submit" :disabled="submitting">
          {{ submitting ? 'Saving...' : 'Create Investor' }}
        </button>
      </div>
    </form>
  </div>
</template>
