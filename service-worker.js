const CACHE_NAME = 'rmt-terminal-v1';
const STATIC_ASSETS = [
  'manifest.webmanifest',
  'images/touch.png',
  'images/logo_drs.png',
  'images/app_icon.png'
];

self.addEventListener('install', event => {
  event.waitUntil(caches.open(CACHE_NAME).then(cache => cache.addAll(STATIC_ASSETS)));
  self.skipWaiting();
});

self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys => Promise.all(keys.filter(key => key !== CACHE_NAME).map(key => caches.delete(key))))
  );
  self.clients.claim();
});

self.addEventListener('fetch', event => {
  if (event.request.method !== 'GET') return;
  const url = new URL(event.request.url);
  if (url.origin !== self.location.origin || !STATIC_ASSETS.some(asset => url.pathname.endsWith(asset))) return;
  event.respondWith(caches.match(event.request).then(cached => cached || fetch(event.request)));
});

