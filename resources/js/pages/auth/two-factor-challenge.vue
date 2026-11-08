<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthLayout from '../../layouts/AuthLayout.vue';
import FormField from '../../components/FormField.vue';

defineOptions({ layout: false });

const useRecoveryCode = ref(false);
const form = useForm({
    code: '',
    recovery_code: '',
});

function toggle() {
    useRecoveryCode.value = !useRecoveryCode.value;
    form.reset();
    form.clearErrors();
}
</script>

<template>
    <Head title="Two-factor authentication" />
    <AuthLayout title="Two-factor authentication">
        <p class="text-body-secondary small">
            {{ useRecoveryCode
                ? 'Enter one of your emergency recovery codes.'
                : 'Enter the code from your authenticator app.' }}
        </p>

        <form novalidate @submit.prevent="form.post('/two-factor-challenge')">
            <FormField
                v-if="useRecoveryCode"
                id="recovery_code"
                v-model="form.recovery_code"
                label="Recovery code"
                autocomplete="one-time-code"
                autofocus
                :error="form.errors.recovery_code"
            />
            <FormField
                v-else
                id="code"
                v-model="form.code"
                label="Code"
                inputmode="numeric"
                autocomplete="one-time-code"
                autofocus
                :error="form.errors.code"
            />

            <button type="submit" class="btn btn-primary w-100" :disabled="form.processing">
                Continue
            </button>
        </form>

        <p class="text-center small mt-3 mb-0">
            <button type="button" class="btn btn-link btn-sm p-0" @click="toggle">
                {{ useRecoveryCode ? 'Use an authenticator code' : 'Use a recovery code' }}
            </button>
        </p>
    </AuthLayout>
</template>
