<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AuthLayout from '@/components/layout/AuthLayout.vue';
import TextField from '@/components/ui/TextField.vue';
import PrimaryButton from '@/components/ui/PrimaryButton.vue';

const { t } = useI18n();

const form = useForm({ password: '' });

function submit(): void {
    form.post('/user/confirm-password', { onFinish: () => form.reset('password') });
}
</script>

<template>
    <Head :title="t('auth.confirmPassword')" />

    <AuthLayout :title="t('auth.confirmPassword')" :subtitle="t('auth.confirmPasswordIntro')">
        <form class="flex flex-col gap-4" @submit.prevent="submit">
            <TextField
                v-model="form.password"
                :label="t('auth.password')"
                type="password"
                autocomplete="current-password"
                :error="form.errors.password"
                required
            />
            <PrimaryButton type="submit" :loading="form.processing">
                {{ t('common.confirm') }}
            </PrimaryButton>
        </form>
    </AuthLayout>
</template>
