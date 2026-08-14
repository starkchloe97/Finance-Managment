<script setup>
import { ref, onMounted } from "vue";
import { useRouter } from "vue-router";
import { getEstimates } from "@/services/estimateService";
import { convertEstimate } from "@/services/transportJobService";
import { money } from "@/utils/money";

const router = useRouter();

const estimates = ref([]);
const loading = ref(true);

const load = async () => {
    loading.value = true;
    const { data } = await getEstimates();
    estimates.value = data?.data ?? [];
    loading.value = false;
};

onMounted(load);

// Accepting a quote starts the job, so go straight to it.
const convert = async (estimate) => {
    try {
        const { data } = await convertEstimate(estimate.id);
        router.push(`/jobs/${data.data.id}`);
    } catch (error) {
        alert(error.response?.data?.message || "Could not convert estimate");
    }
};
</script>

<template>

<div class="page-head">

    <h1>Estimates</h1>

    <RouterLink class="btn" to="/estimates/create">
        New Estimate
    </RouterLink>

</div>

<div class="card">

    <p class="hint">
        Each quote holds both the cost and the sell price, so the profit is known before
        the job starts.
    </p>

    <div class="table-wrap">

        <table>

            <thead>
                <tr>
                    <th>Code</th>
                    <th>Customer</th>
                    <th>Route</th>
                    <th class="right">Cost</th>
                    <th class="right">Sell</th>
                    <th class="right">Profit</th>
                    <th>Status</th>
                    <th class="right">Actions</th>
                </tr>
            </thead>

            <tbody>
                <tr v-for="estimate in estimates" :key="estimate.id">

                    <td>{{ estimate.code }}</td>

                    <td>{{ estimate.customer?.name }}</td>

                    <td>{{ estimate.pickup }} → {{ estimate.destination }}</td>

                    <td class="right">{{ money(estimate.estimated_cost) }}</td>

                    <td class="right">{{ money(estimate.estimated_sell) }}</td>

                    <td class="right">{{ money(estimate.estimated_profit) }}</td>

                    <td><span class="badge">{{ estimate.status }}</span></td>

                    <td class="right">

                        <RouterLink
                            v-if="estimate.transport_job"
                            :to="`/jobs/${estimate.transport_job.id}`"
                        >
                            View job
                        </RouterLink>

                        <button
                            v-else
                            class="btn-light btn-sm"
                            @click="convert(estimate)"
                        >
                            Accept &amp; Start Job
                        </button>

                    </td>

                </tr>
            </tbody>

        </table>

    </div>

    <p v-if="!loading && !estimates.length" class="empty">
        No estimates yet. Create one to get started.
    </p>

</div>

</template>
