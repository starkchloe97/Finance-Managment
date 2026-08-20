<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { createCustomer } from '@/services/customerService'

const router = useRouter()

const saving = ref(false)
const errors = ref({})
const notice = ref('')

const form = reactive({
  name: '',
  phone: '',
  email: '',
  company: '',
  address: '',
  notes: '',
})

const submit = async () => {
  if (saving.value) return

  saving.value = true
  errors.value = {}
  notice.value = ''

  try {
    await createCustomer(form)
    router.push('/customers')
  } catch (error) {
    // 422 carries per-field messages; anything else only has a summary.
    errors.value = error.response?.data?.errors || {}

    if (!Object.keys(errors.value).length) {
      notice.value = error.response?.data?.message || 'Could not save customer.'
    }
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="page-head">
    <h1>New Customer</h1>
  </div>

  <form class="card" @submit.prevent="submit">
    <div class="grid">
      <div class="field">
        <label>Name *</label>
        <input
          v-model="form.name"
          placeholder="Customer name"
          required
          :class="{ invalid: errors.name }"
          :disabled="saving"
        />
        <small v-if="errors.name" class="error">{{ errors.name[0] }}</small>
      </div>

      <div class="field">
        <label>Phone</label>
        <input
          v-model="form.phone"
          placeholder="03001234567"
          :class="{ invalid: errors.phone }"
          :disabled="saving"
        />
        <small v-if="errors.phone" class="error">{{ errors.phone[0] }}</small>
      </div>

      <div class="field">
        <label>Email</label>
        <input
          type="email"
          v-model="form.email"
          placeholder="ops@company.com"
          :class="{ invalid: errors.email }"
          :disabled="saving"
        />
        <small v-if="errors.email" class="error">{{ errors.email[0] }}</small>
      </div>

      <div class="field">
        <label>Company</label>
        <input
          v-model="form.company"
          placeholder="Company name"
          :class="{ invalid: errors.company }"
          :disabled="saving"
        />
        <small v-if="errors.company" class="error">{{ errors.company[0] }}</small>
      </div>
    </div>

    <div class="field">
      <label>Address</label>
      <textarea v-model="form.address" :disabled="saving"></textarea>
    </div>

    <div class="field">
      <label>Notes</label>
      <textarea v-model="form.notes" :disabled="saving"></textarea>
    </div>

    <p v-if="notice" class="error">{{ notice }}</p>

    <div class="actions">
      <button type="submit" :disabled="saving">{{ saving ? 'Saving…' : 'Save Customer' }}</button>
      <RouterLink class="btn btn-light" to="/customers">Cancel</RouterLink>
    </div>
  </form>
</template>
