///////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////// SERVICE WORKER EVENTS


/////////////////////////////////////////////////////////////////////// INSTALL
self.addEventListener('install', (event) => {
 console.log("Service Worker installed.");
});


////////////////////////////////////////////////////////////////////////// PUSH
self.addEventListener('push', function(e) {
  var payload = e.data.json();

  var options = {
    body: payload.body,
    badge: 'img/mikitree.48x48.png',
    icon: 'img/mikitree.120x120.png',
    vibrate: [64, 32, 16, 8, 4]
  };
  e.waitUntil(
    self.registration.showNotification(payload.title, options)
  );
});


//////////////////////////////////////////////////////////// NOTIFICATION-CLICK
self.addEventListener('notificationclick', function(event) {
  var notification = event.notification;
  switch (event.action) {
    case 'close':
      notification.close();
      break;
    default:
      notification.close();
      const url = new URL("/index.php", self.location.origin).href;
      promiseChain = focusWindow(url);
  }
  event.waitUntil(promiseChain);
});


function focusWindow(url){
  return clients.matchAll({
    type: 'window',
    includeUncontrolled: true
  }).then((windowClients) => {
    let matchingClient = null;

    for (let i = 0; i < windowClients.length; i++) {
      const windowClient = windowClients[i];
      if (windowClient.url === url) {
        matchingClient = windowClient;
        break;
      }
    }

    if (matchingClient) {
      return matchingClient.focus();
    } else {
      return clients.openWindow(url);
    }
  });
}


/////////////////////////////////////////////////////////////////////// MESSAGE
self.addEventListener('message', function handler (event) {
    switch (event.data.command) {
        default:
            throw 'no aTopic on incoming message to ChromeWorker';
    }
});


///////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////// WORKBOX

importScripts('https://storage.googleapis.com/workbox-cdn/releases/4.3.1/workbox-sw.js');


workbox.routing.registerRoute(
  /\.(?:png|gif|jpg|jpeg|webp|svg)$/,
  new workbox.strategies.CacheFirst({
    cacheName: 'images',
    plugins: [
      new workbox.expiration.Plugin({
        maxEntries: 60,
        maxAgeSeconds: 30 * 24 * 60 * 60, // 30 Days
      }),
    ],
  })
);


workbox.routing.registerRoute(
  new RegExp('manifest.json$'),
  new workbox.strategies.StaleWhileRevalidate({
    cacheName: 'static-resources',
  })
);


workbox.routing.registerRoute(
  new RegExp('.*workbox-sw.js$'),
  new workbox.strategies.StaleWhileRevalidate({
    cacheName: 'static-resources',
  })
);



workbox.routing.registerRoute(
  new RegExp('^https://storage.googleapis.com/workbox-cdn/.*'),
  new workbox.strategies.StaleWhileRevalidate({
    cacheName: 'static-resources',
  })
);


workbox.routing.registerRoute(
  /\.(?:js|css)$/,
  new workbox.strategies.StaleWhileRevalidate({
    cacheName: 'static-resources',
  })
);


workbox.routing.registerRoute(
  /index\.php.*$/,
  new workbox.strategies.StaleWhileRevalidate({
    cacheName: 'pages',
  })
);


console.log("serviceWorker loaded.");



