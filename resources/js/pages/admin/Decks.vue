<script setup lang="ts">
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { Head, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/components/layout/AdminLayout.vue';
import PrimaryButton from '@/components/ui/PrimaryButton.vue';
import TextField from '@/components/ui/TextField.vue';

interface AdminDeck {
    uuid: string;
    name: string;
    description: string;
    card_count: number;
    open_reports: number;
    status: 'draft' | 'published' | 'hidden';
    visibility: string;
    featured: boolean;
    is_base_game: boolean;
    owner: string | null;
}

defineProps<{ decks: AdminDeck[] }>();

const { t } = useI18n();

const creating = ref(false);
const form = useForm({ name: '', ending_mode: 'cards', ending_count: 30 });
const importForm = useForm<{ file: File | null }>({ file: null });

const STATUS_STYLES: Record<string, string> = {
    published: 'bg-success/20 text-success',
    draft: 'bg-foam/15 text-foam/70',
    hidden: 'bg-danger/20 text-danger',
};

function post(deck: AdminDeck, action: string): void {
    router.post(`/admin/decks/${deck.uuid}/${action}`, {}, { preserveScroll: true });
}
</script>

<template>
    <Head :title="t('admin.decks')" />

    <AdminLayout :title="t('admin.decks')">
        <div class="flex flex-wrap gap-2">
            <PrimaryButton @click="creating = !creating">{{ t('admin.newBaseDeck') }}</PrimaryButton>
            <label class="min-h-14 cursor-pointer rounded-2xl border border-foam/20 px-5 py-4 text-sm font-bold">
                {{ t('creator.importDeck') }}
                <input
                    type="file"
                    accept="application/json,.json"
                    class="sr-only"
                    @change="
                        importForm.file = ($event.target as HTMLInputElement).files?.[0] ?? null;
                        importForm.post('/admin/decks/import', { forceFormData: true });
                    "
                />
            </label>
        </div>

        <p v-if="importForm.errors.file" role="alert" class="text-sm text-danger">
            {{ importForm.errors.file }}
        </p>

        <form
            v-if="creating"
            class="flex flex-col gap-3 rounded-2xl bg-night-soft p-4"
            @submit.prevent="form.post('/admin/decks')"
        >
            <TextField v-model="form.name" :label="t('creator.deckName')" :error="form.errors.name" required />
            <PrimaryButton type="submit" :loading="form.processing">{{ t('common.save') }}</PrimaryButton>
        </form>

        <ul class="flex flex-col gap-3">
            <li v-for="deck in decks" :key="deck.uuid" class="flex flex-col gap-3 rounded-2xl bg-night-soft p-4">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-lg font-bold">{{ deck.name }}</span>

                    <!-- Status is a word, not just a colour. -->
                    <span
                        class="rounded-full px-2 py-0.5 text-[0.65rem] font-bold uppercase tracking-wide"
                        :class="STATUS_STYLES[deck.status]"
                    >
                        {{ t(`admin.status.${deck.status}`) }}
                    </span>

                    <span
                        v-if="deck.is_base_game"
                        class="rounded-full bg-beer/20 px-2 py-0.5 text-[0.65rem] font-bold uppercase tracking-wide text-beer"
                    >
                        {{ t('decks.baseGame') }}
                    </span>

                    <span v-if="deck.featured" aria-label="featured" class="text-beer">★</span>

                    <span
                        v-if="deck.open_reports > 0"
                        class="rounded-full bg-danger/20 px-2 py-0.5 text-[0.65rem] font-bold text-danger"
                    >
                        {{ deck.open_reports }} ⚑
                    </span>
                </div>

                <p class="text-sm text-foam/60">
                    {{ t('decks.cardCount', deck.card_count) }}
                    <template v-if="deck.owner"> · {{ deck.owner }}</template>
                </p>

                <div class="flex flex-wrap gap-2 text-sm">
                    <button
                        type="button"
                        class="min-h-11 rounded-xl bg-night px-3 font-semibold"
                        @click="router.visit(`/creator/decks/${deck.uuid}`)"
                    >
                        {{ t('common.edit') }}
                    </button>
                    <button
                        v-if="deck.status !== 'published'"
                        type="button"
                        class="min-h-11 rounded-xl bg-night px-3"
                        @click="post(deck, 'publish')"
                    >
                        {{ t('admin.publish') }}
                    </button>
                    <button
                        v-else
                        type="button"
                        class="min-h-11 rounded-xl bg-night px-3"
                        @click="post(deck, 'unpublish')"
                    >
                        {{ t('admin.unpublish') }}
                    </button>
                    <button type="button" class="min-h-11 rounded-xl bg-night px-3" @click="post(deck, 'feature')">
                        {{ deck.featured ? t('admin.unfeature') : t('admin.feature') }}
                    </button>
                    <button
                        type="button"
                        class="min-h-11 rounded-xl bg-night px-3 text-danger"
                        @click="post(deck, 'hide')"
                    >
                        {{ t('admin.hide') }}
                    </button>
                </div>
            </li>
        </ul>
    </AdminLayout>
</template>
