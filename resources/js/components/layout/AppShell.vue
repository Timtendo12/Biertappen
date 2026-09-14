<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { usePage } from '@inertiajs/vue3';
import { applyUpdate, updateReady } from '@/pwa';
import type { SharedProps } from '@/types/inertia';

const props = withDefaults(
    defineProps<{
        /** Gameplay is portrait-only; content pages stay usable either way. */
        portraitOnly?: boolean;
    }>(),
    { portraitOnly: false },
);

const { t } = useI18n();
const page = usePage<SharedProps>();

const flash = computed(() => page.props.flash);
</script>

<template>
    <div class="relative flex min-h-dvh flex-col bg-night text-foam">
        <slot />

        <!-- Flash messages are announced, not just shown. -->
        <div
            v-if="flash.success || flash.error"
            role="status"
            aria-live="polite"
            class="pointer-events-none fixed inset-x-0 top-3 z-50 flex justify-center px-4"
        >
            <p
                class="pointer-events-auto rounded-xl px-4 py-2 text-sm font-medium shadow-lg"
                :class="flash.error ? 'bg-danger text-white' : 'bg-success text-night'"
            >
                {{ flash.error ?? flash.success }}
            </p>
        </div>

        <!--
            A waiting service worker never activates on its own: reloading mid-game
            would throw the game away. The player decides when.
        -->
        <div
            v-if="updateReady"
            role="status"
            class="fixed inset-x-0 bottom-0 z-50 flex items-center justify-between gap-3 bg-night-soft px-4 py-3 text-sm"
        >
            <span>{{ t('pwa.updateAvailable') }}</span>
            <button
                type="button"
                class="rounded-lg bg-beer px-3 py-1.5 font-semibold text-night"
                @click="applyUpdate"
            >
                {{ t('pwa.reload') }}
            </button>
        </div>

        <!--
            Orientation lock is unavailable in most mobile browsers, so this overlay
            is the reliable fallback rather than the backup plan.
        -->
        <div
            v-if="props.portraitOnly"
            class="landscape-guard fixed inset-0 z-100 hidden flex-col items-center justify-center gap-4 bg-night p-8 text-center"
        >
            <span class="text-5xl" aria-hidden="true">📱</span>
            <p class="text-lg font-semibold">{{ t('errors.landscape') }}</p>
        </div>
    </div>
</template>

<style scoped>
/*
 * Height-based rather than orientation-based: a landscape *tablet* is perfectly
 * playable, a 400px-tall phone is not.
 */
@media (orientation: landscape) and (max-height: 520px) {
    .landscape-guard {
        display: flex;
    }
}
</style>
