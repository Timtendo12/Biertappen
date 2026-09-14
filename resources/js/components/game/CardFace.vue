<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import type { ResolvedCard } from '@/game';

const props = defineProps<{ card: ResolvedCard }>();

const { t, te } = useI18n();

/**
 * Known card types get their own accent colour. An unrecognised type — which the
 * format allows, since `type` is a free-form slug — falls back to the generic
 * style instead of failing, so adding a type is never a breaking change.
 */
const ACCENTS: Record<string, string> = {
    drinking: 'text-beer-deep',
    challenge: 'text-accent',
    truth: 'text-success',
    dare: 'text-danger',
    vote: 'text-night/70',
};

const accent = computed(() => ACCENTS[props.card.type] ?? 'text-night/50');

// Colour is never the only signal: the type is always spelled out too.
const label = computed(() => {
    const key = `cardTypes.${props.card.type}`;

    return te(key) ? t(key) : t('cardTypes.custom');
});
</script>

<template>
    <div class="flex h-full flex-col justify-between gap-4 p-6">
        <p class="text-xs font-bold uppercase tracking-[0.2em]" :class="accent">
            {{ label }}
        </p>

        <!--
            The flattened text is the accessible name; the styled spans are hidden
            from assistive tech so a screen reader hears one clean sentence rather
            than a stream of fragments.
        -->
        <p class="sr-only">{{ card.text }}</p>

        <p aria-hidden="true" class="text-pretty text-2xl leading-snug font-semibold text-night">
            <template v-for="(segment, index) in card.segments" :key="index">
                <span v-if="segment.type === 'text'">{{ segment.value }}</span>
                <span v-else-if="segment.type === 'amount'" class="font-black text-beer-deep">{{
                    segment.display
                }}</span>
                <span
                    v-else
                    class="font-black underline decoration-beer decoration-4 underline-offset-4"
                    >{{ segment.display }}</span
                >
            </template>
        </p>

        <div aria-hidden="true" class="h-2" />
    </div>
</template>
