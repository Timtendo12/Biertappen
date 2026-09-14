<script setup lang="ts">
import { computed } from 'vue';
import { NodeViewWrapper, nodeViewProps } from '@tiptap/vue-3';
import { variableLabel } from './labels';
import type { VariableChipOptions } from './VariableChip';
import { DEFAULT_AMOUNT_OPTIONS, type AmountOptions, type VariableId } from '@/game';

const props = defineProps(nodeViewProps);

const variable = computed(() => props.node.attrs.variable as VariableId);
const options = computed(() => (props.node.attrs.options ?? null) as AmountOptions | null);

const isAmount = computed(() => variable.value === 'amount');

/**
 * The amount chip shows its actual configuration rather than a generic word, so
 * an author can read the rule off the sentence without opening anything.
 */
const label = computed(() => {
    if (!isAmount.value) {
        return variableLabel(variable.value);
    }

    const resolved = options.value ?? DEFAULT_AMOUNT_OPTIONS;

    return resolved.mode === 'fixed' ? String(resolved.value) : `${resolved.min}–${resolved.max}`;
});

function configure(): void {
    if (!isAmount.value) return;

    // Routed through the extension's options rather than a custom editor event:
    // it is the typed, supported seam, and it keeps the sheet's ownership in the
    // editor component where the rest of the UI lives.
    const handler = (props.extension.options as VariableChipOptions).onConfigureAmount;

    handler?.(props.getPos);
}
</script>

<template>
    <!--
        as="span" keeps the chip inline inside the sentence. contenteditable is
        off so the caret cannot enter it and the variable id stays untouchable —
        backspace against it removes the whole node, which is the behaviour the
        atom flag on the node guarantees.
    -->
    <NodeViewWrapper
        as="span"
        contenteditable="false"
        class="variable-chip"
        :class="[
            isAmount ? 'variable-chip--amount' : 'variable-chip--player',
            selected ? 'variable-chip--selected' : '',
        ]"
        :data-variable="variable"
        role="button"
        :tabindex="isAmount ? 0 : -1"
        :aria-label="variableLabel(variable)"
        @click="configure"
        @keydown.enter.prevent="configure"
    >
        {{ label }}
        <span v-if="isAmount" aria-hidden="true" class="variable-chip__hint">▾</span>
    </NodeViewWrapper>
</template>

<style>
/* Unscoped: the node view is rendered by ProseMirror outside this component's
   style scope, so a scoped rule would never apply to it. */
.variable-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.2em;
    margin: 0 0.1em;
    padding: 0.1em 0.5em;
    border-radius: 0.6rem;
    font-weight: 700;
    font-size: 0.95em;
    line-height: 1.5;
    white-space: nowrap;
    user-select: none;
    cursor: default;
}

.variable-chip--player {
    background: var(--color-accent);
    color: var(--color-foam);
}

.variable-chip--amount {
    background: var(--color-beer);
    color: var(--color-night);
    cursor: pointer;
}

/* A selected chip is about to be deleted by backspace — say so visibly, and not
   with colour alone. */
.variable-chip--selected {
    outline: 3px solid var(--color-foam);
    outline-offset: 1px;
}

.variable-chip__hint {
    font-size: 0.75em;
    opacity: 0.7;
}
</style>
