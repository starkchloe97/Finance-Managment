<script setup>
import { ref, onMounted } from "vue";
import { getJobs } from "@/services/transportJobService";

const jobs = ref([]);
const loading = ref(true);

onMounted(async () => {
    const { data } = await getJobs();
    jobs.value = data?.data ?? [];
    loading.value = false;
});

const money = (value) => Number(value || 0).toLocaleString();
</script>

<template>

<div class="page-head">
    <h1>Jobs</h1>
</div>

<div class="card">

    <p class="hint">
        Quoted is what the customer pays. Planned and actual are what it costs us.
    </p>

    <div class="table-wrap">

        <table>

            <thead>
                <tr>
                    <th>Code</th>
                    <th>Customer</th>
                    <th class="right">Quoted</th>
                    <th class="right">Planned</th>
                    <th class="right">Actual</th>
                    <th class="right">Profit</th>
                    <th>Status</th>
                </tr>
            </thead>

            <tbody>
                <tr v-for="job in jobs" :key="job.id">

                    <td>
                        <RouterLink :to="`/jobs/${job.id}`">{{ job.code }}</RouterLink>
                    </td>

                    <td>{{ job.customer?.name }}</td>

                    <td class="right">{{ money(job.quoted_amount) }}</td>

                    <td class="right">{{ money(job.planned_cost) }}</td>

                    <td class="right">{{ money(job.actual_cost) }}</td>

                    <td class="right">{{ money(job.profit) }}</td>

                    <td><span class="badge">{{ job.status }}</span></td>

                </tr>
            </tbody>

        </table>

    </div>

    <p v-if="!loading && !jobs.length" class="empty">
        No jobs yet. Convert an accepted estimate to create one.
    </p>

</div>

</template>
