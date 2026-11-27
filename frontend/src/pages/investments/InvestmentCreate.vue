<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import InvestmentForm from '@/components/investments/InvestmentForm.vue'
import { useInvestmentStore } from '@/stores/investmentStore'
import { useInvestorStore } from '@/stores/investorStore'
import { useBankAccountStore } from '@/stores/bankAccountStore'
import { ensureMyInvestor } from '@/services/myInvestorService'

const router = useRouter()
const route = useRoute()
const investorStore = useInvestorStore()
const investmentStore = useInvestmentStore()
const bankAccountStore = useBankAccountStore()

const ownerMode = computed(() => route.query.owner === '1')
const ownerInvestor = ref(null)
const investors = computed(() =>
  ownerMode.value
    ? ownerInvestor.value
      ? [ownerInvestor.value]
      : []
    : investorStore.investors,
)
const bankAccounts = computed(() => bankAccountStore.accounts)
const submitting = ref(false)
const validationErrors = ref({})
const initialValues = computed(() => ({
  investor_id: ownerInvestor.value?.id ?? route.query.investor_id ?? '',
}))

onMounted(async () => {
  if (ownerMode.value) {
    const [profileResponse] = await Promise.all([
      ensureMyInvestor(),
      bankAccountStore.fetchAccounts(),
    ])
    ownerInvestor.value = profileResponse.data
  } else {
    await Promise.all([investorStore.fetchInvestors(), bankAccountStore.fetchAccounts()])
  }
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

const cancel = () =>
  router.push({ name: ownerMode.value ? 'my-investments.index' : 'investments.index' })
</script>

<template>
  <div class="page-container">
    <div class="page-header">
      <div>
        <h1>{{ ownerMode ? 'Create owner investment' : 'Create investment' }}</h1>
        <p>
          {{
            ownerMode
              ? 'Set your own investment amount, return terms, duration, and receiving account.'
              : 'Add a new investment for an investor.'
          }}
        </p>
      </div>
    </div>

    <InvestmentForm
      :key="ownerMode ? ownerInvestor?.id || 'owner-pending' : 'all-investors'"
      mode="create"
      :initial-values="initialValues"
      :investors="investors"
      :bank-accounts="bankAccounts"
      :errors="validationErrors"
      :submitting="submitting"
      :error="investmentStore.error"
      @submit="submit"
      @cancel="cancel"
    />
  </div>
</template>
