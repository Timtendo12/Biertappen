<script setup lang="ts">
import { onBeforeUnmount, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { Head, router } from '@inertiajs/vue3';
import AppShell from '@/components/layout/AppShell.vue';
import CardStack from '@/components/game/CardStack.vue';
import PrimaryButton from '@/components/ui/PrimaryButton.vue';
import DonateSheet from '@/components/billing/DonateSheet.vue';
import { usePlayersStore } from '@/stores/players';
import { useGameStore } from '@/stores/game';

const props = defineProps<{ deckUuid: string }>();

const { t } = useI18n();
const players = usePlayersStore();
const game = useGameStore();

/**
 * A running game lives only in memory, so a reload destroys it. Warning before
 * that happens is the honest fix; silently persisting the game would resurrect
 * finished rounds and make a refresh feel broken.
 */
function guardUnload(event: BeforeUnloadEvent): void {
    if (!game.isActive) return;

    event.preventDefault();
    event.returnValue = '';
}

onMounted(async () => {
    if (!players.canStart) {
        router.visit('/');

        return;
    }

    window.addEventListener('beforeunload', guardUnload);
    await game.start(props.deckUuid, players.roster());
});

onBeforeUnmount(() => {
    window.removeEventListener('beforeunload', guardUnload);
    game.reset();
});

function quit(): void {
    if (window.confirm(t('game.quitConfirm'))) {
        router.visit('/');
    }
}
</script>

<template>
    <Head :title="game.deck?.name ?? t('common.appName')" />

    <AppShell portrait-only>
        <main class="mx-auto flex w-full max-w-md flex-1 flex-col px-5 py-4">
            <header class="flex items-center justify-between gap-3 pb-2">
                <button type="button" class="text-sm text-foam/60 underline" @click="quit">
                    {{ t('game.quit') }}
                </button>

                <p
                    v-if="game.total"
                    class="text-sm tabular-nums text-foam/60"
                    aria-live="polite"
                    aria-atomic="true"
                >
                    {{ t('game.progress', { current: Math.min(game.cardsPlayed + 1, game.total), total: game.total }) }}
                </p>
            </header>

            <!-- Progress is announced above as text; this bar is decorative. -->
            <div aria-hidden="true" class="h-1 w-full overflow-hidden rounded-full bg-night-soft">
                <div
                    class="h-full rounded-full bg-beer transition-[width] duration-300"
                    :style="{ width: `${game.progress * 100}%` }"
                />
            </div>

            <p v-if="game.loading" class="py-10 text-center text-sm text-foam/60">
                {{ t('common.loading') }}
            </p>

            <div v-else-if="game.error" class="flex flex-1 flex-col items-center justify-center gap-4" role="alert">
                <p class="text-center text-foam/80">
                    {{ t(game.error, { count: players.named.length }) }}
                </p>
                <PrimaryButton variant="ghost" @click="router.visit('/decks')">
                    {{ t('decks.title') }}
                </PrimaryButton>
            </div>

            <!-- Finished -->
            <div
                v-else-if="game.finished"
                class="flex flex-1 flex-col items-center justify-center gap-6 text-center"
            >
                <span class="text-6xl" aria-hidden="true">🍻</span>
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-bold">{{ t('game.finished') }}</h1>
                    <p class="text-sm text-foam/60">{{ t('game.cardsPlayed', { count: game.cardsPlayed }) }}</p>
                </div>

                <div class="flex w-full flex-col gap-3">
                    <PrimaryButton @click="router.visit('/decks')">{{ t('game.playAgain') }}</PrimaryButton>
                    <PrimaryButton variant="ghost" @click="router.visit('/')">
                        {{ t('game.newGame') }}
                    </PrimaryButton>

                    <DonateSheet />
                </div>
            </div>

            <!-- Playing -->
            <template v-else>
                <CardStack
                    :card="game.current"
                    :revealed="game.revealed"
                    :remaining="game.total ? game.total - game.cardsPlayed - 1 : 2"
                    @reveal="game.reveal"
                    @discard="game.discard"
                />

                <footer class="flex items-center justify-between gap-3 pt-2">
                    <button
                        type="button"
                        class="min-h-12 rounded-xl px-4 text-sm font-semibold text-foam/70 disabled:opacity-30"
                        :disabled="!game.canUndo"
                        @click="game.undo"
                    >
                        ↺ {{ t('game.undo') }}
                    </button>

                    <p class="text-xs text-foam/40">
                        {{ game.revealed ? t('game.swipeToDiscard') : t('game.tapToReveal') }}
                    </p>
                </footer>
            </template>
        </main>
    </AppShell>
</template>
