<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import { Head, router } from '@inertiajs/vue3';
import AppShell from '@/components/layout/AppShell.vue';
import PrimaryButton from '@/components/ui/PrimaryButton.vue';

const props = defineProps<{ kind: 'premium' | 'donation'; hasAccess: boolean }>();

const { t } = useI18n();
</script>

<template>
    <Head :title="t('donate.thanks')" />

    <AppShell>
        <main class="mx-auto flex w-full max-w-md flex-1 flex-col items-center justify-center gap-6 px-5 py-8 text-center">
            <span class="text-6xl" aria-hidden="true">🍻</span>

            <div class="flex flex-col gap-2">
                <h1 class="text-2xl font-bold">{{ t('donate.thanks') }}</h1>

                <!--
                    For a purchase, access depends on the webhook, which may not
                    have arrived yet. So this says the payment is being processed
                    rather than promising the creator is open — the page itself
                    is not evidence of anything.
                -->
                <p class="text-sm text-foam/70">
                    {{
                        props.kind === 'donation'
                            ? t('donate.thanksBody')
                            : props.hasAccess
                              ? t('premium.active')
                              : t('premium.processing')
                    }}
                </p>
            </div>

            <div class="flex w-full flex-col gap-3">
                <PrimaryButton v-if="props.kind === 'premium' && props.hasAccess" @click="router.visit('/decks/mine')">
                    {{ t('decks.myDecks') }}
                </PrimaryButton>
                <PrimaryButton variant="ghost" @click="router.visit('/')">
                    {{ t('common.back') }}
                </PrimaryButton>
            </div>
        </main>
    </AppShell>
</template>
