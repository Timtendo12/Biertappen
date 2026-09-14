<script setup lang="ts">
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppShell from '@/components/layout/AppShell.vue';
import PrimaryButton from '@/components/ui/PrimaryButton.vue';
import TextField from '@/components/ui/TextField.vue';

interface DeckRow {
    uuid: string;
    name: string;
    description: string;
    card_count: number;
    visibility: string;
    status: string;
    share_url: string | null;
    ending: { mode: string; count: number | null };
}

defineProps<{ decks: DeckRow[] }>();

const { t } = useI18n();

const creating = ref(false);
const importing = ref(false);

const form = useForm({ name: '', ending_mode: 'cards', ending_count: 30 });
const importForm = useForm<{ file: File | null }>({ file: null });

function create(): void {
    form.post('/creator/decks', { onSuccess: () => (creating.value = false) });
}

function submitImport(): void {
    importForm.post('/creator/import', { forceFormData: true });
}
</script>

<template>
    <Head :title="t('creator.title')" />

    <AppShell>
        <main class="mx-auto flex w-full max-w-md flex-1 flex-col gap-5 px-5 py-8">
            <header class="flex items-baseline justify-between gap-3">
                <h1 class="text-2xl font-bold">{{ t('creator.title') }}</h1>
                <button type="button" class="text-sm text-foam/60 underline" @click="router.visit('/')">
                    {{ t('common.back') }}
                </button>
            </header>

            <div class="flex gap-2">
                <PrimaryButton class="flex-1" @click="creating = !creating">
                    {{ t('creator.newDeck') }}
                </PrimaryButton>
                <PrimaryButton variant="ghost" @click="importing = !importing">
                    {{ t('creator.importDeck') }}
                </PrimaryButton>
            </div>

            <form v-if="creating" class="flex flex-col gap-3 rounded-2xl bg-night-soft p-4" @submit.prevent="create">
                <TextField v-model="form.name" :label="t('creator.deckName')" :error="form.errors.name" required />
                <PrimaryButton type="submit" :loading="form.processing">{{ t('common.save') }}</PrimaryButton>
            </form>

            <form
                v-if="importing"
                class="flex flex-col gap-3 rounded-2xl bg-night-soft p-4"
                @submit.prevent="submitImport"
            >
                <label class="flex flex-col gap-1.5">
                    <span class="text-sm font-medium text-foam/80">{{ t('creator.importDeck') }}</span>
                    <input
                        type="file"
                        accept="application/json,.json"
                        class="text-sm text-foam/80"
                        @change="importForm.file = ($event.target as HTMLInputElement).files?.[0] ?? null"
                    />
                </label>
                <p v-if="importForm.errors.file" role="alert" class="text-sm text-danger">
                    {{ importForm.errors.file }}
                </p>
                <PrimaryButton type="submit" :loading="importForm.processing" :disabled="!importForm.file">
                    {{ t('creator.importDeck') }}
                </PrimaryButton>
            </form>

            <p v-if="decks.length === 0" class="text-sm text-foam/60">{{ t('creator.empty') }}</p>

            <ul v-else class="flex flex-col gap-3">
                <li v-for="deck in decks" :key="deck.uuid">
                    <button
                        type="button"
                        class="flex w-full flex-col gap-1 rounded-2xl bg-night-soft p-4 text-left"
                        @click="router.visit(`/creator/decks/${deck.uuid}`)"
                    >
                        <span class="flex items-center gap-2">
                            <span class="text-lg font-bold">{{ deck.name }}</span>
                            <span
                                v-if="deck.share_url"
                                class="rounded-full bg-accent/25 px-2 py-0.5 text-[0.65rem] font-bold uppercase tracking-wide text-accent"
                            >
                                {{ t('decks.shared') }}
                            </span>
                        </span>
                        <span class="text-sm text-foam/70">{{ deck.description }}</span>
                        <span class="text-xs text-foam/50">{{ t('decks.cardCount', deck.card_count) }}</span>
                    </button>
                </li>
            </ul>
        </main>
    </AppShell>
</template>
