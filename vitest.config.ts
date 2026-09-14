import { defineConfig } from 'vitest/config';
import { fileURLToPath, URL } from 'node:url';
import vue from '@vitejs/plugin-vue';

/**
 * Kept separate from vite.config.ts so the Laravel and PWA plugins never run
 * during tests — the engine suite has no business generating a service worker.
 */
export default defineConfig({
    plugins: [vue()],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
    test: {
        environment: 'happy-dom',
        globals: true,
        include: ['resources/js/**/*.spec.ts'],
        coverage: {
            include: ['resources/js/game/**/*.ts'],
            reporter: ['text', 'html'],
        },
    },
});
