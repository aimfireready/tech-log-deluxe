<script setup>
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Timeline" />

    <section class="text-center text-body-secondary py-5" aria-labelledby="timeline-empty-title">
        <h1 id="timeline-empty-title" class="h5 text-body">
            No entries yet
        </h1>
        <p class="mb-0">
            Changes you log will appear here, newest first.
        </p>
    </section>
</template>
