const CACHE='pattern-owner-v3';
const SHELL=['/owner-offline.html','/owner-manifest.webmanifest','/assets/css/owner-pwa.css','/assets/css/owner-pwa-premium.css','/assets/js/owner-pwa.js','/assets/js/owner-pwa-premium.js','/assets/css/icons.min.css'];
self.addEventListener('install',event=>event.waitUntil(caches.open(CACHE).then(cache=>cache.addAll(SHELL)).then(()=>self.skipWaiting())));
self.addEventListener('activate',event=>event.waitUntil(caches.keys().then(keys=>Promise.all(keys.filter(key=>key!==CACHE).map(key=>caches.delete(key)))).then(()=>self.clients.claim())));
self.addEventListener('fetch',event=>{
  if(event.request.method!=='GET'||new URL(event.request.url).origin!==self.location.origin) return;
  if(event.request.mode==='navigate'){
    event.respondWith(fetch(event.request).catch(()=>caches.match('/owner-offline.html')));
    return;
  }
  const path=new URL(event.request.url).pathname;
  if(!path.startsWith('/assets/')&&path!=='/owner-manifest.webmanifest') return;
  event.respondWith(caches.match(event.request).then(cached=>cached||fetch(event.request).then(response=>{
    if(response.ok){const copy=response.clone();caches.open(CACHE).then(cache=>cache.put(event.request,copy));}
    return response;
  })));
});
