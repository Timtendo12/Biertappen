import { describe, expect, it } from 'vitest';
import { createGame } from './engine';
import { createRng } from './rng';
import { eligibleCards, minimumPlayers } from './eligibility';
import { resolveCard, rollAmount } from './resolve';
import { availableVariables } from './variables';
import type { Card, Deck, Player, Segment } from './types';
import { GameError } from './types';

/* ------------------------------------------------------------------ */
/* Fixtures                                                            */
/* ------------------------------------------------------------------ */

const roster = (...names: string[]): Player[] =>
    names.map((name, index) => ({ id: `p${index + 1}`, name }));

const FOUR = roster('Tim', 'Lisa', 'Mark', 'Emma');

function card(id: string, participants: Card['participants'], content: Segment[]): Card {
    return { id, type: 'custom', participants, content };
}

const soloCard = card('solo', 1, [
    { type: 'variable', value: 'player1' },
    { type: 'text', value: ' neemt ' },
    { type: 'variable', value: 'amount', options: { mode: 'range', min: 1, max: 5 } },
    { type: 'text', value: ' slokken.' },
]);

const duoCard = card('duo', 2, [
    { type: 'variable', value: 'player1' },
    { type: 'text', value: ' kiest ' },
    { type: 'variable', value: 'player2' },
    { type: 'text', value: '.' },
]);

const everyoneCard = card('all', 'all', [
    { type: 'variable', value: 'all_players' },
    { type: 'text', value: ' nemen 2 slokken.' },
]);

const quadCard = card('quad', 4, [
    { type: 'variable', value: 'player4' },
    { type: 'text', value: ' wint.' },
]);

function deck(cards: Card[], ending: Deck['ending'] = { mode: 'cards', count: 10 }): Deck {
    return {
        uuid: 'deck-1',
        name: 'Test',
        description: '',
        locale: 'nl',
        ending,
        cards,
    };
}

/* ------------------------------------------------------------------ */
/* Variable resolution                                                 */
/* ------------------------------------------------------------------ */

describe('variable resolution', () => {
    it('substitutes a single player by name', () => {
        const resolved = resolveCard(soloCard, FOUR, createRng(1));

        expect(resolved.text).toMatch(/^(Tim|Lisa|Mark|Emma) neemt \d+ slokken\.$/);
    });

    it('never binds the same person to two player variables', () => {
        // Run many seeds: a collision bug would surface as one identical pair.
        for (let seed = 0; seed < 500; seed++) {
            const resolved = resolveCard(duoCard, roster('Tim', 'Lisa'), createRng(seed));

            expect(resolved.players).toHaveLength(2);
            expect(resolved.players[0]!.id).not.toBe(resolved.players[1]!.id);
        }
    });

    it('keeps two players with the same name distinct', () => {
        const twoTims: Player[] = [
            { id: 'a', name: 'Tim' },
            { id: 'b', name: 'Tim' },
        ];

        const resolved = resolveCard(duoCard, twoTims, createRng(7));

        expect(resolved.text).toBe('Tim kiest Tim.');
        expect(resolved.players[0]!.id).not.toBe(resolved.players[1]!.id);
    });

    it('expands all_players to the whole roster', () => {
        const resolved = resolveCard(everyoneCard, FOUR, createRng(3));

        expect(resolved.text).toBe('Tim, Lisa, Mark en Emma nemen 2 slokken.');
        expect(resolved.segments[0]).toMatchObject({ type: 'players' });
    });

    it('uses the supplied conjunction for other locales', () => {
        const resolved = resolveCard(everyoneCard, FOUR, createRng(3), { conjunction: 'and' });

        expect(resolved.text).toBe('Tim, Lisa, Mark and Emma nemen 2 slokken.');
    });

    it('reads a single-player roster without a dangling conjunction', () => {
        const resolved = resolveCard(everyoneCard, roster('Tim'), createRng(3));

        expect(resolved.text).toBe('Tim nemen 2 slokken.');
    });

    it('marks resolved segments so the UI never re-parses text', () => {
        const resolved = resolveCard(soloCard, FOUR, createRng(11));

        expect(resolved.segments.map((s) => s.type)).toEqual(['player', 'text', 'amount', 'text']);
    });

    it('gives each play its own id, so the same card drawn twice is two plays', () => {
        const a = resolveCard(soloCard, FOUR, createRng(1));
        const b = resolveCard(soloCard, FOUR, createRng(1));

        expect(a.playId).not.toBe(b.playId);
    });
});

