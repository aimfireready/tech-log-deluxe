<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AuthLayout from '../../layouts/AuthLayout.vue';
import FormField from '../../components/FormField.vue';

defineOptions({ layout: false });

const form = useForm({
    password: '',
});

function submit() {
    form.post('/user/confirm-password', {
        onFinish: () => form.reset(),
    });
}
</script>

<template>
    <Head title="Confirm password" />
    <AuthLayout title="Confirm your password">
        <p class="text-body-secondary small">
            This is a secure area. Confirm your password to continue.
        </p>

        <form novalidate @submit.prevent="submit">
            <FormField
                id="password"
                v-model="form.password"
                label="Password"
                type="password"
                autocomplete="current-password"
                autofocus
                :error="form.errors.password"
            />

            <button type="submit" class="btn btn-primary w-100" :disabled="form.processing">
                Confirm
            </button>
        </form>
    </AuthLayout>
</template>
