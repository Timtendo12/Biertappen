import { describe, expect, it } from 'vitest';
import { docToSegments, isEmptyDoc, segmentsToDoc, type PmDoc } from './segments';
import type { Segment } from '@/game';

/*
 * The editor is allowed to be replaced; the stored format is not. So these test
 * the conversion directly, without mounting an editor — a lossy round trip here
 * would silently corrupt cards on every save.
 */

const roundTrip = (segments: Segment[]): Segment[] => docToSegments(segmentsToDoc(segments));

describe('round trips', () => {
    it('preserves a plain sentence', () => {
        const segments: Segment[] = [{ type: 'text', value: 'Iedereen drinkt.' }];

        expect(roundTrip(segments)).toEqual(segments);
    });

    it('preserves a player variable between text', () => {
        const segments: Segment[] = [
            { type: 'variable', value: 'player1' },
            { type: 'text', value: ' neemt een slok.' },
        ];

        expect(roundTrip(segments)).toEqual(segments);
    });

    it('preserves two distinct players', () => {
        const segments: Segment[] = [
            { type: 'variable', value: 'player1' },
            { type: 'text', value: ' kiest ' },
            { type: 'variable', value: 'player2' },
            { type: 'text', value: '.' },
        ];

        expect(roundTrip(segments)).toEqual(segments);
    });

    it('preserves the all-players variable', () => {
        const segments: Segment[] = [
            { type: 'variable', value: 'all_players' },
            { type: 'text', value: ' drinken.' },
        ];

        expect(roundTrip(segments)).toEqual(segments);
    });

    it('preserves a ranged amount with its options', () => {
        const segments: Segment[] = [
            { type: 'text', value: 'Drink ' },
            { type: 'variable', value: 'amount', options: { mode: 'range', min: 2, max: 6 } },
            { type: 'text', value: ' slokken.' },
        ];

        expect(roundTrip(segments)).toEqual(segments);
    });

    it('preserves a fixed amount with its value', () => {
        const segments: Segment[] = [
            { type: 'variable', value: 'amount', options: { mode: 'fixed', value: 4 } },
        ];

        expect(roundTrip(segments)).toEqual(segments);
    });

    it('preserves back-to-back variables with no text between them', () => {
        const segments: Segment[] = [
            { type: 'variable', value: 'player1' },
            { type: 'variable', value: 'player2' },
        ];

        expect(roundTrip(segments)).toEqual(segments);
    });

    it('preserves unicode and punctuation', () => {
        const segments: Segment[] = [
            { type: 'variable', value: 'player1' },
            { type: 'text', value: ' doet iets gênants — écht waar! 🍺' },
        ];

        expect(roundTrip(segments)).toEqual(segments);
    });

    it('is stable: a second round trip changes nothing', () => {
        const segments: Segment[] = [
            { type: 'variable', value: 'player1' },
            { type: 'text', value: ' geeft ' },
            { type: 'variable', value: 'player2' },
            { type: 'text', value: ' ' },
            { type: 'variable', value: 'amount', options: { mode: 'range', min: 1, max: 5 } },
            { type: 'text', value: ' slokken.' },
        ];

        const once = roundTrip(segments);

        expect(roundTrip(once)).toEqual(once);
    });
});

describe('normalisation', () => {
    it('merges adjacent text so identical cards store identically', () => {
        const doc: PmDoc = {
            type: 'doc',
            content: [
                {
                    type: 'paragraph',
                    content: [
                        { type: 'text', text: 'Drink ' },
                        { type: 'text', text: 'twee ' },
                        { type: 'text', text: 'slokken.' },
                    ],
                },
            ],
        };

        expect(docToSegments(doc)).toEqual([{ type: 'text', value: 'Drink twee slokken.' }]);
    });

    it('drops empty text segments on the way in', () => {
        const doc = segmentsToDoc([
            { type: 'text', value: '' },
            { type: 'variable', value: 'player1' },
        ]);

        expect(docToSegments(doc)).toEqual([{ type: 'variable', value: 'player1' }]);
    });

    it('turns a paragraph break into a space rather than losing it', () => {
        const doc: PmDoc = {
            type: 'doc',
            content: [
                { type: 'paragraph', content: [{ type: 'text', text: 'Eerst dit.' }] },
                { type: 'paragraph', content: [{ type: 'text', text: 'Dan dat.' }] },
            ],
        };

        expect(docToSegments(doc)).toEqual([{ type: 'text', value: 'Eerst dit. Dan dat.' }]);
    });

    it('drops a chip naming a variable that does not exist', () => {
        const doc = {
            type: 'doc',
            content: [
                {
                    type: 'paragraph',
                    content: [
                        { type: 'variableChip', attrs: { variable: 'player99' } },
                        { type: 'text', text: 'drinkt.' },
                    ],
                },
            ],
        } as unknown as PmDoc;

        // Persisting it would fail validation on save and resolution in a game.
        expect(docToSegments(doc)).toEqual([{ type: 'text', value: 'drinkt.' }]);
    });

    it('never attaches options to a player variable', () => {
        const doc = {
            type: 'doc',
            content: [
                {
                    type: 'paragraph',
                    content: [
                        {
                            type: 'variableChip',
                            attrs: { variable: 'player1', options: { mode: 'fixed', value: 3 } },
                        },
                    ],
                },
            ],
        } as unknown as PmDoc;

        expect(docToSegments(doc)).toEqual([{ type: 'variable', value: 'player1' }]);
    });

    it('handles an empty document', () => {
        expect(docToSegments({ type: 'doc', content: [{ type: 'paragraph' }] })).toEqual([]);
        expect(docToSegments(null)).toEqual([]);
    });
});

describe('emptiness', () => {
    it('treats whitespace-only content as empty', () => {
        expect(isEmptyDoc(segmentsToDoc([{ type: 'text', value: '   ' }]))).toBe(true);
    });

    it('treats a lone chip as not empty', () => {
        expect(isEmptyDoc(segmentsToDoc([{ type: 'variable', value: 'player1' }]))).toBe(false);
    });

    it('treats real text as not empty', () => {
        expect(isEmptyDoc(segmentsToDoc([{ type: 'text', value: 'Proost' }]))).toBe(false);
    });
});
