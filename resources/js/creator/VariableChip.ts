import { Node, mergeAttributes } from '@tiptap/core';
import { VueNodeViewRenderer } from '@tiptap/vue-3';
import VariableChipView from './VariableChipView.vue';
import type { AmountOptions, VariableId } from '@/game';

declare module '@tiptap/core' {
    interface Commands<ReturnType> {
        variableChip: {
            insertVariable: (variable: VariableId, options?: AmountOptions | null) => ReturnType;
        };
    }
}

/**
 * A variable rendered as an indivisible chip.
 *
 * `atom: true` is what delivers the behaviour the product asks for: ProseMirror
 * treats the node as a single unit, so backspace removes the whole chip instead
 * of eating it one character at a time, and the cursor can never land inside it.
 * The variable id lives in node attributes, where typing cannot reach it, which
 * is why a user can never corrupt `player1` into `playe1`.
 */
export interface VariableChipOptions {
    /**
     * Called when an amount chip is tapped. The editor component owns the
     * configuration sheet; the chip only reports that it was asked for.
     */
    onConfigureAmount: ((getPos: () => number | undefined) => void) | null;
}

export const VariableChip = Node.create<VariableChipOptions>({
    name: 'variableChip',

    addOptions() {
        return { onConfigureAmount: null };
    },

    group: 'inline',
    inline: true,
    atom: true,
    selectable: true,
    // Dragging is off deliberately: on touch it fights the page scroll, and
    // reordering by drag inside a sentence is not a gesture people attempt.
    draggable: false,

    addAttributes() {
        return {
            variable: {
                default: null,
                parseHTML: (element) => element.getAttribute('data-variable'),
                renderHTML: (attributes) => ({ 'data-variable': attributes.variable }),
            },
            options: {
                default: null,
                parseHTML: (element) => {
                    const raw = element.getAttribute('data-options');

                    if (!raw) return null;

                    try {
                        return JSON.parse(raw) as AmountOptions;
                    } catch {
                        return null;
                    }
                },
                renderHTML: (attributes) =>
                    attributes.options
                        ? { 'data-options': JSON.stringify(attributes.options) }
                        : {},
            },
        };
    },

    parseHTML() {
        return [{ tag: 'span[data-variable]' }];
    },

    renderHTML({ HTMLAttributes }) {
        return ['span', mergeAttributes(HTMLAttributes, { class: 'variable-chip' })];
    },

    addNodeView() {
        return VueNodeViewRenderer(VariableChipView);
    },

    addCommands() {
        return {
            insertVariable:
                (variable, options = null) =>
                ({ commands }) =>
                    commands.insertContent({
                        type: this.name,
                        attrs: { variable, options },
                    }),
        };
    },
});
