<script setup>
import { ref, reactive, onMounted } from "vue";
import { useRoute } from "vue-router";
import { getJob } from "@/services/transportJobService";
import { addExpense, deleteExpense } from "@/services/transportJobExpenseService";
import { money } from "@/utils/money";

const route = useRoute();

const job = ref(null);

const today = () => new Date().toISOString().slice(0, 10);

const expense = reactive({
    title: "",
    category: "",
    amount: 0,
    expense_date: today(),
});

const load = async () => {
    const { data } = await getJob(route.params.id);
    job.value = data.data;
};

onMounted(load);

const saveExpense = async () => {
    try {
        const { data } = await addExpense(job.value.id, expense);
        job.value = data.data;
        Object.assign(expense, { title: "", category: "", amount: 0, expense_date: today() });
    } catch (error) {
        alert(error.response?.data?.message || "Could not add expense");
    }
};

const removeExpense = async (id) => {
    const { data } = await deleteExpense(id);
    job.value = data.data;
};
</script>

<template>

<div v-if="job">

    <div class="page-head">
        <h1>{{ job.code }}</h1>
        <span class="badge">{{ job.status }}</span>
    </div>

    <div class="chain">

        <div class="step">
            <span>Customer pays</span>
            <b>{{ money(job.sell_price) }}</b>
        </div>

        <div class="op">−</div>

        <div class="step">
            <span>Our cost</span>
            <b>{{ money(job.cost_price) }}</b>
        </div>

        <div class="op">=</div>

        <div class="step">
            <span>Base profit</span>
            <b>{{ money(job.base_profit) }}</b>
        </div>

        <div class="op">−</div>

        <div class="step">
            <span>Unexpected</span>
            <b>{{ money(job.extra_costs) }}</b>
        </div>

        <div class="op">=</div>

        <div class="step final">
            <span>Final profit</span>
            <b :class="{ loss: Number(job.final_profit) < 0 }">
                {{ money(job.final_profit) }}
            </b>
        </div>

    </div>

    <p class="hint" style="margin-top: 12px">
        Base profit was agreed when the job was quoted and does not change. Anything
        unexpected below is a company loss and comes straight off it.
    </p>

    <p v-if="Number(job.final_profit) < 0" class="hint loss">
        Unexpected costs have overtaken the profit — this job is running at a loss.
    </p>

    <div class="card">

        <h3>Unexpected Costs</h3>

        <p class="hint">
            Only costs that were not in the quote. Each one lowers the final profit.
        </p>

        <div class="table-wrap">

            <table>

                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Date</th>
                        <th class="right">Amount</th>
                        <th width="60"></th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-for="item in job.expenses" :key="item.id">

                        <td>{{ item.title }}</td>

                        <td>{{ item.category }}</td>

                        <td>{{ String(item.expense_date).slice(0, 10) }}</td>

                        <td class="right">{{ money(item.amount) }}</td>

                        <td class="right">
                            <button class="btn-danger btn-sm" @click="removeExpense(item.id)">
                                &times;
                            </button>
                        </td>

                    </tr>
                </tbody>

            </table>

        </div>

        <p v-if="!job.expenses?.length" class="empty">
            Nothing unexpected so far — the job is running to plan.
        </p>

        <form class="grid" style="margin-top: 14px" @submit.prevent="saveExpense">

            <div class="field">
                <label>Title</label>
                <input v-model="expense.title" placeholder="Truck repair" required>
            </div>

            <div class="field">
                <label>Category</label>
                <input v-model="expense.category" placeholder="Breakdown" required>
            </div>

            <div class="field">
                <label>Amount</label>
                <input type="number" min="0" v-model="expense.amount" required>
            </div>

            <div class="field">
                <label>Date</label>
                <input type="date" v-model="expense.expense_date" required>
            </div>

            <div class="field" style="align-self: end">
                <button type="submit">Add Cost</button>
            </div>

        </form>

    </div>

    <!-- The quote the job was taken on, for reference -->
    <div class="card" v-if="job.estimate">

        <h3>Quoted Lines</h3>

        <div class="table-wrap">

            <table>

                <thead>
                    <tr>
                        <th>Title</th>
                        <th class="right">Qty</th>
                        <th class="right">Cost</th>
                        <th class="right">Sell</th>
                        <th class="right">Profit</th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-for="item in job.estimate.items" :key="item.id">
                        <td>{{ item.title }}</td>
                        <td class="right">{{ Number(item.quantity) }}</td>
                        <td class="right">{{ money(item.cost_total) }}</td>
                        <td class="right">{{ money(item.sell_total) }}</td>
                        <td class="right">{{ money(item.profit) }}</td>
                    </tr>
                </tbody>

            </table>

        </div>

    </div>

    <div class="actions">
        <RouterLink class="btn btn-light" to="/jobs">Back to jobs</RouterLink>
    </div>

</div>

</template>
