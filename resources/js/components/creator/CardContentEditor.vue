<script setup lang="ts">
import { onBeforeUnmount, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { Editor, EditorContent } from '@tiptap/vue-3';
import Document from '@tiptap/extension-document';
import Paragraph from '@tiptap/extension-paragraph';
import Text from '@tiptap/extension-text';
import Placeholder from '@tiptap/extension-placeholder';
import { VariableChip } from '@/creator/VariableChip';
import { docToSegments, segmentsToDoc, type PmDoc } from '@/creator/segments';
import { variableLabel } from '@/creator/labels';
import AmountSheet from './AmountSheet.vue';
import { availableVariables, DEFAULT_AMOUNT_OPTIONS } from '@/game';
import type { AmountOptions, Participants, Segment, VariableId } from '@/game';

const props = defineProps<{
    /** Decides which variables the toolbar offers. */
    participants: Participants;
}>();

const model = defineModel<Segment[]>({ required: true });

const { t } = useI18n();

const editor = ref<Editor>();
const amountTarget = ref<(() => number | undefined) | null>(null);
const amountOptions = ref<AmountOptions>(DEFAULT_AMOUNT_OPTIONS);

/** Guards the two-way sync so writing back does not re-trigger the watcher. */
let applyingExternal = false;

function openAmountSheet(getPos: () => number | undefined): void {
    const pos = getPos();

    if (pos === undefined || !editor.value) return;

    const node = editor.value.state.doc.nodeAt(pos);

    amountOptions.value = (node?.attrs.options as AmountOptions | null) ?? DEFAULT_AMOUNT_OPTIONS;
    amountTarget.value = getPos;
}

function applyAmount(options: AmountOptions): void {
    const pos = amountTarget.value?.();

    if (pos !== undefined && editor.value) {
        editor.value
            .chain()
            .focus()
            .command(({ tr }) => {
                const node = tr.doc.nodeAt(pos);

                if (!node) return false;

                tr.setNodeMarkup(pos, undefined, { ...node.attrs, options });

                return true;
            })
            .run();
    }

    amountTarget.value = null;
}

editor.value = new Editor({
    extensions: [
        // Deliberately minimal: the card format has no marks, so bold, italic
        // and headings are not merely unstyled — they cannot be typed at all,
        // and nothing can be authored that the format cannot store.
        Document,
        Paragraph,
        Text,
        Placeholder.configure({ placeholder: () => t('creator.contentPlaceholder') }),
        VariableChip.configure({ onConfigureAmount: openAmountSheet }),
    ],
    content: segmentsToDoc(model.value),
    editorProps: {
        attributes: {
            class: 'card-content-editor',
            'aria-label': t('creator.contentLabel'),
        },
    },
    onUpdate: ({ editor: instance }) => {
        if (applyingExternal) return;

        model.value = docToSegments(instance.getJSON() as PmDoc);
    },
});

/*
 * Reloading external content (switching card, cancelling an edit) without
 * clobbering what is being typed: only replace the document when it actually
 * differs from what the editor already holds.
 */
watch(model, (segments) => {
    if (!editor.value) return;

    const current = docToSegments(editor.value.getJSON() as PmDoc);

    if (JSON.stringify(current) === JSON.stringify(segments)) return;

    applyingExternal = true;
    editor.value.commands.setContent(segmentsToDoc(segments), { emitUpdate: false });
    applyingExternal = false;
});

function insert(variable: VariableId): void {
    editor.value
        ?.chain()
        .focus()
        .insertVariable(variable, variable === 'amount' ? DEFAULT_AMOUNT_OPTIONS : null)
        .run();
}

onBeforeUnmount(() => editor.value?.destroy());
</script>

<template>
    <div class="flex flex-col gap-3">
        <EditorContent :editor="editor" />

        <!--
            The toolbar offers exactly the variables this participant count
            allows, so an invalid card cannot be built in the first place rather
            than being rejected on save. Nobody ever types {player1}.
        -->
        <div class="flex flex-wrap gap-2" role="group" :aria-label="t('creator.insertVariable')">
            <button
                v-for="variable in availableVariables(props.participants)"
                :key="variable"
                type="button"
                class="min-h-11 rounded-xl bg-night-soft px-3 text-sm font-semibold text-foam/85"
                @click="insert(variable)"
            >
                + {{ variableLabel(variable) }}
            </button>
        </div>

        <AmountSheet
            v-if="amountTarget"
            :options="amountOptions"
            @apply="applyAmount"
            @close="amountTarget = null"
        />
    </div>
</template>

<style>
/* Unscoped: ProseMirror renders the editable surface outside this component's
   style scope. */
.card-content-editor {
    min-height: 7rem;
    padding: 1rem;
    border-radius: 1rem;
    background: var(--color-night-soft);
    color: var(--color-foam);
    font-size: 1.05rem;
    line-height: 1.7;
    outline: none;
}

.card-content-editor p {
    margin: 0;
}

.card-content-editor p.is-editor-empty:first-child::before {
    content: attr(data-placeholder);
    float: left;
    height: 0;
    pointer-events: none;
    color: color-mix(in srgb, var(--color-foam) 35%, transparent);
}
</style>
