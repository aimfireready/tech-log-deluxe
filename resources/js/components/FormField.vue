<script setup>
const model = defineModel({ type: String, default: '' });

defineProps({
    id: { type: String, required: true },
    label: { type: String, required: true },
    type: { type: String, default: 'text' },
    error: { type: String, default: null },
    autocomplete: { type: String, default: null },
    autofocus: { type: Boolean, default: false },
    inputmode: { type: String, default: null },
});
</script>

<template>
    <div class="mb-3">
        <label :for="id" class="form-label">{{ label }}</label>
        <input
            :id="id"
            v-model="model"
            :type="type"
            :autocomplete="autocomplete"
            :autofocus="autofocus"
            :inputmode="inputmode"
            :aria-invalid="error ? 'true' : null"
            :aria-describedby="error ? `${id}-error` : null"
            class="form-control"
            :class="{ 'is-invalid': error }"
        >
        <div v-if="error" :id="`${id}-error`" class="invalid-feedback">
            {{ error }}
        </div>
    </div>
</template>
