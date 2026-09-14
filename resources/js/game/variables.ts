import registry from '../../deck/variables.json';
import type { AmountOptions, Participants, PlayerVariableId, VariableId } from './types';

/**
 * The variable registry, loaded from the same JSON file PHP validates against.
 * Importing it rather than re-declaring the list here is what keeps client and
 * server from drifting apart.
 */

interface RegistryEntry {
    id: string;
    kind: 'player' | 'player_group' | 'numeric';
    index?: number;
    labelKey: string;
    defaultOptions?: AmountOptions;
}

const entries = registry.variables as RegistryEntry[];

const byId = new Map<string, RegistryEntry>(entries.map((entry) => [entry.id, entry]));

export const MAX_PLAYER_VARIABLES = registry.maxPlayerVariables;

export const DEFAULT_AMOUNT_OPTIONS: AmountOptions =
    (byId.get('amount')?.defaultOptions as AmountOptions | undefined) ?? {
        mode: 'range',
        min: 1,
        max: 5,
    };

export function isKnownVariable(id: string): id is VariableId {
    return byId.has(id);
}

export function isPlayerVariable(id: string): id is PlayerVariableId {
    return byId.get(id)?.kind === 'player';
}

/** The N in playerN, or null for non-player variables. */
export function playerIndex(id: string): number | null {
    const entry = byId.get(id);

    return entry?.kind === 'player' ? (entry.index ?? null) : null;
}

export function labelKey(id: string): string | null {
    return byId.get(id)?.labelKey ?? null;
}

/**
 * Which variables an author may insert for a given participant count.
 *
 * This is what the editor toolbar renders, and it is deliberately the same rule
 * the validator enforces: a 2-player card offers player1 and player2 and nothing
 * else, so an invalid card is not merely rejected later — it cannot be built.
 */
export function availableVariables(participants: Participants): VariableId[] {
    if (participants === 'all') {
        return ['all_players', 'amount'];
    }

    const capped = Math.min(Math.max(participants, 1), MAX_PLAYER_VARIABLES);

    const players = entries
        .filter((entry): entry is RegistryEntry & { index: number } =>
            entry.kind === 'player' && (entry.index ?? 0) <= capped)
        .sort((a, b) => a.index - b.index)
        .map((entry) => entry.id as VariableId);

    return [...players, 'amount'];
}
