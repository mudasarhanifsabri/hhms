const CACHE_NAME = 'hhms-pwa-v21-bright';
const APP_SHELL = [
  '/',
  '/maintainer/tasks',
  '/assets/css/vendor.min.css',
  '/assets/css/icons.min.css',
  '/assets/css/app.min.css',
  '/assets/css/maintainer-pwa.css',
  '/manifest.webmanifest',
  '/assets/css/admin-pwa.css',
  '/assets/js/admin-pwa.js',
  '/admin-manifest.webmanifest',
  '/admin-offline.html',
  '/assets/js/vendor.js',
  '/assets/js/app.js',
  '/assets/images/logo-sm.png',
  '/assets/images/pwa-icon-192.png',
  '/assets/images/pwa-icon-512.png'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => cache.addAll(APP_SHELL))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const request = event.request;

  if (request.method !== 'GET') {
    return;
  }

  const url = new URL(request.url);
  const isStaticAsset = APP_SHELL.includes(url.pathname);
  const isMaintainerRoute = url.pathname.startsWith('/maintainer');
  const isAdminRoute = url.pathname.startsWith('/admin');

  if (!isStaticAsset && !isMaintainerRoute && !isAdminRoute) {
    event.respondWith(fetch(request));
    return;
  }

  event.respondWith(
    fetch(request)
      .then((response) => {
        if (isStaticAsset || isMaintainerRoute || isAdminRoute) {
          const clone = response.clone();
          caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
        }
        return response;
      })
      .catch(() => caches.match(request).then((cached) => cached || caches.match(isAdminRoute ? '/admin-offline.html' : '/maintainer/tasks')))
  );
});

self.addEventListener('notificationclick', (event) => {
  const targetUrl = event.notification.data && event.notification.data.url
    ? event.notification.data.url
    : '/maintainer/tasks';

  event.notification.close();
  event.waitUntil(
    self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
      for (const client of clientList) {
        if ('navigate' in client && 'focus' in client) {
          return client.navigate(targetUrl).then(() => client.focus());
        }
      }

      return self.clients.openWindow(targetUrl);
    })
  );
});
