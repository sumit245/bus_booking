// Agent Panel Service Worker - Optimized for Low Bandwidth
const CACHE_NAME = 'agent-panel-v1.0.0';
const STATIC_CACHE_NAME = 'agent-static-v1.0.0';
const DYNAMIC_CACHE_NAME = 'agent-dynamic-v1.0.0';

// Critical assets to cache immediately
const STATIC_ASSETS = [
    '/agent/dashboard',
    '/assets/global/css/all.min.css',
    '/assets/global/css/line-awesome.min.css',
    '/assets/global/css/select2.min.css',
    '/assets/global/css/datepicker.min.css',
    '/assets/global/js/jquery.min.js',
    '/assets/global/js/bootstrap.min.js',
    '/assets/global/js/select2.min.js',
    '/assets/global/js/datepicker.min.js',
    '/assets/images/logoIcon/logo.png',
    '/assets/images/logoIcon/favicon.png'
];

// API endpoints to cache with network-first strategy
const API_ENDPOINTS = [
    '/agent/api/bus-search',
    '/agent/api/schedules',
    '/agent/api/seat-layout',
    '/agent/api/booking'
];

// Install event - cache static assets
self.addEventListener('install', event => {
    console.log('[SW] Installing service worker...');
    event.waitUntil(
        caches.open(STATIC_CACHE_NAME)
            .then(cache => {
                console.log('[SW] Caching static assets');
                return cache.addAll(STATIC_ASSETS);
            })
            .then(() => {
                console.log('[SW] Static assets cached successfully');
                return self.skipWaiting();
            })
            .catch(error => {
                console.error('[SW] Failed to cache static assets:', error);
            })
    );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
    console.log('[SW] Activating service worker...');
    event.waitUntil(
        caches.keys()
            .then(cacheNames => {
                return Promise.all(
                    cacheNames.map(cacheName => {
                        if (cacheName !== STATIC_CACHE_NAME && cacheName !== DYNAMIC_CACHE_NAME) {
                            console.log('[SW] Deleting old cache:', cacheName);
                            return caches.delete(cacheName);
                        }
                    })
                );
            })
            .then(() => {
                console.log('[SW] Service worker activated');
                return self.clients.claim();
            })
    );
});

// Fetch event - implement caching strategies
self.addEventListener('fetch', event => {
    const request = event.request;
    const url = new URL(request.url);

    // Skip non-GET requests
    if (request.method !== 'GET') {
        return;
    }

    // Handle API requests with network-first strategy
    if (isApiRequest(url)) {
        event.respondWith(networkFirstStrategy(request));
        return;
    }

    // Handle static assets with cache-first strategy
    if (isStaticAsset(url)) {
        event.respondWith(cacheFirstStrategy(request));
        return;
    }

    // Handle HTML pages with stale-while-revalidate strategy
    if (isHtmlRequest(request)) {
        event.respondWith(staleWhileRevalidateStrategy(request));
        return;
    }

    // Default: network with cache fallback
    event.respondWith(networkWithCacheFallback(request));
});

// Cache-first strategy for static assets
async function cacheFirstStrategy(request) {
    try {
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }

        const networkResponse = await fetch(request);
        if (networkResponse.ok) {
            const cache = await caches.open(STATIC_CACHE_NAME);
            cache.put(request, networkResponse.clone());
        }
        return networkResponse;
    } catch (error) {
        console.error('[SW] Cache-first strategy failed:', error);
        return new Response('Asset not available offline', { status: 503 });
    }
}

// Network-first strategy for API requests
async function networkFirstStrategy(request) {
    try {
        const networkResponse = await fetch(request);
        if (networkResponse.ok) {
            const cache = await caches.open(DYNAMIC_CACHE_NAME);
            cache.put(request, networkResponse.clone());
        }
        return networkResponse;
    } catch (error) {
        console.log('[SW] Network failed, trying cache:', error);
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        return new Response('API not available offline', { status: 503 });
    }
}

// Stale-while-revalidate for HTML pages
async function staleWhileRevalidateStrategy(request) {
    const cache = await caches.open(DYNAMIC_CACHE_NAME);
    const cachedResponse = await cache.match(request);

    const fetchPromise = fetch(request).then(networkResponse => {
        if (networkResponse.ok) {
            cache.put(request, networkResponse.clone());
        }
        return networkResponse;
    }).catch(error => {
        console.log('[SW] Network failed for HTML:', error);
        return cachedResponse || new Response('Page not available offline', { status: 503 });
    });

    return cachedResponse || fetchPromise;
}

// Network with cache fallback
async function networkWithCacheFallback(request) {
    try {
        const networkResponse = await fetch(request);
        if (networkResponse.ok) {
            const cache = await caches.open(DYNAMIC_CACHE_NAME);
            cache.put(request, networkResponse.clone());
        }
        return networkResponse;
    } catch (error) {
        const cachedResponse = await caches.match(request);
        return cachedResponse || new Response('Content not available offline', { status: 503 });
    }
}

// Helper functions
function isApiRequest(url) {
    return API_ENDPOINTS.some(endpoint => url.pathname.includes(endpoint));
}

function isStaticAsset(url) {
    const staticExtensions = ['.css', '.js', '.png', '.jpg', '.jpeg', '.gif', '.svg', '.woff', '.woff2', '.ttf'];
    return staticExtensions.some(ext => url.pathname.endsWith(ext));
}

function isHtmlRequest(request) {
    return request.headers.get('accept')?.includes('text/html');
}

// Background sync for offline actions
self.addEventListener('sync', event => {
    if (event.tag === 'background-sync-booking') {
        event.waitUntil(syncOfflineBookings());
    }
});

// Sync offline bookings when connection is restored
async function syncOfflineBookings() {
    try {
        const cache = await caches.open('offline-actions');
        const requests = await cache.keys();

        for (const request of requests) {
            if (request.url.includes('/agent/api/booking')) {
                try {
                    const response = await fetch(request);
                    if (response.ok) {
                        await cache.delete(request);
                        console.log('[SW] Synced offline booking');
                    }
                } catch (error) {
                    console.error('[SW] Failed to sync booking:', error);
                }
            }
        }
    } catch (error) {
        console.error('[SW] Background sync failed:', error);
    }
}

// Push notifications for agent updates
self.addEventListener('push', event => {
    if (event.data) {
        const data = event.data.json();
        const options = {
            body: data.body,
            icon: '/assets/images/logoIcon/favicon.png',
            badge: '/assets/images/logoIcon/favicon.png',
            vibrate: [100, 50, 100],
            data: data.data || {},
            actions: data.actions || []
        };

        event.waitUntil(
            self.registration.showNotification(data.title, options)
        );
    }
});

// Handle notification clicks
self.addEventListener('notificationclick', event => {
    event.notification.close();

    const urlToOpen = event.notification.data?.url || '/agent/dashboard';

    event.waitUntil(
        clients.matchAll({ type: 'window' }).then(clientList => {
            for (const client of clientList) {
                if (client.url === urlToOpen && 'focus' in client) {
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(urlToOpen);
            }
        })
    );
});
