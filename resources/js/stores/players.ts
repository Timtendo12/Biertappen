import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import { uid } from '@/lib/id';
import type { Player } from '@/game/types';

/** Below two players most cards simply cannot be dealt. */
export const MIN_PLAYERS = 2;

export const MAX_PLAYERS = 20;

export const usePlayersStore = defineStore('players', () => {
    const players = ref<Player[]>([
        { id: uid(), name: '' },
        { id: uid(), name: '' },
    ]);

    const named = computed(() => players.value.filter((p) => p.name.trim().length > 0));

    const canStart = computed(() => named.value.length >= MIN_PLAYERS);

    function add(): Player | null {
        if (players.value.length >= MAX_PLAYERS) {
            return null;
        }

        const player: Player = { id: uid(), name: '' };
        players.value.push(player);

        return player;
    }

    function remove(id: string): void {
        players.value = players.value.filter((p) => p.id !== id);

        // Always keep enough rows on screen to reach a startable game.
        while (players.value.length < MIN_PLAYERS) {
            players.value.push({ id: uid(), name: '' });
        }
    }

    function rename(id: string, name: string): void {
        const player = players.value.find((p) => p.id === id);
        if (player) {
            player.name = name;
        }
    }

    /** The roster handed to the engine: trimmed, named, ids intact. */
    function roster(): Player[] {
        return named.value.map((p) => ({ id: p.id, name: p.name.trim() }));
    }

    function reset(): void {
        players.value = [
            { id: uid(), name: '' },
            { id: uid(), name: '' },
        ];
    }

    return { players, named, canStart, add, remove, rename, roster, reset };
});
