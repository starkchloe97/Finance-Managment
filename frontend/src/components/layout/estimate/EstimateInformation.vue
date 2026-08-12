<script setup>
import { onMounted } from "vue";
import { useCustomerStore } from "@/stores/customerStore";

const props = defineProps({

    form:Object

});

const customers = useCustomerStore();

onMounted(()=>{

    customers.loadOptions();

});
</script>

<template>

<div>

<h3>Basic Information</h3>

<div>

<label>

Customer

</label>

<select v-model="form.customer_id">

<option :value="null">

Select Customer

</option>

<option

v-for="customer in customers.customers"

:key="customer.id"

:value="customer.id"

>

{{ customer.code }} -

{{ customer.name }}

</option>

</select>

</div>

<div>

<label>

Estimate Date

</label>

<input
type="date"
v-model="form.estimate_date"
/>

</div>

<div>

<label>

Valid Until

</label>

<input
type="date"
v-model="form.valid_until"
/>

</div>

<div>

<label>

Pickup

</label>

<input
v-model="form.pickup"
/>

</div>

<div>

<label>

Destination

</label>

<input
v-model="form.destination"
/>

</div>

<div>

<label>

Service Type

</label>

<select
v-model="form.service_type"
>

<option value="goods">

Goods

</option>

<option value="vehicle">

Vehicle

</option>

</select>

</div>

</div>

</template>