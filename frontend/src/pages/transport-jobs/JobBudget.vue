<script setup>
import { reactive } from "vue";
import { updateBudget } from "@/services/transportJobBudgetService";

const props = defineProps({
    job: Object
});

const form = reactive({

    items: []

});

const save = async () => {

    await updateBudget(props.job.id, form);

    alert("Budget Saved");

};
</script>

<template>

<h2>Job Budget</h2>

<BudgetItemsTable :form="form"/>

<button @click="save">
    Save Budget
</button>

</template>