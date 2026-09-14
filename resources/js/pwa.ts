import { ref } from 'vue';

/**
 * Set when a new build is waiting. The UI surfaces a prompt rather than
 * reloading: swapping assets underneath a running game would lose it.
 */
export const updateReady = ref(false);

let waitingWorker: ServiceWorker | null = null;

export function registerServiceWorker(): void {
    if (!('serviceWorker' in navigator) || import.meta.env.DEV) {
        return;
    }

    window.addEventListener('load', () => {
        // /sw.js is a shim that importScripts the hashed worker from /build,
        // so the worker controls the whole origin without a
        // Service-Worker-Allowed header (shared hosting can't always set one).
        navigator.serviceWorker
            .register('/sw.js', { scope: '/' })
            .then((registration) => {
                if (registration.waiting) {
                    waitingWorker = registration.waiting;
                    updateReady.value = true;
                }

                registration.addEventListener('updatefound', () => {
                    const installing = registration.installing;
                    if (!installing) return;

                    installing.addEventListener('statechange', () => {
                        if (installing.state === 'installed' && navigator.serviceWorker.controller) {
                            waitingWorker = installing;
                            updateReady.value = true;
                        }
                    });
                });
            })
            .catch(() => {
                // An unavailable service worker must never break the app.
            });
    });
}

/** Called from the update prompt, never automatically. */
export function applyUpdate(): void {
    if (!waitingWorker) {
        window.location.reload();
        return;
    }

    waitingWorker.postMessage({ type: 'SKIP_WAITING' });
    navigator.serviceWorker.addEventListener('controllerchange', () => window.location.reload(), {
        once: true,
    });
}
