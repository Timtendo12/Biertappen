<script setup lang="ts">
import { computed } from 'vue';
import CardFace from '@/components/game/CardFace.vue';
import { createRng, resolveCard } from '@/game';
import type { Card, Participants, Player, Segment } from '@/game';

const props = defineProps<{
    type: string;
    participants: Participants;
    content: Segment[];
}>();

/** Stand-ins, so the preview shows names rather than {player1}. */
const PREVIEW_PLAYERS: Player[] = [
    { id: 'preview-1', name: 'Alex' },
    { id: 'preview-2', name: 'Jamie' },
    { id: 'preview-3', name: 'Sam' },
    { id: 'preview-4', name: 'Robin' },
];

/**
 * Rendered by the real engine and the real gameplay card component.
 *
 * Nothing about this preview is a reimplementation: the same resolveCard() that
 * deals a live card produces these segments, and CardFace draws them. Preview
 * fidelity is therefore structural — the two cannot drift, because there is only
 * one implementation.
 */
const resolved = computed(() => {
    const card: Card = {
        id: 'preview',
        type: props.type,
        participants: props.participants,
        content: props.content.length > 0 ? props.content : [{ type: 'text', value: '…' }],
    };

    // Fixed seed: the preview must not reshuffle names on every keystroke.
    return resolveCard(card, PREVIEW_PLAYERS, createRng(42));
});
</script>

<template>
    <div class="mx-auto aspect-[3/4.2] w-full max-w-[18rem] overflow-hidden rounded-3xl bg-foam shadow-xl">
        <CardFace :card="resolved" />
    </div>
</template>
