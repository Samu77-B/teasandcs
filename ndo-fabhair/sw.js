const CACHE_NAME = 'teas-cs-v10';

// Install event - cache resources (kept for PWA install)
self.addEventListener('install', function(event) {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(function(cache) {
        return cache.addAll([
          './',
          './index.html',
          'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap'
        ]);
      })
  );
  self.skipWaiting(); // Activate new SW immediately
});

// Fetch: DO NOT intercept - was causing "Load failed" / "respondWith received an error" on mobile
// All requests go directly to network. Payment/API must not go through SW.
self.addEventListener('fetch', function(event) {
  return; // No interception - fixes payment init on phone
});

// Activate - claim clients so new SW takes over immediately
self.addEventListener('activate', function(event) {
  event.waitUntil(
    caches.keys().then(function(cacheNames) {
      return Promise.all(
        cacheNames.map(function(cacheName) {
          return cacheName !== CACHE_NAME ? caches.delete(cacheName) : Promise.resolve();
        })
      );
    }).then(function() {
      return self.clients.claim();
    })
  );
});
