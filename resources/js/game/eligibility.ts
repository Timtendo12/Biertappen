import type { Card, Player } from './types';

/**
 * A card is playable when the roster can supply the distinct players it asks
 * for. Everything else in the engine assumes it is working with an already
 * filtered pool, so this runs once at game start rather than on every draw.
 */
export function isPlayable(card: Card, playerCount: number): boolean {
    if (card.participants === 'all') {
        return playerCount > 0;
    }

    return card.participants <= playerCount;
}

export function eligibleCards(cards: readonly Card[], players: readonly Player[]): Card[] {
    return cards.filter((card) => isPlayable(card, players.length));
}

/**
 * The smallest roster that can play anything in this deck. Surfaced on the deck
 * list so a group of two is told *before* starting that a deck needs four.
 */
export function minimumPlayers(cards: readonly Card[]): number {
    if (cards.length === 0) {
        return 0;
    }

    return cards.reduce((lowest, card) => {
        const needed = card.participants === 'all' ? 1 : card.participants;

        return Math.min(lowest, needed);
    }, Number.POSITIVE_INFINITY);
}