describe('amount variable', () => {
    it('returns the exact value in fixed mode', () => {
        expect(rollAmount({ mode: 'fixed', value: 7 }, createRng(1))).toBe(7);
    });

    it('stays inside the range and varies across plays', () => {
        const rng = createRng(42);
        const rolls = Array.from({ length: 200 }, () => rollAmount({ mode: 'range', min: 2, max: 4 }, rng));

        expect(Math.min(...rolls)).toBeGreaterThanOrEqual(2);
        expect(Math.max(...rolls)).toBeLessThanOrEqual(4);
        expect(new Set(rolls).size).toBeGreaterThan(1);
    });

    it('falls back to the registry default when no options are given', () => {
        const rng = createRng(5);
        const rolls = Array.from({ length: 100 }, () => rollAmount(undefined, rng));

        expect(Math.min(...rolls)).toBeGreaterThanOrEqual(1);
        expect(Math.max(...rolls)).toBeLessThanOrEqual(5);
    });

    it('re-rolls on every play, so a repeated card is not identical', () => {
        const rng = createRng(99);
        const texts = new Set(
            Array.from({ length: 30 }, () => resolveCard(soloCard, FOUR, rng).text),
        );

        expect(texts.size).toBeGreaterThan(1);
    });
});

/* ------------------------------------------------------------------ */
/* Eligibility                                                         */
/* ------------------------------------------------------------------ */

describe('eligibility', () => {
    it('excludes cards needing more players than the roster has', () => {
        const playable = eligibleCards([soloCard, duoCard, quadCard], roster('Tim', 'Lisa'));

        expect(playable.map((c) => c.id)).toEqual(['solo', 'duo']);
    });

    it('always allows all-players cards', () => {
        expect(eligibleCards([everyoneCard], roster('Tim'))).toHaveLength(1);
    });

    it('reports the smallest roster a deck can be played with', () => {
        expect(minimumPlayers([quadCard, duoCard])).toBe(2);
        expect(minimumPlayers([quadCard])).toBe(4);
    });

    it('refuses to start when nothing is playable', () => {
        expect(() => createGame(deck([quadCard]), roster('Tim', 'Lisa'), { seed: 1 }))
            .toThrow(GameError);
    });

    it('refuses to start with no players', () => {
        expect(() => createGame(deck([soloCard]), [], { seed: 1 })).toThrow(GameError);
    });
});

/* ------------------------------------------------------------------ */
/* Card selection and ending conditions                                */
/* ------------------------------------------------------------------ */

describe('ending after a fixed number of cards', () => {
    it('plays exactly the configured count', () => {
        const game = createGame(deck([soloCard, duoCard, everyoneCard], { mode: 'cards', count: 7 }), FOUR, {
            seed: 4,
        });

        let drawn = 0;
        game.drawNext();

        while (!game.isFinished) {
            drawn++;
            game.discard();
        }

        expect(drawn).toBe(7);
        expect(game.state.cardsPlayed).toBe(7);
    });

    it('reuses cards once the bag empties', () => {
        const game = createGame(deck([soloCard, duoCard], { mode: 'cards', count: 10 }), FOUR, { seed: 2 });

        game.drawNext();
        let plays = 0;
        while (!game.isFinished) {
            plays++;
            game.discard();
        }

        expect(plays).toBe(10);
    });

    it('does not repeat the same card back to back on reshuffle', () => {
        const game = createGame(deck([soloCard, duoCard], { mode: 'cards', count: 40 }), FOUR, { seed: 8 });

        const seen: string[] = [];
        let card = game.drawNext();
        while (card) {
            seen.push(card.cardId);
            card = game.discard();
        }

        for (let i = 1; i < seen.length; i++) {
            expect(seen[i]).not.toBe(seen[i - 1]);
        }
    });
});

