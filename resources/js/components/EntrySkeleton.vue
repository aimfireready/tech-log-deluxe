<script setup>
defineProps({
    count: { type: Number, default: 3 },
});
</script>

<template>
    <div aria-hidden="true">
        <div v-for="n in count" :key="n" class="card tl-entry mb-2">
            <div class="card-body py-3">
                <div class="d-flex gap-3">
                    <div class="tl-skeleton" style="width: 3rem" />
                    <div class="flex-grow-1">
                        <div class="tl-skeleton mb-2" style="width: 55%; height: 1.1rem" />
                        <div class="tl-skeleton mb-1" style="width: 92%" />
                        <div class="tl-skeleton" style="width: 70%" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
