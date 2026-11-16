/*
 * Offline fallback only: every request still goes to the network as normal.
 * When a page navigation fails because there is no connection, the branded
 * /offline page is shown instead of the browser's own error screen.
 */
const CACHE = 'nsales-offline-v1';
const OFFLINE_URL = '/offline';

async function cacheOfflinePage() {
    const cache = await caches.open(CACHE);
    const response = await fetch(OFFLINE_URL, { cache: 'no-store', credentials: 'omit' });
    if (response.ok) await cache.put(OFFLINE_URL, response);
}

self.addEventListener('install', (event) => {
    event.waitUntil(cacheOfflinePage().catch(() => {}).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((key) => key !== CACHE).map((key) => caches.delete(key))))
            .then(() => self.clients.claim())
    );
});

// Pages ask for a refresh now and then so branding changes reach the cached copy.
self.addEventListener('message', (event) => {
    if (event.data === 'refresh-offline-page') event.waitUntil(cacheOfflinePage().catch(() => {}));
});

self.addEventListener('fetch', (event) => {
    if (event.request.mode !== 'navigate') return;

    event.respondWith(
        fetch(event.request).catch(async () => {
            const cached = await caches.match(OFFLINE_URL);
            return cached || new Response(
                '<!doctype html><meta name="viewport" content="width=device-width,initial-scale=1"><title>You are offline</title>' +
                '<body style="font-family:system-ui,sans-serif;text-align:center;padding:60px 16px">' +
                '<h1>You are offline</h1><p>Check your internet connection, then try again.</p>' +
                '<button onclick="location.reload()">Try again</button></body>',
                { status: 503, headers: { 'Content-Type': 'text/html; charset=utf-8' } }
            );
        })
    );
});
