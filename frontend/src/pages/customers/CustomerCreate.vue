<script setup>
import { reactive } from "vue";
import { useRouter } from "vue-router";
import { createCustomer } from "@/services/customerService";

const router = useRouter();

const form = reactive({
    name: "",
    phone: "",
    email: "",
    company: "",
    address: "",
    notes: "",
});

const submit = async () => {

    await createCustomer(form);

    router.push("/customers");

};
</script>

<template>

<div class="page-head">
    <h1>New Customer</h1>
</div>

<form class="card" @submit.prevent="submit">

    <div class="grid">

        <div class="field">
            <label>Name</label>
            <input v-model="form.name" placeholder="Customer name" required>
        </div>

        <div class="field">
            <label>Phone</label>
            <input v-model="form.phone" placeholder="03001234567">
        </div>

        <div class="field">
            <label>Email</label>
            <input type="email" v-model="form.email" placeholder="ops@company.com">
        </div>

        <div class="field">
            <label>Company</label>
            <input v-model="form.company" placeholder="Company name">
        </div>

    </div>

    <div class="field">
        <label>Address</label>
        <textarea v-model="form.address"></textarea>
    </div>

    <div class="field">
        <label>Notes</label>
        <textarea v-model="form.notes"></textarea>
    </div>

    <div class="actions">
        <button type="submit">Save Customer</button>
        <RouterLink class="btn btn-light" to="/customers">Cancel</RouterLink>
    </div>

</form>

</template>
