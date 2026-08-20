<script setup>
import { onMounted } from 'vue'
import { useCustomerStore } from '@/stores/customerStore'

const props = defineProps({
  form: Object,
})

const customers = useCustomerStore()

onMounted(() => {
  customers.loadOptions()
})
</script>

<template>
  <div>
    <h3>Basic Information</h3>

    <div class="grid">
      <div class="field">
        <label>Customer</label>

        <select v-model="form.customer_id">
          <option :value="null">Select customer</option>

          <option v-for="customer in customers.customers" :key="customer.id" :value="customer.id">
            {{ customer.name }}
          </option>
        </select>
      </div>

      <div class="field">
        <label>Service Type</label>

        <select v-model="form.service_type">
          <option value="goods">Goods</option>

          <option value="vehicle">Vehicle</option>
        </select>
      </div>

      <div class="field">
        <label>Estimate Date</label>

        <input type="date" v-model="form.estimate_date" />
      </div>

      <div class="field">
        <label>Valid Until</label>

        <input type="date" v-model="form.valid_until" />
      </div>

      <div class="field">
        <label>Pickup</label>

        <input v-model="form.pickup" placeholder="Karachi" />
      </div>

      <div class="field">
        <label>Destination</label>

        <input v-model="form.destination" placeholder="Lahore" />
      </div>
    </div>
  </div>
</template>
