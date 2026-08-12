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

<h2>Create Customer</h2>

<form @submit.prevent="submit">

<input v-model="form.name" placeholder="Name">

<input v-model="form.phone" placeholder="Phone">

<input v-model="form.email" placeholder="Email">

<input v-model="form.company" placeholder="Company">

<textarea v-model="form.address"></textarea>

<textarea v-model="form.notes"></textarea>

<button>

Save

</button>

</form>

</template>