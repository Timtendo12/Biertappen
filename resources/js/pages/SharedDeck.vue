<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import { Head, router } from '@inertiajs/vue3';
import AppShell from '@/components/layout/AppShell.vue';
import PrimaryButton from '@/components/ui/PrimaryButton.vue';
import ReportDeckSheet from '@/components/ReportDeckSheet.vue';
import { usePlayersStore } from '@/stores/players';

interface SharedDeckSummary {
    uuid: string;
    name: string;
    description: string;
    card_count: number;
    min_players: number;
    ending: { mode: string; count: number | null };
}

const props = defineProps<{ deck: SharedDeckSummary }>();

const { t } = useI18n();
const players = usePlayersStore();

/**
 * Someone arriving on a share link has no roster yet, so this sends them to the
 * player screen first rather than starting a game with nobody in it.
 */
function play(): void {
    router.visit(players.canStart ? `/play/${props.deck.uuid}` : '/');
}
</script>

<template>
    <Head :title="deck.name" />

    <AppShell>
        <main class="mx-auto flex w-full max-w-md flex-1 flex-col justify-center gap-6 px-5 py-8">
            <header class="flex flex-col gap-2 text-center">
                <p class="text-sm font-semibold uppercase tracking-wide text-accent">
                    {{ t('decks.shared') }}
                </p>
                <h1 class="text-3xl font-black text-beer">{{ deck.name }}</h1>
                <p class="text-sm text-foam/70">{{ deck.description }}</p>
            </header>

            <dl class="flex justify-center gap-6 text-center text-sm">
                <div>
                    <dt class="text-foam/50">{{ t('creator.cards') }}</dt>
                    <dd class="text-lg font-bold">{{ deck.card_count }}</dd>
                </div>
                <div>
                    <dt class="text-foam/50">{{ t('home.players') }}</dt>
                    <dd class="text-lg font-bold">{{ deck.min_players }}+</dd>
                </div>
            </dl>

            <PrimaryButton @click="play">
                {{ players.canStart ? t('game.playAgain') : t('home.start') }}
            </PrimaryButton>

            <!-- Anyone with the link can flag it, account or not. -->
            <div class="text-center">
                <ReportDeckSheet :deck-uuid="deck.uuid" />
            </div>
        </main>
    </AppShell>
</template>
