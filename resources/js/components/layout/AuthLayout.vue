<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppShell from './AppShell.vue';

defineProps<{ title: string; subtitle?: string }>();

const { t } = useI18n();
</script>

<template>
    <AppShell>
        <main class="mx-auto flex w-full max-w-md flex-1 flex-col gap-6 px-5 py-8">
            <Link href="/" class="text-center text-2xl font-black tracking-tight text-beer">
                {{ t('common.appName') }}
            </Link>

            <header class="flex flex-col gap-1">
                <h1 class="text-2xl font-bold">{{ title }}</h1>
                <p v-if="subtitle" class="text-sm text-foam/70">{{ subtitle }}</p>
            </header>

            <slot />
        </main>
    </AppShell>
</template>
