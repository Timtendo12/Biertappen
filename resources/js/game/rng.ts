/**
 * Seedable pseudo-random number generator (mulberry32).
 *
 * Gameplay never passes a seed, so real games are unpredictable. Tests always
 * pass one, which turns "cards are shuffled" and "two different players are
 * chosen" from statistical hand-waving into exact, repeatable assertions.
 */
export interface Rng {
    /** Float in [0, 1). */
    next(): number;
    /** Integer in [min, max], inclusive at both ends. */
    int(min: number, max: number): number;
    /** Fisher-Yates on a copy; the input array is never mutated. */
    shuffle<T>(items: readonly T[]): T[];
    /** Draws `count` distinct items. Throws if the pool is too small. */
    sample<T>(items: readonly T[], count: number): T[];
}

export function createRng(seed?: number): Rng {
    let state = (seed ?? Math.floor(Math.random() * 0xffffffff)) >>> 0;

    const next = (): number => {
        state = (state + 0x6d2b79f5) >>> 0;
        let t = state;
        t = Math.imul(t ^ (t >>> 15), t | 1);
        t ^= t + Math.imul(t ^ (t >>> 7), t | 61);
        return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
    };

    const int = (min: number, max: number): number => {
        if (max < min) {
            [min, max] = [max, min];
        }
        return min + Math.floor(next() * (max - min + 1));
    };

    const shuffle = <T>(items: readonly T[]): T[] => {
        const result = [...items];
        for (let i = result.length - 1; i > 0; i--) {
            const j = Math.floor(next() * (i + 1));
            const a = result[i] as T;
            const b = result[j] as T;
            result[i] = b;
            result[j] = a;
        }
        return result;
    };

    const sample = <T>(items: readonly T[], count: number): T[] => {
        if (count > items.length) {
            throw new RangeError(`Cannot draw ${count} items from a pool of ${items.length}.`);
        }
        return shuffle(items).slice(0, count);
    };

    return { next, int, shuffle, sample };
}
