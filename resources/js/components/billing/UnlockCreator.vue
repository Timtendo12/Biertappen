<script setup lang="ts">
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { router, usePage } from '@inertiajs/vue3';
import PrimaryButton from '@/components/ui/PrimaryButton.vue';
import { startCheckout } from '@/lib/checkout';
import type { SharedProps } from '@/types/inertia';

const { t } = useI18n();
const page = usePage<SharedProps>();

const busy = ref(false);
const error = ref<string | null>(null);

/**
 * Whether to show this at all comes from the server on every request, never from
 * anything remembered client-side. Hiding the button is presentation; the
 * entitlement middleware is what actually denies access.
 */
async function unlock(): Promise<void> {
    if (!page.props.auth.user) {
        router.visit('/login');

        return;
    }

    busy.value = true;
    error.value = null;

    const failure = await startCheckout('/billing/checkout');

    if (failure === 'already_owned') {
        // The webhook landed while they were looking at this. Reload so the
        // shared props reflect it.
        router.reload();
    } else if (failure) {
        error.value = t('premium.failed');
    }

    busy.value = false;
}
</script>

<template>
    <section class="flex flex-col gap-3 rounded-2xl bg-night-soft p-5">
        <h2 class="text-lg font-bold">{{ t('premium.unlock') }}</h2>
        <p class="text-sm text-foam/70">{{ t('premium.oneTime') }}</p>

        <p v-if="error" role="alert" class="text-sm text-danger">{{ error }}</p>

        <PrimaryButton :loading="busy" @click="unlock">
            {{ t('premium.unlock') }}
        </PrimaryButton>
    </section>
</template>
