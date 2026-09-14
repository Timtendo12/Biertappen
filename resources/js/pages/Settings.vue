<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Head, router } from '@inertiajs/vue3';
import AppShell from '@/components/layout/AppShell.vue';
import ToggleRow from '@/components/ui/ToggleRow.vue';
import { useSettingsStore } from '@/stores/settings';
import { SUPPORTED_LOCALES, type AppLocale } from '@/i18n';

const { t } = useI18n();
const settings = useSettingsStore();

const LOCALE_NAMES: Record<AppLocale, string> = {
    nl: 'Nederlands',
    en: 'English',
};

/**
 * The switch is bound to the *effective* value, so a player who has never
 * touched it still sees their OS preference reflected. Flipping it records an
 * explicit override from then on.
 */
const reducedMotion = computed({
    get: () => settings.reducedMotion,
    set: (value: boolean) => {
        settings.reducedMotionOverride = value;
    },
});
</script>

<template>
    <Head :title="t('settings.title')" />

    <AppShell>
        <main class="mx-auto flex w-full max-w-md flex-1 flex-col gap-6 px-5 py-8">
            <header class="flex items-baseline justify-between gap-3">
                <h1 class="text-2xl font-bold">{{ t('settings.title') }}</h1>
                <button type="button" class="text-sm text-foam/60 underline" @click="router.visit('/')">
                    {{ t('common.back') }}
                </button>
            </header>

            <section class="flex flex-col gap-2" aria-labelledby="language-heading">
                <h2 id="language-heading" class="text-sm font-semibold uppercase tracking-wide text-foam/60">
                    {{ t('settings.language') }}
                </h2>

                <div class="flex gap-2" role="radiogroup" :aria-label="t('settings.language')">
                    <button
                        v-for="code in SUPPORTED_LOCALES"
                        :key="code"
                        type="button"
                        role="radio"
                        :aria-checked="settings.locale === code"
                        class="min-h-12 flex-1 rounded-2xl px-4 font-semibold transition-colors"
                        :class="
                            settings.locale === code
                                ? 'bg-beer text-night'
                                : 'bg-night-soft text-foam/75'
                        "
                        @click="settings.changeLocale(code)"
                    >
                        {{ LOCALE_NAMES[code] }}
                    </button>
                </div>
            </section>

            <section class="flex flex-col gap-2" aria-labelledby="feedback-heading">
                <h2 id="feedback-heading" class="text-sm font-semibold uppercase tracking-wide text-foam/60">
                    {{ t('settings.feedback') }}
                </h2>

                <ToggleRow
                    v-model="settings.sound"
                    :label="t('settings.sound')"
                    :unsupported="settings.canUseSound ? undefined : t('settings.soundUnsupported')"
                />

                <ToggleRow
                    v-model="settings.haptics"
                    :label="t('settings.haptics')"
                    :unsupported="settings.canUseHaptics ? undefined : t('settings.hapticsUnsupported')"
                />

                <ToggleRow
                    v-model="reducedMotion"
                    :label="t('settings.reducedMotion')"
                    :hint="t('settings.reducedMotionHint')"
                />
            </section>
        </main>
    </AppShell>
</template>
