<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { getEstimate } from '@/services/estimateService'
import EstimateForm from '@/components/layout/estimate/EstimateForm.vue'

const route = useRoute()
const estimate = ref(null)

onMounted(async () => {
  const { data } = await getEstimate(route.params.id)
  estimate.value = data.data
})
</script>

<template>
  <EstimateForm v-if="estimate" :estimate="estimate" />
</template>
