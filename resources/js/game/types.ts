/**
 * The game engine's data contract.
 *
 * Nothing in this directory imports Vue, touches the network, or reads the DOM.
 * The UI calls in; the engine hands back resolved, display-ready state. That
 * separation is what makes every rule below testable without rendering anything.
 */

export type PlayerVariableId =
    | 'player1' | 'player2' | 'player3' | 'player4' | 'player5'
    | 'player6' | 'player7' | 'player8' | 'player9' | 'player10';

export type VariableId = PlayerVariableId | 'all_players' | 'amount';

export interface Player {
    id: string;
    name: string;
}

export interface TextSegment {
    type: 'text';
    value: string;
}

/** How an {amount} resolves. Omitted means the registry default (1–5). */
export type AmountOptions =
    | { mode: 'fixed'; value: number }
    | { mode: 'range'; min: number; max: number };

export interface VariableSegment {
    type: 'variable';
    value: VariableId;
    options?: AmountOptions;
}

export type Segment = TextSegment | VariableSegment;

/** `'all'` targets the whole roster; a number is how many *distinct* players it needs. */
export type Participants = number | 'all';

export interface Card {
    id: string;
    type: string;
    participants: Participants;
    content: Segment[];
}

export type Ending =
    | { mode: 'cards'; count: number }
    | { mode: 'all_cards_once' };

export interface Deck {
    uuid: string;
    name: string;
    description: string;
    locale: string;
    ending: Ending;
    cards: Card[];
}

/* ------------------------------------------------------------------ */
/* Resolved output                                                     */
/* ------------------------------------------------------------------ */

export type ResolvedSegment =
    | { type: 'text'; value: string }
    | { type: 'player'; variable: PlayerVariableId; player: Player; display: string }
    | { type: 'players'; variable: 'all_players'; players: Player[]; display: string }
    | { type: 'amount'; variable: 'amount'; amount: number; display: string };

export interface ResolvedCard {
    /** Unique per *play*, not per card: the same card drawn twice yields two of these. */
    playId: string;
    cardId: string;
    type: string;
    segments: ResolvedSegment[];
    /** Players this play bound, in variable order. Empty for pure-text cards. */
    players: Player[];
    /** Flattened text — for screen readers, tests, and previews. */
    text: string;
}

export type GamePhase = 'idle' | 'revealing' | 'revealed' | 'finished';

export interface GameState {
    phase: GamePhase;
    current: ResolvedCard | null;
    cardsPlayed: number;
    /** Total cards this game will show, when that is knowable up front. */
    total: number | null;
    canUndo: boolean;
}

export interface EngineOptions {
    /** Fixed seed ⇒ reproducible games. Tests rely on this; gameplay does not pass one. */
    seed?: number;
}

export class GameError extends Error {
    constructor(
        message: string,
        public readonly code: 'no_playable_cards' | 'not_enough_players' | 'empty_deck',
    ) {
        super(message);
        this.name = 'GameError';
    }
}
