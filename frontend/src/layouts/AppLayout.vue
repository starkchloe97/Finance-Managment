<script setup>
import Sidebar from "@/components/layout/Sidebar.vue";
import Navbar from "@/components/layout/Navbar.vue";
</script>

<template>

<div class="app">

    <Sidebar />

    <div class="content">

        <Navbar />

        <main>

            <RouterView />

        </main>

    </div>

</div>

</template>