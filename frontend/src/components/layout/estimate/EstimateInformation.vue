<script setup>
import { onMounted } from 'vue'
import { useCustomerStore } from '@/stores/customerStore'
import InfoTip from '@/components/ui/InfoTip.vue'

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
    <div class="section-heading">
      <h3>Basic information</h3>
      <p>Who the quote is for, what's moving, and where it's going.</p>
    </div>

    <div class="grid">
      <div class="field">
        <label for="estimate-customer">Customer</label>
        <select id="estimate-customer" v-model="form.customer_id">
          <option :value="null">Select customer</option>
          <option v-for="customer in customers.customers" :key="customer.id" :value="customer.id">
            {{ customer.name }}
          </option>
        </select>
      </div>

      <div class="field">
        <label for="estimate-service">
          Service type
          <InfoTip label="What is being moved — goods or a vehicle. It affects how the job is planned and priced." />
        </label>
        <select id="estimate-service" v-model="form.service_type">
          <option value="goods">Goods</option>
          <option value="vehicle">Vehicle</option>
        </select>
      </div>

      <div class="field">
        <label for="estimate-date">Estimate date</label>
        <input id="estimate-date" type="date" v-model="form.estimate_date" />
      </div>

      <div class="field">
        <label for="estimate-valid">
          Valid until
          <InfoTip label="The quoted price is guaranteed until this date. After it, the estimate expires." />
        </label>
        <input id="estimate-valid" type="date" v-model="form.valid_until" />
      </div>

      <div class="field">
        <label for="estimate-pickup">Pickup</label>
        <div class="route-input">
          <span class="route-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10" /><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10Z" /><path d="M2 12h20" />
            </svg>
          </span>
          <input id="estimate-pickup" v-model="form.pickup" placeholder="Karachi" />
        </div>
      </div>

      <div class="field">
        <label for="estimate-destination">Destination</label>
        <div class="route-input">
          <span class="route-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z" /><circle cx="12" cy="10" r="3" />
            </svg>
          </span>
          <input id="estimate-destination" v-model="form.destination" placeholder="Lahore" />
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.section-heading { margin-bottom: var(--space-4); }
.section-heading h3 {
  font-size: 15px;
  font-weight: 600;
  margin: 0;
}
.section-heading p {
  color: var(--text-muted);
  font-size: 13px;
  margin: 2px 0 0;
}

.field label {
  align-items: center;
  display: flex;
  gap: 5px;
}

.route-input { position: relative; }

.route-icon {
  align-items: center;
  color: var(--text-muted);
  display: flex;
  height: 100%;
  left: 11px;
  pointer-events: none;
  position: absolute;
  top: 0;
}
.route-icon svg { height: 15px; width: 15px; }

.route-input input { padding-left: 34px; }
</style>