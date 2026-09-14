/**
 * Public surface of the game engine.
 *
 * Consumers import from '@/game' and nothing deeper, so the internals stay free
 * to change without touching the UI.
 */
export { createGame, type Game, type EngineOptions } from './engine';
export { createRng, type Rng } from './rng';
export { eligibleCards, isPlayable, minimumPlayers } from './eligibility';
export { resolveCard, rollAmount } from './resolve';
export {
    availableVariables,
    isKnownVariable,
    isPlayerVariable,
    labelKey,
    playerIndex,
    DEFAULT_AMOUNT_OPTIONS,
    MAX_PLAYER_VARIABLES,
} from './variables';
export * from './types';
