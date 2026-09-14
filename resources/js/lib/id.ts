/**
 * Stable unique ids for players. Two people called "Tim" must stay distinct
 * everywhere the engine touches them, so identity never rides on the name.
 */
export function uid(): string {
    if (typeof crypto !== 'undefined' && 'randomUUID' in crypto) {
        return crypto.randomUUID();
    }

    return `id-${Math.random().toString(36).slice(2)}-${Date.now().toString(36)}`;
}
