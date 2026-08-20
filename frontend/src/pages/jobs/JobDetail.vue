<script setup>
import { ref, reactive, computed, onMounted } from "vue";
import { useRoute } from "vue-router";
import {
    getJob,
    updateJobStatus,
    updateJobNotes,
    getJobActivities,
} from "@/services/transportJobService";
import {
    addExpense,
    updateExpense,
    deleteExpense,
} from "@/services/transportJobExpenseService";
import { money } from "@/utils/money";
import { nextStatuses, statusLabel } from "@/utils/jobStatus";
import { EXPENSE_CATEGORIES, categoryLabel } from "@/utils/expenseCategories";
import { createDistribution, getJobDistributions, createFinancialAdjustment } from "@/services/investmentFinanceService";

const route = useRoute();

const job = ref(null);

const nextStatus = ref("");

const notes = ref("");

const notesSaved = ref(false);

const activities = ref([]);
const distributions = ref([]);
const distributing = ref(false);
const adjustmentSaving = ref(false);
const adjustmentError = ref("");
const adjustment = reactive({ field: "", old_value: "", new_value: "", reason: "" });

const today = () => new Date().toISOString().slice(0, 10);

const blankExpense = () => ({
    title: "",
    category: "",
    // Empty rather than 0 — zero is not a valid cost, so it should not be the
    // value the form opens with.
    amount: "",
    expense_date: today(),
    notes: "",
});

const expense = reactive(blankExpense());

// Set while editing an existing cost — the same form serves both, so this is
// what decides whether saving adds or updates.
const editingId = ref(null);

// Field-level messages straight from the server's 422, keyed by field name.
// The rules live in ExpenseRequest and are not restated here.
const errors = ref({});

const saving = ref(false);

const notice = ref("");

// Display grouping only. Each line's amount is what the server stored, and the
// grand total below comes from the job itself rather than being summed here.
const byCategory = computed(() => {
    const groups = new Map();

    for (const item of job.value?.expenses ?? []) {
        if (!groups.has(item.category)) {
            groups.set(item.category, { category: item.category, items: [], subtotal: 0 });
        }

        const group = groups.get(item.category);
        group.items.push(item);
        group.subtotal += Number(item.amount || 0);
    }

    return [...groups.values()].sort((a, b) => b.subtotal - a.subtotal);
});

const isLoss = computed(() => Number(job.value?.final_profit) < 0);

// Every change to the job writes to its timeline, so anything that updates the
// job re-reads it.
const loadActivities = async () => {
    const { data } = await getJobActivities(job.value.id);
    activities.value = data.data;
};

const apply = (updated) => {
    job.value = updated;
    notes.value = updated.internal_notes || "";
};

const load = async () => {
    const { data } = await getJob(route.params.id);
    apply(data.data);
    distributions.value = (await getJobDistributions(route.params.id)).data.data;
    await loadActivities();
};

const distribute = async (allocation) => {
    if (distributing.value || job.value.financially_locked_at) return;

    distributing.value = true;
    try {
        await createDistribution(job.value.id, { investment_id: allocation.investment_id });
        await load();
    } catch (error) {
        alert(error.response?.data?.message || "Could not calculate distribution");
    } finally {
        distributing.value = false;
    }
};

const saveAdjustment = async () => {
    if (adjustmentSaving.value) return;

    adjustmentSaving.value = true;
    adjustmentError.value = "";

    try {
        await createFinancialAdjustment(job.value.id, adjustment);
        Object.assign(adjustment, { field: "", old_value: "", new_value: "", reason: "" });
        await load();
    } catch (error) {
        adjustmentError.value = error.response?.data?.errors?.reason?.[0]
            || error.response?.data?.errors?.field?.[0]
            || error.response?.data?.message
            || "Could not record the adjustment.";
    } finally {
        adjustmentSaving.value = false;
    }
};

onMounted(load);

// Take the job back from the response rather than assuming the move went
// through — the server has the final say on which stage may follow which.
const moveStatus = async () => {
    const target = nextStatus.value;

    if (!target) return;

    if (!confirm(`Move ${job.value.code} to ${statusLabel(target)}?`)) return;

    try {
        const { data } = await updateJobStatus(job.value.id, target);
        apply(data.data);
        nextStatus.value = "";
        await loadActivities();
    } catch (error) {
        alert(error.response?.data?.message || "Could not update status");
    }
};

