const SW_VERSION = 'v1.0.0';
const CACHE_STATIC = `pwa-static-${SW_VERSION}`;
const CACHE_RUNTIME = `pwa-runtime-${SW_VERSION}`;

function scopeBasePath() {
  const scopeUrl = new URL(self.registration.scope);
  let path = scopeUrl.pathname || '/';
  if (!path.endsWith('/')) {
    path += '/';
  }
  return path === '/' ? '' : path.replace(/\/$/, '');
}

function publicShellUrls(basePath) {
  const root = basePath === '' ? '' : basePath;
  return [
    `${root}/`,
    `${root}/index.php`,
    `${root}/src/pwa/offline.html`
  ];
}

function isSameOrigin(requestUrl) {
  return requestUrl.origin === self.location.origin;
}

function isStaticDestination(request) {
  return ['style', 'script', 'image', 'font', 'manifest'].includes(request.destination);
}

function isPublicNavigation(requestUrl, basePath) {
  const root = basePath === '' ? '' : basePath;
  const path = requestUrl.pathname;
  if (path === `${root}/` || path === `${root}/index.php`) {
    return true;
  }
  if (path.startsWith(`${root}/src/public/`)) {
    return true;
  }
  return false;
}

self.addEventListener('install', (event) => {
  const basePath = scopeBasePath();
  event.waitUntil(
    caches.open(CACHE_STATIC).then((cache) => cache.addAll(publicShellUrls(basePath)))
  );
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(
        keys
          .filter((key) => key.startsWith('pwa-') && ![CACHE_STATIC, CACHE_RUNTIME].includes(key))
          .map((key) => caches.delete(key))
      )
    )
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  const request = event.request;
  if (request.method !== 'GET') {
    return;
  }

  const requestUrl = new URL(request.url);
  if (!isSameOrigin(requestUrl)) {
    return;
  }

  const basePath = scopeBasePath();

  if (request.mode === 'navigate' && isPublicNavigation(requestUrl, basePath)) {
    event.respondWith(
      fetch(request)
        .then((networkResponse) => {
          const clone = networkResponse.clone();
          caches.open(CACHE_RUNTIME).then((cache) => cache.put(request, clone));
          return networkResponse;
        })
        .catch(async () => {
          const cached = await caches.match(request);
          if (cached) {
            return cached;
          }
          return caches.match(`${basePath}/src/pwa/offline.html`);
        })
    );
    return;
  }

  if (isStaticDestination(request)) {
    event.respondWith(
      caches.match(request).then((cached) => {
        const networkFetch = fetch(request)
          .then((response) => {
            if (response && response.status === 200) {
              const clone = response.clone();
              caches.open(CACHE_RUNTIME).then((cache) => cache.put(request, clone));
            }
            return response;
          })
          .catch(() => cached);

        return cached || networkFetch;
      })
    );
  }
});
