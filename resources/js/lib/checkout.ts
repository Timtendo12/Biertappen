/**
 * Opens a Lemon Squeezy checkout.
 *
 * Prefers the overlay provided by lemon.js so the purchase happens over the app.
 * Inside an installed PWA a plain redirect can escape the standalone window into
 * a browser tab, and the user does not reliably find their way back.
 *
 * The script can be blocked or still loading, so a redirect is kept as the
 * fallback — a checkout that never opens is a worse failure than one that
 * navigates.
 */

interface LemonSqueezyGlobal {
    Url?: { Open?: (url: string) => void };
    Setup?: (options: { eventHandler?: (event: { event: string }) => void }) => void;
}

declare global {
    interface Window {
        LemonSqueezy?: LemonSqueezyGlobal;
        createLemonSqueezy?: () => void;
    }
}

export function openCheckout(url: string): void {
    const overlay = window.LemonSqueezy?.Url?.Open;

    if (typeof overlay === 'function') {
        try {
            overlay(url);

            return;
        } catch {
            // Fall through to the redirect below.
        }
    }

    window.location.href = url;
}

export type CheckoutError = 'already_owned' | 'unauthenticated' | 'failed';

/**
 * Asks the server for a checkout URL and opens it.
 *
 * The server decides the price; the client only ever states which product, or a
 * whole-euro donation amount that the server bounds.
 */
export async function startCheckout(
    endpoint: '/billing/checkout' | '/billing/donate',
    body: Record<string, unknown> = {},
): Promise<CheckoutError | null> {
    const token = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';

    try {
        const response = await fetch(endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': token,
            },
            body: JSON.stringify(body),
        });

        if (response.status === 401 || response.status === 419) {
            return 'unauthenticated';
        }

        if (response.status === 409) {
            return 'already_owned';
        }

        if (!response.ok) {
            return 'failed';
        }

        const { url } = (await response.json()) as { url?: string };

        if (!url) {
            return 'failed';
        }

        openCheckout(url);

        return null;
    } catch {
        return 'failed';
    }
}
