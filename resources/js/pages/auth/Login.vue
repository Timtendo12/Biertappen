<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AuthLayout from '@/components/layout/AuthLayout.vue';
import TextField from '@/components/ui/TextField.vue';
import PrimaryButton from '@/components/ui/PrimaryButton.vue';
import GoogleButton from '@/components/ui/GoogleButton.vue';

defineProps<{ canResetPassword?: boolean; status?: string | null }>();

const { t } = useI18n();

const form = useForm({ email: '', password: '', remember: false });

function submit(): void {
    form.post('/login', { onFinish: () => form.reset('password') });
}
</script>

<template>
    <Head :title="t('auth.login')" />

    <AuthLayout :title="t('auth.login')">
        <p v-if="status" role="status" class="rounded-xl bg-success/15 px-4 py-3 text-sm text-success">
            {{ status }}
        </p>

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
                autocomplete="current-password"
                :error="form.errors.password"
                required
            />

            <PrimaryButton type="submit" :loading="form.processing">
                {{ t('auth.login') }}
            </PrimaryButton>
        </form>

        <GoogleButton />

        <div class="flex flex-col items-center gap-2 text-sm text-foam/70">
            <Link v-if="canResetPassword" href="/forgot-password" class="underline">
                {{ t('auth.forgotPassword') }}
            </Link>
            <p>
                {{ t('auth.noAccount') }}
                <Link href="/register" class="font-semibold text-beer underline">
                    {{ t('auth.register') }}
                </Link>
            </p>
        </div>
    </AuthLayout>
</template>
