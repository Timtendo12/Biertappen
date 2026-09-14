<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/components/layout/AdminLayout.vue';
import { paginationLabel } from '@/lib/pagination';

interface ReportRow {
    id: number;
    reason: string;
    description: string | null;
    status: string;
    created_at: string | null;
    reporter: string | null;
    deck: { uuid: string; name: string; status: string; card_count: number } | null;
}

interface Paginated<T> {
    data: T[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

defineProps<{ reports: Paginated<ReportRow>; status: string }>();

const { t } = useI18n();

const FILTERS = ['open', 'reviewed', 'actioned', 'dismissed'] as const;

const OUTCOMES = ['reviewed', 'actioned', 'dismissed'] as const;

function resolve(report: ReportRow, status: string): void {
    router.put(`/admin/reports/${report.id}`, { status }, { preserveScroll: true });
}
</script>

<template>
    <Head :title="t('admin.reports')" />

    <AdminLayout :title="t('admin.reports')">
        <nav class="flex flex-wrap gap-2" :aria-label="t('admin.reports')">
            <button
                v-for="filter in FILTERS"
                :key="filter"
                type="button"
                class="min-h-11 rounded-xl px-3 text-sm font-semibold"
                :class="status === filter ? 'bg-beer text-night' : 'bg-night-soft text-foam/80'"
                @click="router.get('/admin/reports', { status: filter }, { preserveState: true })"
            >
                {{ t(`admin.reportStatus.${filter}`) }}
            </button>
        </nav>

        <p v-if="reports.data.length === 0" class="text-sm text-foam/60">{{ t('admin.noReports') }}</p>

        <ul class="flex flex-col gap-3">
            <li v-for="report in reports.data" :key="report.id" class="flex flex-col gap-3 rounded-2xl bg-night-soft p-4">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <span class="font-bold">{{ t(`admin.reasons.${report.reason}`) }}</span>
                    <span class="text-xs text-foam/50">{{ report.created_at }}</span>
                </div>

                <p v-if="report.description" class="rounded-xl bg-night p-3 text-sm text-foam/80">
                    {{ report.description }}
                </p>

                <p class="text-sm text-foam/60">
                    <template v-if="report.deck">
                        {{ report.deck.name }} · {{ t('decks.cardCount', report.deck.card_count) }}
                    </template>
                    <template v-else>{{ t('decks.notFound') }}</template>
                    <template v-if="report.reporter"> · {{ report.reporter }}</template>
                </p>

                <div class="flex flex-wrap gap-2 text-sm">
                    <button
                        v-if="report.deck"
                        type="button"
                        class="min-h-11 rounded-xl bg-night px-3 font-semibold"
                        @click="router.visit(`/creator/decks/${report.deck.uuid}`)"
                    >
                        {{ t('admin.viewDeck') }}
                    </button>

                    <!-- Hiding the deck is the moderation action; resolving the
                         report only records the decision. They are separate on
                         purpose, so neither happens by accident. -->
                    <button
                        v-if="report.deck && report.deck.status !== 'hidden'"
                        type="button"
                        class="min-h-11 rounded-xl bg-night px-3 text-danger"
                        @click="router.post(`/admin/decks/${report.deck.uuid}/hide`, {}, { preserveScroll: true })"
                    >
                        {{ t('admin.hide') }}
                    </button>

                    <button
                        v-for="outcome in OUTCOMES"
                        :key="outcome"
                        type="button"
                        class="min-h-11 rounded-xl bg-night px-3"
                        :disabled="report.status === outcome"
                        @click="resolve(report, outcome)"
                    >
                        {{ t(`admin.reportStatus.${outcome}`) }}
                    </button>
                </div>
            </li>
        </ul>

        <nav v-if="reports.links.length > 3" class="flex flex-wrap gap-1" :aria-label="t('admin.reports')">
            <button
                v-for="link in reports.links"
                :key="link.label"
                type="button"
                :disabled="!link.url"
                class="min-h-10 rounded-lg px-3 text-sm disabled:opacity-30"
                :class="link.active ? 'bg-beer text-night font-bold' : 'bg-night-soft'"
                @click="link.url && router.visit(link.url, { preserveState: true })"
            >
                {{ paginationLabel(link.label) }}
            </button>
        </nav>
    </AdminLayout>
</template>
