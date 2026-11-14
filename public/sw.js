importScripts('./chatbot/store.js', './chatbot/sync.js');

const CACHE = 'orbit-assistant-v11';
const BASE = new URL('./', self.location.href);
const ASSETS = [
    'manifest.webmanifest', 'chatbot/offline.html', 'chatbot/assistant.css',
    'chatbot/store.js', 'chatbot/sync.js', 'chatbot/engine.js', 'chatbot/assistant.js', 'chatbot/icon.svg',
    'chatbot/inter-latin.woff2', 'chatbot/vendor/chart.umd.min.js',
    'chatbot/intents.json', 'chatbot/health-knowledge.json', 'chatbot/procedures.json', 'chatbot/faq.json',
].map((path) => new URL(path, BASE).href);

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(CACHE).then((cache) => cache.addAll(ASSETS)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
    event.waitUntil(caches.keys().then((keys) => Promise.all(keys
        .filter((key) => key.startsWith('orbit-assistant-') && key !== CACHE)
        .map((key) => caches.delete(key)))).then(() => self.clients.claim()));
});

self.addEventListener('fetch', (event) => {
    const request = event.request;
    if (request.method !== 'GET' || new URL(request.url).origin !== self.location.origin) return;
    if (ASSETS.includes(request.url)) {
        event.respondWith(caches.match(request).then((cached) => cached || fetch(request)));
    } else if (request.mode === 'navigate') {
        // Never cache authenticated HTML, which can contain another user's health data.
        event.respondWith(fetch(request).catch(() => Response.redirect(new URL('chatbot/offline.html', BASE).href)));
    }
});

self.addEventListener('sync', (event) => {
    if (event.tag === 'orbit-message-sync') {
        event.waitUntil(OrbitSync(BASE.href));
    }
});
