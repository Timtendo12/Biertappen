<script setup lang="ts">
import { ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/components/layout/AdminLayout.vue';
import { paginationLabel } from '@/lib/pagination';

interface AdminUser {
    id: number;
    name: string;
    email: string;
    role: 'user' | 'admin';
    verified: boolean;
    deck_count: number;
    has_deck_creator: boolean;
    created_at: string | null;
}

interface Paginated<T> {
    data: T[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

const props = defineProps<{ users: Paginated<AdminUser>; search: string }>();

const { t } = useI18n();

const search = ref(props.search);
let timer: number | undefined;

// Debounced so typing a name does not fire a request per keystroke.
watch(search, (value) => {
    window.clearTimeout(timer);
    timer = window.setTimeout(() => {
        router.get('/admin/users', { search: value }, { preserveState: true, replace: true });
    }, 300);
});

function toggleEntitlement(user: AdminUser): void {
    const url = `/admin/users/${user.id}/entitlement`;

    if (user.has_deck_creator) {
        router.delete(url, { preserveScroll: true });
    } else {
        router.post(url, {}, { preserveScroll: true });
    }
}

function toggleRole(user: AdminUser): void {
    router.put(
        `/admin/users/${user.id}/role`,
        { role: user.role === 'admin' ? 'user' : 'admin' },
        { preserveScroll: true },
    );
}
</script>

<template>
    <Head :title="t('admin.users')" />

    <AdminLayout :title="t('admin.users')">
        <label class="flex flex-col gap-1.5">
            <span class="text-sm font-medium text-foam/80">{{ t('admin.searchUsers') }}</span>
            <input
                v-model="search"
                type="search"
                class="min-h-12 rounded-xl bg-night-soft px-4 text-base text-foam"
            />
        </label>

        <ul class="flex flex-col gap-3">
            <li v-for="user in users.data" :key="user.id" class="flex flex-col gap-3 rounded-2xl bg-night-soft p-4">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="font-semibold">{{ user.name }}</span>
                    <span
                        v-if="user.role === 'admin'"
                        class="rounded-full bg-accent/25 px-2 py-0.5 text-[0.65rem] font-bold uppercase tracking-wide text-accent"
                    >
                        {{ t('admin.roleAdmin') }}
                    </span>
                    <span
                        v-if="user.has_deck_creator"
                        class="rounded-full bg-beer/20 px-2 py-0.5 text-[0.65rem] font-bold uppercase tracking-wide text-beer"
                    >
                        {{ t('admin.hasCreator') }}
                    </span>
                    <span v-if="!user.verified" class="text-xs text-foam/50">{{ t('admin.unverified') }}</span>
                </div>

                <p class="text-sm text-foam/60">
                    {{ user.email }} · {{ t('decks.cardCount', user.deck_count) }}
                </p>

                <div class="flex flex-wrap gap-2 text-sm">
                    <button
                        type="button"
                        class="min-h-11 rounded-xl bg-night px-3 font-semibold"
                        @click="toggleEntitlement(user)"
                    >
                        {{ user.has_deck_creator ? t('admin.revokeCreator') : t('admin.grantCreator') }}
                    </button>
                    <button type="button" class="min-h-11 rounded-xl bg-night px-3" @click="toggleRole(user)">
                        {{ user.role === 'admin' ? t('admin.makeUser') : t('admin.makeAdmin') }}
                    </button>
                </div>
            </li>
        </ul>

        <nav v-if="users.links.length > 3" class="flex flex-wrap gap-1" :aria-label="t('admin.users')">
            <button
                v-for="link in users.links"
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
