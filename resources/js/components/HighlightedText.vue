<script setup>
// Renders search-highlight segments from the server as text, so no markup is ever injected.
defineProps({
    segments: { type: Array, required: true },
});
</script>

<template>
    <template v-for="(segment, index) in segments" :key="index">
        <mark v-if="segment.match" class="tl-mark">{{ segment.text }}</mark>
        <template v-else>
            {{ segment.text }}
        </template>
    </template>
</template>
