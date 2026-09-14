<script setup lang="ts">
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import CardFace from './CardFace.vue';
import { useSettingsStore } from '@/stores/settings';
import type { ResolvedCard } from '@/game';

const props = defineProps<{
    card: ResolvedCard | null;
    revealed: boolean;
    /** Cards still to come, drawn as inert paper behind the top card. */
    remaining: number;
}>();

const emit = defineEmits<{ reveal: []; discard: [direction: 'left' | 'right'] }>();

const { t } = useI18n();
const settings = useSettingsStore();

/** Past this fraction of the viewport width, releasing throws the card away. */
const SWIPE_THRESHOLD = 0.28;

const dragX = ref(0);
const dragging = ref(false);
const leaving = ref<'left' | 'right' | null>(null);

let pointerId: number | null = null;
let startX = 0;

const backdropCount = computed(() => Math.min(2, Math.max(0, props.remaining)));

/**
 * Rotation and fade are derived from the drag distance, so the card tracks the
 * finger exactly instead of animating on a timer.
 */
const cardStyle = computed(() => {
    if (leaving.value) {
        const direction = leaving.value === 'left' ? -1 : 1;

        return {
            transform: `translateX(${direction * 140}vw) rotate(${direction * 28}deg)`,
            opacity: '0',
            transition: settings.reducedMotion
                ? 'opacity 120ms linear'
                : 'transform var(--duration-swipe) var(--ease-card), opacity var(--duration-swipe) linear',
        };
    }

    if (!dragging.value && dragX.value === 0) {
        return {};
    }

    return {
        transform: `translateX(${dragX.value}px) rotate(${dragX.value * 0.04}deg)`,
        transition: dragging.value ? 'none' : 'transform 200ms var(--ease-card)',
    };
});

const flipStyle = computed(() => ({
    transform: props.revealed ? 'rotateY(180deg)' : 'rotateY(0deg)',
    transition: settings.reducedMotion
        ? 'none'
        : 'transform var(--duration-flip) var(--ease-card)',
}));

function threshold(): number {
    return (typeof window === 'undefined' ? 320 : window.innerWidth) * SWIPE_THRESHOLD;
}

function onPointerDown(event: PointerEvent): void {
    if (!props.card || !props.revealed || leaving.value) return;

    pointerId = event.pointerId;
    startX = event.clientX;
    dragging.value = true;

    // Capture so the drag survives the pointer leaving the card's own bounds.
    (event.currentTarget as HTMLElement).setPointerCapture(event.pointerId);
}

function onPointerMove(event: PointerEvent): void {
    if (!dragging.value || event.pointerId !== pointerId) return;

    dragX.value = event.clientX - startX;
}

function onPointerUp(event: PointerEvent): void {
    if (!dragging.value || event.pointerId !== pointerId) return;

    dragging.value = false;
    pointerId = null;

    const distance = dragX.value;

    if (Math.abs(distance) >= threshold()) {
        throwAway(distance < 0 ? 'left' : 'right');
    } else {
        // Not far enough: spring back rather than discarding by accident.
        dragX.value = 0;
    }
}

function throwAway(direction: 'left' | 'right'): void {
    leaving.value = direction;

    const settle = settings.reducedMotion ? 120 : 240;

    window.setTimeout(() => {
        emit('discard', direction);
        // Reset only after the parent has swapped in the next card, so the new
        // card does not inherit the outgoing one's transform.
        leaving.value = null;
        dragX.value = 0;
    }, settle);
}

function onCardActivate(): void {
    if (!props.revealed) {
        emit('reveal');
    }
}

/** Keyboard equivalents: the whole game is playable without touch. */
function onKeydown(event: KeyboardEvent): void {
    if (!props.card || leaving.value) return;

    if (!props.revealed && (event.key === 'Enter' || event.key === ' ')) {
        event.preventDefault();
        emit('reveal');

        return;
    }

    if (props.revealed && (event.key === 'ArrowLeft' || event.key === 'ArrowRight')) {
        event.preventDefault();
        throwAway(event.key === 'ArrowLeft' ? 'left' : 'right');
    }
}
</script>

<template>
    <div class="relative flex w-full flex-1 items-center justify-center">
        <!-- Inert paper behind the live card, purely to suggest depth. -->
        <div
            v-for="index in backdropCount"
            :key="`backdrop-${index}`"
            aria-hidden="true"
            class="absolute aspect-[3/4.2] w-[min(82vw,22rem)] rounded-3xl bg-night-card/70 shadow-xl"
            :style="{
                transform: `translateY(${index * 10}px) scale(${1 - index * 0.035})`,
                zIndex: 0,
            }"
        />

        <div
            v-if="card"
            :key="card.playId"
            class="no-select absolute aspect-[3/4.2] w-[min(82vw,22rem)] touch-none"
            :style="{ ...cardStyle, zIndex: 10, perspective: '1200px' }"
            role="button"
            tabindex="0"
            :aria-label="revealed ? t('game.swipeToDiscard') : t('game.tapToReveal')"
            @pointerdown="onPointerDown"
            @pointermove="onPointerMove"
            @pointerup="onPointerUp"
            @pointercancel="onPointerUp"
            @click="onCardActivate"
            @keydown="onKeydown"
        >
            <div class="relative size-full" :style="{ transformStyle: 'preserve-3d', ...flipStyle }">
                <!-- Back: what the player taps. -->
                <div
                    class="absolute inset-0 grid place-items-center rounded-3xl bg-gradient-to-br from-night-card to-night-soft shadow-2xl ring-1 ring-foam/10"
                    :style="{ backfaceVisibility: 'hidden' }"
                >
                    <div class="flex flex-col items-center gap-3">
                        <span class="text-6xl" aria-hidden="true">🍺</span>
                        <span class="text-sm font-medium text-foam/60">{{ t('game.tapToReveal') }}</span>
                    </div>
                </div>

                <!-- Front: the resolved card. -->
                <div
                    class="absolute inset-0 overflow-hidden rounded-3xl bg-foam shadow-2xl"
                    :style="{ backfaceVisibility: 'hidden', transform: 'rotateY(180deg)' }"
                >
                    <CardFace :card="card" />
                </div>
            </div>
        </div>
    </div>
</template>
