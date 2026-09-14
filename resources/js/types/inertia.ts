import type { Page, PageProps } from '@inertiajs/core';
import type { AppLocale } from '../i18n';

export interface AuthUser {
    id: number;
    name: string;
    email: string;
    locale: AppLocale;
    is_admin: boolean;
    has_deck_creator: boolean;
}

export interface SharedProps extends PageProps {
    auth: { user: AuthUser | null };
    locale: AppLocale;
    flash: { success: string | null; error: string | null };
}

export type AppPage = Page<SharedProps>;
