<script setup>
import { ref, onMounted } from "vue";
import { getJobs } from "@/services/transportJobService";
import { money } from "@/utils/money";
import { statusLabel } from "@/utils/jobStatus";

const jobs = ref([]);
const loading = ref(true);

onMounted(async () => {
    const { data } = await getJobs();
    jobs.value = data?.data ?? [];
    loading.value = false;
});
</script>

<template>

<div class="page-head">
    <h1>Jobs</h1>
</div>

<div class="card">

    <p class="hint">
        Base profit is sell minus cost, agreed at quotation. Extra costs are unexpected
        and come off it to give the final profit.
    </p>

    <div class="table-wrap">

        <table>

            <thead>
                <tr>
                    <th>Code</th>
                    <th>Customer</th>
                    <th class="right">Sell</th>
                    <th class="right">Cost</th>
                    <th class="right">Base Profit</th>
                    <th class="right">Extra Costs</th>
                    <th class="right">Final Profit</th>
                    <th>Status</th>
                </tr>
            </thead>

            <tbody>
                <tr v-for="job in jobs" :key="job.id">

                    <td>
                        <RouterLink :to="`/jobs/${job.id}`">{{ job.code }}</RouterLink>
                    </td>

                    <td>{{ job.customer?.name }}</td>

                    <td class="right">{{ money(job.sell_price) }}</td>

                    <td class="right">{{ money(job.cost_price) }}</td>

                    <td class="right">{{ money(job.base_profit) }}</td>

                    <td class="right">{{ money(job.extra_costs) }}</td>

                    <td class="right" :class="{ loss: Number(job.final_profit) < 0 }">
                        {{ money(job.final_profit) }}
                    </td>

                    <td><span class="badge">{{ statusLabel(job.status) }}</span></td>

                </tr>
            </tbody>

        </table>

    </div>

    <p v-if="!loading && !jobs.length" class="empty">
        No jobs yet. Convert an accepted estimate to create one.
    </p>

</div>

</template>