describe('ending when every card has been played once', () => {
    const many = Array.from({ length: 12 }, (_, i) =>
        card(`c${i}`, 1, [{ type: 'variable', value: 'player1' }, { type: 'text', value: ` #${i}` }]),
    );

    it('plays each card exactly once, across many seeds', () => {
        for (let seed = 0; seed < 60; seed++) {
            const game = createGame(deck(many, { mode: 'all_cards_once' }), FOUR, { seed });

            const seen: string[] = [];
            let current = game.drawNext();
            while (current) {
                seen.push(current.cardId);
                current = game.discard();
            }

            expect(seen).toHaveLength(many.length);
            expect(new Set(seen).size).toBe(many.length);
        }
    });

    it('counts only eligible cards toward the total', () => {
        const game = createGame(
            deck([soloCard, duoCard, quadCard], { mode: 'all_cards_once' }),
            roster('Tim', 'Lisa'),
            { seed: 3 },
        );

        // quadCard needs four players, so this game is two cards long, not three.
        expect(game.state.total).toBe(2);

        let played = 0;
        game.drawNext();
        while (!game.isFinished) {
            played++;
            game.discard();
        }

        expect(played).toBe(2);
    });

    it('terminates rather than running forever', () => {
        const game = createGame(deck(many, { mode: 'all_cards_once' }), FOUR, { seed: 1 });

        let guard = 0;
        game.drawNext();
        while (!game.isFinished && guard < 1000) {
            guard++;
            game.discard();
        }

        expect(game.isFinished).toBe(true);
        expect(guard).toBeLessThan(1000);
    });
});

/* ------------------------------------------------------------------ */
/* Undo                                                                */
/* ------------------------------------------------------------------ */

describe('undo', () => {
    it('is unavailable before anything has been discarded', () => {
        const game = createGame(deck([soloCard, duoCard]), FOUR, { seed: 1 });
        game.drawNext();

        expect(game.state.canUndo).toBe(false);
        expect(game.undo()).toBe(false);
    });

    it('restores the discarded card exactly, resolved values included', () => {
        const game = createGame(deck([soloCard, duoCard, everyoneCard]), FOUR, { seed: 12 });

        const first = game.drawNext();
        game.discard();

        expect(game.undo()).toBe(true);
        expect(game.state.current).toEqual(first);
        expect(game.state.cardsPlayed).toBe(0);
    });

    it('does not consume an extra card from the deck', () => {
        const game = createGame(deck([soloCard, duoCard, everyoneCard], { mode: 'all_cards_once' }), FOUR, {
            seed: 6,
        });

        game.drawNext();
        game.discard();
        game.undo();

        const seen: string[] = [];
        let current = game.state.current;
        while (current) {
            seen.push(current.cardId);
            current = game.discard();
        }

        expect(new Set(seen).size).toBe(3);
    });

    it('steps back only one card', () => {
        const game = createGame(deck([soloCard, duoCard, everyoneCard]), FOUR, { seed: 9 });

        game.drawNext();
        game.discard();
        game.discard();

        expect(game.undo()).toBe(true);
        expect(game.undo()).toBe(false);
    });

    it('can undo the final discard and finish again', () => {
        const game = createGame(deck([soloCard, duoCard], { mode: 'cards', count: 2 }), FOUR, { seed: 5 });

        game.drawNext();
        game.discard();
        game.discard();

        expect(game.isFinished).toBe(true);
        expect(game.undo()).toBe(true);
        expect(game.isFinished).toBe(false);
        expect(game.state.current).not.toBeNull();

        game.discard();
        expect(game.isFinished).toBe(true);
    });
});

/* ------------------------------------------------------------------ */
/* Determinism and editor support                                      */
/* ------------------------------------------------------------------ */

describe('determinism', () => {
    it('produces an identical game for the same seed', () => {
        const play = (): string[] => {
            const game = createGame(deck([soloCard, duoCard, everyoneCard], { mode: 'cards', count: 12 }), FOUR, {
                seed: 2024,
            });

            const out: string[] = [];
            let current = game.drawNext();
            while (current) {
                out.push(current.text);
                current = game.discard();
            }

            return out;
        };

        expect(play()).toEqual(play());
    });

    it('produces different games for different seeds', () => {
        const play = (seed: number): string => {
            const game = createGame(deck([soloCard, duoCard, everyoneCard], { mode: 'cards', count: 12 }), FOUR, {
                seed,
            });
            const out: string[] = [];
            let current = game.drawNext();
            while (current) {
                out.push(current.text);
                current = game.discard();
            }

            return out.join('|');
        };

        expect(play(1)).not.toBe(play(2));
    });
});

describe('available variables for the editor', () => {
    it('offers exactly the players the card declared', () => {
        expect(availableVariables(2)).toEqual(['player1', 'player2', 'amount']);
    });

    it('offers the group variable for an all-players card', () => {
        expect(availableVariables('all')).toEqual(['all_players', 'amount']);
    });

    it('never offers more than the registry supports', () => {
        expect(availableVariables(99)).toHaveLength(11);
    });
});
