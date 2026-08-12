<script setup>
import { useRoute } from "vue-router";

const route = useRoute();
</script>

<template>

<div class="page-head">
    <h1>Edit Estimate</h1>
</div>

<div class="card">

    <p class="empty">
        Estimate #{{ route.params.id }} — editing is not implemented yet.
    </p>

    <div class="actions">
        <RouterLink class="btn btn-light" to="/estimates">Back to estimates</RouterLink>
    </div>

</div>

</template>
