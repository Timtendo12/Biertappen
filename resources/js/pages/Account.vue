<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppShell from '@/components/layout/AppShell.vue';
import PrimaryButton from '@/components/ui/PrimaryButton.vue';
import UnlockCreator from '@/components/billing/UnlockCreator.vue';
import DonateSheet from '@/components/billing/DonateSheet.vue';
import type { SharedProps } from '@/types/inertia';

const { t } = useI18n();
const page = usePage<SharedProps>();

const user = computed(() => page.props.auth.user);

// Read from shared props, which the server recomputes per request — a stale
// client can never imply access it does not have.
const hasCreator = computed(() => user.value?.has_deck_creator || user.value?.is_admin);
</script>

<template>
    <Head :title="t('account.title')" />

    <AppShell>
        <main class="mx-auto flex w-full max-w-md flex-1 flex-col gap-6 px-5 py-8">
            <header class="flex items-baseline justify-between gap-3">
                <h1 class="text-2xl font-bold">{{ t('account.title') }}</h1>
                <button type="button" class="text-sm text-foam/60 underline" @click="router.visit('/')">
                    {{ t('common.back') }}
                </button>
            </header>

            <section v-if="user" class="flex flex-col gap-1 rounded-2xl bg-night-soft p-4">
                <p class="text-lg font-semibold">{{ user.name }}</p>
                <p class="text-sm text-foam/60">{{ user.email }}</p>
            </section>

            <section v-if="hasCreator" class="flex flex-col gap-3 rounded-2xl bg-night-soft p-5">
                <p class="text-sm text-success">{{ t('premium.active') }}</p>
                <Link
                    href="/creator"
                    class="min-h-14 rounded-2xl bg-beer px-5 py-4 text-center text-base font-bold text-night"
                >
                    {{ t('creator.title') }}
                </Link>
            </section>

            <UnlockCreator v-else />

            <DonateSheet />

            <PrimaryButton variant="ghost" @click="router.post('/logout')">
                {{ t('auth.logout') }}
            </PrimaryButton>
        </main>
    </AppShell>
</template>
