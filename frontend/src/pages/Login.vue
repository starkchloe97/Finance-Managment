<script setup>
import { reactive } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "@/stores/authStore";

const router = useRouter();

const auth = useAuthStore();

const form = reactive({
    email: "",
    password: "",
});

const submit = async () => {
    try {
        await auth.login(form);

        router.push("/");
    } catch (error) {
        alert(error.response?.data?.message || "Login failed");
    }
};
</script>

<template>
    <div class="login">

        <form @submit.prevent="submit">

            <h2>Transport ERP</h2>

            <input
                type="email"
                v-model="form.email"
                placeholder="Email"
            />

            <input
                type="password"
                v-model="form.password"
                placeholder="Password"
            />

            <button type="submit">

                Login

            </button>

        </form>

    </div>
</template>