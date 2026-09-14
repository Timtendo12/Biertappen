import { defineConfig, devices } from '@playwright/test';

const PORT = 8321;
const BASE_URL = `http://127.0.0.1:${PORT}`;

/**
 * End-to-end tests for the gameplay loop.
 *
 * The engine and the components are already covered by unit tests; what only a
 * real browser can prove is that flip, swipe and auto-advance actually work
 * together on a touch screen. So this runs one mobile project against a real
 * server and a real database.
 */
export default defineConfig({
    testDir: './e2e',
    // Card animations are time-based; a little headroom avoids flakes on CI.
    timeout: 30_000,
    expect: { timeout: 10_000 },
    fullyParallel: false,
    workers: 1,
    retries: process.env.CI ? 1 : 0,
    reporter: process.env.CI ? [['list'], ['html', { open: 'never' }]] : [['list']],

    use: {
        baseURL: BASE_URL,
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
    },

    projects: [
        {
            // Portrait phone: the orientation the game is designed for and the
            // only one the card stack is laid out against.
            name: 'mobile',
            use: {
                ...devices['Pixel 7'],
                /*
                 * Playwright's device presets default to en-US, and the app
                 * honours Accept-Language — so without this the suite runs
                 * against the English interface while asserting Dutch copy.
                 * Dutch is the primary locale, so that is what is exercised.
                 */
                locale: 'nl-NL',
            },
        },
    ],

    /*
     * A real server against the real MariaDB test database. Assets must be built
     * first — these tests exercise the production bundle, not the dev server,
     * which is what actually ships.
     */
    webServer: {
        command: `php artisan serve --port=${PORT} --env=e2e`,
        url: `${BASE_URL}/up`,
        reuseExistingServer: !process.env.CI,
        timeout: 60_000,
    },
});
