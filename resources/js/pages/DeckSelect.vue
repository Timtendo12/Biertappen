<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { Head, router } from '@inertiajs/vue3';
import AppShell from '@/components/layout/AppShell.vue';
import PrimaryButton from '@/components/ui/PrimaryButton.vue';
import { usePlayersStore } from '@/stores/players';

interface DeckSummary {
    uuid: string;
    name: string;
    description: string;
    tags: string[];
    featured: boolean;
    is_base_game: boolean;
    card_count: number;
    min_players: number;
    ending: { mode: 'cards' | 'all_cards_once'; count: number | null };
}

const { t } = useI18n();
const players = usePlayersStore();

const decks = ref<DeckSummary[]>([]);
const loading = ref(true);
const failed = ref(false);

onMounted(async () => {
    // A group that reloaded here mid-setup has lost its roster; send them back
    // rather than letting them start a game with nobody in it.
    if (!players.canStart) {
        router.visit('/');

        return;
    }

    await load();
});

async function load(): Promise<void> {
    loading.value = true;
    failed.value = false;

    try {
        const response = await fetch('/api/decks', {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });

        if (!response.ok) throw new Error('request failed');

        decks.value = ((await response.json()) as { decks: DeckSummary[] }).decks;
    } catch {
        failed.value = true;
    } finally {
        loading.value = false;
    }
}

function playable(deck: DeckSummary): boolean {
    return players.named.length >= deck.min_players;
}

function choose(deck: DeckSummary): void {
    if (!playable(deck)) return;

    router.visit(`/play/${deck.uuid}`);
}
</script>

<template>
    <Head :title="t('decks.title')" />

    <AppShell>
        <main class="mx-auto flex w-full max-w-md flex-1 flex-col gap-5 px-5 py-8">
            <header class="flex items-baseline justify-between gap-3">
                <h1 class="text-2xl font-bold">{{ t('decks.title') }}</h1>
                <button type="button" class="text-sm text-foam/60 underline" @click="router.visit('/')">
                    {{ t('common.back') }}
                </button>
            </header>

            <p v-if="loading" class="text-sm text-foam/60">{{ t('common.loading') }}</p>

            <div v-else-if="failed" class="flex flex-col gap-3" role="alert">
                <p class="text-sm text-danger">{{ t('decks.loadFailed') }}</p>
                <PrimaryButton variant="ghost" @click="load">{{ t('common.retry') }}</PrimaryButton>
            </div>

            <p v-else-if="decks.length === 0" class="text-sm text-foam/60">{{ t('decks.empty') }}</p>

            <ul v-else class="flex flex-col gap-3">
                <li v-for="deck in decks" :key="deck.uuid">
                    <button
                        type="button"
                        class="flex w-full flex-col gap-1.5 rounded-2xl bg-night-soft p-4 text-left transition-opacity disabled:opacity-45"
                        :disabled="!playable(deck)"
                        @click="choose(deck)"
                    >
                        <span class="flex items-center gap-2">
                            <span class="text-lg font-bold">{{ deck.name }}</span>
                            <span
                                v-if="deck.is_base_game"
                                class="rounded-full bg-beer/20 px-2 py-0.5 text-[0.65rem] font-bold uppercase tracking-wide text-beer"
                            >
                                {{ t('decks.baseGame') }}
                            </span>
                        </span>

                        <span class="text-sm text-foam/70">{{ deck.description }}</span>

                        <span class="flex flex-wrap gap-x-3 text-xs text-foam/50">
                            <span>{{ t('decks.cardCount', deck.card_count) }}</span>
                            <span v-if="deck.ending.mode === 'cards'">
                                {{ t('decks.endingCards', { count: deck.ending.count }) }}
                            </span>
                            <span v-else>{{ t('decks.endingAll') }}</span>
                        </span>

                        <!-- Says why it is unavailable rather than just greying it out. -->
                        <span v-if="!playable(deck)" class="text-xs font-medium text-danger">
                            {{ t('decks.notEnoughPlayers', { count: deck.min_players }) }}
                        </span>
                    </button>
                </li>
            </ul>
        </main>
    </AppShell>
</template>
