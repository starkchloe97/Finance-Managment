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

<h2>Edit Customer</h2>

<form @submit.prevent="submit">

<input v-model="form.name">

<input v-model="form.phone">

<input v-model="form.email">

<input v-model="form.company">

<textarea v-model="form.address"></textarea>

<textarea v-model="form.notes"></textarea>

<button>

Update

</button>

</form>

</template>