<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import { Link } from '@inertiajs/vue3';
import AppShell from './AppShell.vue';

defineProps<{ title: string }>();

const { t } = useI18n();

const TABS = [
    { href: '/admin', key: 'admin.dashboard' },
    { href: '/admin/decks', key: 'admin.decks' },
    { href: '/admin/users', key: 'admin.users' },
    { href: '/admin/reports', key: 'admin.reports' },
] as const;
</script>

<template>
    <AppShell>
        <!-- Wider than the game screens: admin work is table-shaped and mostly
             happens on a laptop, even though it stays usable on a phone. -->
        <div class="mx-auto flex w-full max-w-3xl flex-1 flex-col gap-5 px-5 py-8">
            <header class="flex flex-wrap items-baseline justify-between gap-3">
                <h1 class="text-2xl font-bold">{{ title }}</h1>
                <Link href="/" class="text-sm text-foam/60 underline">{{ t('common.back') }}</Link>
            </header>

            <nav class="flex flex-wrap gap-2" :aria-label="t('admin.title')">
                <Link
                    v-for="tab in TABS"
                    :key="tab.href"
                    :href="tab.href"
                    class="min-h-11 rounded-xl bg-night-soft px-3 py-2.5 text-sm font-semibold text-foam/80"
                >
                    {{ t(tab.key) }}
                </Link>
            </nav>

            <slot />
        </div>
    </AppShell>
</template>
