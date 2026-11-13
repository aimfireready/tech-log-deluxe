<script setup>
import { onBeforeUnmount, ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

const UNDO_SECONDS = 8;
const STATUS_SECONDS = 3;

const page = usePage();
const status = ref(null);
const deleted = ref(null);
const timers = {};

function showFor(target, value, seconds, key) {
    clearTimeout(timers[key]);
    target.value = value;
    timers[key] = setTimeout(() => (target.value = null), seconds * 1000);
}

// Flash values arrive once, on the response right after the action.
watch(() => page.props.flash, (flash) => {
    if (flash?.deleted_entry) {
        showFor(deleted, flash.deleted_entry, UNDO_SECONDS, 'deleted');
    } else if (flash?.status) {
        showFor(status, flash.status, STATUS_SECONDS, 'status');
    }
}, { immediate: true });

function undo() {
    const entry = deleted.value;
    deleted.value = null;
    router.post(`/entries/${entry.id}/restore`, {}, { preserveScroll: true });
}

onBeforeUnmount(() => Object.values(timers).forEach(clearTimeout));
</script>

<template>
    <div class="toast-container position-fixed bottom-0 end-0 p-3" aria-live="polite">
        <div v-if="deleted" class="toast show align-items-center border-0" role="status">
            <div class="d-flex align-items-center">
                <div class="toast-body text-truncate">
                    Deleted “{{ deleted.title }}”.
                </div>
                <button type="button" class="btn btn-sm btn-primary me-2" @click="undo">
                    Undo
                </button>
                <button type="button" class="btn-close me-2" aria-label="Dismiss" @click="deleted = null" />
            </div>
        </div>
        <div v-else-if="status" class="toast show align-items-center border-0" role="status">
            <div class="d-flex">
                <div class="toast-body">
                    {{ status }}
                </div>
                <button type="button" class="btn-close me-2 m-auto" aria-label="Dismiss" @click="status = null" />
            </div>
        </div>
    </div>
</template>
