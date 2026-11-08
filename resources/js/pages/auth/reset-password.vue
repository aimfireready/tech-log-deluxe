<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AuthLayout from '../../layouts/AuthLayout.vue';
import FormField from '../../components/FormField.vue';

defineOptions({ layout: false });

const props = defineProps({
    token: { type: String, required: true },
    email: { type: String, default: '' },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post('/reset-password', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
    <Head title="Choose a new password" />
    <AuthLayout title="Choose a new password">
        <form novalidate @submit.prevent="submit">
            <FormField
                id="email"
                v-model="form.email"
                label="Email"
                type="email"
                autocomplete="username"
                :error="form.errors.email"
            />
            <FormField
                id="password"
                v-model="form.password"
                label="New password"
                type="password"
                autocomplete="new-password"
                autofocus
                :error="form.errors.password"
            />
            <FormField
                id="password_confirmation"
                v-model="form.password_confirmation"
                label="Confirm new password"
                type="password"
                autocomplete="new-password"
                :error="form.errors.password_confirmation"
            />

            <button type="submit" class="btn btn-primary w-100" :disabled="form.processing">
                Reset password
            </button>
        </form>
    </AuthLayout>
</template>
