<script setup>
import { reactive, ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { getCustomer, updateCustomer } from '@/services/customerService'

const route = useRoute()
const router = useRouter()

const saving = ref(false)
const loading = ref(true)
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

onMounted(async () => {
  try {
    const { data } = await getCustomer(route.params.id)
    Object.assign(form, data.data)
  } catch (error) {
    notice.value = error.response?.data?.message || 'Could not load customer.'
  } finally {
    loading.value = false
  }
})

const submit = async () => {
  if (saving.value) return

  saving.value = true
  errors.value = {}
  notice.value = ''

  try {
    await updateCustomer(route.params.id, form)
    router.push('/customers')
  } catch (error) {
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
    <h1>Edit Customer</h1>
  </div>

  <form class="card" @submit.prevent="submit">
    <div v-if="loading" class="state-panel state-loading"><div class="skeleton-block"></div></div>

    <template v-else>
      <div class="grid">
        <div class="field">
          <label>Name *</label>
          <input
            v-model="form.name"
            required
            :class="{ invalid: errors.name }"
            :disabled="saving"
          />
          <small v-if="errors.name" class="error">{{ errors.name[0] }}</small>
        </div>

        <div class="field">
          <label>Phone</label>
          <input v-model="form.phone" :class="{ invalid: errors.phone }" :disabled="saving" />
          <small v-if="errors.phone" class="error">{{ errors.phone[0] }}</small>
        </div>

        <div class="field">
          <label>Email</label>
          <input
            type="email"
            v-model="form.email"
            :class="{ invalid: errors.email }"
            :disabled="saving"
          />
          <small v-if="errors.email" class="error">{{ errors.email[0] }}</small>
        </div>

        <div class="field">
          <label>Company</label>
          <input v-model="form.company" :class="{ invalid: errors.company }" :disabled="saving" />
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
        <button type="submit" :disabled="saving || loading">
          {{ saving ? 'Saving…' : 'Update Customer' }}
        </button>
        <RouterLink class="btn btn-light" to="/customers">Cancel</RouterLink>
      </div>
    </template>
  </form>
</template>
