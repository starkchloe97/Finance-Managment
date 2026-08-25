<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import InvestmentForm from '@/components/investments/InvestmentForm.vue'
import { useInvestmentStore } from '@/stores/investmentStore'
import { useInvestorStore } from '@/stores/investorStore'

const router = useRouter()
const route = useRoute()
const investorStore = useInvestorStore()
const investmentStore = useInvestmentStore()

const investors = computed(() => investorStore.investors)
const submitting = ref(false)
const validationErrors = ref({})
const initialValues = computed(() => ({ investor_id: route.query.investor_id || '' }))

onMounted(async () => {
  await investorStore.fetchInvestors()
})

const submit = async (data) => {
  submitting.value = true
  validationErrors.value = {}

  try {
    const investment = await investmentStore.createInvestment(data)

    router.push({
      name: 'investments.show',
      params: { id: investment.id },
    })
  } catch (error) {
    if (error.response?.status === 422) {
      validationErrors.value = error.response.data.errors || {}
    }
  } finally {
    submitting.value = false
  }
}

const cancel = () => router.push({ name: 'investments.index' })
</script>

<template>
  <div class="page-container">
    <div class="page-header">
      <div>
        <h1>Create investment</h1>
        <p>Add a new investment for an investor.</p>
      </div>
    </div>

    <InvestmentForm
      mode="create"
      :initial-values="initialValues"
      :investors="investors"
      :errors="validationErrors"
      :submitting="submitting"
      :error="investmentStore.error"
      @submit="submit"
      @cancel="cancel"
    />
  </div>
</template>
