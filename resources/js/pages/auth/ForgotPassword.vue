<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AuthLayout from '@/components/layout/AuthLayout.vue';
import TextField from '@/components/ui/TextField.vue';
import PrimaryButton from '@/components/ui/PrimaryButton.vue';

defineProps<{ status?: string | null }>();

const { t } = useI18n();

const form = useForm({ email: '' });
</script>

<template>
    <Head :title="t('auth.resetPassword')" />

    <AuthLayout :title="t('auth.resetPassword')" :subtitle="t('auth.forgotPasswordIntro')">
        <p v-if="status" role="status" class="rounded-xl bg-success/15 px-4 py-3 text-sm text-success">
            {{ status }}
        </p>

        <form class="flex flex-col gap-4" @submit.prevent="form.post('/forgot-password')">
            <TextField
                v-model="form.email"
                :label="t('auth.email')"
                type="email"
                autocomplete="email"
                :error="form.errors.email"
                required
            />
            <PrimaryButton type="submit" :loading="form.processing">
                {{ t('auth.sendResetLink') }}
            </PrimaryButton>
        </form>

        <Link href="/login" class="text-center text-sm text-foam/70 underline">
            {{ t('common.back') }}
        </Link>
    </AuthLayout>
</template>
