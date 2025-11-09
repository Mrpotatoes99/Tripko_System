// Basic service worker for caching map assets & last markers request (Phase 1 minimal)
const CACHE_NAME = 'tripko-map-v1';
const CORE_ASSETS = [
  // Filled dynamically if needed
];
const MARKERS_ENDPOINT = /\/api\/map\/markers\.php$/;
const TILE_URL = /tile\.openstreetmap\.org/;

self.addEventListener('install', evt => {
  evt.waitUntil(caches.open(CACHE_NAME).then(c=>c.addAll(CORE_ASSETS)).catch(()=>{}));
});

self.addEventListener('activate', evt => {
  evt.waitUntil(caches.keys().then(keys => Promise.all(keys.filter(k=>k!==CACHE_NAME).map(k=>caches.delete(k)))));
});

self.addEventListener('fetch', evt => {
  const url = evt.request.url;
  if(MARKERS_ENDPOINT.test(url)){
    evt.respondWith(
      fetch(evt.request).then(res=>{
        const clone = res.clone();
        caches.open(CACHE_NAME).then(c=>c.put(evt.request, clone));
        return res;
      }).catch(()=>caches.match(evt.request))
    );
    return;
  }
  if(TILE_URL.test(url)){
    evt.respondWith(
      caches.match(evt.request).then(cached=>{
        if(cached) return cached;
        return fetch(evt.request).then(res=>{
          const clone = res.clone();
          caches.open(CACHE_NAME).then(c=>c.put(evt.request, clone));
          return res;
        }).catch(()=>cached);
      })
    );
  }
});
