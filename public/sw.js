/*
 * Root-scope shim.
 *
 * Vite writes the real, Workbox-generated worker to /build/sw.js, but a worker
 * may only control paths at or below its own URL. Rather than depend on a
 * Service-Worker-Allowed response header (which shared hosting does not reliably
 * let us set), this one-line worker is served from the origin root and pulls the
 * real implementation in. Scope is inherited from *this* file: '/'.
 */
importScripts('/build/sw.js');
