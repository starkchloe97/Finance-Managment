
<script setup>
import * as jobService from "@/services/transportJobService";

const convert = async (id) => {

    await jobService.convertEstimate(id);

    alert("Job Created");

};
</script>

<template>

    <button
    @click="convert(estimate.id)"
    >
    Convert To Job
</button>

</template>