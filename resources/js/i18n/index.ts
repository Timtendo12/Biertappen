import { createI18n } from 'vue-i18n';
import nl from './nl';

/**
 * Dutch is the source of truth for the message shape.
 *
 * Recursive so nested groups (admin.status.draft) are typed to the same depth
 * they are written — a missing or renamed key in another locale is a compile
 * error rather than a blank string at runtime.
 */
type DeepStrings<T> = { [K in keyof T]: T[K] extends string ? string : DeepStrings<T[K]> };

export type MessageSchema = DeepStrings<typeof nl>;

export const SUPPORTED_LOCALES = ['nl', 'en'] as const;

export type AppLocale = (typeof SUPPORTED_LOCALES)[number];

export const DEFAULT_LOCALE: AppLocale = 'nl';

export function isSupportedLocale(value: unknown): value is AppLocale {
    return typeof value === 'string' && (SUPPORTED_LOCALES as readonly string[]).includes(value);
}

/**
 * English is loaded on demand: a Dutch-speaking player never downloads it.
 */
const loaded = new Set<AppLocale>(['nl']);

// The explicit `false` selects Composition-mode typings; without it the
// legacy shape is inferred and `locale` is typed as a plain string.
export const i18n = createI18n<[MessageSchema], AppLocale, false>({
    legacy: false,
    globalInjection: true,
    locale: DEFAULT_LOCALE,
    fallbackLocale: DEFAULT_LOCALE,
    messages: { nl } as unknown as Record<AppLocale, MessageSchema>,
});

/** Locales that ship in their own chunk. Dutch is bundled with the app. */
const lazyLocales: Record<Exclude<AppLocale, 'nl'>, () => Promise<{ default: MessageSchema }>> = {
    en: () => import('./en'),
};

export async function setLocale(locale: AppLocale): Promise<void> {
    if (!loaded.has(locale)) {
        const load = lazyLocales[locale as Exclude<AppLocale, 'nl'>];
        if (load) {
            const messages = await load();
            i18n.global.setLocaleMessage(locale, messages.default);
        }
        loaded.add(locale);
    }

    i18n.global.locale.value = locale;
    document.documentElement.setAttribute('lang', locale);
}
