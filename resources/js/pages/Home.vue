<script setup lang="ts">
import { nextTick, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { Head, Link, router } from '@inertiajs/vue3';
import AppShell from '@/components/layout/AppShell.vue';
import { MIN_PLAYERS, usePlayersStore } from '@/stores/players';
import DonateSheet from '@/components/billing/DonateSheet.vue';
import AccountMenu from '@/components/layout/AccountMenu.vue';

const { t } = useI18n();
const store = usePlayersStore();

const inputs = ref<HTMLInputElement[]>([]);

async function addPlayer(): Promise<void> {
    const player = store.add();
    if (!player) return;

    // Adding a row is only useful if you can immediately type in it.
    await nextTick();
    inputs.value[store.players.length - 1]?.focus();
}

function start(): void {
    if (!store.canStart) return;
    router.visit('/decks');
}
</script>

<template>
    <Head :title="t('common.appName')" />

    <AppShell>
        <main class="mx-auto flex w-full max-w-md flex-1 flex-col gap-6 px-5 py-8">
            <header class="relative text-center">
                <h1 class="text-4xl font-black tracking-tight text-beer">
                    {{ t('common.appName') }}
                </h1>
                <p class="mt-1 text-sm text-foam/70">{{ t('home.tagline') }}</p>

                <Link
                    href="/settings"
                    class="absolute top-0 right-0 grid size-11 place-items-center rounded-xl text-foam/60"
                    :aria-label="t('settings.title')"
                >
                    <span aria-hidden="true" class="text-xl">&#9881;</span>
                </Link>
            </header>

            <section aria-labelledby="players-heading" class="flex flex-col gap-3">
                <h2 id="players-heading" class="text-sm font-semibold uppercase tracking-wide text-foam/60">
                    {{ t('home.players') }}
                </h2>

                <ul class="flex flex-col gap-2">
                    <li v-for="(player, index) in store.players" :key="player.id" class="flex items-center gap-2">
                        <input
                            :ref="(el) => { if (el) inputs[index] = el as HTMLInputElement }"
                            :value="player.name"
                            :aria-label="`${t('home.playerName')} ${index + 1}`"
                            :placeholder="t('home.playerPlaceholder')"
                            type="text"
                            autocomplete="off"
                            maxlength="24"
                            enterkeyhint="next"
                            class="min-h-12 flex-1 rounded-xl bg-night-soft px-4 text-base text-foam placeholder:text-foam/35"
                            @input="store.rename(player.id, ($event.target as HTMLInputElement).value)"
                            @keydown.enter.prevent="addPlayer"
                        />
                        <button
                            type="button"
                            class="grid size-12 shrink-0 place-items-center rounded-xl bg-night-soft text-foam/60"
                            :aria-label="t('home.removePlayer', { name: player.name || index + 1 })"
                            @click="store.remove(player.id)"
                        >
                            <span aria-hidden="true">✕</span>
                        </button>
                    </li>
                </ul>

                <button
                    type="button"
                    class="min-h-12 rounded-xl border border-dashed border-foam/25 text-sm font-medium text-foam/70"
                    @click="addPlayer"
                >
                    + {{ t('home.addPlayer') }}
                </button>
            </section>

            <div class="mt-auto flex flex-col gap-2">
                <!--
                    The button stays enabled and explains itself; a disabled control
                    with no reason is the more frustrating failure on mobile.
                -->
                <p v-if="!store.canStart" class="text-center text-sm text-foam/60">
                    {{ t('home.minPlayers', { count: MIN_PLAYERS }) }}
                </p>
                <button
                    type="button"
                    class="min-h-14 rounded-2xl bg-beer text-lg font-bold text-night disabled:opacity-40"
                    :disabled="!store.canStart"
                    @click="start"
                >
                    {{ t('home.start') }}
                </button>

                <DonateSheet />

                <AccountMenu />
            </div>
        </main>
    </AppShell>
</template>
