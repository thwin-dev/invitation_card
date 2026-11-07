const CACHE_NAME = 'pos-pwa-cache-v3';
const PRECACHE_ASSETS = [
    '/',
    '/manifest.json',
    '/favicon.ico',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(async (cache) => {
            await cache.addAll(PRECACHE_ASSETS).catch((err) => {
                console.warn('[ServiceWorker] Base pre-cache error:', err);
            });

            // Proactively pre-cache all compiled Vite assets (CSS, JS) from manifest.json
            try {
                const manifestRes = await fetch('/build/manifest.json');
                if (manifestRes && manifestRes.ok) {
                    const manifest = await manifestRes.json();
                    const assetUrls = ['/build/manifest.json'];
                    for (const key in manifest) {
                        const entry = manifest[key];
                        if (entry.file) assetUrls.push('/build/' + entry.file);
                        if (entry.css && Array.isArray(entry.css)) {
                            entry.css.forEach(cssFile => assetUrls.push('/build/' + cssFile));
                        }
                    }
                    await cache.addAll(assetUrls).catch(e => console.warn('[SW] Build asset pre-cache error:', e));
                }
            } catch (err) {
                console.warn('[SW] Could not read build manifest for pre-cache:', err);
            }
        })
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
            );
        })
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    // Skip non-GET requests and API sync requests from full caching
    if (event.request.method !== 'GET') {
        return;
    }

    const url = new URL(event.request.url);

    // Skip catalog API and sync API from service worker interception
    if (url.pathname.includes('/sales/offline-catalog') || url.pathname.includes('/sales/sync-offline')) {
        return;
    }

    // Dynamic cache for build assets (CSS, JS, Fonts, Images)
    if (url.pathname.startsWith('/build/') || url.pathname.match(/\.(css|js|woff2?|png|jpg|svg|ico)$/)) {
        event.respondWith(
            caches.match(event.request).then((cachedResponse) => {
                if (cachedResponse) {
                    return cachedResponse;
                }
                return fetch(event.request).then((networkResponse) => {
                    if (networkResponse && networkResponse.status === 200) {
                        const responseToCache = networkResponse.clone();
                        caches.open(CACHE_NAME).then((cache) => cache.put(event.request, responseToCache));
                    }
                    return networkResponse;
                }).catch(() => cachedResponse);
            })
        );
        return;
    }

    // Network-first strategy for navigation and HTML pages with resilient cache fallback
    if (event.request.mode === 'navigate' || event.request.destination === 'document' || (event.request.headers.get('accept') && event.request.headers.get('accept').includes('text/html'))) {
        event.respondWith(
            fetch(event.request)
                .then((networkResponse) => {
                    if (networkResponse && networkResponse.status === 200) {
                        const responseToCache = networkResponse.clone();
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(event.request, responseToCache.clone());
                            cache.put(url.pathname, responseToCache);
                        });
                    }
                    return networkResponse;
                })
                .catch(async () => {
                    const cached = await caches.match(event.request);
                    if (cached) return cached;

                    const pathCached = await caches.match(url.pathname);
                    if (pathCached) return pathCached;

                    const posFallback = await caches.match('/management/sales/create');
                    if (posFallback) return posFallback;

                    const dashFallback = await caches.match('/management');
                    if (dashFallback) return dashFallback;

                    return new Response(
                        `<!DOCTYPE html>
                        <html lang="en">
                        <head>
                            <meta charset="utf-8">
                            <meta name="viewport" content="width=device-width, initial-scale=1">
                            <title>Offline Mode - Sale POS</title>
                            <style>
                                body { font-family: system-ui, -apple-system, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; background: #f8f9fc; color: #1f2937; text-align: center; padding: 20px; }
                                .card { background: #fff; padding: 36px 28px; border-radius: 16px; border: 1px solid #e5e7eb; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.06); max-width: 440px; width: 100%; }
                                .badge { display: inline-block; background: #fef3c7; color: #b45309; padding: 4px 12px; border-radius: 9999px; font-size: 12px; font-weight: 700; text-transform: uppercase; margin-bottom: 16px; }
                                h1 { font-size: 20px; font-weight: 700; margin: 0 0 8px; color: #111827; }
                                p { font-size: 14px; color: #6b7280; line-height: 1.5; margin: 0 0 24px; }
                                .btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; background: #465fff; color: #fff; text-decoration: none; padding: 12px 24px; border-radius: 10px; font-weight: 600; font-size: 14px; transition: background 0.2s; width: 100%; box-sizing: border-box; }
                                .btn:hover { background: #364bcc; }
                            </style>
                        </head>
                        <body>
                            <div class="card">
                                <span class="badge">Offline Mode</span>
                                <h1>Network Disconnected</h1>
                                <p>You are currently offline. You can continue selling products and printing receipts from the POS Screen.</p>
                                <a href="/management/sales/create" class="btn">Open POS Cashier Screen</a>
                            </div>
                        </body>
                        </html>`,
                        { headers: { 'Content-Type': 'text/html; charset=utf-8' } }
                    );
                })
        );
        return;
    }
});
