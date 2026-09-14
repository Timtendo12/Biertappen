<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AuthLayout from '@/components/layout/AuthLayout.vue';
import TextField from '@/components/ui/TextField.vue';
import PrimaryButton from '@/components/ui/PrimaryButton.vue';
import GoogleButton from '@/components/ui/GoogleButton.vue';

const { t } = useI18n();

const form = useForm({ name: '', email: '', password: '', password_confirmation: '' });

function submit(): void {
    form.post('/register', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
    <Head :title="t('auth.register')" />

    <AuthLayout :title="t('auth.register')">
        <form class="flex flex-col gap-4" @submit.prevent="submit">
            <TextField
                v-model="form.name"
                :label="t('auth.name')"
                autocomplete="name"
                :error="form.errors.name"
                required
            />
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
                {{ t('auth.register') }}
            </PrimaryButton>
        </form>

        <GoogleButton />

        <p class="text-center text-sm text-foam/70">
            {{ t('auth.alreadyRegistered') }}
            <Link href="/login" class="font-semibold text-beer underline">{{ t('auth.login') }}</Link>
        </p>
    </AuthLayout>
</template>
