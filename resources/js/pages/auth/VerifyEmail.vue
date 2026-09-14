<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AuthLayout from '@/components/layout/AuthLayout.vue';
import PrimaryButton from '@/components/ui/PrimaryButton.vue';

defineProps<{ status?: string | null }>();

const { t } = useI18n();

const resend = useForm({});
const logout = useForm({});
</script>

<template>
    <Head :title="t('auth.verifyEmail')" />

    <AuthLayout :title="t('auth.verifyEmail')" :subtitle="t('auth.verifyEmailIntro')">
        <p
            v-if="status === 'verification-link-sent'"
            role="status"
            class="rounded-xl bg-success/15 px-4 py-3 text-sm text-success"
        >
            {{ t('auth.verificationSent') }}
        </p>

        <div class="flex flex-col gap-3">
            <PrimaryButton
                type="button"
                :loading="resend.processing"
                @click="resend.post('/email/verification-notification')"
            >
                {{ t('auth.resendVerification') }}
            </PrimaryButton>
            <PrimaryButton variant="ghost" type="button" @click="logout.post('/logout')">
                {{ t('auth.logout') }}
            </PrimaryButton>
        </div>
    </AuthLayout>
</template>
