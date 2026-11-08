<script setup>
defineProps({
    title: { type: String, required: true },
});
</script>

<template>
    <main class="min-vh-100 d-flex align-items-center justify-content-center px-3">
        <div class="tl-auth-card w-100">
            <p class="text-center text-body-secondary small text-uppercase fw-semibold mb-2">
                Tech Log
            </p>
            <div class="card border-secondary-subtle shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h5 mb-3">
                        {{ title }}
                    </h1>
                    <slot />
                </div>
            </div>
        </div>
    </main>
</template>
