<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AuthLayout from '../../layouts/AuthLayout.vue';
import FormField from '../../components/FormField.vue';

defineOptions({ layout: false });

const page = usePage();
const form = useForm({
    email: '',
});
</script>

<template>
    <Head title="Forgot password" />
    <AuthLayout title="Reset your password">
        <p class="text-body-secondary small">
            Enter your email and we'll send you a link to choose a new password.
        </p>

        <div v-if="page.props.flash.status" class="alert alert-success py-2 small" role="status">
            {{ page.props.flash.status }}
        </div>

        <form novalidate @submit.prevent="form.post('/forgot-password')">
            <FormField
                id="email"
                v-model="form.email"
                label="Email"
                type="email"
                autocomplete="username"
                autofocus
                :error="form.errors.email"
            />

            <button type="submit" class="btn btn-primary w-100" :disabled="form.processing">
                Email reset link
            </button>
        </form>

        <p class="text-center small mt-3 mb-0">
            <Link href="/login">
                Back to log in
            </Link>
        </p>
    </AuthLayout>
</template>
