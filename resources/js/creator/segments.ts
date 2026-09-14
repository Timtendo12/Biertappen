import type { AmountOptions, Segment, VariableId } from '@/game';
import { isKnownVariable } from '@/game';

/**
 * Translation between the editor's document and the stored card format.
 *
 * The stored format is always `content[]` — ProseMirror JSON is never persisted.
 * Keeping the conversion here as two pure functions means it can be round-trip
 * tested without mounting an editor, and it keeps the editor an implementation
 * detail we could replace without touching the data.
 */

export interface ChipAttrs {
    variable: VariableId;
    /** Only ever set on the amount chip. */
    options?: AmountOptions | null;
}

interface PmText {
    type: 'text';
    text: string;
}

interface PmChip {
    type: 'variableChip';
    attrs: ChipAttrs;
}

type PmInline = PmText | PmChip;

export interface PmDoc {
    type: 'doc';
    content: Array<{ type: 'paragraph'; content?: PmInline[] }>;
}

/** Stored segments → an editor document. */
export function segmentsToDoc(segments: readonly Segment[]): PmDoc {
    const inline: PmInline[] = [];

    for (const segment of segments) {
        if (segment.type === 'text') {
            // ProseMirror rejects empty text nodes; skipping them is lossless
            // because adjacent text is merged on the way back out.
            if (segment.value !== '') {
                inline.push({ type: 'text', text: segment.value });
            }

            continue;
        }

        inline.push({
            type: 'variableChip',
            attrs: {
                variable: segment.value,
                options: segment.options ?? null,
            },
        });
    }

    return {
        type: 'doc',
        content: [{ type: 'paragraph', ...(inline.length > 0 ? { content: inline } : {}) }],
    };
}

/**
 * An editor document → stored segments.
 *
 * Adjacent text nodes are merged and empty ones dropped, so the same visible
 * card always produces the same stored content no matter how it was typed. That
 * is what makes the round trip stable rather than merely lossless.
 */
export function docToSegments(doc: PmDoc | null | undefined): Segment[] {
    const segments: Segment[] = [];

    const pushText = (value: string): void => {
        if (value === '') return;

        const last = segments[segments.length - 1];

        if (last?.type === 'text') {
            last.value += value;

            return;
        }

        segments.push({ type: 'text', value });
    };

    const paragraphs = doc?.content ?? [];

    paragraphs.forEach((paragraph, index) => {
        // A paragraph break is a space: the card format is a single sentence,
        // not a document, so newlines would not survive rendering anyway.
        if (index > 0) {
            pushText(' ');
        }

        for (const node of paragraph.content ?? []) {
            if (node.type === 'text') {
                pushText(node.text);

                continue;
            }

            const { variable, options } = node.attrs;

            if (!isKnownVariable(variable)) {
                // An unknown chip is dropped rather than persisted: it would
                // fail validation on save and fail resolution in a game.
                continue;
            }

            segments.push(
                variable === 'amount' && options
                    ? { type: 'variable', value: variable, options }
                    : { type: 'variable', value: variable },
            );
        }
    });

    return segments;
}

/** Whether the card has anything worth saving. */
export function isEmptyDoc(doc: PmDoc | null | undefined): boolean {
    return docToSegments(doc).every(
        (segment) => segment.type === 'text' && segment.value.trim() === '',
    );
}