const saveNotes = async () => {
    notesSaved.value = false;

    try {
        const { data } = await updateJobNotes(job.value.id, notes.value);
        apply(data.data);
        notesSaved.value = true;
        await loadActivities();
    } catch (error) {
        alert(error.response?.data?.message || "Could not save notes");
    }
};

const startEdit = (item) => {
    editingId.value = item.id;
    errors.value = {};
    notice.value = "";

    Object.assign(expense, {
        title: item.title,
        category: item.category,
        amount: Number(item.amount),
        expense_date: String(item.expense_date).slice(0, 10),
        notes: item.notes || "",
    });
};

const cancelEdit = () => {
    editingId.value = null;
    errors.value = {};
    Object.assign(expense, blankExpense());
};

// Adding and editing are the same form and the same rules, so they are the
// same submit. Either way the response carries the job with its totals already
// recalculated — nothing here works them out.
const saveExpense = async () => {
    // The button is disabled while saving, but guard the handler too so a
    // second submit cannot slip through.
    if (saving.value) return;

    saving.value = true;
    errors.value = {};
    notice.value = "";

    const wasEditing = Boolean(editingId.value);

    try {
        const { data } = wasEditing
            ? await updateExpense(job.value.id, editingId.value, expense)
            : await addExpense(job.value.id, expense);

        apply(data.data);
        cancelEdit();
        notice.value = wasEditing
            ? "Unexpected cost updated successfully."
            : "Unexpected cost added successfully.";
        await loadActivities();
    } catch (error) {
        // 422 carries per-field messages; anything else only has a summary.
        errors.value = error.response?.data?.errors || {};

        if (!Object.keys(errors.value).length) {
            notice.value = error.response?.data?.message || "Could not save the cost.";
        }
    } finally {
        saving.value = false;
    }
};

const removeExpense = async (item) => {
    if (saving.value) return;

    if (!confirm(`Delete "${item.title}" (${money(item.amount)})? This cannot be undone.`)) return;

    saving.value = true;
    notice.value = "";

    try {
        const { data } = await deleteExpense(job.value.id, item.id);

        apply(data.data);

        if (editingId.value === item.id) cancelEdit();

        notice.value = "Unexpected cost removed successfully.";
        await loadActivities();
    } catch (error) {
        notice.value = error.response?.data?.message || "Could not delete the cost.";
    } finally {
        saving.value = false;
    }
};

const when = (value) => new Date(value).toLocaleString();
</script>

<template>

