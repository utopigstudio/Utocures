self.addEventListener('install', () => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((keys) => Promise.all(keys.filter((key) => key.startsWith('utocures-pwa-')).map((key) => caches.delete(key))))
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    if (event.request.mode !== 'navigate') {
        return;
    }

    event.respondWith(fetch(event.request));
});

function internalUrl(value = '/') {
    const url = new URL(value, self.location.origin);

    return url.origin === self.location.origin ? url : new URL('/', self.location.origin);
}

function normalizedPath(url) {
    const pathname = url.pathname.replace(/\/+$/, '');

    return pathname || '/';
}

function refreshVisibleConversation(client, targetUrl) {
    return new Promise((resolve) => {
        const channel = new MessageChannel();
        const timeout = setTimeout(() => resolve(false), 750);

        channel.port1.onmessage = (event) => {
            clearTimeout(timeout);
            resolve(event.data?.handled === true);
        };

        client.postMessage(
            {
                type: 'utocures:new-message',
                url: `${targetUrl.pathname}${targetUrl.search}`,
            },
            [channel.port2],
        );
    });
}

self.addEventListener('push', (event) => {
    let payload = {};

    try {
        payload = event.data?.json() ?? {};
    } catch {
        payload = { body: event.data?.text() };
    }

    event.waitUntil(
        (async () => {
            const targetUrl = internalUrl(payload.data?.url);
            const windowClients = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
            const visibleConversation = windowClients.find((client) => {
                const clientUrl = internalUrl(client.url);
                const isVisible = client.visibilityState === 'visible' || client.focused;

                return isVisible && normalizedPath(clientUrl) === normalizedPath(targetUrl);
            });

            if (visibleConversation) {
                const wasHandled = await refreshVisibleConversation(visibleConversation, targetUrl);

                if (wasHandled) {
                    return;
                }
            }

            await self.registration.showNotification(payload.title ?? 'Utocures', {
                body: payload.body ?? 'Tienes un nuevo mensaje.',
                icon: payload.icon ?? '/pwa-icon-192.png',
                badge: payload.badge ?? '/pwa-icon-192.png',
                tag: payload.tag ?? 'utocures-messages',
                renotify: payload.renotify ?? true,
                vibrate: payload.vibrate ?? [100, 50, 100],
                data: payload.data ?? { url: '/' },
            });
        })(),
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const targetUrl = internalUrl(event.notification.data?.url);

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(async (windowClients) => {
            const applicationClient = windowClients.find((client) => new URL(client.url).origin === self.location.origin);

            if (applicationClient) {
                if ('navigate' in applicationClient) {
                    await applicationClient.navigate(targetUrl.href);
                }

                return applicationClient.focus();
            }

            return self.clients.openWindow(targetUrl.href);
        }),
    );
});
