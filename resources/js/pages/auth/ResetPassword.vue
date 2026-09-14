<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AuthLayout from '@/components/layout/AuthLayout.vue';
import TextField from '@/components/ui/TextField.vue';
import PrimaryButton from '@/components/ui/PrimaryButton.vue';

const props = defineProps<{ email: string; token: string }>();

const { t } = useI18n();

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

function submit(): void {
    form.post('/reset-password', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
    <Head :title="t('auth.resetPassword')" />

    <AuthLayout :title="t('auth.resetPassword')">
        <form class="flex flex-col gap-4" @submit.prevent="submit">
            <TextField
                v-model="form.email"
                :label="t('auth.email')"
                type="email"
                autocomplete="email"
                :error="form.errors.email"
                required
            />
            <TextField
                v-model="form.password"
                :label="t('auth.password')"
                type="password"
                autocomplete="new-password"
                :error="form.errors.password"
                required
            />
            <TextField
                v-model="form.password_confirmation"
                :label="t('auth.passwordConfirm')"
                type="password"
                autocomplete="new-password"
                :error="form.errors.password_confirmation"
                required
            />
            <PrimaryButton type="submit" :loading="form.processing">
                {{ t('auth.resetPassword') }}
            </PrimaryButton>
        </form>
    </AuthLayout>
</template>
