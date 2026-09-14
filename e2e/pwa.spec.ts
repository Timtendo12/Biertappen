import { expect, test } from '@playwright/test';

/**
 * PWA behaviour that only a browser can confirm.
 *
 * The manifest and service worker are generated at build time, so a
 * misconfiguration produces a perfectly green test suite and an app that will
 * not install.
 */

test.describe('the web app manifest', () => {
    test('is served and describes an installable portrait app', async ({ page, request }) => {
        await page.goto('/');

        const href = await page.locator('link[rel="manifest"]').getAttribute('href');

        expect(href).toBe('/build/manifest.webmanifest');

        const manifest = await (await request.get(href!)).json();

        expect(manifest.name).toBe('Biertappen');
        expect(manifest.start_url).toBe('/');
        // Installability and the portrait-only requirement both live here.
        expect(manifest.display).toBe('standalone');
        expect(manifest.orientation).toBe('portrait');
        expect(manifest.icons.length).toBeGreaterThanOrEqual(2);
    });

    test('ships icons that actually exist, including a maskable one', async ({ page, request }) => {
        await page.goto('/');

        const href = await page.locator('link[rel="manifest"]').getAttribute('href');
        const manifest = await (await request.get(href!)).json();

        for (const icon of manifest.icons) {
            const response = await request.get(icon.src);

            expect(response.status(), `${icon.src} is referenced but missing`).toBe(200);
        }

        // Without a maskable icon, Android crops the launcher icon awkwardly.
        expect(manifest.icons.some((i: { purpose?: string }) => i.purpose === 'maskable')).toBe(true);
    });
});

test.describe('the service worker', () => {
    test('registers from the root so it controls the whole origin', async ({ page }) => {
        await page.goto('/');

        const scope = await page.evaluate(async () => {
            const registration = await navigator.serviceWorker.getRegistration('/');

            return registration?.scope ?? null;
        });

        // The root-scope shim is what makes this possible without a
        // Service-Worker-Allowed header, which shared hosting cannot set.
        expect(scope).not.toBeNull();
        expect(new URL(scope!).pathname).toBe('/');
    });

    test('is served with no-cache, so an update is never stuck behind a stale worker', async ({ request }) => {
        const response = await request.get('/sw.js');

        expect(response.status()).toBe(200);
        expect(await response.text()).toContain('/build/sw.js');
    });

    test('does not precache the premium editor bundle', async ({ request }) => {
        // Tiptap is larger than the rest of the app combined and is useless
        // offline. Precaching it would make every player download it.
        const worker = await (await request.get('/build/sw.js')).text();

        expect(worker).not.toContain('DeckEditor');
    });
});

test.describe('the installed experience', () => {
    test('declares a theme colour and viewport fit for notched devices', async ({ page }) => {
        await page.goto('/');

        await expect(page.locator('meta[name="theme-color"]')).toHaveAttribute('content', '#0f0a1e');

        const viewport = await page.locator('meta[name="viewport"]').getAttribute('content');

        expect(viewport).toContain('viewport-fit=cover');
    });

    test('sets the document language from the server', async ({ page }) => {
        await page.goto('/');

        // Playwright runs this project in nl-NL, and the server honours it.
        await expect(page.locator('html')).toHaveAttribute('lang', 'nl');
    });
});
