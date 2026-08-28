<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import InvestmentForm from '@/components/investments/InvestmentForm.vue'
import { useInvestmentStore } from '@/stores/investmentStore'

const route = useRoute()
const router = useRouter()
const investmentStore = useInvestmentStore()

const investment = computed(() => investmentStore.investment)
const loading = computed(() => investmentStore.loading)
const submitting = ref(false)
const validationErrors = ref({})

onMounted(async () => {
  await investmentStore.fetchInvestment(route.params.id)
})

const submit = async (data) => {
  submitting.value = true
  validationErrors.value = {}

  try {
    await investmentStore.updateInvestment(route.params.id, data)

    router.push({
      name: 'investments.show',
      params: { id: route.params.id },
    })
  } catch (error) {
    if (error.response?.status === 422) {
      validationErrors.value = error.response.data.errors || {}
    }
  } finally {
    submitting.value = false
  }
}

const cancel = () =>
  router.push({
    name: 'investments.show',
    params: { id: route.params.id },
  })
</script>

<template>
  <div class="page-container">
   <div v-if="loading" class="state-panel state-loading">
  <div class="skeleton-block"></div>
</div>

    <template v-else-if="investment">
      <div class="page-head">
  <div>
    <span class="section-kicker">Capital / Investments</span>
    <h1>{{ isCreate ? 'Add investment' : `Edit ${investment.investment_code}` }}</h1>
    <p class="page-sub">
      {{ isCreate
        ? 'Record capital an investor is placing with the company.'
        : 'Update terms while the investment is still active.' }}
    </p>
  </div>
</div>

      <InvestmentForm
        mode="edit"
        :initial-values="investment"
        :errors="validationErrors"
        :submitting="submitting"
        :error="investmentStore.error"
        @submit="submit"
        @cancel="cancel"
      />
    </template>
  </div>
</template>
