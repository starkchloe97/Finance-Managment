<script setup>
import { ref, onMounted } from "vue";
import { getEstimates } from "@/services/estimateService";
import { convertEstimate } from "@/services/transportJobService";

const estimates = ref([]);
const loading = ref(true);

const load = async () => {
    loading.value = true;
    const { data } = await getEstimates();
    estimates.value = data?.data ?? [];
    loading.value = false;
};

onMounted(load);

const convert = async (estimate) => {
    try {
        await convertEstimate(estimate.id);
        await load();
    } catch (error) {
        alert(error.response?.data?.message || "Could not convert estimate");
    }
};

const money = (value) => Number(value ?? 0).toLocaleString();
</script>

<template>

<div class="page-head">

    <h1>Estimates</h1>

    <RouterLink class="btn" to="/estimates/create">
        New Estimate
    </RouterLink>

</div>

<div class="card">

    <div class="table-wrap">

        <table>

            <thead>
                <tr>
                    <th>Code</th>
                    <th>Route</th>
                    <th>Date</th>
                    <th class="right">Total</th>
                    <th>Status</th>
                    <th class="right">Actions</th>
                </tr>
            </thead>

            <tbody>
                <tr v-for="estimate in estimates" :key="estimate.id">

                    <td>{{ estimate.code }}</td>

                    <td>{{ estimate.pickup }} → {{ estimate.destination }}</td>

                    <td>{{ String(estimate.estimate_date).slice(0, 10) }}</td>

                    <td class="right">{{ money(estimate.estimated_sell ?? estimate.total) }}</td>

                    <td><span class="badge">{{ estimate.status }}</span></td>

                    <td class="right">
                        <RouterLink :to="`/estimates/${estimate.id}/edit`">
                            Edit
                        </RouterLink>

                        <button
                            v-if="estimate.status !== 'accepted'"
                            class="btn-light btn-sm"
                            @click="convert(estimate)"
                        >
                            Convert to Job
                        </button>
                    </td>

                </tr>
            </tbody>

        </table>

    </div>

    <p v-if="!loading && !estimates.length" class="empty">
        No estimates to show.
    </p>

</div>

</template>
