import { defineConfig } from 'vite';
import { fileURLToPath, URL } from 'node:url';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.ts'],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        tailwindcss(),
        VitePWA({
            // The generated worker lands in public/build; public/sw.js importScripts it
            // so the worker is served from the root scope without needing a
            // Service-Worker-Allowed header (shared hosting can't always set one).
            outDir: 'public/build',
            filename: 'sw.js',
            manifestFilename: 'manifest.webmanifest',
            registerType: 'prompt', // never swap assets mid-game; prompt to reload
            injectRegister: null, // registration is handled in resources/js/pwa.ts
            manifest: {
                name: 'Biertappen',
                short_name: 'Biertappen',
                description: 'Het drankspel voor onderweg.',
                lang: 'nl',
                start_url: '/',
                scope: '/',
                display: 'standalone',
                orientation: 'portrait',
                background_color: '#0f0a1e',
                theme_color: '#0f0a1e',
                icons: [
                    { src: '/icons/icon-192.png', sizes: '192x192', type: 'image/png' },
                    { src: '/icons/icon-512.png', sizes: '512x512', type: 'image/png' },
                    { src: '/icons/maskable-512.png', sizes: '512x512', type: 'image/png', purpose: 'maskable' },
                ],
            },
            workbox: {
                globDirectory: 'public',
                globPatterns: ['build/assets/**/*.{js,css,woff2}', 'icons/*.png'],
                // Inertia responses carry a CSRF token: never cache HTML navigations.
                navigateFallback: null,
                /*
                 * Keep the premium deck editor out of the precache. It carries
                 * the whole Tiptap bundle — larger than the rest of the app put
                 * together — and every action in it requires the server, so it
                 * is useless offline. Precaching it would make every player who
                 * never opens the creator download it anyway.
                 */
                manifestTransforms: [
                    (entries) => ({
                        manifest: entries.filter((entry) => !/DeckEditor/.test(entry.url)),
                        warnings: [],
                    }),
                ],
                cleanupOutdatedCaches: true,
                runtimeCaching: [
                    {
                        urlPattern: ({ request }) => request.destination === 'font',
                        handler: 'CacheFirst',
                        options: { cacheName: 'fonts', expiration: { maxEntries: 12 } },
                    },
                ],
            },
            devOptions: { enabled: false },
        }),
    ],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
    server: {
        watch: { ignored: ['**/storage/framework/views/**'] },
    },
});
