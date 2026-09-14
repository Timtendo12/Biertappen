import { defineStore } from 'pinia';
import { computed, nextTick, ref, shallowRef } from 'vue';
import { createGame, GameError, type Deck, type Game, type Player, type ResolvedCard } from '@/game';
import { useSettingsStore } from './settings';

/**
 * Thin Vue adapter over the engine.
 *
 * Holds no rules of its own: it fetches the deck once, hands it to the engine,
 * and mirrors the engine's state into refs the components can render. Anything
 * that decides *what happens* belongs in resources/js/game, not here.
 *
 * State is intentionally in-memory only. Closing the app ends the game, which is
 * the specified behaviour — persisting it would resurrect finished games and
 * make a refresh feel like a bug.
 */
export const useGameStore = defineStore('game', () => {
    const settings = useSettingsStore();

    // shallowRef: the engine owns this object, Vue must not proxy its internals.
    const engine = shallowRef<Game | null>(null);

    const deck = ref<Deck | null>(null);
    const current = ref<ResolvedCard | null>(null);
    const revealed = ref(false);
    const cardsPlayed = ref(0);
    const total = ref<number | null>(null);
    const canUndo = ref(false);
    const finished = ref(false);
    const loading = ref(false);
    const error = ref<string | null>(null);
    const errorCode = ref<GameError['code'] | null>(null);

    const progress = computed(() => {
        if (!total.value) return 0;

        return Math.min(1, cardsPlayed.value / total.value);
    });

    const isActive = computed(() => engine.value !== null && !finished.value);

    function sync(): void {
        const game = engine.value;
        if (!game) return;

        current.value = game.state.current;
        cardsPlayed.value = game.state.cardsPlayed;
        total.value = game.state.total;
        canUndo.value = game.state.canUndo;
        finished.value = game.isFinished;
    }

    async function start(deckUuid: string, players: Player[]): Promise<boolean> {
        loading.value = true;
        error.value = null;
        errorCode.value = null;

        try {
            // The only network call a game makes. Everything after this is local,
            // so card transitions never wait on the server.
            const response = await fetch(`/api/decks/${deckUuid}/play`, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                error.value = response.status === 404 ? 'decks.notFound' : 'errors.generic';

                return false;
            }

            const body = (await response.json()) as { deck: Deck };
            deck.value = body.deck;

            engine.value = createGame(body.deck, players, {
                conjunction: settings.locale === 'en' ? 'and' : 'en',
            });

            revealed.value = false;
            engine.value.drawNext();
            sync();

            return true;
        } catch (e) {
            if (e instanceof GameError) {
                errorCode.value = e.code;
                error.value = e.code === 'no_playable_cards' ? 'game.noPlayableCards' : 'errors.generic';
            } else {
                error.value = 'errors.network';
            }

            return false;
        } finally {
            loading.value = false;
        }
    }

    /**
     * Flips a freshly dealt card face up, visibly.
     *
     * The new card mounts as a new element (it is keyed by play id). Setting it
     * face up in the same tick means it is born at 180° and the browser has no
     * starting state to transition from — it just appears revealed. So it is
     * left face down for one painted frame first, and flipped on the next.
     */
    function revealAfterPaint(): void {
        void nextTick(() => {
            requestAnimationFrame(() => requestAnimationFrame(() => reveal()));
        });
    }

    /** The tap that flips the top card. */
    function reveal(): void {
        if (!engine.value || revealed.value || !current.value) return;

        settings.unlock();
        engine.value.reveal();
        revealed.value = true;
        settings.feedback('flip', 'flip');
    }

    /** The swipe that throws the card away and deals the next one. */
    function discard(): void {
        if (!engine.value || !current.value) return;

        settings.feedback('swipe', 'discard');
        engine.value.discard();
        sync();

        revealed.value = false;

        if (finished.value) {
            settings.feedback('finish', 'finish');

            return;
        }

        // The next card turns itself over — one tap per game, not per card —
        // but it still turns over, rather than arriving face up.
        revealAfterPaint();
    }

    function undo(): boolean {
        if (!engine.value?.undo()) return false;

        sync();

        // The restored card is a remount too, so it gets the same visible flip.
        revealed.value = false;
        revealAfterPaint();

        return true;
    }

    function reset(): void {
        engine.value = null;
        deck.value = null;
        current.value = null;
        revealed.value = false;
        cardsPlayed.value = 0;
        total.value = null;
        canUndo.value = false;
        finished.value = false;
        error.value = null;
        errorCode.value = null;
    }

    return {
        deck,
        current,
        revealed,
        cardsPlayed,
        total,
        canUndo,
        finished,
        loading,
        error,
        errorCode,
        progress,
        isActive,
        start,
        reveal,
        discard,
        undo,
        reset,
    };
});
