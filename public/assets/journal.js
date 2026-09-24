/* Das Journal: zwei Kleinigkeiten, mehr braucht eine Leseseite nicht.

   1. Die Tagesleiste zeigt, in welchem Kapitel man gerade steckt.
   2. Jedes Kapitel bekommt seine Karte — aber erst, wenn es in Sichtweite
      kommt. Zwoelf Leaflet-Karten gleichzeitig zu bauen macht das Scrollen
      auf dem Telefon zaeh, und die meisten sieht man nie. */
(function () {
  'use strict';

  /* ---------- Wo bin ich? ---------------------------------------------- */
  var leiste = document.getElementById('jleiste');
  var kapitel = Array.prototype.slice.call(document.querySelectorAll('.jkap'));

  if (leiste && kapitel.length && 'IntersectionObserver' in window) {
    var knoepfe = {};
    leiste.querySelectorAll('a').forEach(function (a) { knoepfe[a.dataset.k] = a; });

    var sichtbar = new IntersectionObserver(function (eintraege) {
      eintraege.forEach(function (e) {
        var a = knoepfe[e.target.id];
        if (!a) return;
        if (e.isIntersecting) {
          Object.keys(knoepfe).forEach(function (k) { knoepfe[k].classList.remove('da'); });
          a.classList.add('da');
          // Mitscrollen, sonst steht der aktive Tag auf dem Telefon ausserhalb.
          var links = a.offsetLeft - leiste.clientWidth / 2 + a.clientWidth / 2;
          leiste.scrollTo({ left: Math.max(0, links), behavior: 'smooth' });
        }
      });
    }, { rootMargin: '-45% 0px -45% 0px' });

    kapitel.forEach(function (k) { sichtbar.observe(k); });
  }

  /* ---------- Karten, erst bei Bedarf ----------------------------------- */
  var kartenEl = document.querySelectorAll('.jkarte');
  if (!kartenEl.length || typeof L === 'undefined') return;

  var daten = {};
  try {
    daten = JSON.parse(document.getElementById('journal-karte').textContent);
  } catch (e) { daten = {}; }
  var linie = daten.linie || [];

  function baue(el) {
    if (el.dataset.fertig) return;
    el.dataset.fertig = '1';

    var lat = parseFloat(el.dataset.lat);
    var lng = parseFloat(el.dataset.lng);
    if (isNaN(lat) || isNaN(lng)) return;

    try {
      el.innerHTML = '';        // der Rueckfalltext hat seine Schuldigkeit getan
      el.style.display = 'block';
      var karte = L.map(el, {
        scrollWheelZoom: false, dragging: false, zoomControl: false,
        doubleClickZoom: false, boxZoom: false, keyboard: false, attributionControl: false
      }).setView([lat, lng], 10);

      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 16 }).addTo(karte);

      if (linie.length > 1) {
        L.polyline(linie, { color: '#f4b400', weight: 3, opacity: 0.85 }).addTo(karte);
      }
      L.circleMarker([lat, lng], {
        radius: 8, color: '#232a2e', weight: 3, fillColor: '#f4b400', fillOpacity: 1
      }).addTo(karte).bindTooltip(el.dataset.name || '', { permanent: true, direction: 'right' });
    } catch (err) {
      el.innerHTML = '<p style="padding:18px;font-family:monospace;font-size:12px;color:#857c6c">'
        + 'Karte nicht verfügbar.</p>';
    }
  }

  if ('IntersectionObserver' in window) {
    var nah = new IntersectionObserver(function (eintraege, beobachter) {
      eintraege.forEach(function (e) {
        if (!e.isIntersecting) return;
        baue(e.target);
        beobachter.unobserve(e.target);
      });
    }, { rootMargin: '300px 0px' });
    kartenEl.forEach(function (el) { nah.observe(el); });
  } else {
    kartenEl.forEach(baue);
  }
})();
