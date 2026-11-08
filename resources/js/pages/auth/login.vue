<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AuthLayout from '../../layouts/AuthLayout.vue';
import FormField from '../../components/FormField.vue';

defineOptions({ layout: false });

const page = usePage();
const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit() {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <Head title="Log in" />
    <AuthLayout title="Log in">
        <div v-if="page.props.flash.status" class="alert alert-success py-2 small" role="status">
            {{ page.props.flash.status }}
        </div>

        <form novalidate @submit.prevent="submit">
            <FormField
                id="email"
                v-model="form.email"
                label="Email"
                type="email"
                autocomplete="username"
                autofocus
                :error="form.errors.email"
            />
            <FormField
                id="password"
                v-model="form.password"
                label="Password"
                type="password"
                autocomplete="current-password"
                :error="form.errors.password"
            />

            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="form-check">
                    <input id="remember" v-model="form.remember" type="checkbox" class="form-check-input">
                    <label for="remember" class="form-check-label">Remember me</label>
                </div>
                <Link href="/forgot-password" class="small">
                    Forgot password?
                </Link>
            </div>

            <button type="submit" class="btn btn-primary w-100" :disabled="form.processing">
                Log in
            </button>
        </form>
    </AuthLayout>
</template>
