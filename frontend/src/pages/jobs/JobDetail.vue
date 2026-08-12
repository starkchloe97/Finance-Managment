<script setup>
import { ref, reactive, computed, onMounted } from "vue";
import { useRoute } from "vue-router";
import { getJob } from "@/services/transportJobService";
import { updateBudget } from "@/services/transportJobBudgetService";
import { addExpense, deleteExpense } from "@/services/transportJobExpenseService";

const route = useRoute();

const job = ref(null);

// Budget lines are edited locally and saved as one full list.
const budget = ref([]);

const today = () => new Date().toISOString().slice(0, 10);

const expense = reactive({
    title: "",
    category: "",
    amount: 0,
    expense_date: today(),
});

const money = (value) => Number(value || 0).toLocaleString();

const apply = (data) => {
    job.value = data;

    budget.value = (data.budget_items ?? []).map((item) => ({
        title: item.title,
        category: item.category,
        quantity: Number(item.quantity),
        unit_cost: Number(item.unit_cost),
        notes: item.notes,
    }));
};

const load = async () => {
    const { data } = await getJob(route.params.id);
    apply(data.data);
};

onMounted(load);

const budgetTotal = computed(() =>
    budget.value.reduce(
        (sum, item) => sum + Number(item.quantity || 0) * Number(item.unit_cost || 0),
        0
    )
);

const addRow = () =>
    budget.value.push({ title: "", category: "", quantity: 1, unit_cost: 0, notes: "" });

const removeRow = (index) => budget.value.splice(index, 1);

const saveBudget = async () => {
    try {
        await updateBudget(job.value.id, { items: budget.value });
        await load();
    } catch (error) {
        alert(error.response?.data?.message || "Could not save budget");
    }
};

const saveExpense = async () => {
    try {
        const { data } = await addExpense(job.value.id, expense);
        apply(data.data);
        Object.assign(expense, { title: "", category: "", amount: 0, expense_date: today() });
    } catch (error) {
        alert(error.response?.data?.message || "Could not add expense");
    }
};

const removeExpense = async (id) => {
    const { data } = await deleteExpense(id);
    apply(data.data);
};
</script>

<template>

<div v-if="job">

    <div class="page-head">
        <h1>{{ job.code }}</h1>
        <span class="badge">{{ job.status }}</span>
    </div>

    <div class="stats">

        <div class="stat">
            <div class="label">Quoted to customer</div>
            <div class="value">{{ money(job.quoted_amount) }}</div>
        </div>

        <div class="stat">
            <div class="label">Planned cost</div>
            <div class="value">{{ money(job.planned_cost) }}</div>
        </div>

        <div class="stat">
            <div class="label">Actual cost</div>
            <div class="value">{{ money(job.actual_cost) }}</div>
        </div>

        <div class="stat">
            <div class="label">Profit</div>
            <div class="value">{{ money(job.profit) }}</div>
        </div>

    </div>

    <!-- Budget: what we expect to spend -->
    <div class="card" style="margin-top: 18px">

        <h3>Budget</h3>

        <p class="hint">What we expect to spend. The customer never sees this.</p>

        <div class="table-wrap">

            <table>

                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Category</th>
                        <th width="110">Qty</th>
                        <th width="150">Unit Cost</th>
                        <th width="140" class="right">Amount</th>
                        <th width="60"></th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-for="(item, index) in budget" :key="index">

                        <td><input v-model="item.title" placeholder="Transportation"></td>

                        <td><input v-model="item.category" placeholder="Transport"></td>

                        <td><input type="number" min="0" v-model="item.quantity"></td>

                        <td><input type="number" min="0" v-model="item.unit_cost"></td>

                        <td class="right">
                            {{ money(Number(item.quantity || 0) * Number(item.unit_cost || 0)) }}
                        </td>

                        <td class="right">
                            <button class="btn-danger btn-sm" @click="removeRow(index)">&times;</button>
                        </td>

                    </tr>
                </tbody>

            </table>

        </div>

        <div class="actions">
            <button class="btn-light btn-sm" @click="addRow">+ Add Line</button>
            <button @click="saveBudget">Save Budget</button>
            <span class="muted">Planned total: {{ money(budgetTotal) }}</span>
        </div>

    </div>

    <!-- Expenses: what we actually spent -->
    <div class="card">

        <h3>Expenses</h3>

        <p class="hint">What we actually spent. Each one lowers the profit.</p>

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

        <p v-if="!job.expenses?.length" class="empty">Nothing spent yet.</p>

        <form class="grid" style="margin-top: 14px" @submit.prevent="saveExpense">

            <div class="field">
                <label>Title</label>
                <input v-model="expense.title" placeholder="Truck repair" required>
            </div>

            <div class="field">
                <label>Category</label>
                <input v-model="expense.category" placeholder="Other" required>
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
                <button type="submit">Add Expense</button>
            </div>

        </form>

    </div>

    <div class="actions">
        <RouterLink class="btn btn-light" to="/jobs">Back to jobs</RouterLink>
    </div>

</div>

</template>
