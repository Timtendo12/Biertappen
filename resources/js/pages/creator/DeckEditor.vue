<script setup lang="ts">
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import AppShell from '@/components/layout/AppShell.vue';
import PrimaryButton from '@/components/ui/PrimaryButton.vue';
import TextField from '@/components/ui/TextField.vue';
import CardWizard, { type CardDraft } from '@/components/creator/CardWizard.vue';
import CardPreview from '@/components/creator/CardPreview.vue';
import type { Participants, Segment } from '@/game';
import type { RequestPayload } from '@inertiajs/core';
import type { SharedProps } from '@/types/inertia';

interface EditorCard {
    id: number;
    type: string;
    participants: Participants;
    content: Segment[];
    position: number;
}

interface EditorDeck {
    uuid: string;
    name: string;
    description: string;
    ending_mode: 'cards' | 'all_cards_once';
    ending_count: number | null;
    visibility: string;
    share_url: string | null;
    cards: EditorCard[];
}

const props = defineProps<{ deck: EditorDeck }>();

const { t } = useI18n();
const page = usePage<SharedProps>();

const editing = ref<CardDraft | null>(null);
const adding = ref(false);
const copied = ref(false);

const base = `/creator/decks/${props.deck.uuid}`;

const meta = useForm({
    name: props.deck.name,
    description: props.deck.description,
    ending_mode: props.deck.ending_mode,
    ending_count: props.deck.ending_count ?? 30,
});

// Plain router calls rather than useForm: the card payload is a nested
// structure useForm cannot type, and its errors already arrive via page props.
const savingCard = ref(false);

/** Server-side validation messages for the card being edited. */
const cardErrors = computed(() => Object.values(page.props.errors ?? {}) as string[]);

function saveMeta(): void {
    meta.put(base, { preserveScroll: true });
}

function saveCard(card: CardDraft, again: boolean): void {
    /*
     * Cast because Inertia's FormDataConvertible cannot express a discriminated
     * union like Segment — it requires an index signature on every nested
     * object. The value is plain JSON and serialises correctly; only the type
     * is inexpressible.
     */
    const payload = {
        type: card.type,
        participants: card.participants,
        content: card.content,
    } as unknown as RequestPayload;

    const done = () => {
        if (again) {
            // Straight into a fresh card: writing a deck means writing many.
            editing.value = null;
            adding.value = true;
        } else {
            adding.value = false;
            editing.value = null;
        }
    };

    savingCard.value = true;

    const options = {
        preserveScroll: true,
        onSuccess: done,
        onFinish: () => {
            savingCard.value = false;
        },
    };

    if (card.id) {
        router.put(`${base}/cards/${card.id}`, payload, options);

        return;
    }

    router.post(`${base}/cards`, payload, options);
}

function deleteCard(card: EditorCard): void {
    if (!window.confirm(t('creator.deleteCardConfirm'))) return;

    router.delete(`${base}/cards/${card.id}`, { preserveScroll: true });
}

function duplicateCard(card: EditorCard): void {
    router.post(`${base}/cards/${card.id}/duplicate`, {}, { preserveScroll: true });
}

function move(card: EditorCard, delta: number): void {
    const ids = props.deck.cards.map((c) => c.id);
    const from = ids.indexOf(card.id);
    const to = from + delta;

    if (to < 0 || to >= ids.length) return;

    ids.splice(to, 0, ...ids.splice(from, 1));

    router.post(`${base}/cards/reorder`, { ids }, { preserveScroll: true });
}

function deleteDeck(): void {
    if (!window.confirm(t('creator.deleteConfirm'))) return;

    router.delete(base);
}

async function copyShareLink(): Promise<void> {
    if (!props.deck.share_url) return;

    try {
        await navigator.clipboard.writeText(props.deck.share_url);
        copied.value = true;
        window.setTimeout(() => (copied.value = false), 2000);
    } catch {
        // Clipboard access can be refused; the link stays visible and selectable.
    }
}
</script>