<div v-if="job">

    <div class="page-head">

        <div class="head-title">
            <h1>{{ job.code }}</h1>
            <span class="badge">{{ statusLabel(job.status) }}</span>
        </div>

        <form
            v-if="nextStatuses(job.status).length"
            class="status-move"
            @submit.prevent="moveStatus"
        >
            <select v-model="nextStatus">
                <option value="">Move to&hellip;</option>
                <option v-for="stage in nextStatuses(job.status)" :key="stage" :value="stage">
                    {{ statusLabel(stage) }}
                </option>
            </select>

            <button type="submit" :disabled="!nextStatus">Update Status</button>
        </form>

        <span v-else class="hint">Delivered and completed — no stages left.</span>

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
            <b :class="{ loss: isLoss }">
                {{ money(job.final_profit) }}
            </b>
            <span v-if="isLoss" class="badge badge-loss">LOSS</span>
        </div>

    </div>

    <p class="hint" style="margin-top: 12px">
        Base profit was agreed when the job was quoted and does not change. Anything
        unexpected below is a company loss and comes straight off it.
    </p>

    <p v-if="isLoss" class="hint loss">
        Unexpected costs have overtaken the profit — this job is running at a loss.
    </p>

    <div class="card">
        <h3>Funding</h3>
        <p v-if="!job.allocations?.length" class="empty">No investor funding allocated.</p>
        <table v-else>
            <thead><tr><th>Investor</th><th>Allocation</th><th></th></tr></thead>
            <tbody><tr v-for="allocation in job.allocations" :key="allocation.id"><td>{{ allocation.investment?.investor?.name || '-' }}</td><td class="right">{{ money(allocation.amount) }}</td><td><button v-if="allocation.status === 'active' && !job.financially_locked_at" :disabled="distributing" @click="distribute(allocation)">Calculate distribution</button></td></tr></tbody>
        </table>
        <p v-if="job.financially_locked_at" class="hint">Financially locked.</p>
    </div>

    <div class="card">
        <h3>Profit Distributions</h3>
        <p v-if="!distributions.length" class="empty">No distributions calculated.</p>
        <table v-else><thead><tr><th>Investor</th><th>Share</th><th class="right">Profit</th></tr></thead><tbody><tr v-for="distribution in distributions" :key="distribution.id"><td>{{ distribution.investor?.name }}</td><td>{{ distribution.profit_share_value }}</td><td class="right">{{ money(distribution.profit_amount) }}</td></tr></tbody></table>
    </div>

    <div class="card">
        <h3>Financial Adjustments</h3>
        <p class="hint">Keep a reasoned audit record for any correction made after financial review.</p>
        <p v-if="!job.financial_adjustments?.length" class="empty">No financial adjustments recorded.</p>
        <table v-else>
            <thead><tr><th>Field</th><th>Previous</th><th>Corrected</th><th>Reason</th><th>By</th></tr></thead>
            <tbody><tr v-for="item in job.financial_adjustments" :key="item.id"><td>{{ item.field }}</td><td>{{ item.old_value || '-' }}</td><td>{{ item.new_value || '-' }}</td><td>{{ item.reason }}</td><td>{{ item.author?.name || 'system' }}</td></tr></tbody>
        </table>

        <form class="grid" style="margin-top: 14px" @submit.prevent="saveAdjustment">
            <div class="field"><label>Field corrected</label><input v-model="adjustment.field" :disabled="adjustmentSaving" placeholder="Unexpected cost"></div>
            <div class="field"><label>Previous value</label><input v-model="adjustment.old_value" :disabled="adjustmentSaving"></div>
            <div class="field"><label>Corrected value</label><input v-model="adjustment.new_value" :disabled="adjustmentSaving"></div>
            <div class="field"><label>Reason</label><input v-model="adjustment.reason" :disabled="adjustmentSaving" required placeholder="Supporting document corrected the amount"></div>
            <div class="field actions" style="align-self: end"><button type="submit" :disabled="adjustmentSaving">{{ adjustmentSaving ? 'Recording…' : 'Record Adjustment' }}</button></div>
        </form>
        <p v-if="adjustmentError" class="error">{{ adjustmentError }}</p>
    </div>

    <div class="card">

        <h3>Unexpected Costs</h3>

        <p class="hint">
            Only costs that were not in the quote. Each one lowers the final profit.
        </p>

        <div class="table-wrap" v-if="job.expenses?.length">

            <table>

                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Date</th>
                        <th class="right">Amount</th>
                        <th width="130"></th>
                    </tr>
                </thead>

                <!-- Grouped by category for reading. The subtotals are summed
                     from the lines; the grand total is the job's own figure. -->
                <tbody v-for="group in byCategory" :key="group.category">

                    <tr class="group-head">
                        <th colspan="4">{{ categoryLabel(group.category) }}</th>
                    </tr>

                    <tr v-for="item in group.items" :key="item.id" :class="{ editing: editingId === item.id }">

                        <td>
                            {{ item.title }}
                            <span v-if="item.notes" class="hint">{{ item.notes }}</span>
                        </td>

                        <td>{{ String(item.expense_date).slice(0, 10) }}</td>

                        <td class="right">{{ money(item.amount) }}</td>

                        <td class="right">
                            <button class="btn-light btn-sm" :disabled="saving" @click="startEdit(item)">
                                Edit
                            </button>
                            <button class="btn-danger btn-sm" :disabled="saving" @click="removeExpense(item)">
                                Delete
                            </button>
                        </td>

                    </tr>

                    <tr class="group-total">
                        <td colspan="2">{{ categoryLabel(group.category) }} subtotal</td>
                        <td class="right">{{ money(group.subtotal) }}</td>
                        <td></td>
                    </tr>

                </tbody>

                <tfoot>
                    <tr class="grand-total">
                        <td colspan="2">Total unexpected costs</td>
                        <td class="right">{{ money(job.extra_costs) }}</td>
                        <td></td>
                    </tr>
                </tfoot>

            </table>

        </div>

        <p v-else class="empty">
            Nothing unexpected so far — the job is running to plan.
        </p>

        <p v-if="notice" class="notice">{{ notice }}</p>

        <!-- The messages under each field come from the server, so the form
             cannot drift out of step with what the API actually enforces. -->
        <form class="grid" style="margin-top: 14px" @submit.prevent="saveExpense">

            <div class="field">
                <label>Title</label>
                <input
                    v-model="expense.title"
                    maxlength="255"
                    placeholder="Engine repair"
                    :class="{ invalid: errors.title }"
                    :disabled="saving"
                >
                <small v-if="errors.title" class="error">{{ errors.title[0] }}</small>
            </div>

            <div class="field">
                <label>Category</label>
                <select
                    v-model="expense.category"
                    :class="{ invalid: errors.category }"
                    :disabled="saving"
                >
                    <option value="" disabled>Choose one&hellip;</option>
                    <option v-for="option in EXPENSE_CATEGORIES" :key="option.value" :value="option.value">
                        {{ option.label }}
                    </option>
                </select>
                <small v-if="errors.category" class="error">{{ errors.category[0] }}</small>
            </div>

            <div class="field">
                <label>Amount</label>
                <input
                    type="number"
                    min="0.01"
                    step="0.01"
                    v-model="expense.amount"
                    :class="{ invalid: errors.amount }"
                    :disabled="saving"
                >
                <small v-if="errors.amount" class="error">{{ errors.amount[0] }}</small>
            </div>

            <div class="field">
                <label>Date</label>
                <input
                    type="date"
                    v-model="expense.expense_date"
                    :class="{ invalid: errors.expense_date }"
                    :disabled="saving"
                >
                <small v-if="errors.expense_date" class="error">{{ errors.expense_date[0] }}</small>
            </div>

            <div class="field">
                <label>Notes</label>
                <input
                    v-model="expense.notes"
                    placeholder="Engine overheated during the Lahore run"
                    :class="{ invalid: errors.notes }"
                    :disabled="saving"
                >
                <small v-if="errors.notes" class="error">{{ errors.notes[0] }}</small>
            </div>

            <div class="field actions" style="align-self: end">
                <button type="submit" :disabled="saving">
                    {{ saving ? "Saving&hellip;" : editingId ? "Save Changes" : "Add Cost" }}
                </button>
                <button
                    v-if="editingId"
                    type="button"
                    class="btn-light"
                    :disabled="saving"
                    @click="cancelEdit"
                >
                    Cancel
                </button>
            </div>

        </form>

    </div>

    <div class="card">

        <h3>Internal Notes</h3>

        <p class="hint">
            For whoever is running the job. Never shown to the customer and not part
            of the quote.
        </p>

        <form @submit.prevent="saveNotes">

            <textarea
                v-model="notes"
                placeholder="Driver says the crane will be an hour late&hellip;"
            ></textarea>

            <div class="actions" style="margin-top: 12px">
                <button type="submit">Save Notes</button>
                <span v-if="notesSaved" class="hint">Saved.</span>
            </div>

        </form>

    </div>

    <!-- Written by the server as things happen. Read-only on purpose. -->
    <div class="card">

        <h3>Activity Timeline</h3>

        <p class="hint">A record of what happened to this job, newest first.</p>

        <ul class="timeline">

            <li v-for="item in activities" :key="item.id">

                <div class="timeline-when">{{ when(item.created_at) }}</div>

                <div class="timeline-what">
                    <b>{{ item.description }}</b>
                    <span class="hint">{{ item.author || "system" }}</span>
                </div>

            </li>

        </ul>

        <p v-if="!activities.length" class="empty">Nothing recorded yet.</p>

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
