<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import PrimaryButton from '@/components/ui/PrimaryButton.vue';
import CardContentEditor from './CardContentEditor.vue';
import CardPreview from './CardPreview.vue';
import { MAX_PLAYER_VARIABLES, type Participants, type Segment } from '@/game';

export interface CardDraft {
    id?: number;
    type: string;
    participants: Participants;
    content: Segment[];
}

const props = defineProps<{ card?: CardDraft | null; saving?: boolean; errors?: string[] }>();

const emit = defineEmits<{ save: [card: CardDraft, again: boolean]; cancel: [] }>();

const { t } = useI18n();

const CARD_TYPES = ['challenge', 'truth', 'dare', 'vote', 'drinking', 'custom'] as const;

const PARTICIPANT_CHOICES: Participants[] = [1, 2, 3, 4, 'all'];

const step = ref(props.card ? 3 : 1);
const participants = ref<Participants>(props.card?.participants ?? 1);
const type = ref<string>(props.card?.type ?? 'drinking');
const content = ref<Segment[]>(props.card ? [...props.card.content] : []);

const isEditing = computed(() => Boolean(props.card?.id));

const hasContent = computed(() =>
    content.value.some((s) => (s.type === 'text' ? s.value.trim() !== '' : true)),
);

/**
 * Narrowing the participant count can strip variables the card still uses, and
 * a card referencing player3 with 2 participants is rejected by the server. So
 * those chips are removed here, as the choice is made, rather than surfacing a
 * validation error later.
 */
watch(participants, (next) => {
    const limit = next === 'all' ? 0 : Math.min(next, MAX_PLAYER_VARIABLES);

    content.value = content.value.filter((segment) => {
        if (segment.type !== 'variable') return true;

        if (segment.value === 'all_players') return next === 'all';
        if (segment.value === 'amount') return true;

        const index = Number(segment.value.replace('player', ''));

        return Number.isFinite(index) && index <= limit;
    });
});

function draft(): CardDraft {
    return {
        ...(props.card?.id ? { id: props.card.id } : {}),
        type: type.value,
        participants: participants.value,
        content: content.value,
    };
}

function participantLabel(choice: Participants): string {
    return choice === 'all' ? t('creator.everyone') : String(choice);
}
</script>

<template>
    <div class="flex flex-col gap-5">
        <!-- Step indicator: text as well as position, so progress is not conveyed
             by layout alone. -->
        <p class="text-xs font-semibold uppercase tracking-wide text-foam/50">
            {{ t('creator.step', { current: step, total: 4 }) }}
        </p>

        <!-- Step 1 — how many people are involved -->
        <section v-if="step === 1" class="flex flex-col gap-4" aria-labelledby="step-participants">
            <h2 id="step-participants" class="text-xl font-bold">{{ t('creator.participantsQuestion') }}</h2>

            <div class="grid grid-cols-5 gap-2" role="radiogroup" :aria-label="t('creator.participantsQuestion')">
                <button
                    v-for="choice in PARTICIPANT_CHOICES"
                    :key="String(choice)"
                    type="button"
                    role="radio"
                    :aria-checked="participants === choice"
                    class="min-h-14 rounded-2xl text-base font-bold"
                    :class="participants === choice ? 'bg-beer text-night' : 'bg-night-soft text-foam/80'"
                    @click="participants = choice"
                >
                    {{ participantLabel(choice) }}
                </button>
            </div>

            <PrimaryButton @click="step = 2">{{ t('common.next') }}</PrimaryButton>
        </section>

        <!-- Step 2 — card type -->
        <section v-else-if="step === 2" class="flex flex-col gap-4" aria-labelledby="step-type">
            <h2 id="step-type" class="text-xl font-bold">{{ t('creator.typeQuestion') }}</h2>

            <div class="grid grid-cols-2 gap-2" role="radiogroup" :aria-label="t('creator.typeQuestion')">
                <button
                    v-for="option in CARD_TYPES"
                    :key="option"
                    type="button"
                    role="radio"
                    :aria-checked="type === option"
                    class="min-h-14 rounded-2xl text-sm font-bold"
                    :class="type === option ? 'bg-beer text-night' : 'bg-night-soft text-foam/80'"
                    @click="type = option"
                >
                    {{ t(`cardTypes.${option}`) }}
                </button>
            </div>

            <div class="flex gap-2">
                <PrimaryButton variant="ghost" @click="step = 1">{{ t('common.back') }}</PrimaryButton>
                <PrimaryButton class="flex-1" @click="step = 3">{{ t('common.next') }}</PrimaryButton>
            </div>
        </section>

        <!-- Step 3 — the content editor -->
        <section v-else-if="step === 3" class="flex flex-col gap-4" aria-labelledby="step-content">
            <h2 id="step-content" class="text-xl font-bold">{{ t('creator.contentQuestion') }}</h2>

            <CardContentEditor v-model="content" :participants="participants" />

            <div class="flex gap-2">
                <PrimaryButton variant="ghost" @click="step = 2">{{ t('common.back') }}</PrimaryButton>
                <PrimaryButton class="flex-1" :disabled="!hasContent" @click="step = 4">
                    {{ t('common.next') }}
                </PrimaryButton>
            </div>
        </section>

        <!-- Step 4 — review -->
        <section v-else class="flex flex-col gap-4" aria-labelledby="step-review">
            <h2 id="step-review" class="text-xl font-bold">{{ t('creator.review') }}</h2>

            <CardPreview :type="type" :participants="participants" :content="content" />

            <dl class="grid grid-cols-2 gap-2 text-sm">
                <div class="rounded-xl bg-night-soft p-3">
                    <dt class="text-foam/55">{{ t('creator.cardType') }}</dt>
                    <dd class="font-semibold">{{ t(`cardTypes.${type}`) }}</dd>
                </div>
                <div class="rounded-xl bg-night-soft p-3">
                    <dt class="text-foam/55">{{ t('creator.participants') }}</dt>
                    <dd class="font-semibold">{{ participantLabel(participants) }}</dd>
                </div>
            </dl>

            <ul v-if="props.errors?.length" class="flex flex-col gap-1" role="alert">
                <li v-for="error in props.errors" :key="error" class="text-sm text-danger">{{ error }}</li>
            </ul>

            <div class="flex flex-col gap-2">
                <PrimaryButton :loading="props.saving" @click="emit('save', draft(), false)">
                    {{ isEditing ? t('common.save') : t('creator.addCard') }}
                </PrimaryButton>

                <!-- Straight back to the editor for the next card: authoring a
                     deck means writing many cards in a row. -->
                <PrimaryButton
                    v-if="!isEditing"
                    variant="ghost"
                    :loading="props.saving"
                    @click="emit('save', draft(), true)"
                >
                    {{ t('creator.addAndNext') }}
                </PrimaryButton>

                <PrimaryButton variant="ghost" @click="step = 3">{{ t('common.edit') }}</PrimaryButton>
                <PrimaryButton variant="ghost" @click="emit('cancel')">{{ t('common.cancel') }}</PrimaryButton>
            </div>
        </section>
    </div>
</template>
