/* Offline-Betrieb.

   Auf dem Camino ist streckenweise kein Netz — der Plan muss trotzdem lesbar
   sein. Der Service Worker legt die zuletzt geladene Seite und die Dateien
   dazu ab und liefert sie aus, wenn nichts antwortet.

   Zwei Dinge sind dabei wichtig:
   1. Es wird nie etwas Angemeldetes gespeichert, wenn die Antwort 401 war —
      und eine vorhandene Kopie wird dann sogar weggeworfen. Sonst zeigte die
      Seite nach dem Abmelden noch fremde Inhalte.
   2. Gespeichert wird nur, was per GET kommt. api.php und upload.php laufen
      über POST und fassen wir nie an. */

var VERSION = 'pilger-v3';
var SEITE = VERSION + '-seite';
var ZEUG  = VERSION + '-zeug';
var BILD  = VERSION + '-bild';

var MITBRINGEN = [
  'assets/app.css',
  'assets/app.js',
  'assets/tagebuch.js'
];

self.addEventListener('install', function (e) {
  e.waitUntil(
    caches.open(ZEUG).then(function (c) {
      return Promise.all(MITBRINGEN.map(function (u) {
        return c.add(u).catch(function () { /* fehlt eben */ });
      }));
    }).then(function () { return self.skipWaiting(); })
  );
});

self.addEventListener('activate', function (e) {
  e.waitUntil(
    caches.keys().then(function (namen) {
      return Promise.all(namen.map(function (n) {
        return n.indexOf(VERSION) === 0 ? null : caches.delete(n);
      }));
    }).then(function () { return self.clients.claim(); })
  );
});

self.addEventListener('fetch', function (e) {
  var anfrage = e.request;
  if (anfrage.method !== 'GET') return;

  var url = new URL(anfrage.url);
  if (url.origin !== self.location.origin) return;
  if (url.pathname.indexOf('/api.php') !== -1 || url.pathname.indexOf('/upload.php') !== -1) return;

  // Die Seite selbst: erst das Netz, sonst die letzte Fassung.
  if (anfrage.mode === 'navigate') {
    e.respondWith(
      fetch(anfrage).then(function (antwort) {
        if (antwort.status === 401 || antwort.status === 403) {
          // Nicht angemeldet — alte Kopie darf nicht liegen bleiben.
          caches.open(SEITE).then(function (c) { c.delete('seite'); });
          return antwort;
        }
        if (antwort.ok) {
          var kopie = antwort.clone();
          caches.open(SEITE).then(function (c) { c.put('seite', kopie); });
        }
        return antwort;
      }).catch(function () {
        return caches.open(SEITE).then(function (c) {
          return c.match('seite').then(function (treffer) {
            return treffer || new Response(
              '<!DOCTYPE html><meta charset="utf-8"><title>offline</title>' +
              '<body style="font-family:system-ui;background:#232a2e;color:#f6f0e2;padding:40px">' +
              '<h1>Kein Netz</h1><p>Die Seite war auf diesem Gerät noch nicht geladen, ' +
              'deshalb liegt keine Fassung bereit. Sprachnotizen aufnehmen geht trotzdem, ' +
              'sobald die Seite einmal geladen war.</p>',
              { headers: { 'Content-Type': 'text/html; charset=utf-8' } }
            );
          });
        });
      })
    );
    return;
  }

  // Vorschaubilder: einmal geholt, bleiben sie da. Sie ändern sich nie.
  if (url.pathname.indexOf('/media.php') !== -1 && url.searchParams.get('art') === 'klein') {
    e.respondWith(
      caches.open(BILD).then(function (c) {
        return c.match(anfrage).then(function (treffer) {
          if (treffer) return treffer;
          return fetch(anfrage).then(function (antwort) {
            if (antwort.ok) c.put(anfrage, antwort.clone());
            return antwort;
          });
        });
      })
    );
    return;
  }

  // Alles andere aus dem eigenen Haus: aus dem Speicher zeigen, im
  // Hintergrund erneuern.
  if (url.pathname.indexOf('/assets/') !== -1) {
    e.respondWith(
      caches.open(ZEUG).then(function (c) {
        return c.match(anfrage).then(function (treffer) {
          var frisch = fetch(anfrage).then(function (antwort) {
            if (antwort.ok) c.put(anfrage, antwort.clone());
            return antwort;
          }).catch(function () { return treffer; });
          return treffer || frisch;
        });
      })
    );
  }
});
