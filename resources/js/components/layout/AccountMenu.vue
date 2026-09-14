<script setup lang="ts">
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Link, router, usePage } from '@inertiajs/vue3';
import type { SharedProps } from '@/types/inertia';

const { t } = useI18n();
const page = usePage<SharedProps>();

const user = computed(() => page.props.auth.user);

/**
 * What this offers is decided by the server on every request. The entitlement
 * middleware is what actually protects the creator; this only decides which
 * link is worth showing.
 */
const canCreate = computed(() => user.value?.has_deck_creator || user.value?.is_admin);
</script>

<template>
    <nav class="flex flex-wrap items-center justify-center gap-x-4 gap-y-2 text-sm">
        <template v-if="user">
            <Link v-if="canCreate" href="/creator" class="font-semibold text-beer underline">
                {{ t('creator.title') }}
            </Link>
            <Link v-else href="/account" class="text-foam/70 underline">
                {{ t('premium.unlock') }}
            </Link>

            <Link v-if="user.is_admin" href="/admin" class="text-foam/70 underline">
                {{ t('admin.title') }}
            </Link>

            <button type="button" class="text-foam/60 underline" @click="router.post('/logout')">
                {{ t('auth.logout') }}
            </button>
        </template>

        <Link v-else href="/login" class="text-foam/70 underline">{{ t('auth.login') }}</Link>
    </nav>
</template>
