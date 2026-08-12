<script setup>
import { ref, reactive, computed } from "vue";
import { useRouter } from "vue-router";
import { createEstimate } from "@/services/estimateService";

import EstimateInformation from "./EstimateInformation.vue";
import EstimateItemsTable from "./EstimateItemsTable.vue";

const router = useRouter();

const saving = ref(false);

const form = reactive({
    customer_id: null,
    estimate_date: new Date().toISOString().substring(0, 10),
    valid_until: "",
    pickup: "",
    destination: "",
    service_type: "goods",
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

// The estimate is the selling price, so the total is just the sum of the lines.
const total = computed(() =>
    form.items.reduce((sum, item) => sum + Number(item.total || 0), 0)
);

const save = async () => {
    saving.value = true;
    try {
        await createEstimate(form);
        router.push("/estimates");
    } catch (error) {
        alert(error.response?.data?.message || "Could not save estimate");
    } finally {
        saving.value = false;
    }
};
</script>

<template>

<div class="page-head">
    <h1>New Estimate</h1>
</div>

<form @submit.prevent="save">

    <div class="card">
        <EstimateInformation :form="form" />
    </div>

    <div class="card">
        <EstimateItemsTable :form="form" />
    </div>

    <div class="card">

        <h3>Remarks</h3>

        <div class="field">
            <textarea
                v-model="form.remarks"
                placeholder="Anything the customer should see on the quote"
            ></textarea>
        </div>

        <div class="totals">
            <dl>
                <div class="grand">
                    <dt>Customer Total</dt>
                    <dd>{{ total.toLocaleString() }}</dd>
                </div>
            </dl>
        </div>

    </div>

    <div class="actions">
        <button type="submit" :disabled="saving">
            {{ saving ? "Saving…" : "Save Estimate" }}
        </button>
        <RouterLink class="btn btn-light" to="/estimates">Cancel</RouterLink>
    </div>

</form>

</template>
