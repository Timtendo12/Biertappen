<script setup lang="ts">
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { router } from '@inertiajs/vue3';
import PrimaryButton from '@/components/ui/PrimaryButton.vue';

const props = defineProps<{ deckUuid: string }>();

const { t } = useI18n();

const REASONS = ['offensive', 'illegal', 'spam', 'other'] as const;

const open = ref(false);
const reason = ref<(typeof REASONS)[number]>('offensive');
const description = ref('');
const sending = ref(false);

function submit(): void {
    sending.value = true;

    router.post(
        `/decks/${props.deckUuid}/report`,
        { reason: reason.value, description: description.value },
        {
            preserveScroll: true,
            onFinish: () => {
                sending.value = false;
                open.value = false;
            },
        },
    );
}
</script>

<template>
    <div>
        <button type="button" class="text-sm text-foam/50 underline" @click="open = true">
            {{ t('report.button') }}
        </button>

        <div
            v-if="open"
            class="fixed inset-0 z-50 flex items-end justify-center bg-night/70"
            role="dialog"
            aria-modal="true"
            :aria-label="t('report.title')"
            @click.self="open = false"
        >
            <div class="flex w-full max-w-md flex-col gap-4 rounded-t-3xl bg-night-soft p-5 pb-8">
                <h2 class="text-lg font-bold">{{ t('report.title') }}</h2>

                <div class="flex flex-col gap-2" role="radiogroup" :aria-label="t('report.title')">
                    <button
                        v-for="option in REASONS"
                        :key="option"
                        type="button"
                        role="radio"
                        :aria-checked="reason === option"
                        class="min-h-12 rounded-xl px-4 text-left text-sm font-semibold"
                        :class="reason === option ? 'bg-beer text-night' : 'bg-night text-foam/80'"
                        @click="reason = option"
                    >
                        {{ t(`admin.reasons.${option}`) }}
                    </button>
                </div>

                <label class="flex flex-col gap-1.5">
                    <span class="text-sm text-foam/80">
                        {{ t('report.details') }} ({{ t('common.optional') }})
                    </span>
                    <textarea
                        v-model="description"
                        rows="3"
                        maxlength="2000"
                        class="rounded-xl bg-night px-4 py-3 text-base text-foam"
                    />
                </label>

                <div class="flex flex-col gap-2">
                    <PrimaryButton :loading="sending" @click="submit">{{ t('report.send') }}</PrimaryButton>
                    <PrimaryButton variant="ghost" @click="open = false">{{ t('common.cancel') }}</PrimaryButton>
                </div>
            </div>
        </div>
    </div>
</template>
