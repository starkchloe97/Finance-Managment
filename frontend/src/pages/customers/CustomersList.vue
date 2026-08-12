<script setup>
import { onMounted } from "vue";
import { useCustomerStore } from "@/stores/customerStore";

const store = useCustomerStore();

onMounted(() => {
    store.fetchCustomers();
});
</script>

<template>
    <div>
        <h1>Customers</h1>

        <RouterLink to="/customers/create">
            Add Customer
        </RouterLink>
<input
    v-model="store.search"
    @input="store.fetchCustomers()"
    placeholder="Search customer..."
>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Company</th>
                    <th></th>
                </tr>
            </thead>

            <tbody>
                <tr
                    v-for="customer in store.customers"
                    :key="customer.id"
                >
                    <td>{{ customer.name }}</td>
                    <td>{{ customer.phone }}</td>
                    <td>{{ customer.company }}</td>
                    <button @click="store.deleteCustomer(customer.id)">
    Delete
</button>

                    <td>
                        <RouterLink :to="`/customers/${customer.id}/edit`">
                            Edit
                        </RouterLink>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>