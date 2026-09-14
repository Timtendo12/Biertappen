<script setup lang="ts">
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import PrimaryButton from '@/components/ui/PrimaryButton.vue';
import { startCheckout } from '@/lib/checkout';

const { t } = useI18n();

/** Whole euros. The server re-validates against its own bounds regardless. */
const PRESETS = [3, 5, 10] as const;

const open = ref(false);
const amount = ref<number>(5);
const custom = ref('');
const busy = ref(false);
const error = ref<string | null>(null);

function choose(value: number): void {
    amount.value = value;
    custom.value = '';
}

function effectiveAmount(): number {
    const typed = Number.parseInt(custom.value, 10);

    return Number.isFinite(typed) && typed > 0 ? typed : amount.value;
}

async function donate(): Promise<void> {
    busy.value = true;
    error.value = null;

    const failure = await startCheckout('/billing/donate', { amount: effectiveAmount() });

    if (failure) {
        error.value = t('donate.failed');
    } else {
        open.value = false;
    }

    busy.value = false;
}
</script>

<template>
    <div>
        <button
            type="button"
            class="min-h-12 w-full rounded-2xl border border-beer/40 px-4 text-sm font-semibold text-beer"
            @click="open = true"
        >
            🍺 {{ t('donate.button') }}
        </button>

        <!-- A bottom sheet rather than a centred dialog: it sits within thumb
             reach on a phone, which is where this will almost always be tapped. -->
        <div
            v-if="open"
            class="fixed inset-0 z-50 flex items-end justify-center bg-night/70"
            role="dialog"
            aria-modal="true"
            :aria-label="t('donate.title')"
            @click.self="open = false"
        >
            <div class="w-full max-w-md rounded-t-3xl bg-night-soft p-5 pb-8">
                <div class="flex flex-col gap-4">
                    <header class="flex flex-col gap-1">
                        <h2 class="text-xl font-bold">{{ t('donate.title') }}</h2>
                        <p class="text-sm text-foam/70">{{ t('donate.intro') }}</p>
                    </header>

                    <div class="flex gap-2" role="radiogroup" :aria-label="t('donate.title')">
                        <button
                            v-for="preset in PRESETS"
                            :key="preset"
                            type="button"
                            role="radio"
                            :aria-checked="!custom && amount === preset"
                            class="min-h-14 flex-1 rounded-2xl text-lg font-bold transition-colors"
                            :class="
                                !custom && amount === preset
                                    ? 'bg-beer text-night'
                                    : 'bg-night text-foam/80'
                            "
                            @click="choose(preset)"
                        >
                            €{{ preset }}
                        </button>
                    </div>

                    <label class="flex flex-col gap-1.5">
                        <span class="text-sm font-medium text-foam/80">{{ t('donate.custom') }}</span>
                        <input
                            v-model="custom"
                            type="number"
                            inputmode="numeric"
                            min="1"
                            max="500"
                            class="min-h-12 rounded-xl bg-night px-4 text-base text-foam"
                        />
                    </label>

                    <p v-if="error" role="alert" class="text-sm text-danger">{{ error }}</p>

                    <div class="flex flex-col gap-2">
                        <PrimaryButton :loading="busy" @click="donate">
                            {{ t('donate.give', { amount: effectiveAmount() }) }}
                        </PrimaryButton>
                        <PrimaryButton variant="ghost" @click="open = false">
                            {{ t('common.cancel') }}
                        </PrimaryButton>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
