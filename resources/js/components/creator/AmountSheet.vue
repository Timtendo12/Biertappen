<script setup lang="ts">
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import PrimaryButton from '@/components/ui/PrimaryButton.vue';
import type { AmountOptions } from '@/game';

const props = defineProps<{ options: AmountOptions }>();

const emit = defineEmits<{ apply: [options: AmountOptions]; close: [] }>();

const { t } = useI18n();

const mode = ref<AmountOptions['mode']>(props.options.mode);
const value = ref(props.options.mode === 'fixed' ? props.options.value : 3);
const min = ref(props.options.mode === 'range' ? props.options.min : 1);
const max = ref(props.options.mode === 'range' ? props.options.max : 5);

function apply(): void {
    if (mode.value === 'fixed') {
        emit('apply', { mode: 'fixed', value: clamp(value.value) });

        return;
    }

    // Swap rather than reject an inverted range: the author's intent is
    // obvious, and the validator would refuse min > max on save anyway.
    const low = clamp(Math.min(min.value, max.value));
    const high = clamp(Math.max(min.value, max.value));

    emit('apply', { mode: 'range', min: low, max: high });
}

function clamp(n: number): number {
    return Math.min(100, Math.max(1, Math.round(Number.isFinite(n) ? n : 1)));
}
</script>

<template>
    <div
        class="fixed inset-0 z-50 flex items-end justify-center bg-night/70"
        role="dialog"
        aria-modal="true"
        :aria-label="t('variables.amount')"
        @click.self="emit('close')"
    >
        <div class="flex w-full max-w-md flex-col gap-4 rounded-t-3xl bg-night-soft p-5 pb-8">
            <h2 class="text-lg font-bold">{{ t('variables.amount') }}</h2>

            <div class="flex gap-2" role="radiogroup" :aria-label="t('variables.amount')">
                <button
                    type="button"
                    role="radio"
                    :aria-checked="mode === 'fixed'"
                    class="min-h-12 flex-1 rounded-2xl text-sm font-semibold"
                    :class="mode === 'fixed' ? 'bg-beer text-night' : 'bg-night text-foam/80'"
                    @click="mode = 'fixed'"
                >
                    {{ t('variables.amountFixed') }}
                </button>
                <button
                    type="button"
                    role="radio"
                    :aria-checked="mode === 'range'"
                    class="min-h-12 flex-1 rounded-2xl text-sm font-semibold"
                    :class="mode === 'range' ? 'bg-beer text-night' : 'bg-night text-foam/80'"
                    @click="mode = 'range'"
                >
                    {{ t('variables.amountRange') }}
                </button>
            </div>

            <label v-if="mode === 'fixed'" class="flex flex-col gap-1.5">
                <span class="text-sm text-foam/80">{{ t('variables.amountValue') }}</span>
                <input
                    v-model.number="value"
                    type="number"
                    inputmode="numeric"
                    min="1"
                    max="100"
                    class="min-h-12 rounded-xl bg-night px-4 text-base text-foam"
                />
            </label>

            <div v-else class="flex gap-3">
                <label class="flex flex-1 flex-col gap-1.5">
                    <span class="text-sm text-foam/80">{{ t('variables.amountMin') }}</span>
                    <input
                        v-model.number="min"
                        type="number"
                        inputmode="numeric"
                        min="1"
                        max="100"
                        class="min-h-12 rounded-xl bg-night px-4 text-base text-foam"
                    />
                </label>
                <label class="flex flex-1 flex-col gap-1.5">
                    <span class="text-sm text-foam/80">{{ t('variables.amountMax') }}</span>
                    <input
                        v-model.number="max"
                        type="number"
                        inputmode="numeric"
                        min="1"
                        max="100"
                        class="min-h-12 rounded-xl bg-night px-4 text-base text-foam"
                    />
                </label>
            </div>

            <div class="flex flex-col gap-2">
                <PrimaryButton @click="apply">{{ t('common.save') }}</PrimaryButton>
                <PrimaryButton variant="ghost" @click="emit('close')">
                    {{ t('common.cancel') }}
                </PrimaryButton>
            </div>
        </div>
    </div>
</template>
