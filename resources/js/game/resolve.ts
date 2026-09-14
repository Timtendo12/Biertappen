import type { Rng } from './rng';
import { DEFAULT_AMOUNT_OPTIONS, playerIndex } from './variables';
import type {
    AmountOptions,
    Card,
    Player,
    ResolvedCard,
    ResolvedSegment,
    Segment,
    VariableSegment,
} from './types';
import { uid } from '@/lib/id';

/**
 * Turns a stored card plus the current roster into a display-ready card.
 *
 * This is the whole of the "dynamic variable" feature. The UI receives resolved
 * names and numbers and renders them; it never learns which player was picked or
 * why, which is exactly what keeps the rule in one testable place.
 */

/** How many distinct players a card needs bound. */
function requiredPlayers(card: Card): number {
    if (card.participants === 'all') {
        return 0;
    }

    // Trust the declared count, but never bind more than the content uses.
    const highestUsed = card.content.reduce((highest, segment) => {
        if (segment.type !== 'variable') return highest;
        const index = playerIndex(segment.value);

        return index === null ? highest : Math.max(highest, index);
    }, 0);

    return Math.min(card.participants, Math.max(highestUsed, 0));
}

export function rollAmount(options: AmountOptions | undefined, rng: Rng): number {
    const resolved = options ?? DEFAULT_AMOUNT_OPTIONS;

    if (resolved.mode === 'fixed') {
        return resolved.value;
    }

    return rng.int(resolved.min, resolved.max);
}

/** "Tim, Lisa en Mark" — joined in the UI locale's style. */
function joinNames(players: readonly Player[], conjunction: string): string {
    const names = players.map((player) => player.name);

    if (names.length <= 1) {
        return names[0] ?? '';
    }

    const last = names[names.length - 1] as string;

    return `${names.slice(0, -1).join(', ')} ${conjunction} ${last}`;
}

export interface ResolveOptions {
    /** Word joining the final two names in an all-players list. */
    conjunction?: string;
}

export function resolveCard(
    card: Card,
    players: readonly Player[],
    rng: Rng,
    options: ResolveOptions = {},
): ResolvedCard {
    const conjunction = options.conjunction ?? 'en';

    /*
     * Draw every player this card needs up front, in one sample. Picking them
     * one variable at a time would need retry logic to avoid collisions; drawing
     * the whole set at once makes "player1 and player2 are different people" a
     * property of the draw rather than a check after it.
     */
    const needed = requiredPlayers(card);
    const chosen = needed > 0 ? rng.sample(players, needed) : [];

    const bound = new Map<number, Player>();
    chosen.forEach((player, index) => bound.set(index + 1, player));

    const segments: ResolvedSegment[] = card.content.map((segment: Segment): ResolvedSegment => {
        if (segment.type === 'text') {
            return { type: 'text', value: segment.value };
        }

        const variable = segment as VariableSegment;

        if (variable.value === 'all_players') {
            return {
                type: 'players',
                variable: 'all_players',
                players: [...players],
                display: joinNames(players, conjunction),
            };
        }

        if (variable.value === 'amount') {
            const amount = rollAmount(variable.options, rng);

            return { type: 'amount', variable: 'amount', amount, display: String(amount) };
        }

        const index = playerIndex(variable.value);
        const player = index === null ? undefined : bound.get(index);

        if (!player) {
            /*
             * Only reachable if a card declares fewer participants than it uses,
             * which validation rejects on import. Rendering the placeholder is
             * better than crashing a live game on one bad card.
             */
            return { type: 'text', value: `{${variable.value}}` };
        }

        return {
            type: 'player',
            variable: variable.value,
            player,
            display: player.name,
        };
    });

    return {
        playId: uid(),
        cardId: card.id,
        type: card.type,
        segments,
        players: chosen,
        text: segments.map((segment) => (segment.type === 'text' ? segment.value : segment.display)).join(''),
    };
}
