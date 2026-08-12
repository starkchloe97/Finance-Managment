<script setup>
import { onMounted } from "vue";
import { useCustomerStore } from "@/stores/customerStore";

const store=useCustomerStore();

onMounted(()=>{

    store.fetchCustomers();

});
</script>

<template>

<h1>Customers</h1>

<ul>

<li
v-for="customer in store.customers"
:key="customer.id"
>

{{ customer.name }}

</li>

</ul>

</template>