<template>
    <Head :title="deck.name" />

    <AppShell>
        <main class="mx-auto flex w-full max-w-md flex-1 flex-col gap-6 px-5 py-8">
            <header class="flex items-baseline justify-between gap-3">
                <h1 class="truncate text-2xl font-bold">{{ deck.name }}</h1>
                <button type="button" class="shrink-0 text-sm text-foam/60 underline" @click="router.visit('/creator')">
                    {{ t('common.back') }}
                </button>
            </header>

            <!-- A card being written takes over the screen: on a phone there is
                 no room for the editor and the deck list at once. -->
            <CardWizard
                v-if="adding || editing"
                :card="editing"
                :saving="savingCard"
                :errors="cardErrors"
                @save="saveCard"
                @cancel="((adding = false), (editing = null))"
            />

            <template v-else>
                <section class="flex flex-col gap-3" aria-labelledby="deck-meta">
                    <h2 id="deck-meta" class="text-sm font-semibold uppercase tracking-wide text-foam/60">
                        {{ t('creator.deckName') }}
                    </h2>

                    <TextField v-model="meta.name" :label="t('creator.deckName')" :error="meta.errors.name" />
                    <TextField
                        v-model="meta.description"
                        :label="t('creator.deckDescription')"
                        :error="meta.errors.description"
                    />

                    <div class="flex gap-2" role="radiogroup" :aria-label="t('creator.endingMode')">
                        <button
                            type="button"
                            role="radio"
                            :aria-checked="meta.ending_mode === 'cards'"
                            class="min-h-12 flex-1 rounded-2xl px-3 text-sm font-semibold"
                            :class="meta.ending_mode === 'cards' ? 'bg-beer text-night' : 'bg-night-soft text-foam/80'"
                            @click="meta.ending_mode = 'cards'"
                        >
                            {{ t('creator.endingCards') }}
                        </button>
                        <button
                            type="button"
                            role="radio"
                            :aria-checked="meta.ending_mode === 'all_cards_once'"
                            class="min-h-12 flex-1 rounded-2xl px-3 text-sm font-semibold"
                            :class="
                                meta.ending_mode === 'all_cards_once'
                                    ? 'bg-beer text-night'
                                    : 'bg-night-soft text-foam/80'
                            "
                            @click="meta.ending_mode = 'all_cards_once'"
                        >
                            {{ t('creator.endingAll') }}
                        </button>
                    </div>

                    <label v-if="meta.ending_mode === 'cards'" class="flex flex-col gap-1.5">
                        <span class="text-sm font-medium text-foam/80">{{ t('creator.endingCount') }}</span>
                        <input
                            v-model.number="meta.ending_count"
                            type="number"
                            inputmode="numeric"
                            min="1"
                            class="min-h-12 rounded-xl bg-night-soft px-4 text-base text-foam"
                        />
                    </label>

                    <PrimaryButton :loading="meta.processing" @click="saveMeta">
                        {{ t('common.save') }}
                    </PrimaryButton>
                </section>

                <section class="flex flex-col gap-3" aria-labelledby="deck-cards">
                    <div class="flex items-baseline justify-between">
                        <h2 id="deck-cards" class="text-sm font-semibold uppercase tracking-wide text-foam/60">
                            {{ t('creator.cards') }}
                        </h2>
                        <span class="text-sm text-foam/60">{{ t('decks.cardCount', deck.cards.length) }}</span>
                    </div>

                    <PrimaryButton @click="adding = true">{{ t('creator.addCard') }}</PrimaryButton>

                    <p v-if="deck.cards.length === 0" class="text-sm text-foam/60">{{ t('creator.noCards') }}</p>

                    <ul v-else class="flex flex-col gap-3">
                        <li
                            v-for="(card, index) in deck.cards"
                            :key="card.id"
                            class="flex flex-col gap-3 rounded-2xl bg-night-soft p-3"
                        >
                            <CardPreview
                                :type="card.type"
                                :participants="card.participants"
                                :content="card.content"
                            />

                            <div class="flex flex-wrap gap-2">
                                <button
                                    type="button"
                                    class="min-h-11 flex-1 rounded-xl bg-night px-3 text-sm font-semibold"
                                    @click="editing = { ...card }"
                                >
                                    {{ t('common.edit') }}
                                </button>
                                <button
                                    type="button"
                                    class="min-h-11 rounded-xl bg-night px-3 text-sm"
                                    @click="duplicateCard(card)"
                                >
                                    {{ t('common.duplicate') }}
                                </button>
                                <button
                                    type="button"
                                    class="min-h-11 rounded-xl bg-night px-3 text-sm text-danger"
                                    @click="deleteCard(card)"
                                >
                                    {{ t('common.delete') }}
                                </button>
                                <button
                                    type="button"
                                    class="min-h-11 w-11 rounded-xl bg-night text-sm disabled:opacity-30"
                                    :disabled="index === 0"
                                    :aria-label="t('common.back')"
                                    @click="move(card, -1)"
                                >
                                    ↑
                                </button>
                                <button
                                    type="button"
                                    class="min-h-11 w-11 rounded-xl bg-night text-sm disabled:opacity-30"
                                    :disabled="index === deck.cards.length - 1"
                                    :aria-label="t('common.next')"
                                    @click="move(card, 1)"
                                >
                                    ↓
                                </button>
                            </div>
                        </li>
                    </ul>
                </section>

                <section class="flex flex-col gap-3" aria-labelledby="deck-share">
                    <h2 id="deck-share" class="text-sm font-semibold uppercase tracking-wide text-foam/60">
                        {{ t('creator.share') }}
                    </h2>

                    <template v-if="deck.share_url">
                        <p class="rounded-xl bg-night-soft p-3 text-xs break-all text-foam/70">
                            {{ deck.share_url }}
                        </p>
                        <div class="flex gap-2">
                            <PrimaryButton class="flex-1" variant="ghost" @click="copyShareLink">
                                {{ copied ? t('creator.copied') : t('creator.copyLink') }}
                            </PrimaryButton>
                            <PrimaryButton
                                variant="ghost"
                                @click="router.delete(`${base}/share`, { preserveScroll: true })"
                            >
                                {{ t('creator.stopSharing') }}
                            </PrimaryButton>
                        </div>
                    </template>

                    <PrimaryButton
                        v-else
                        variant="ghost"
                        @click="router.post(`${base}/share`, {}, { preserveScroll: true })"
                    >
                        {{ t('creator.share') }}
                    </PrimaryButton>

                    <a
                        :href="`${base}/export`"
                        class="min-h-12 rounded-2xl border border-foam/20 px-5 py-3 text-center text-base font-bold"
                    >
                        {{ t('creator.export') }}
                    </a>

                    <PrimaryButton variant="ghost" @click="deleteDeck">
                        {{ t('creator.deleteDeck') }}
                    </PrimaryButton>
                </section>
            </template>
        </main>
    </AppShell>
</template>
