<script setup>
import { reactive } from "vue";

import EstimateInformation from "./EstimateInformation.vue";

const form = reactive({
    customer_id: null,
    estimate_date: new Date().toISOString().substring(0, 10),
    valid_until: "",
    pickup: "",
    destination: "",
    service_type: "goods",
    margin_type: "fixed",
    margin: 0,
    remarks: "",

    items: [
        {
            title: "",
            category: "",
            quantity: 1,
            unit_price: 0,
            total: 0,
            notes: ""
        }
    ]
});
</script>

<template>

<div>

<h1>Create Estimate</h1>

<EstimateInformation :form="form" />

<EstimateItemsTable :form="form" />

</div>

</template>