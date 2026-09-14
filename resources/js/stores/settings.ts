import { defineStore } from 'pinia';
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { hapticsSupported, vibrate } from '@/lib/haptics';
import { playSound, soundSupported, unlockAudio } from '@/lib/sound';
import { DEFAULT_LOCALE, isSupportedLocale, setLocale, type AppLocale } from '@/i18n';

const STORAGE_KEY = 'biertappen.settings';

interface StoredSettings {
    sound: boolean;
    haptics: boolean;
    reducedMotion: boolean | null;
}

/**
 * Player preferences.
 *
 * Persisted in localStorage — unlike game state, which is deliberately
 * in-memory. Preferences should survive closing the app; a half-played game
 * should not.
 */
export const useSettingsStore = defineStore('settings', () => {
    const stored = read();

    const sound = ref(stored.sound);
    const haptics = ref(stored.haptics);

    /** null = follow the operating system; true/false = the player overrode it. */
    const reducedMotionOverride = ref<boolean | null>(stored.reducedMotion);

    const locale = ref<AppLocale>(DEFAULT_LOCALE);

    const systemReducedMotion = ref(
        typeof window !== 'undefined' &&
            window.matchMedia?.('(prefers-reduced-motion: reduce)').matches === true,
    );

    const reducedMotion = computed(() => reducedMotionOverride.value ?? systemReducedMotion.value);

    const canUseSound = computed(() => soundSupported());
    const canUseHaptics = computed(() => hapticsSupported());

    if (typeof window !== 'undefined') {
        window
            .matchMedia?.('(prefers-reduced-motion: reduce)')
            .addEventListener?.('change', (event) => {
                systemReducedMotion.value = event.matches;
            });
    }

    // The CSS hook lives on <html> so it also covers content rendered outside Vue.
    watch(
        reducedMotion,
        (value) => {
            if (typeof document !== 'undefined') {
                document.documentElement.classList.toggle('reduce-motion', value);
            }
        },
        { immediate: true },
    );

    watch([sound, haptics, reducedMotionOverride], persist);

    function read(): StoredSettings {
        const fallback: StoredSettings = { sound: true, haptics: true, reducedMotion: null };

        if (typeof localStorage === 'undefined') {
            return fallback;
        }

        try {
            const raw = localStorage.getItem(STORAGE_KEY);

            return raw ? { ...fallback, ...(JSON.parse(raw) as Partial<StoredSettings>) } : fallback;
        } catch {
            // Private mode, disabled storage, corrupted value — defaults are fine.
            return fallback;
        }
    }

    function persist(): void {
        if (typeof localStorage === 'undefined') return;

        try {
            localStorage.setItem(
                STORAGE_KEY,
                JSON.stringify({
                    sound: sound.value,
                    haptics: haptics.value,
                    reducedMotion: reducedMotionOverride.value,
                } satisfies StoredSettings),
            );
        } catch {
            // Nothing to do: the session simply will not remember the change.
        }
    }

    /**
     * Language changes go to the server too, so server-rendered messages, the
     * <html lang> attribute and the account setting all agree.
     */
    function changeLocale(next: AppLocale): void {
        if (!isSupportedLocale(next) || next === locale.value) return;

        locale.value = next;
        void setLocale(next);

        router.post('/locale', { locale: next }, { preserveScroll: true, preserveState: true });
    }

    function applyServerLocale(next: unknown): void {
        if (isSupportedLocale(next)) {
            locale.value = next;
            void setLocale(next);
        }
    }

    /** Sound and haptics are always requested through these two, never directly. */
    function feedback(sfx: Parameters<typeof playSound>[0], haptic: Parameters<typeof vibrate>[0]): void {
        if (sound.value) playSound(sfx);
        if (haptics.value) vibrate(haptic);
    }

    function unlock(): void {
        if (sound.value) unlockAudio();
    }

    return {
        sound,
        haptics,
        reducedMotionOverride,
        reducedMotion,
        canUseSound,
        canUseHaptics,
        locale,
        changeLocale,
        applyServerLocale,
        feedback,
        unlock,
    };
});
