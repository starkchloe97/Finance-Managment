<script setup>
import { ref, reactive, computed } from "vue";
import { useRouter } from "vue-router";
import { createEstimate } from "@/services/estimateService";

import EstimateInformation from "./EstimateInformation.vue";
import EstimateItemsTable from "./EstimateItemsTable.vue";
import { money } from "@/utils/money";

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
            cost_price: 0,
            sell_price: 0,
            cost_total: 0,
            sell_total: 0,
            profit: 0,
            remarks: ""
        }
    ]
});

const sum = (field) =>
    form.items.reduce((total, item) => total + Number(item[field] || 0), 0);

const totalCost = computed(() => sum("cost_total"));

const totalSell = computed(() => sum("sell_total"));

const totalProfit = computed(() => totalSell.value - totalCost.value);

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
                <div>
                    <dt>Our cost</dt>
                    <dd>{{ money(totalCost) }}</dd>
                </div>
                <div>
                    <dt>Customer pays</dt>
                    <dd>{{ money(totalSell) }}</dd>
                </div>
                <div class="grand">
                    <dt>Profit</dt>
                    <dd>{{ money(totalProfit) }}</dd>
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
