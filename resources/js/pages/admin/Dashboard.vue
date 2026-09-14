<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/components/layout/AdminLayout.vue';

interface Stats {
    users: number;
    admins: number;
    base_decks: number;
    published_base_decks: number;
    user_decks: number;
    shared_decks: number;
    cards: number;
    active_entitlements: number;
    open_reports: number;
    donations: number;
    donated_cents: number;
}

const props = defineProps<{ stats: Stats }>();

const { t } = useI18n();

const tiles = computed(() => [
    { key: 'admin.statUsers', value: String(props.stats.users) },
    { key: 'admin.statBaseDecks', value: `${props.stats.published_base_decks}/${props.stats.base_decks}` },
    { key: 'admin.statUserDecks', value: String(props.stats.user_decks) },
    { key: 'admin.statShared', value: String(props.stats.shared_decks) },
    { key: 'admin.statCards', value: String(props.stats.cards) },
    { key: 'admin.statEntitlements', value: String(props.stats.active_entitlements) },
    { key: 'admin.statDonations', value: `€${(props.stats.donated_cents / 100).toFixed(2)}` },
]);
</script>

<template>
    <Head :title="t('admin.title')" />

    <AdminLayout :title="t('admin.title')">
        <!-- Open reports lead, because they are the only number that ever needs
             acting on today. -->
        <Link
            href="/admin/reports"
            class="flex items-center justify-between rounded-2xl p-4"
            :class="stats.open_reports > 0 ? 'bg-danger/20 text-danger' : 'bg-night-soft text-foam/70'"
        >
            <span class="font-semibold">{{ t('admin.openReports') }}</span>
            <span class="text-2xl font-black tabular-nums">{{ stats.open_reports }}</span>
        </Link>

        <dl class="grid grid-cols-2 gap-3 sm:grid-cols-3">
            <div v-for="tile in tiles" :key="tile.key" class="rounded-2xl bg-night-soft p-4">
                <dt class="text-xs uppercase tracking-wide text-foam/50">{{ t(tile.key) }}</dt>
                <dd class="mt-1 text-2xl font-black tabular-nums">{{ tile.value }}</dd>
            </div>
        </dl>
    </AdminLayout>
</template>
