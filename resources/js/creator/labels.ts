import { labelKey, playerIndex } from '@/game';
import type { VariableId } from '@/game';
import { i18n } from '@/i18n';

/**
 * Human label for a variable chip.
 *
 * Reads the label key from the shared registry rather than a second list in the
 * UI, so adding a variable to variables.json is all it takes for the editor to
 * offer it with a proper name.
 */
export function variableLabel(variable: VariableId): string {
    const key = labelKey(variable);

    if (!key) {
        return variable;
    }

    const index = playerIndex(variable);
    const t = i18n.global.t;

    return index === null ? t(key) : t(key, { index });
}
