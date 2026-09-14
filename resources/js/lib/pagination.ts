/**
 * Renders a Laravel pagination label as plain text.
 *
 * Laravel emits labels containing HTML entities ("&laquo; Previous"), which is
 * why the obvious binding is v-html. Doing that in an admin panel means one
 * future label carrying user data becomes stored XSS, so the entities are
 * translated here and the label is bound as text instead.
 */
const ENTITIES: Record<string, string> = {
    '&laquo;': '‹',
    '&raquo;': '›',
    '&lsaquo;': '‹',
    '&rsaquo;': '›',
    '&amp;': '&',
    '&nbsp;': ' ',
};

export function paginationLabel(label: string): string {
    return Object.entries(ENTITIES)
        .reduce((text, [entity, char]) => text.split(entity).join(char), label)
        // Anything else that looks like markup is dropped rather than rendered.
        .replace(/<[^>]*>/g, '')
        .trim();
}
