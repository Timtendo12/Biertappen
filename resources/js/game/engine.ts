import { createRng, type Rng } from './rng';
import { createShuffleBag, type BagSnapshot, type ShuffleBag } from './selector';
import { eligibleCards } from './eligibility';
import { resolveCard, type ResolveOptions } from './resolve';
import { GameError, type Deck, type GameState, type Player, type ResolvedCard } from './types';

/**
 * The game engine.
 *
 * Owns every rule about which card comes next, who is on it, and when the game
 * ends. It holds no reference to Vue, the DOM or the network: a game is created
 * from a deck and a roster, and driven by four methods. That is what makes the
 * rules testable without rendering a card, and what stops the UI from quietly
 * growing a second copy of them.
 */

export interface Game {
    readonly state: GameState;
    /** Draws and resolves the next card. Returns null once the game is over. */
    drawNext(): ResolvedCard | null;
    /** Marks the current card revealed (the flip finished). */
    reveal(): void;
    /** Discards the current card and draws the next one. */
    discard(): ResolvedCard | null;
    /** Steps back exactly one card. Returns false when there is nothing to undo. */
    undo(): boolean;
    readonly isFinished: boolean;
}

export interface EngineOptions extends ResolveOptions {
    seed?: number;
    rng?: Rng;
}

/** Exactly one step of history — the PRD asks for one card of undo, not a stack. */
interface Snapshot {
    bag: BagSnapshot;
    current: ResolvedCard | null;
    cardsPlayed: number;
    phase: GameState['phase'];
}

export function createGame(deck: Deck, players: readonly Player[], options: EngineOptions = {}): Game {
    if (players.length === 0) {
        throw new GameError('A game needs at least one player.', 'not_enough_players');
    }

    if (deck.cards.length === 0) {
        throw new GameError('This deck contains no cards.', 'empty_deck');
    }

    const playable = eligibleCards(deck.cards, players);

    if (playable.length === 0) {
        throw new GameError(
            `No card in this deck can be played with ${players.length} players.`,
            'no_playable_cards',
        );
    }

    const rng = options.rng ?? createRng(options.seed);
    const playAllOnce = deck.ending.mode === 'all_cards_once';

    const bag: ShuffleBag = createShuffleBag(playable, rng, { refill: !playAllOnce });

    /*
     * In all_cards_once mode the total is the eligible pool, not the deck size:
     * a deck with four-player cards played by two people is genuinely shorter,
     * and the progress indicator must not promise cards that will never come.
     */
    // Narrowed on the discriminant itself so `count` is only read where it exists.
    const total = deck.ending.mode === 'all_cards_once' ? playable.length : deck.ending.count;

    const state: GameState = {
        phase: 'idle',
        current: null,
        cardsPlayed: 0,
        total,
        canUndo: false,
    };

    let previous: Snapshot | null = null;

    const reachedEnd = (): boolean => state.cardsPlayed >= total;

    function finish(): null {
        state.phase = 'finished';
        state.current = null;

        return null;
    }

    function takeSnapshot(): Snapshot {
        return {
            bag: bag.snapshot(),
            current: state.current,
            cardsPlayed: state.cardsPlayed,
            phase: state.phase,
        };
    }

    function drawNext(): ResolvedCard | null {
        if (state.phase === 'finished' || reachedEnd()) {
            return finish();
        }

        const card = bag.draw();

        if (!card) {
            return finish();
        }

        state.current = resolveCard(card, players, rng, options);
        state.phase = 'revealing';

        return state.current;
    }

    return {
        state,

        drawNext,

        reveal(): void {
            if (state.phase === 'revealing') {
                state.phase = 'revealed';
            }
        },

        discard(): ResolvedCard | null {
            if (!state.current || state.phase === 'finished') {
                return null;
            }

            // Snapshot *before* mutating, so undo restores the discarded card
            // together with the bag position that produced it.
            previous = takeSnapshot();
            state.canUndo = true;

            state.cardsPlayed += 1;

            return drawNext();
        },

        undo(): boolean {
            if (!previous) {
                return false;
            }

            bag.restore(previous.bag);
            state.current = previous.current;
            state.cardsPlayed = previous.cardsPlayed;
            state.phase = previous.phase;

            // One step only: undoing twice in a row is not supported by design.
            previous = null;
            state.canUndo = false;

            return true;
        },

        get isFinished(): boolean {
            return state.phase === 'finished';
        },
    };
}
