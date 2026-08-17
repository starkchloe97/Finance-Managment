<script setup>
import { ref, onMounted } from "vue";
import { useAuthStore } from "@/stores/authStore";
import { getSummary } from "@/services/reportService";
import { getJobs } from "@/services/transportJobService";
import { money } from "@/utils/money";

const auth = useAuthStore();

const summary = ref(null);
const jobs = ref([]);
const loading = ref(true);
const failed = ref(false);

onMounted(async () => {
    try {
        const [summaryResponse, jobsResponse] = await Promise.all([getSummary(), getJobs()]);
        summary.value = summaryResponse.data;
        jobs.value = (jobsResponse.data?.data ?? []).slice(0, 5);
    } catch {
        // Without this the page sits blank forever on a failed request.
        failed.value = true;
    } finally {
        loading.value = false;
    }
});
</script>

<template>

<div class="page-head">
    <h1>Dashboard</h1>
    <span class="muted">{{ auth.user?.name }}</span>
</div>

<p v-if="loading" class="empty">Loading…</p>

<p v-else-if="failed" class="empty loss">
    Could not load the dashboard. Check that the API is running and try again.
</p>

<!-- The money story across every job, in the order it happens -->
<div class="chain" v-if="summary">

    <div class="step">
        <span>Customers pay</span>
        <b>{{ money(summary.sell_price) }}</b>
    </div>

    <div class="op">−</div>

    <div class="step">
        <span>Our cost</span>
        <b>{{ money(summary.cost_price) }}</b>
    </div>

    <div class="op">=</div>

    <div class="step">
        <span>Base profit</span>
        <b>{{ money(summary.base_profit) }}</b>
    </div>

    <div class="op">−</div>

    <div class="step">
        <span>Unexpected</span>
        <b>{{ money(summary.extra_costs) }}</b>
    </div>

    <div class="op">=</div>

    <div class="step final">
        <span>Final profit</span>
        <b :class="{ loss: Number(summary.final_profit) < 0 }">
            {{ money(summary.final_profit) }}
        </b>
    </div>

</div>

<!-- How the work moves through the system -->
<div class="section-head">
    <h2>How a job flows</h2>
</div>

<div class="flow">

    <div class="step">
        <div class="num">1</div>
        <h4>Quote</h4>
        <p>Enter cost and sell price per line. Profit is known right away.</p>
        <RouterLink class="btn btn-light btn-sm" to="/estimates/create">New Estimate</RouterLink>
    </div>

    <div class="step">
        <div class="num">2</div>
        <h4>Accept</h4>
        <p>Convert the estimate. The job locks in that cost, sell and profit.</p>
        <RouterLink class="btn btn-light btn-sm" to="/estimates">
            {{ summary?.estimates ?? 0 }} estimates
        </RouterLink>
    </div>

    <div class="step">
        <div class="num">3</div>
        <h4>Run the job</h4>
        <p>Record anything unexpected. Each one comes off the profit.</p>
        <RouterLink class="btn btn-light btn-sm" to="/jobs">
            {{ summary?.jobs ?? 0 }} jobs
        </RouterLink>
    </div>

</div>

<div class="section-head">
    <h2>Recent jobs</h2>
    <RouterLink to="/jobs">View all</RouterLink>
</div>

<div class="card">

    <div class="table-wrap">

        <table>

            <thead>
                <tr>
                    <th>Code</th>
                    <th>Customer</th>
                    <th class="right">Base Profit</th>
                    <th class="right">Unexpected</th>
                    <th class="right">Final Profit</th>
                </tr>
            </thead>

            <tbody>
                <tr v-for="job in jobs" :key="job.id">

                    <td><RouterLink :to="`/jobs/${job.id}`">{{ job.code }}</RouterLink></td>

                    <td>{{ job.customer?.name }}</td>

                    <td class="right">{{ money(job.base_profit) }}</td>

                    <td class="right">{{ money(job.extra_costs) }}</td>

                    <td class="right" :class="{ loss: Number(job.final_profit) < 0 }">
                        {{ money(job.final_profit) }}
                    </td>

                </tr>
            </tbody>

        </table>

    </div>

    <p v-if="summary && !jobs.length" class="empty">
        No jobs yet. Start by creating an estimate.
    </p>

</div>

</template>
