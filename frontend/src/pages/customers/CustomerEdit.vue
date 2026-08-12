<script setup>
import { reactive, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import {
    getCustomer,
    updateCustomer,
} from "@/services/customerService";

const route = useRoute();

const router = useRouter();

const form = reactive({
    name: "",
    phone: "",
    email: "",
    company: "",
    address: "",
    notes: "",
});

onMounted(async () => {

    const { data } = await getCustomer(route.params.id);

    Object.assign(form, data.data);

});

const submit = async () => {

    await updateCustomer(route.params.id, form);

    router.push("/customers");

};
</script>

<template>

<div class="page-head">
    <h1>Edit Customer</h1>
</div>

<form class="card" @submit.prevent="submit">

    <div class="grid">

        <div class="field">
            <label>Name</label>
            <input v-model="form.name" required>
        </div>

        <div class="field">
            <label>Phone</label>
            <input v-model="form.phone">
        </div>

        <div class="field">
            <label>Email</label>
            <input type="email" v-model="form.email">
        </div>

        <div class="field">
            <label>Company</label>
            <input v-model="form.company">
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
        <button type="submit">Update Customer</button>
        <RouterLink class="btn btn-light" to="/customers">Cancel</RouterLink>
    </div>

</form>

</template>
