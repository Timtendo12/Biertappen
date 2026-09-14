import type { Card } from './types';
import type { Rng } from './rng';

/**
 * Card selection as a shuffle bag rather than repeated random picks.
 *
 * Drawing at random and retrying on a repeat is the obvious approach and the
 * wrong one: it makes "every card exactly once" a probabilistic hope. Shuffling
 * the pool and dealing from the top makes it structural — in all_cards_once mode
 * the bag simply runs out, and no card can appear twice because it is only in
 * the bag once.
 */
export interface ShuffleBag {
    /** Cards remaining before a reshuffle is needed. */
    readonly remaining: number;
    readonly size: number;
    /** Next card, or null when the bag is empty and refilling is not allowed. */
    draw(): Card | null;
    /** Serialisable snapshot, used by undo. */
    snapshot(): BagSnapshot;
    restore(snapshot: BagSnapshot): void;
}

export interface BagSnapshot {
    order: string[];
    cursor: number;
    lastDrawnId: string | null;
}

export function createShuffleBag(
    cards: readonly Card[],
    rng: Rng,
    options: { refill: boolean },
): ShuffleBag {
    const byId = new Map(cards.map((card) => [card.id, card]));

    let order: string[] = rng.shuffle(cards.map((card) => card.id));
    let cursor = 0;
    let lastDrawnId: string | null = null;

    /**
     * On refill, make sure the card that just played does not reappear as the
     * very next one — technically random, but it reads as a bug to players.
     */
    function refill(): void {
        order = rng.shuffle(order);

        if (order.length > 1 && order[0] === lastDrawnId) {
            const swapWith = rng.int(1, order.length - 1);
            const first = order[0] as string;
            const other = order[swapWith] as string;
            order[0] = other;
            order[swapWith] = first;
        }

        cursor = 0;
    }

    return {
        get remaining() {
            return order.length - cursor;
        },
        get size() {
            return order.length;
        },
        draw(): Card | null {
            if (cursor >= order.length) {
                if (!options.refill || order.length === 0) {
                    return null;
                }
                refill();
            }

            const id = order[cursor++] as string;
            lastDrawnId = id;

            return byId.get(id) ?? null;
        },
        snapshot(): BagSnapshot {
            return { order: [...order], cursor, lastDrawnId };
        },
        restore(snapshot: BagSnapshot): void {
            order = [...snapshot.order];
            cursor = snapshot.cursor;
            lastDrawnId = snapshot.lastDrawnId;
        },
    };
}
