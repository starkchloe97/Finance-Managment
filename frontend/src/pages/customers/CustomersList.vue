<script setup>
import { onMounted } from "vue";
import { useCustomerStore } from "@/stores/customerStore";

const store = useCustomerStore();

onMounted(() => {
    store.fetchCustomers();
});

const remove = (customer) => {
    if (confirm(`Delete ${customer.name}?`)) {
        store.deleteCustomer(customer.id);
    }
};
</script>

<template>

<div class="page-head">

    <h1>Customers</h1>

    <RouterLink class="btn" to="/customers/create">
        New Customer
    </RouterLink>

</div>

<div class="card">

    <div class="toolbar">
        <input
            v-model="store.search"
            @input="store.fetchCustomers()"
            placeholder="Search by name, phone or company…"
        />
    </div>

    <div class="table-wrap">

        <table>

            <thead>
                <tr>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Company</th>
                    <th class="right">Actions</th>
                </tr>
            </thead>

            <tbody>
                <tr v-for="customer in store.customers" :key="customer.id">

                    <td>{{ customer.name }}</td>

                    <td>{{ customer.phone || "—" }}</td>

                    <td>{{ customer.company || "—" }}</td>

                    <td class="right">
                        <RouterLink :to="`/customers/${customer.id}/edit`">
                            Edit
                        </RouterLink>

                        <button class="btn-danger btn-sm" @click="remove(customer)">
                            Delete
                        </button>
                    </td>

                </tr>
            </tbody>

        </table>

    </div>

    <p v-if="!store.loading && !store.customers.length" class="empty">
        No customers yet.
    </p>

</div>

</template>
