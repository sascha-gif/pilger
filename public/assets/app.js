/* Camino-Masterplan — Frontend.
   Jede Änderung geht sofort per fetch() an api.php und liegt danach in der DB. */
(function () {
  'use strict';

  var API = 'api.php';
  var readOnly = document.body.hasAttribute('data-readonly');

  /* ---------- Speicher-Indikator ---------------------------------------- */
  var saver = document.getElementById('saver');
  var saverTimer = null;

  function flash(html, isError) {
    if (!saver) return;
    saver.innerHTML = html;
    saver.classList.toggle('err', !!isError);
    saver.classList.add('show');
    clearTimeout(saverTimer);
    saverTimer = setTimeout(function () {
      saver.classList.remove('show');
    }, isError ? 6000 : 1800);
  }

  function send(payload) {
    return fetch(API, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    }).then(function (res) {
      return res.json().then(function (data) {
        if (!res.ok || !data.ok) {
          throw new Error(data && data.error ? data.error : 'HTTP ' + res.status);
        }
        return data;
      });
    }).catch(function (err) {
      flash('Nicht gespeichert: ' + err.message, true);
      throw err;
    });
  }

  /* ---------- Einblenden beim Scrollen ---------------------------------- */
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); }
      });
    }, { threshold: 0.12 });
    document.querySelectorAll('.reveal').forEach(function (el) { io.observe(el); });
  } else {
    document.querySelectorAll('.reveal').forEach(function (el) { el.classList.add('in'); });
  }

  /* ---------- Packliste -------------------------------------------------- */
  var progressBar = document.querySelector('.progress .bar i');
  var progressCnt = document.querySelector('.progress .cnt');

  function paintProgress(done, total) {
    if (!progressBar || !total) return;
    progressBar.style.width = Math.round((done / total) * 100) + '%';
    if (progressCnt) progressCnt.textContent = done + ' / ' + total + ' gepackt';
  }

  /* Zähler im zugeklappten Kopf eines Bereichs nachführen. */
  function paintCategory(details) {
    if (!details) return;
    var boxes = details.querySelectorAll('input[type=checkbox]');
    var done = 0;
    boxes.forEach(function (b) { if (b.checked) done++; });
    var out = details.querySelector('.catcount');
    if (out) {
      out.textContent = done + ' / ' + boxes.length;
      out.classList.toggle('full', boxes.length > 0 && done === boxes.length);
    }
  }

  document.querySelectorAll('.ptbl input[type=checkbox]').forEach(function (cb) {
    cb.addEventListener('change', function () {
      var row = cb.closest('tr');
      var details = cb.closest('details.pcat');
      var checked = cb.checked;
      row.classList.toggle('done', checked);
      paintCategory(details);

      if (readOnly) {
        cb.checked = !checked;
        row.classList.toggle('done', !checked);
        paintCategory(details);
        return;
      }

      send({ action: 'pack.toggle', id: Number(cb.dataset.id), checked: checked })
        .then(function (data) {
          paintProgress(data.done, data.total);
          flash(checked ? 'Abgehakt · <b>' + data.done + '/' + data.total + '</b>' : 'Häkchen entfernt');
        })
        .catch(function () {
          cb.checked = !checked;
          row.classList.toggle('done', !checked);
          paintCategory(details);
        });
    });
  });

  /* Aufklapper: Zustand je Bereich merken, damit die Seite nach dem Neuladen
     wieder so aussieht wie vorher. Das ist reine Ansicht — nichts für die
     Datenbank, es gilt pro Gerät. */
  var STORE = 'pilger.pack.open';
  var packs = Array.prototype.slice.call(document.querySelectorAll('details.pcat'));
  var toggleAll = document.getElementById('toggleAll');

  function readOpen() {
    try { return JSON.parse(localStorage.getItem(STORE)) || []; } catch (e) { return []; }
  }

  function writeOpen() {
    try {
      localStorage.setItem(STORE, JSON.stringify(
        packs.filter(function (d) { return d.open; }).map(function (d) { return d.dataset.cat; })
      ));
    } catch (e) { /* privater Modus o. ä. — dann eben nicht merken */ }
  }

  function paintToggleAll() {
    if (!toggleAll) return;
    var allOpen = packs.length > 0 && packs.every(function (d) { return d.open; });
    toggleAll.textContent = allOpen ? 'alle zuklappen' : 'alle aufklappen';
    toggleAll.setAttribute('aria-expanded', allOpen ? 'true' : 'false');
  }

  if (packs.length) {
    var open = readOpen();
    packs.forEach(function (d) {
      if (open.indexOf(d.dataset.cat) !== -1) d.open = true;
      d.addEventListener('toggle', function () { writeOpen(); paintToggleAll(); });
    });
    paintToggleAll();

    if (toggleAll) {
      toggleAll.addEventListener('click', function () {
        var allOpen = packs.every(function (d) { return d.open; });
        packs.forEach(function (d) { d.open = !allOpen; });
      });
    }

    // Beim Drucken muss alles sichtbar sein — zugeklappte Bereiche fehlten sonst.
    var reopen = [];
    window.addEventListener('beforeprint', function () {
      reopen = packs.filter(function (d) { return !d.open; });
      reopen.forEach(function (d) { d.open = true; });
    });
    window.addEventListener('afterprint', function () {
      reopen.forEach(function (d) { d.open = false; });
      reopen = [];
    });
  }

  /* ---------- Kosten ----------------------------------------------------- */
  var euro = new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR' });
  var totalOut = document.getElementById('costTotal');

  function localTotal() {
    var sum = 0;
    document.querySelectorAll('input.cost').forEach(function (i) {
      var v = parseFloat(i.value);
      if (!isNaN(v)) sum += v;
    });
    return sum;
  }

  function repaintTotal() {
    if (totalOut) totalOut.textContent = euro.format(localTotal());
  }

  var debounce = {};
  function debounced(key, fn, ms) {
    clearTimeout(debounce[key]);
    debounce[key] = setTimeout(fn, ms || 600);
  }

  document.querySelectorAll('input.cost').forEach(function (input) {
    input.addEventListener('input', function () {
      repaintTotal();
      if (readOnly) return;
      var id = Number(input.dataset.id);
      var raw = input.value.trim();
      var amount = raw === '' ? null : parseFloat(raw.replace(',', '.'));
      if (amount !== null && isNaN(amount)) return;

      debounced('cost' + id, function () {
        send({ action: 'cost.set', id: id, amount: amount }).then(function (data) {
          if (totalOut) totalOut.textContent = data.total_formatted;
          flash('Kosten gespeichert · Summe <b>' + data.total_formatted + '</b>');
        });
      });
    });
  });

  /* ---------- Gewicht ---------------------------------------------------- */
  document.querySelectorAll('input.wt').forEach(function (input) {
    input.addEventListener('input', function () {
      if (readOnly) return;
      var id = Number(input.dataset.id);
      var raw = input.value.trim();
      var actual = raw === '' ? null : parseFloat(raw.replace(',', '.'));
      if (actual !== null && isNaN(actual)) return;

      debounced('wt' + id, function () {
        send({ action: 'weight.set', id: id, actual: actual }).then(function () {
          flash(actual === null ? 'Gewicht geleert' : 'Gewicht gespeichert · <b>' + actual.toFixed(1) + ' kg</b>');
        });
      });
    });
  });

  repaintTotal();

  /* ---------- Karte ------------------------------------------------------ */
  var mapEl = document.getElementById('map');
  var dataEl = document.getElementById('map-data');
  if (!mapEl || !dataEl) return;

  var mapData;
  try {
    mapData = JSON.parse(dataEl.textContent);
  } catch (e) {
    mapEl.innerHTML = '<div style="padding:28px;font-family:monospace;color:#857c6c">Kartendaten konnten nicht gelesen werden. Etappen siehe Liste unten.</div>';
    return;
  }

  if (typeof L === 'undefined') {
    mapEl.innerHTML = '<div style="padding:28px;font-family:monospace;color:#857c6c;line-height:1.5">Karte konnte nicht geladen werden (Internet-Verbindung nötig). Alle Etappen &amp; Orte stehen in der Liste unten.</div>';
    return;
  }

  try {
    var stops = mapData.stops || [];
    var map = L.map('map', { scrollWheelZoom: false }).setView(mapData.center || [42.0, -8.72], mapData.zoom || 8);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 18, attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    // Die Linien kommen aus der Datenbank. Früher wurde die Hauptlinie aus den
    // Etappenorten abgeleitet — als Gerade von Ort zu Ort, was quer über Land
    // schnitt. Gelaufen wird aber die Küste, also ist der Verlauf hinterlegt.
    var overlays = {};
    var bounds = [];

    (mapData.routes || []).forEach(function (r) {
      if (!r.points || r.points.length < 2) return;
      var opts = { color: r.color, weight: r.weight, opacity: 0.9 };
      if (r.dashed) opts.dashArray = '6 7';
      overlays[r.name] = L.polyline(r.points, opts).addTo(map);
      bounds = bounds.concat(r.points);
    });

    // Das Ebenen-Menü lohnt erst, wenn es etwas auszuwählen gibt.
    if (Object.keys(overlays).length > 1) {
      L.control.layers(null, overlays, { collapsed: false }).addTo(map);
    }

    stops.forEach(function (s) {
      var hub = Number(s.hub) === 1;
      L.circleMarker([s.lat, s.lng], {
        radius: hub ? 9 : 7, color: '#fff', weight: 2,
        fillColor: hub ? '#f4b400' : '#1f5d6c', fillOpacity: 1
      }).addTo(map).bindPopup(
        '<div class="pop"><div class="pe">' + s.e + '</div><div class="pn">' + s.n + '</div><div class="pm">' + s.m + '</div></div>'
      );
    });

    if (!bounds.length) {
      bounds = stops.map(function (s) { return [s.lat, s.lng]; });
    }
    if (bounds.length) {
      map.fitBounds(L.latLngBounds(bounds).pad(0.12));
    }
  } catch (err) {
    mapEl.innerHTML = '<div style="padding:28px;font-family:monospace;color:#857c6c">Karte nicht verfügbar. Etappen siehe Liste unten.</div>';
    console.error(err);
  }
})();
