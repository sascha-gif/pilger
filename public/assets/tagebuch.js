/* Tagebuch — Sprachnotizen, Fotos, Zeitleiste.

   Der wichtigste Teil steht ganz oben: die Warteschlange. Auf dem Camino gibt
   es streckenweise kein Netz, und ein Eintrag, der erst beim Hochladen
   entsteht, wäre dann weg. Deshalb landet alles zuerst in der IndexedDB des
   Geräts und geht erst danach raus — sobald wieder Empfang ist, von selbst.
   Nichts hier darf einen Eintrag verlieren. */
(function () {
  'use strict';

  var API = 'api.php';
  var UPLOAD = 'upload.php';

  var listeEl = document.getElementById('tbListe');
  if (!listeEl) return;

  /* ================= Warteschlange (IndexedDB) ========================== */

  var DB_NAME = 'pilger-tagebuch';
  var STORE = 'warteschlange';
  var dbP = null;

  function db() {
    if (dbP) return dbP;
    dbP = new Promise(function (ok, fehler) {
      var anfrage = indexedDB.open(DB_NAME, 1);
      anfrage.onupgradeneeded = function () {
        anfrage.result.createObjectStore(STORE, { keyPath: 'id' });
      };
      anfrage.onsuccess = function () { ok(anfrage.result); };
      anfrage.onerror = function () { fehler(anfrage.error); };
    });
    return dbP;
  }

  function mitStore(modus, fn) {
    return db().then(function (d) {
      return new Promise(function (ok, fehler) {
        var t = d.transaction(STORE, modus);
        var r = fn(t.objectStore(STORE));
        t.oncomplete = function () { ok(r && r.result !== undefined ? r.result : undefined); };
        t.onerror = function () { fehler(t.error); };
      });
    });
  }

  function inDieSchlange(paket) { return mitStore('readwrite', function (s) { return s.put(paket); }); }
  function ausDerSchlange(id)   { return mitStore('readwrite', function (s) { return s.delete(id); }); }
  function schlange()           { return mitStore('readonly',  function (s) { return s.getAll(); }); }

  function kennung() {
    if (window.crypto && crypto.randomUUID) return crypto.randomUUID();
    return 'x' + Date.now().toString(36) + Math.random().toString(36).slice(2, 10);
  }

  /* ================= Anzeige ============================================ */

  var hinweisEl = document.getElementById('tbHinweis');
  var queueEl = document.getElementById('tbQueue');
  var netzEl = document.getElementById('netz');

  function sag(text, istFehler) {
    if (!hinweisEl) return;
    hinweisEl.textContent = text || '';
    hinweisEl.classList.toggle('fehler', !!istFehler);
    if (text && !istFehler) {
      setTimeout(function () { if (hinweisEl.textContent === text) hinweisEl.textContent = ''; }, 4000);
    }
  }

  function roh(text) {
    return String(text === null || text === undefined ? '' : text)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  /* Ab so vielen Fehlversuchen laeuft nichts mehr von selbst. Ein Paket, das
     76-mal gescheitert ist, wird beim 77. Mal auch nicht durchgehen — es zieht
     nur Akku und macht die Liste unleserlich. Der Knopf geht weiter. */
  var AUFGEBEN_AB = 5;

  /* Was gerade in der Warteschlange liegt, nach id — der Verwerfen-Knopf muss
     wissen, ob eine Sprachaufnahme drin steckt. */
  var offeneNachId = {};

  function malSchlange() {
    return schlange().then(function (offen) {
      offeneNachId = {};
      offen.forEach(function (p) { offeneNachId[p.id] = p; });
      if (!queueEl) return offen;
      if (!offen.length) {
        queueEl.hidden = true;
        queueEl.innerHTML = '';
        return offen;
      }

      // Haengt etwas fest? Dann ist „wartet auf Netz" die falsche Auskunft.
      var klemmt = offen.some(function (p) { return (p.versuche || 0) >= 2; });
      var ruht   = offen.some(function (p) { return (p.versuche || 0) >= AUFGEBEN_AB; });

      queueEl.hidden = false;
      queueEl.innerHTML =
        '<b>' + offen.length + (offen.length === 1 ? ' Eintrag liegt' : ' Einträge liegen') +
        ' noch auf diesem Gerät.</b> ' +
        (ruht
          ? 'Nach ' + AUFGEBEN_AB + ' Fehlversuchen wird nicht mehr von selbst weiterprobiert — '
            + 'das kostet nur Akku. Der Grund steht dabei. Verloren ist nichts: '
            + 'alles bleibt gespeichert, auch wenn du die Seite schließt.'
          : klemmt
            ? 'Das Hochladen hat mehrfach nicht geklappt — der Grund steht dabei. '
              + 'Bis dahin ist nichts verloren: alles bleibt gespeichert, auch wenn du die Seite schließt.'
            : 'Sie gehen von selbst raus, sobald wieder Empfang da ist.') +
        '<ul>' + offen.map(function (p) {
          var was = [];
          if (p.audio) was.push('Sprachnotiz');
          if (p.text) was.push('Text');
          if (p.fotos && p.fotos.length) {
            // Wie weit ist es gekommen? Ein Paket mit zehn Bildern laedt Stueck
            // fuer Stueck hoch; ohne diese Zahl sieht ein halb erledigtes Paket
            // aus wie ein gar nicht begonnenes.
            var fertig = (p.fotosFertig || []).filter(Boolean).length;
            was.push(p.fotos.length + ' Foto' + (p.fotos.length > 1 ? 's' : '') +
                     (fertig ? ' (' + fertig + ' schon oben)' : ''));
          }
          return '<li>' + roh(p.etappe || 'ohne Tag') + ' — ' + was.join(' + ') +
                 (p.versuche ? ' <em>' + p.versuche + '× versucht</em>' : '') +
                 ' <button type="button" class="qweg" data-id="' + roh(p.id) +
                 '" title="Dieses Paket verwerfen">verwerfen</button>' +
                 (p.fehler ? '<span class="qgrund">' + roh(p.fehler) + '</span>' : '') +
                 '</li>';
        }).join('') + '</ul>' +
        '<button type="button" class="tb-mini" id="tbNochmal">Jetzt noch einmal versuchen</button>' +
        (offen.length > 1
          ? ' <button type="button" class="tb-mini" id="tbAllesWeg">Alle verwerfen</button>'
          : '');
      return offen;
    }).catch(function () { return []; });
  }

  /* Von Hand anstossen. Der Selbstlauf versucht es alle 45 Sekunden, aber wer
     gerade sieht, dass wieder Balken da sind, will nicht warten. */
  if (queueEl) {
    queueEl.addEventListener('click', function (e) {
      var weg = e.target.closest('.qweg');
      if (weg) {
        // Verwerfen ist ungefaehrlich: die Fotos liegen weiter in der
        // Kamerarolle, nur das wartende Paket verschwindet. Bei einer
        // Sprachnotiz ist die Aufnahme dagegen weg — deshalb wird gefragt.
        var paket = offeneNachId[weg.dataset.id];
        var frage = (paket && paket.audio)
          ? 'Dieses Paket verwerfen? Die Sprachaufnahme darin ist danach weg.'
          : 'Dieses Paket verwerfen? Die Fotos bleiben in deiner Kamerarolle.';
        if (!confirm(frage)) return;
        ausDerSchlange(weg.dataset.id).then(malSchlange)
          .then(function () { sag('Verworfen.'); });
        return;
      }

      if (e.target.closest('#tbAllesWeg')) {
        if (!confirm('Alle wartenden Pakete verwerfen? Fotos bleiben in der Kamerarolle, '
                   + 'Sprachaufnahmen sind danach weg.')) return;
        schlange().then(function (offen) {
          return Promise.all(offen.map(function (p) { return ausDerSchlange(p.id); }));
        }).then(malSchlange).then(function () { sag('Warteschlange geleert.'); });
        return;
      }

      if (!e.target.closest('#tbNochmal')) return;
      if (!navigator.onLine) { sag('Immer noch kein Netz.', true); return; }
      // Von Hand heisst: noch einmal von vorn. Sonst bliebe ein Paket, das
      // schon aufgegeben hatte, auch beim Knopfdruck liegen.
      sag('Wird versucht …');
      schlange().then(function (offen) {
        return Promise.all(offen.map(function (p) {
          p.versuche = 0;
          return inDieSchlange(p);
        }));
      }).then(function () { return abarbeiten(); });
    });
  }

  function netzstand() {
    if (netzEl) netzEl.hidden = navigator.onLine;
  }

  /* ================= Bilder vor dem Hochladen verkleinern =============== */
  /* Ein Handyfoto hat heute 12 bis 48 Megapixel und 3 bis 6 MB. Gespeichert
     wird davon ohnehin nur die 1600-px-Fassung — die vollen Megabyte durchs
     portugiesische Mobilnetz zu schieben kostet also nichts als Zeit, und auf
     halber Strecke bricht die Verbindung ab. Deshalb wird schon auf dem Gerät
     verkleinert: aus 5 MB werden ungefähr 400 KB.

     Zwei Nebenwirkungen, beide erwünscht:
     - Was hier herauskommt, ist ein frischer Blob im Speicher. Der iOS-Fall,
       dass ein in der Warteschlange liegender Verweis auf die Galerie später
       nur noch 0 Bytes liefert, kann damit gar nicht mehr auftreten.
     - HEIC wird zu JPEG. Das kann der Server dann auch verkleinern, statt es
       unverändert durchzureichen, weil GD das Format nicht kennt. */

  var MAX_SENDE   = 2000;          // Serverseitig bleiben 1600 — etwas Reserve.
  var SENDE_GUETE = 0.85;
  var KLEIN_GENUG = 600 * 1024;    // Darunter lohnt das Umrechnen nicht.

  function ueberBildElement(datei) {
    return new Promise(function (ok, nein) {
      var url = URL.createObjectURL(datei);
      var bild = new Image();
      bild.onload  = function () { URL.revokeObjectURL(url); ok(bild); };
      bild.onerror = function () { URL.revokeObjectURL(url); nein(new Error('Bild nicht lesbar')); };
      bild.src = url;
    });
  }

  /* createImageBitmap ist der sparsame Weg, kann aber je nach Browser die
     Optionen nicht oder das Format nicht. Dann eben über ein <img>. */
  function entpacke(datei) {
    if (!window.createImageBitmap) return ueberBildElement(datei);
    try {
      return createImageBitmap(datei, { imageOrientation: 'from-image' })
        .catch(function () { return createImageBitmap(datei); })
        .catch(function () { return ueberBildElement(datei); });
    } catch (e) {
      return ueberBildElement(datei);
    }
  }

  function verkleinere(datei) {
    var istBild = /^image\//.test((datei && datei.type) || '');
    if (!datei || !istBild || datei.size <= KLEIN_GENUG) {
      return Promise.resolve(datei);
    }
    return entpacke(datei).then(function (quelle) {
      // Schon fertig? Dann nicht noch einmal. Ein JPEG, das die lange Kante
      // einhaelt, ist entweder aus diesem Rechenweg gekommen oder war von
      // vornherein klein genug. Es erneut zu kodieren kostet bei jedem
      // Wiederholversuch ein Stueck Bildqualitaet — und gewinnt nichts.
      if ((datei.type || '') === 'image/jpeg'
          && Math.max(quelle.width, quelle.height) <= MAX_SENDE) {
        if (quelle.close) quelle.close();
        return null;
      }
      var faktor = Math.min(1, MAX_SENDE / Math.max(quelle.width, quelle.height));
      var nb = Math.max(1, Math.round(quelle.width * faktor));
      var nh = Math.max(1, Math.round(quelle.height * faktor));
      var leinwand = document.createElement('canvas');
      leinwand.width = nb;
      leinwand.height = nh;
      leinwand.getContext('2d').drawImage(quelle, 0, 0, nb, nh);
      if (quelle.close) quelle.close();
      return new Promise(function (ok) {
        leinwand.toBlob(function (blob) { ok(blob); }, 'image/jpeg', SENDE_GUETE);
      });
    }).then(function (blob) {
      // Nichts gewonnen — dann bleibt das Original, es ist ja schon klein.
      if (!blob || blob.size >= datei.size) return datei;
      var name = (datei.name || 'bild').replace(/\.[^.]+$/, '') + '.jpg';
      try {
        return new File([blob], name, {
          type: 'image/jpeg',
          lastModified: datei.lastModified || Date.now()
        });
      } catch (e) {
        // Ohne File-Konstruktor tut es der Blob auch; der Name geht beim
        // Hochladen ohnehin als eigenes Feld mit.
        return blob;
      }
    }).catch(function () {
      // Kann der Browser das Format nicht lesen (HEIC unter Android), geht
      // das Original raus. Langsam hochladen ist besser als gar nicht.
      // Vermerkt wird es trotzdem: scheitert der Upload danach, ist das der
      // Unterschied zwischen „zu gross" und „Verbindung weg".
      try { datei.nichtVerkleinert = true; } catch (e) { /* eingefroren */ }
      return datei;
    });
  }

  /* Eins nach dem anderen: 15 Handyfotos gleichzeitig zu dekodieren bringt
     Safari auf dem iPhone zuverlässig um. */
  function verkleinereAlle(dateien, melde) {
    var fertig = [];
    return dateien.reduce(function (kette, datei, i) {
      return kette.then(function () {
        if (melde) melde(i + 1, dateien.length);
        return verkleinere(datei).then(function (f) { fertig.push(f); });
      });
    }, Promise.resolve()).then(function () { return fertig; });
  }


  /* ================= Wo ein Bild entstanden ist ========================= */

  /* Die Koordinaten stehen im Bild selbst, im EXIF-Block. Gelesen werden
     muessen sie **hier**, vor dem Verkleinern: die Leinwand malt Pixel ab,
     die Metadaten bleiben dabei liegen. Danach sind sie fort — das Handy hat
     das Original noch, der Server sieht es nie.

     Gelesen wird nur der Anfang der Datei. Der EXIF-Block steht im ersten
     Segment nach dem Dateikopf und ist auf 64 KB begrenzt; ein halbes
     Megabyte ist reichlich und kostet auf dem Telefon nichts.

     Alles hier drin darf scheitern, ohne dass jemand etwas merkt: kommt
     nichts heraus, geht das Bild ohne Koordinaten raus — so wie bisher. */

  var EXIF_BLICK = 512 * 1024;

  /* Groesse je EXIF-Typ. 5 = RATIONAL (zwei LONGs), 2 = ASCII, 4 = LONG. */
  var EXIF_GROESSE = { 1: 1, 2: 1, 3: 2, 4: 4, 5: 8, 6: 1, 7: 1, 8: 2, 9: 4, 10: 8, 11: 4, 12: 8 };

  function exifFelder(d, offset, klein) {
    var felder = {};
    if (offset < 0 || offset + 2 > d.byteLength) return felder;
    var anzahl = d.getUint16(offset, klein);
    // Ein beschaedigter Block kann hier jede Zahl behaupten. Mehr als 512
    // Eintraege schreibt keine Kamera.
    if (anzahl > 512) return felder;
    for (var i = 0; i < anzahl; i++) {
      var e = offset + 2 + i * 12;
      if (e + 12 > d.byteLength) break;
      felder[d.getUint16(e, klein)] = {
        typ: d.getUint16(e + 2, klein),
        n:   d.getUint32(e + 4, klein),
        pos: e + 8
      };
    }
    return felder;
  }

  /* Bis vier Bytes steht der Wert im Eintrag selbst, darueber ein Verweis —
     gerechnet ab dem Anfang des TIFF-Kopfes, nicht ab dem Dateianfang. */
  function exifWertPos(d, basis, f, klein) {
    if (!f) return -1;
    var g = (EXIF_GROESSE[f.typ] || 0) * f.n;
    if (!g) return -1;
    if (g <= 4) return f.pos;
    var o = basis + d.getUint32(f.pos, klein);
    return (o >= 0 && o + g <= d.byteLength) ? o : -1;
  }

  function exifBrueche(d, basis, f, klein) {
    var o = exifWertPos(d, basis, f, klein);
    if (o < 0 || f.typ !== 5) return null;
    var raus = [];
    for (var i = 0; i < f.n; i++) {
      var zaehler = d.getUint32(o + i * 8, klein);
      var nenner  = d.getUint32(o + i * 8 + 4, klein);
      raus.push(nenner ? zaehler / nenner : 0);
    }
    return raus;
  }

  function exifText(d, basis, f, klein) {
    var o = exifWertPos(d, basis, f, klein);
    if (o < 0 || f.typ !== 2) return '';
    var s = '';
    for (var i = 0; i < f.n; i++) {
      var c = d.getUint8(o + i);
      if (!c) break;
      s += String.fromCharCode(c);
    }
    return s.trim();
  }

  /* Grad, Minuten, Sekunden — so steht es im Bild — werden zu einer Zahl.
     Sechs Nachkommastellen sind rund zehn Zentimeter; mehr ist Unfug. */
  function exifGrad(teile, richtung) {
    if (!teile || teile.length < 3) return null;
    var g = teile[0] + teile[1] / 60 + teile[2] / 3600;
    if (richtung === 'S' || richtung === 'W') g = -g;
    if (!isFinite(g)) return null;
    return Math.round(g * 1e6) / 1e6;
  }

  function exifAusTiff(d, basis) {
    if (basis + 8 > d.byteLength) return null;
    var ordnung = d.getUint16(basis);
    var klein;
    if (ordnung === 0x4949)      { klein = true; }
    else if (ordnung === 0x4D4D) { klein = false; }
    else                         { return null; }
    if (d.getUint16(basis + 2, klein) !== 0x002A) return null;

    var ifd0 = exifFelder(d, basis + d.getUint32(basis + 4, klein), klein);
    var raus = { lat: null, lng: null, zeit: null };

    /* ---- Koordinaten (eigener Unterblock, Verweis in Feld 0x8825) ---- */
    var gpsZeiger = ifd0[0x8825];
    if (gpsZeiger && gpsZeiger.typ === 4) {
      var gps = exifFelder(d, basis + d.getUint32(gpsZeiger.pos, klein), klein);
      var lat = exifGrad(exifBrueche(d, basis, gps[2], klein), exifText(d, basis, gps[1], klein));
      var lng = exifGrad(exifBrueche(d, basis, gps[4], klein), exifText(d, basis, gps[3], klein));
      // Genau 0/0 liegt im Atlantik vor Afrika und heisst in der Praxis
      // „kein Empfang gehabt" — das ist keine Koordinate, das ist ein Loch.
      if (lat !== null && lng !== null
          && Math.abs(lat) <= 90 && Math.abs(lng) <= 180
          && (lat !== 0 || lng !== 0)) {
        raus.lat = lat;
        raus.lng = lng;
      }
    }

    /* ---- Aufnahmezeit (Unterblock, Verweis in Feld 0x8769) ----------- */
    var exifZeiger = ifd0[0x8769];
    if (exifZeiger && exifZeiger.typ === 4) {
      var unter = exifFelder(d, basis + d.getUint32(exifZeiger.pos, klein), klein);
      // „2026:09:24 08:31:12" — Doppelpunkte im Datum, so will es das Format.
      var roh = exifText(d, basis, unter[0x9003], klein) || exifText(d, basis, unter[0x9004], klein);
      var m = /^(\d{4}):(\d{2}):(\d{2})[ T](\d{2}):(\d{2}):(\d{2})/.exec(roh);
      if (m) {
        // Dazu der Zeitversatz, falls die Kamera ihn geschrieben hat. Ohne
        // ihn bleibt die Zeit ortlos — besser als gar keine, und der Server
        // liest sie dann als das, was auf der Uhr stand.
        var versatz = exifText(d, basis, unter[0x9011], klein);
        var vm = /^([+-]\d{2}:\d{2})$/.exec(versatz);
        raus.zeit = m[1] + '-' + m[2] + '-' + m[3] + 'T' + m[4] + ':' + m[5] + ':' + m[6]
                  + (vm ? vm[1] : '');
      }
    }

    return (raus.lat !== null || raus.zeit) ? raus : null;
  }

  /* JPEG ist eine Kette von Segmenten. Gesucht ist APP1 (0xFFE1), das mit
     „Exif\0\0" anfaengt. Bei 0xFFDA fangen die Bilddaten an — danach kommt
     nichts mehr, was uns hilft. */
  function exifAusJpeg(puffer) {
    var d = new DataView(puffer);
    if (d.byteLength < 4 || d.getUint16(0) !== 0xFFD8) return null;
    var p = 2;
    while (p + 4 <= d.byteLength) {
      if (d.getUint8(p) !== 0xFF) return null;          // aus dem Tritt geraten
      var marke = d.getUint8(p + 1);
      if (marke === 0xD8 || (marke >= 0xD0 && marke <= 0xD9)) { p += 2; continue; }
      if (marke === 0xDA) return null;                  // Bilddaten
      var laenge = d.getUint16(p + 2);
      if (laenge < 2) return null;
      if (marke === 0xE1 && p + 10 <= d.byteLength
          && d.getUint32(p + 4) === 0x45786966          // „Exif"
          && d.getUint16(p + 8) === 0x0000) {
        return exifAusTiff(d, p + 10);
      }
      p += 2 + laenge;
    }
    return null;
  }

  /* Das Ergebnis je Bild: { lat, lng, zeit } oder null. Nie ein Fehler. */
  function bildHerkunft(datei) {
    if (!datei || !/^image\//.test(datei.type || '') || !datei.slice || !window.FileReader) {
      return Promise.resolve(null);
    }
    return new Promise(function (ok) {
      var leser = new FileReader();
      leser.onload  = function () {
        var raus = null;
        try { raus = exifAusJpeg(leser.result); } catch (e) { raus = null; }
        ok(raus);
      };
      leser.onerror = function () { ok(null); };
      try {
        leser.readAsArrayBuffer(datei.slice(0, EXIF_BLICK));
      } catch (e) {
        ok(null);
      }
    });
  }

  /* Erst lesen, dann verkleinern — in dieser Reihenfolge, sonst ist es weg.
     Heraus kommen drei Listen mit demselben Index: die fertigen Dateien, die
     Herkunft aus dem Bild und bei Videos Standbild und Länge. */
  function bilderVorbereiten(roh, melde) {
    return Promise.all(roh.map(bildHerkunft)).then(function (orte) {
      // Videos eins nach dem anderen: mehrere gleichzeitig zu dekodieren
      // bringt Safari auf dem iPhone genauso um wie bei den Bildern.
      return roh.reduce(function (kette, datei, i) {
        return kette.then(function (bisher) {
          if (!istVideo(datei)) {
            bisher.push(null);
            return bisher;
          }
          if (melde) { melde(i + 1, roh.length, true); }
          return videoDaten(datei).then(function (vd) {
            bisher.push(vd);
            return bisher;
          });
        });
      }, Promise.resolve([])).then(function (videos) {
        return verkleinereAlle(roh, melde).then(function (dateien) {
          return { dateien: dateien, orte: orte, videos: videos };
        });
      });
    });
  }

  /* ================= Videos ============================================= */

  /* Ein Video wird nicht verkleinert — dafür bräuchte es einen Umkodierer, und
     den gibt es im Browser nicht so nebenbei. Was der Browser aber kann: es
     abspielen. Also kann er auch ein Bild daraus abgreifen und die Länge
     ablesen. Beides geht mit hoch; ohne Standbild stünde im Mosaik ein
     schwarzer Kasten, und ohne Breite und Höhe fiele es aus dem Raster.

     Misslingt das hier, ist das kein Grund, das Video nicht hochzuladen. */

  var STAND_KANTE = 1280;

  function istVideo(datei) {
    return !!datei && /^video\//.test(datei.type || '');
  }

  function videoDaten(datei) {
    return new Promise(function (ok) {
      var fertig = false;
      var url = URL.createObjectURL(datei);
      var v = document.createElement('video');
      v.preload = 'metadata';
      v.muted = true;
      v.playsInline = true;

      var raus = function (ergebnis) {
        if (fertig) return;
        fertig = true;
        URL.revokeObjectURL(url);
        v.removeAttribute('src');
        ok(ergebnis);
      };

      // Hängen bleiben darf das nicht — lieber ohne Standbild weiter.
      var wecker = setTimeout(function () { raus({ standbild: null, dauer: null }); }, 15000);

      v.addEventListener('loadedmetadata', function () {
        var dauer = isFinite(v.duration) ? Math.round(v.duration) : null;
        // Eine Sekunde hinein: das allererste Bild ist oft schwarz.
        var stelle = (dauer && dauer > 2) ? 1 : 0;
        v.addEventListener('seeked', function () {
          var ergebnis = { standbild: null, dauer: dauer };
          try {
            var faktor = Math.min(1, STAND_KANTE / Math.max(v.videoWidth, v.videoHeight));
            var leinwand = document.createElement('canvas');
            leinwand.width = Math.max(1, Math.round(v.videoWidth * faktor));
            leinwand.height = Math.max(1, Math.round(v.videoHeight * faktor));
            leinwand.getContext('2d').drawImage(v, 0, 0, leinwand.width, leinwand.height);
            ergebnis.standbild = leinwand.toDataURL('image/jpeg', 0.8);
          } catch (e) { /* dann eben ohne */ }
          clearTimeout(wecker);
          raus(ergebnis);
        }, { once: true });

        try {
          v.currentTime = stelle;
        } catch (e) {
          clearTimeout(wecker);
          raus({ standbild: null, dauer: dauer });
        }
      }, { once: true });

      v.addEventListener('error', function () {
        clearTimeout(wecker);
        raus({ standbild: null, dauer: null });
      }, { once: true });

      v.src = url;
    });
  }

  /* ================= Hochladen ========================================== */

  /* Was schiefging, so genau wie möglich — dieser Text landet in der
     Warteschlange und ist oft das Einzige, woran man erkennt, woran es liegt.
     Deshalb immer mit HTTP-Status, und wenn die Antwort gar kein JSON ist
     (Fehlerseite vom Reverse-Proxy, Anmeldeseite), auch deren Anfang. */
  function antwortLesen(r, wo) {
    return r.text().then(function (text) {
      var d = null;
      try { d = JSON.parse(text); } catch (e) { /* kein JSON */ }

      if (r.ok && d && d.ok) return d;

      var grund;
      if (d && d.error) {
        grund = d.error;
        // Was der Server ueber sich selbst sagt, gehoert dazu — sonst raet man
        // weiter, woran „keine Datei" liegt.
        if (d.diagnose) {
          grund += ' {' + Object.keys(d.diagnose).map(function (k) {
            return k + '=' + d.diagnose[k];
          }).join(' ') + '}';
        }
      } else if (!text) {
        grund = 'leere Antwort';
      } else {
        grund = text.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 90);
      }
      throw new Error(wo + ' HTTP ' + r.status + ' — ' + grund);
    });
  }

  function sendeJson(nutzlast) {
    return fetch(API, {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(nutzlast)
    }).then(function (r) { return antwortLesen(r, 'api'); })
      .catch(function (err) {
        // Kein Netz, abgebrochene Verbindung: fetch wirft ohne Antwort.
        if (err instanceof TypeError) throw new Error('api — keine Verbindung (' + err.message + ')');
        throw err;
      });
  }

  function mengeMB(bytes) {
    if (typeof bytes !== 'number') return '?';
    return (bytes / 1048576).toFixed(1).replace('.', ',') + ' MB';
  }

  function sendeDatei(felder, datei, dateiname) {
    var fd = new FormData();
    Object.keys(felder).forEach(function (k) {
      if (felder[k] !== null && felder[k] !== undefined) fd.append(k, felder[k]);
    });
    fd.append('datei', datei, dateiname);

    // Eine Datei mit 0 Bytes kommt beim Server als „keine Datei" an und
    // scheitert dort ewig. Das passiert, wenn iOS den Zugriff auf ein Bild
    // aus der Galerie verliert, nachdem es in der Warteschlange lag.
    if (datei && typeof datei.size === 'number' && datei.size === 0) {
      return Promise.reject(new Error('upload — die Datei ist auf dem Gerät leer (0 Bytes), '
        + 'iOS hat den Zugriff darauf verloren. Bild noch einmal auswählen.'));
    }

    // Was genau da rausging, gehoert in die Meldung. „Es kam keine Datei an"
    // heisst je nach Groesse etwas voellig anderes: bei 0,4 MB ist die
    // Verbindung schuld, bei 45 MB die Obergrenze des Servers. Ohne die Zahl
    // ist das nicht zu unterscheiden.
    var woher = ' [' + dateiname + ', ' + mengeMB(datei && datei.size)
      + (datei && datei.nichtVerkleinert ? ', nicht verkleinerbar' : '') + ']';

    return fetch(UPLOAD, { method: 'POST', body: fd })
      .then(function (r) { return antwortLesen(r, 'upload'); })
      .catch(function (err) {
        if (err instanceof TypeError) {
          throw new Error('upload — keine Verbindung (' + err.message + ')' + woher);
        }
        throw new Error(err.message + woher);
      });
  }

  /* Ein Foto als JSON schicken — der Rueckfall, wenn multipart klemmt.

     Auf Saschas Server scheitern Fotos mit „Es kam keine Datei an", obwohl sie
     nur 0,7 MB gross sind und Sprachnotizen ueber genau denselben Weg
     durchgehen. Woran es liegt, muss die Diagnose aus upload.php zeigen. Bis
     dahin gibt es einen zweiten Weg, von dem feststeht, dass er funktioniert:
     Texteintraege kommen als JSON an, also kommt ein Bild als Base64 im JSON
     auch an.

     Base64 blaeht die Daten um ein Drittel auf. Deshalb ist das der Rueckfall
     und nicht der Normalweg. */
  function alsBase64(datei) {
    return new Promise(function (ok, nein) {
      var leser = new FileReader();
      leser.onload = function () { ok(String(leser.result)); };
      leser.onerror = function () { nein(new Error('Die Datei liess sich auf dem Gerät nicht lesen.')); };
      leser.readAsDataURL(datei);
    });
  }

  /* Stückweise hochladen.

     Ein Handyvideo sind dreihundert Megabyte. Die passen weder in
     `post_max_size` noch durch ein portugiesisches Mobilnetz am Stück — und
     wenn doch, bricht es bei achtzig Prozent ab und fängt wieder bei null an.
     Also in Brocken von anderthalb Megabyte: jeder ist eine eigene Anfrage,
     die für sich gelingt, und was schon liegt, bleibt liegen. */

  var STUECK = 1.5 * 1024 * 1024;

  function alsBase64Roh(teil) {
    return alsBase64(teil).then(function (url) {
      var komma = url.indexOf(',');
      return komma >= 0 ? url.slice(komma + 1) : url;
    });
  }

  function sendeStueckweise(felder, datei, dateiname, melde) {
    return sendeJson({ action: 'datei.anfang' }).then(function (d) {
      var marke = d.marke;
      var gesamt = datei.size;
      var pos = 0;

      function weiter() {
        if (pos >= gesamt) {
          var schluss = { action: 'datei.fertig', marke: marke, name: dateiname };
          Object.keys(felder).forEach(function (k) {
            if (felder[k] !== null && felder[k] !== undefined) schluss[k] = felder[k];
          });
          return sendeJson(schluss);
        }
        var ende = Math.min(pos + STUECK, gesamt);
        return alsBase64Roh(datei.slice(pos, ende)).then(function (roh) {
          return sendeJson({ action: 'datei.stueck', marke: marke, daten: roh });
        }).then(function () {
          pos = ende;
          if (melde) { melde(Math.round(pos / gesamt * 100)); }
          return weiter();
        });
      }
      return weiter();
    });
  }

  function sendeFotoAlsJson(felder, datei, dateiname) {
    return alsBase64(datei).then(function (daten) {
      return sendeJson({
        action: 'foto.daten',
        stage: felder.stage || '',
        entry: felder.entry || '',
        client_id: felder.client_id || '',
        aufgenommen: felder.aufgenommen || '',
        lat: felder.lat || '',
        lng: felder.lng || '',
        name: dateiname,
        daten: daten
      });
    });
  }

  /* Ein Paket abarbeiten. Was durch ist, wird im Paket vermerkt — bricht die
     Verbindung mitten in einem Paket mit fünf Fotos ab, fängt der nächste
     Versuch nicht wieder bei null an. */
  function schickePaket(p) {
    // Der Zwischenstand ist eine Bequemlichkeit, keine Bedingung: er sorgt nur
    // dafuer, dass ein abgebrochener Upload nicht wieder bei null anfaengt.
    // Faellt die Warteschlange aus, laeuft der Upload trotzdem weiter.
    var merke = function (paket) { return inDieSchlange(paket).catch(function () {}); };

    var kette = Promise.resolve(p.entryId || null);

    if (p.audio && !p.audioFertig) {
      kette = kette.then(function () {
        return sendeDatei({
          art: 'audio', stage: p.stage, tag: p.tag, client_id: p.id, sekunden: p.sekunden
        }, p.audio, 'notiz.' + (p.audioEndung || 'webm')).then(function (d) {
          p.audioFertig = true;
          p.entryId = d.eintrag && d.eintrag.id;
          return merke(p).then(function () { return p.entryId; });
        });
      });
    }

    if (p.text && !p.textFertig) {
      kette = kette.then(function (entryId) {
        return sendeJson({
          action: 'tagebuch.text', stage: p.stage, tag: p.tag,
          text: p.text, client_id: p.id + '-t'
        }).then(function (d) {
          p.textFertig = true;
          if (!entryId) p.entryId = d.eintrag && d.eintrag.id;
          return merke(p).then(function () { return p.entryId; });
        });
      });
    }

    (p.fotos || []).forEach(function (datei, i) {
      kette = kette.then(function (entryId) {
        p.fotosFertig = p.fotosFertig || [];
        if (p.fotosFertig[i]) return entryId;
        // Auch hier noch einmal verkleinern. Normalerweise ist das Bild schon
        // bei der Auswahl kleingerechnet und faellt sofort durch — aber Pakete,
        // die vom Gerät noch aus der Zeit davor stammen, liegen mit dem vollen
        // Handyfoto in der Warteschlange. Die sollen nicht ewig weiterscheitern.
        return verkleinere(datei).then(function (klein) {
          p.fotos[i] = klein;
          // Die Zeit aus dem Bild ist die bessere: `lastModified` ist das,
          // was das Dateisystem zuletzt angefasst hat, und das kann das
          // Kopieren aus der Galerie gewesen sein.
          var ort = (p.orte || [])[i] || null;
          var video = istVideo(klein);
          var felder = {
            art: video ? 'video' : 'foto', stage: p.stage, entry: entryId || '',
            client_id: p.id + '-f' + i,
            aufgenommen: (ort && ort.zeit)
              ? ort.zeit
              : (klein.lastModified ? new Date(klein.lastModified).toISOString() : ''),
            lat: (ort && ort.lat !== null && ort.lat !== undefined) ? String(ort.lat) : '',
            lng: (ort && ort.lng !== null && ort.lng !== undefined) ? String(ort.lng) : ''
          };
          var name = klein.name || ('bild' + i + (video ? '.mp4' : '.jpg'));

          /* Videos gehen immer stückweise — am Stück passen sie weder in
             `post_max_size` noch durch ein wackliges Netz. Standbild und
             Länge hat das Gerät beim Auswählen schon abgegriffen. */
          if (video) {
            var vd = (p.videos || [])[i] || {};
            if (vd.standbild) { felder.standbild = vd.standbild; }
            if (vd.dauer !== null && vd.dauer !== undefined) { felder.dauer = String(vd.dauer); }
            return sendeStueckweise(felder, klein, name, function (prozent) {
              sag('Video geht raus … ' + prozent + ' %');
            });
          }

          return sendeDatei(felder, klein, name).catch(function (err) {
            // Der Server hat die Anfrage bekommen, aber keine Datei darin
            // gefunden. Dann ist der Weg kaputt und nicht die Datei — also
            // denselben Inhalt noch einmal, diesmal als JSON.
            if (!/Es kam keine Datei an/.test(err.message || '')) {
              throw err;
            }
            sag('Der übliche Weg klemmt — Bild geht als JSON raus …');
            return sendeFotoAlsJson(felder, klein, name);
          });
        }).then(function () {
          p.fotosFertig[i] = true;
          return merke(p).then(function () { return entryId; });
        });
      });
    });

    return kette.then(function () {
      /* Der Tagblock, in dem der Eintrag gleich auftaucht, muss offen sein.
         Sonst laedt die Seite neu und der frisch hochgeladene Eintrag steckt
         hinter einer zugeklappten Ueberschrift — hochgeladen und trotzdem
         nicht zu finden. */
      if (p.tag) {
        document.dispatchEvent(new CustomEvent('tag-aufklappen', { detail: { tag: p.tag } }));
      }
      return ausDerSchlange(p.id).catch(function () {});
    });
  }

  /* Ein Paket loswerden — auf dem sicheren Weg, und wenn der versperrt ist,
     auf dem direkten.

     Der sichere Weg ist die Warteschlange: erst auf dem Geraet merken, dann
     hochladen. Faellt sie aus — in Safaris privatem Fenster bekommt die
     IndexedDB keinen Platz, auf einem vollen Geraet auch nicht —, ist das kein
     Grund, den Eintrag wegzuwerfen. Solange Netz da ist, geht er eben direkt
     raus. Nur ohne Netz *und* ohne Zwischenspeicher ist wirklich Schluss.

     Bewusst eine Funktion fuer alle Wege: neuer Eintrag, nachgereichte Bilder,
     alles. Als das zwei getrennte Stellen waren, hatte die eine den Rueckfall
     und die andere nicht — und nachgereichte Bilder gingen im privaten Fenster
     wortlos verloren. */
  function stelleEin(paket, gelungen, direktGelungen) {
    return inDieSchlange(paket).then(function () {
      if (gelungen) gelungen();
      return malSchlange().then(abarbeiten);
    }).catch(function (err) {
      if (!navigator.onLine) {
        sag('Kein Netz, und dieses Gerät lässt nichts zwischenspeichern (privates Fenster?). '
          + 'Bitte den Text kopieren, bevor du die Seite verlässt!', true);
        throw err;
      }
      sag('Zwischenspeicher streikt — wird direkt hochgeladen …');
      return schickePaket(paket).then(function () {
        if (direktGelungen) { direktGelungen(); } else if (gelungen) { gelungen(); }
        sag('Hochgeladen. Die Seite lädt gleich neu.');
        setTimeout(function () { location.reload(); }, 900);
      }).catch(function (zweiter) {
        sag('Hochladen fehlgeschlagen und Zwischenspeichern geht auf diesem Gerät nicht. '
          + 'Bitte den Text kopieren! (' + (zweiter && zweiter.message ? zweiter.message : 'unbekannt') + ')', true);
        throw zweiter;
      });
    });
  }

  var laeuft = false;

  function abarbeiten(stillschweigend) {
    if (laeuft || !navigator.onLine) return Promise.resolve();
    laeuft = true;

    return schlange().then(function (alle) {
      // Aufgegebene Pakete laufen im Selbstlauf nicht mehr mit. Von Hand
      // schon — der Knopf setzt den Zaehler vorher zurueck.
      var offen = stillschweigend
        ? alle.filter(function (p) { return (p.versuche || 0) < AUFGEBEN_AB; })
        : alle;
      if (!offen.length) return null;
      return offen.reduce(function (kette, p) {
        return kette.then(function () {
          return schickePaket(p).catch(function (err) {
            p.versuche = (p.versuche || 0) + 1;
            p.fehler = err.message;
            return inDieSchlange(p);
          });
        });
      }, Promise.resolve()).then(function () { return offen.length; });
    }).then(function (anzahl) {
      laeuft = false;
      return malSchlange().then(function (rest) {
        if (anzahl && !rest.length) {
          if (!stillschweigend) sag('Hochgeladen. Die Seite lädt gleich neu.');
          setTimeout(function () { location.reload(); }, 900);
        } else if (rest.length && !stillschweigend) {
          sag('Noch ' + rest.length + ' wartend — es wird weiter versucht.', true);
        }
      });
    }).catch(function () { laeuft = false; });
  }

  window.addEventListener('online', function () { netzstand(); abarbeiten(true); });
  window.addEventListener('offline', netzstand);
  setInterval(function () { abarbeiten(true); }, 45000);
  netzstand();
  malSchlange().then(function (offen) { if (offen.length) abarbeiten(true); });

  /* ================= Aufnehmen ========================================== */

  var knopf = document.getElementById('tbAufnahme');
  var uhr = document.getElementById('tbUhr');
  var recorder = null;
  var brocken = [];
  var startZeit = 0;
  var uhrTimer = null;
  var fertigeAufnahme = null;
  var stopWarter = [];

  /* Die Aufnahme beenden und warten, bis der Brocken wirklich da ist. Ohne das
     ginge ein Tippen auf „Eintrag speichern" bei laufender Aufnahme ins Leere:
     `fertigeAufnahme` entsteht erst in `onstop`, und das kommt spaeter. */
  function beendeAufnahme() {
    if (!recorder || recorder.state !== 'recording') return Promise.resolve();
    return new Promise(function (ok) {
      stopWarter.push(ok);
      recorder.stop();
      recorder = null;
    });
  }

  function formatZeit(s) {
    return Math.floor(s / 60) + ':' + String(Math.floor(s % 60)).padStart(2, '0');
  }

  function typWaehlen() {
    var kandidaten = ['audio/webm;codecs=opus', 'audio/webm', 'audio/mp4', 'audio/ogg;codecs=opus'];
    for (var i = 0; i < kandidaten.length; i++) {
      if (window.MediaRecorder && MediaRecorder.isTypeSupported(kandidaten[i])) return kandidaten[i];
    }
    return '';
  }

  function endungZu(typ) {
    if (typ.indexOf('mp4') !== -1) return 'm4a';
    if (typ.indexOf('ogg') !== -1) return 'ogg';
    return 'webm';
  }

  function starteAufnahme() {
    if (!navigator.mediaDevices || !window.MediaRecorder) {
      sag('Dieses Gerät kann im Browser nicht aufnehmen — tippen geht aber.', true);
      return;
    }
    navigator.mediaDevices.getUserMedia({ audio: true }).then(function (spur) {
      var typ = typWaehlen();
      // Eigene Referenz. Frueher stand hier `recorder.mimeType` — und weil das
      // Beenden `recorder = null` setzt, bevor `onstop` an der Reihe ist, warf
      // der Handler jedes Mal und `fertigeAufnahme` blieb leer. Jede Aufnahme
      // war damit weg, sobald man sie beendete.
      var rec = new MediaRecorder(spur, typ ? { mimeType: typ } : undefined);
      recorder = rec;
      brocken = [];
      rec.ondataavailable = function (e) { if (e.data && e.data.size) brocken.push(e.data); };
      rec.onstop = function () {
        spur.getTracks().forEach(function (t) { t.stop(); });
        var typJetzt = rec.mimeType || typ || 'audio/webm';
        fertigeAufnahme = {
          blob: new Blob(brocken, { type: typJetzt }),
          sekunden: Math.round((Date.now() - startZeit) / 1000),
          endung: endungZu(typJetzt)
        };
        knopf.classList.remove('an');
        knopf.querySelector('.beschriftung').textContent =
          'Aufnahme ' + formatZeit(fertigeAufnahme.sekunden) + ' — noch nicht gespeichert';
        knopf.classList.add('fertig');
        clearInterval(uhrTimer);
        uhr.hidden = true;

        // Wer waehrend der Aufnahme auf „Eintrag speichern" tippt, wartet hier.
        var warter = stopWarter;
        stopWarter = [];
        warter.forEach(function (f) { f(); });
      };
      rec.start();
      startZeit = Date.now();
      knopf.classList.add('an');
      knopf.classList.remove('fertig');
      knopf.querySelector('.beschriftung').textContent = 'Aufnahme läuft — zum Beenden tippen';
      uhr.hidden = false;
      uhrTimer = setInterval(function () {
        uhr.textContent = formatZeit((Date.now() - startZeit) / 1000);
      }, 500);
    }).catch(function () {
      sag('Kein Zugriff aufs Mikrofon. In den Browser-Einstellungen erlauben — oder tippen.', true);
    });
  }

  if (knopf) {
    knopf.addEventListener('click', function () {
      if (recorder && recorder.state === 'recording') {
        recorder.stop();
        recorder = null;
      } else if (fertigeAufnahme) {
        // Zweiter Klick auf eine fertige Aufnahme: verwerfen und neu.
        fertigeAufnahme = null;
        knopf.classList.remove('fertig');
        knopf.querySelector('.beschriftung').textContent = 'Sprachnotiz aufnehmen';
      } else {
        starteAufnahme();
      }
    });
  }

  /* ================= Speichern ========================================== */

  var fotoEingabe = document.getElementById('tbFotos');
  var wahlEl = document.getElementById('tbWahl');
  var gewaehlteFotos = [];
  /* Merkmale der Originale, Index fuer Index zu `gewaehlteFotos`. Gebraucht
     wird das, weil in `gewaehlteFotos` die verkleinerte Fassung liegt — die
     hat eine andere Groesse als das, was aus der Galerie kam. */
  var wahlQuellen = [];

  /* Zwei Bilder sind dasselbe, wenn Name, Groesse und Zeitstempel stimmen.
     Wer die Galerie zweimal oeffnet und dasselbe Foto nochmal antippt, soll es
     nicht doppelt hochladen. */
  function schonDrin(datei) {
    return wahlQuellen.some(function (a) {
      return a.name === datei.name && a.size === datei.size && a.lastModified === datei.lastModified;
    });
  }

  function malWahl() {
    if (!wahlEl) return;
    if (!gewaehlteFotos.length) {
      wahlEl.hidden = true;
      wahlEl.innerHTML = '';
      return;
    }
    wahlEl.hidden = false;
    wahlEl.innerHTML = '';

    var kopf = document.createElement('p');
    kopf.className = 'tb-wahl-kopf';
    kopf.textContent = gewaehlteFotos.length + (gewaehlteFotos.length === 1 ? ' Bild' : ' Bilder') +
      ' ausgewählt — mit „Eintrag speichern" übernehmen.';
    wahlEl.appendChild(kopf);

    /* Wie viele davon wissen, wo sie entstanden sind. Das steht hier, weil man
       es sonst erst im Journal merkt — und dann ist das Bild schon oben und
       das Original vielleicht vom Telefon geloescht. Steht hier „0 mit Ort",
       ist entweder der Ort am Handy aus oder die Bilder kommen aus einer App,
       die ihn wegschneidet. */
    var mitOrt = wahlQuellen.filter(function (q) { return q.ort && q.ort.lat !== null; }).length;
    var ortszeile = document.createElement('p');
    ortszeile.className = 'tb-wahl-ort' + (mitOrt ? '' : ' leer');
    ortszeile.textContent = mitOrt === gewaehlteFotos.length
      ? (gewaehlteFotos.length === 1 ? 'Mit Ort — landet auf der Tageskarte.'
                                     : 'Alle mit Ort — landen auf der Tageskarte.')
      : (mitOrt === 0 ? 'Keins davon hat einen Ort im Bild.'
                      : mitOrt + ' von ' + gewaehlteFotos.length + ' mit Ort.');
    wahlEl.appendChild(ortszeile);

    var strecke = document.createElement('div');
    strecke.className = 'tb-wahl-bilder';
    gewaehlteFotos.forEach(function (datei, i) {
      var kachel = document.createElement('figure');
      kachel.className = 'bk vorschau';
      var bild = document.createElement('img');
      bild.alt = datei.name || 'Bild';

      /* Ein Video hat kein Bild, das ein <img> anzeigen könnte — dafür liegt
         das Standbild schon bereit, das beim Auswählen abgegriffen wurde.
         Kam keins zustande, bleibt die Kachel leer und trägt nur die Marke. */
      var vd = (wahlQuellen[i] || {}).video;
      if (istVideo(datei)) {
        kachel.classList.add('istvideo');
        if (vd && vd.dauer) {
          kachel.dataset.dauer = Math.floor(vd.dauer / 60) + ':'
            + String(vd.dauer % 60).padStart(2, '0');
        }
        if (vd && vd.standbild) { bild.src = vd.standbild; }
      } else {
        // Objekt-URL wieder freigeben, sonst haengen 30 Handyfotos im Speicher.
        var url = URL.createObjectURL(datei);
        bild.src = url;
        bild.onload = function () { URL.revokeObjectURL(url); };
      }
      var weg = document.createElement('button');
      weg.type = 'button';
      weg.className = 'bk-weg';
      weg.title = 'Aus der Auswahl nehmen';
      weg.textContent = '\u00d7';
      weg.addEventListener('click', function () {
        gewaehlteFotos.splice(i, 1);
        wahlQuellen.splice(i, 1);
        malWahl();
      });
      kachel.appendChild(bild);
      kachel.appendChild(weg);
      strecke.appendChild(kachel);
    });
    wahlEl.appendChild(strecke);
  }

  if (fotoEingabe) {
    fotoEingabe.addEventListener('change', function () {
      // Sammeln, nicht ersetzen: am Handy kommt das zweite Bild aus einem
      // zweiten Griff in die Galerie, und der erste darf davon nicht weg sein.
      var roh = Array.prototype.slice.call(fotoEingabe.files || []);
      // Zuruecksetzen, damit dasselbe Bild erneut ausgewaehlt werden koennte
      // und `change` beim naechsten Mal ueberhaupt wieder feuert.
      fotoEingabe.value = '';

      var neu = roh.filter(function (datei) { return !schonDrin(datei); });
      if (!neu.length) { sag('Diese Bilder sind schon in der Auswahl.'); return; }

      // Die Merkmale gleich vormerken, sonst gilt dasselbe Bild waehrend des
      // Verkleinerns noch als neu.
      var abIndex = wahlQuellen.length;
      neu.forEach(function (datei) {
        wahlQuellen.push({ name: datei.name, size: datei.size, lastModified: datei.lastModified,
                           ort: null, video: null });
      });

      bilderVorbereiten(neu, function (i, von, vid) {
        sag(vid ? 'Video wird vorbereitet … (' + i + ' von ' + von + ')'
                : (von === 1 ? 'Bild wird vorbereitet …'
                             : 'Bilder werden vorbereitet … (' + i + ' von ' + von + ')'));
      }).then(function (fertig) {
        fertig.dateien.forEach(function (datei) { gewaehlteFotos.push(datei); });
        fertig.videos.forEach(function (vd, i) {
          if (wahlQuellen[abIndex + i]) wahlQuellen[abIndex + i].video = vd;
        });
        // Die Herkunft gehoert zum Original, nicht zur verkleinerten Fassung —
        // deshalb liegt sie neben der Auswahl und wird beim Entfernen eines
        // Bildes mit weggeschnitten.
        fertig.orte.forEach(function (ort, i) {
          if (wahlQuellen[abIndex + i]) wahlQuellen[abIndex + i].ort = ort;
        });
        malWahl();
        sag(fertig.dateien.length === 1 ? 'Bild bereit.' : fertig.dateien.length + ' Bilder bereit.');
      });
    });
  }

  var speichern = document.getElementById('tbSpeichern');
  var textFeld = document.getElementById('tbText');
  var tagWahl = document.getElementById('tbTag');

  if (tagWahl) {
    tagWahl.addEventListener('change', malTagZiel);
    malTagZiel();
  }

  if (speichern) {
    speichern.addEventListener('click', function () {
      // Laeuft die Aufnahme noch, wird sie erst beendet — sonst waere sie beim
      // Packen des Pakets schlicht nicht da und stillschweigend verloren.
      var laeuftNoch = recorder && recorder.state === 'recording';
      if (laeuftNoch) sag('Aufnahme wird beendet …');
      speichern.disabled = true;
      // Getrennte Faenger, und zwar mit Absicht: ein `.catch` hinter
      // `.then(packUndSpeichere)` wuerde auch dessen eigene Fehler fangen und
      // den Eintrag ein zweites Mal abschicken.
      beendeAufnahme()
        .catch(function () { /* dann eben ohne Ton */ })
        .then(packUndSpeichere)
        .catch(function () { /* gemeldet ist es schon */ })
        .then(function () { speichern.disabled = false; });
    });
  }

  /* Das Datum, das auf diesem Gerät gerade gilt — nicht das der Serveruhr,
     und ohne Umweg über UTC (toISOString() wirft einen abends geschriebenen
     Eintrag sonst auf den nächsten Tag). */
  function ortsDatum() {
    var d = new Date();
    var m = String(d.getMonth() + 1);
    var t = String(d.getDate());
    return d.getFullYear() + '-' + (m.length < 2 ? '0' + m : m) + '-' + (t.length < 2 ? '0' + t : t);
  }

  /* Welcher Tag gehoert zum Eintrag?

     Normalerweise der, an dem geschrieben wird. Das Datum aus der Etappe zu
     nehmen geht schief, sobald eine Etappe mehr als einen Tag abdeckt: das
     Basislager Porto steht auf den 18.09., eine Notiz vom 17. bekaeme damit
     die Zahlen des falschen Tages.

     Liegt der Tag der gewaehlten Etappe dagegen in der Vergangenheit, hat er
     ihn bewusst ausgesucht — dann gilt der. Genau dafuer gibt es die Gruppe
     „Gerade abgehakt". */
  function tagDesEintrags(option) {
    var heute = ortsDatum();
    var ausEtappe = option && option.dataset ? (option.dataset.tag || '') : '';
    return (ausEtappe && ausEtappe < heute) ? ausEtappe : heute;
  }

  /* Hinschreiben, welchen Tag der Eintrag bekommt. Ohne das ist es eine
     Ueberraschung: waehlt man abends eine abgehakte Etappe, landet die Notiz
     auf gestern — richtig so, aber dann sucht man sie oben und findet sie
     nicht, weil der Tagblock von gestern zugeklappt ist. */
  var tagZielEl = document.getElementById('tbTagZiel');
  var WOCHENTAGE = ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'];
  var MONATE = ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni',
                'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];

  function malTagZiel() {
    if (!tagZielEl || !tagWahl) return;
    var tag = tagDesEintrags(tagWahl.options[tagWahl.selectedIndex]);
    var teile = tag.split('-');
    // Mittags bauen, damit keine Zeitzone den Tag verschiebt.
    var d = new Date(Number(teile[0]), Number(teile[1]) - 1, Number(teile[2]), 12);
    var heute = tag === ortsDatum();
    tagZielEl.hidden = false;
    tagZielEl.innerHTML = 'Kommt auf <b>' + WOCHENTAGE[d.getDay()] + ', ' + d.getDate() + '. '
      + MONATE[d.getMonth()] + '</b>' + (heute ? ' (heute)' : '');
  }

  function packUndSpeichere() {
      var text = (textFeld.value || '').trim();
      if (!text && !fertigeAufnahme && !gewaehlteFotos.length) {
        sag('Da ist noch nichts zum Speichern.', true);
        return;
      }

      var gewaehlt = tagWahl.options[tagWahl.selectedIndex];
      var paket = {
        id: kennung(),
        stage: tagWahl.value,
        tag: tagDesEintrags(gewaehlt),
        etappe: gewaehlt ? gewaehlt.textContent.trim() : '',
        text: text || null,
        audio: fertigeAufnahme ? fertigeAufnahme.blob : null,
        sekunden: fertigeAufnahme ? fertigeAufnahme.sekunden : null,
        audioEndung: fertigeAufnahme ? fertigeAufnahme.endung : null,
        fotos: gewaehlteFotos,
        orte: wahlQuellen.map(function (q) { return q.ort || null; }),
        videos: wahlQuellen.map(function (q) { return q.video || null; }),
        erstellt: new Date().toISOString(),
        versuche: 0
      };

      var aufraeumen = function () {
        textFeld.value = '';
        gewaehlteFotos = [];
        wahlQuellen = [];
        if (fotoEingabe) fotoEingabe.value = '';
        malWahl();
        fertigeAufnahme = null;
        if (knopf) {
          knopf.classList.remove('fertig');
          knopf.querySelector('.beschriftung').textContent = 'Sprachnotiz aufnehmen';
        }
      };

      return stelleEin(paket, function () {
        aufraeumen();
        sag(navigator.onLine ? 'Gespeichert — wird hochgeladen.' : 'Auf dem Gerät gemerkt — geht raus, sobald Netz da ist.');
      }, aufraeumen);
  }

  /* ================= Lesen oder bearbeiten =============================== */
  /* Der Normalzustand ist Lesen — die Seite zeigt Sascha auch seiner Familie.
     Erst dieser Knopf holt Löschkreuze, Eingabefelder und Knopfleisten hervor.
     Bewusst nicht gemerkt: nach jedem Neuladen ist wieder Tagebuch, nicht
     Werkstatt. */
  var abschnitt = document.getElementById('tagebuch');
  var editKnopf = document.getElementById('tbEdit');

  if (abschnitt && editKnopf) {
    editKnopf.addEventListener('click', function () {
      var an = abschnitt.getAttribute('data-edit') !== '1';
      if (an) { abschnitt.setAttribute('data-edit', '1'); }
      else { abschnitt.removeAttribute('data-edit'); }
      editKnopf.setAttribute('aria-pressed', an ? 'true' : 'false');
      editKnopf.textContent = an ? 'Fertig' : 'Bearbeiten';
    });
  }

  /* ================= Einträge bearbeiten ================================ */

  listeEl.addEventListener('click', function (e) {
    var karte = e.target.closest('.tbe');
    if (!karte) return;
    var id = Number(karte.dataset.id);

    if (e.target.classList.contains('veredeln')) {
      // Der Ausbau ueberschreibt die ausgebaute Fassung — das Original bleibt,
      // eine von Hand nachgebesserte Fassung nicht. Deshalb hier gefragt.
      if (e.target.classList.contains('erneut') &&
          !confirm('Neu ausbauen? Der Text wird wieder aus dem Original erzeugt — eigene Änderungen daran gehen verloren. Das Original selbst bleibt.')) {
        return;
      }
      var beschriftung = e.target.textContent;
      e.target.disabled = true;
      e.target.textContent = 'läuft …';
      sendeJson({ action: 'tagebuch.veredeln', id: id })
        .then(function () { location.reload(); })
        .catch(function (err) {
          e.target.disabled = false;
          e.target.textContent = beschriftung;
          sag(err.message, true);
        });
      return;
    }

    if (e.target.classList.contains('bearbeiten')) {
      var feld = karte.querySelector('.tbtext');
      var alt = feld.innerText;
      var box = document.createElement('textarea');
      box.className = 'tb-bearbeitung';
      box.value = alt;
      box.rows = Math.max(3, Math.ceil(alt.length / 60));
      feld.replaceWith(box);
      box.focus();
      var sichern = document.createElement('button');
      sichern.type = 'button';
      sichern.className = 'tb-mini';
      sichern.textContent = 'Übernehmen';
      box.after(sichern);
      sichern.addEventListener('click', function () {
        sendeJson({ action: 'tagebuch.aendern', id: id, text: box.value })
          .then(function () { location.reload(); })
          .catch(function (err) { sag(err.message, true); });
      });
      e.target.disabled = true;
      return;
    }

    if (e.target.classList.contains('loeschen')) {
      if (!confirm('Diesen Eintrag samt Aufnahme und Bildern löschen?')) return;
      sendeJson({ action: 'tagebuch.loeschen', id: id })
        .then(function () { karte.remove(); })
        .catch(function (err) { sag(err.message, true); });
    }
  });

  /* ================= Bilder zu einem bestehenden Eintrag ================ */
  /* Ein Tagebucheintrag waechst ueber den Abend: erst die Sprachnotiz, dann
     die Bilder, die man beim Durchsehen noch findet. Der Weg geht durch
     dieselbe Warteschlange wie alles andere — auch nachgereichte Bilder
     duerfen bei fehlendem Netz nicht verloren gehen. */
  listeEl.addEventListener('change', function (e) {
    var eingabe = e.target.closest('.tbe-fotos');
    if (!eingabe) return;
    var karte = eingabe.closest('.tbe');
    if (!karte) return;

    var roh = Array.prototype.slice.call(eingabe.files || []);
    eingabe.value = '';
    if (!roh.length) return;

    eingabe.disabled = true;
    bilderVorbereiten(roh, function (i, von) {
      sag(von === 1 ? 'Bild wird vorbereitet …' : 'Bilder werden vorbereitet … (' + i + ' von ' + von + ')');
    }).then(function (fertig) {
      eingabe.disabled = false;
      schickeNachtrag(karte, fertig.dateien, fertig.orte, fertig.videos);
    });
  });

  function schickeNachtrag(karte, dateien, orte, videos) {
    stelleEin({
      id: kennung(),
      entryId: Number(karte.dataset.id),
      stage: karte.dataset.stage || '',
      etappe: (karte.querySelector('.tbuhr') || {}).textContent || '',
      text: null,
      audio: null,
      fotos: dateien,
      orte: orte || [],
      videos: videos || [],
      erstellt: new Date().toISOString(),
      versuche: 0
    }, function () {
      sag(navigator.onLine
        ? dateien.length + (dateien.length === 1 ? ' Bild wird hochgeladen.' : ' Bilder werden hochgeladen.')
        : 'Auf dem Gerät gemerkt — geht raus, sobald Netz da ist.');
    }).catch(function () { /* gemeldet ist es schon */ });
  }

  /* ================= Tag eines Eintrags berichtigen ===================== */
  /* Der Tag entscheidet, welche Zahlen der Uhr beim Ausbau mitkommen. Eine
     Notiz, die morgens über gestern gesprochen wird, gehoert auf gestern —
     sonst rechnet der Ausbau mit einem Tag, der gerade erst angefangen hat. */
  listeEl.addEventListener('change', function (e) {
    var feld = e.target.closest('.tbe-tag');
    if (!feld) return;
    var karte = feld.closest('.tbe');
    if (!karte || !feld.value) return;

    feld.disabled = true;
    sendeJson({ action: 'tagebuch.tag', id: Number(karte.dataset.id), tag: feld.value })
      .then(function () {
        sag('Tag geändert. Zum Neuausbauen „Neu ausbauen" drücken.');
        setTimeout(function () { location.reload(); }, 1200);
      })
      .catch(function (err) { feld.disabled = false; sag(err.message, true); });
  });

  /* ================= Bilder beschriften und löschen ===================== */
  /* Die Kacheln stehen an zwei Stellen — an den Einträgen und in der
     Zeitleiste. Ein Zuhörer am Dokument erwischt beide, auch die, die nach
     einem Upload dazukommen. */
  document.addEventListener('click', function (e) {
    var weg = e.target.closest('.bk-weg');
    if (!weg) return;
    var kachel = weg.closest('.bk');
    var id = Number(kachel.dataset.foto);
    if (!confirm('Dieses Bild löschen?')) return;
    weg.disabled = true;
    sendeJson({ action: 'foto.loeschen', id: id })
      .then(function () {
        // Auch die zweite Kachel desselben Bildes verschwindet.
        document.querySelectorAll('.bk[data-foto="' + id + '"]').forEach(function (k) { k.remove(); });
        sag('Bild gelöscht.');
      })
      .catch(function (err) { weg.disabled = false; sag(err.message, true); });
  });

  var textUhren = {};
  document.addEventListener('input', function (e) {
    var feld = e.target.closest('.bk-text');
    if (!feld) return;
    var id = Number(feld.closest('.bk').dataset.foto);
    clearTimeout(textUhren[id]);
    textUhren[id] = setTimeout(function () {
      sendeJson({ action: 'foto.bildtext', id: id, text: feld.value })
        .then(function () {
          document.querySelectorAll('.bk[data-foto="' + id + '"] .bk-text').forEach(function (anderes) {
            if (anderes !== feld) anderes.value = feld.value;
          });
          sag('Bildunterschrift gespeichert.');
        })
        .catch(function (err) { sag(err.message, true); });
    }, 700);
  });

  /* ================= Google Health ====================================== */
  var gsdHinweis = document.getElementById('gsdHinweis');

  function gsdSag(text, fehler) {
    if (!gsdHinweis) return;
    gsdHinweis.textContent = text;
    gsdHinweis.classList.toggle('fehler', !!fehler);
  }

  var gsdSpeichern = document.getElementById('gsdSpeichern');
  if (gsdSpeichern) {
    gsdSpeichern.addEventListener('click', function () {
      var nutzlast = { action: 'gesundheit.zugang' };
      var id = document.getElementById('gsdId').value.trim();
      var secret = document.getElementById('gsdSecret').value.trim();
      if (id) nutzlast.client_id = id;
      if (secret) nutzlast.client_secret = secret;

      gsdSpeichern.disabled = true;
      gsdSag('wird gespeichert …');
      sendeJson(nutzlast).then(function (d) {
        gsdSpeichern.disabled = false;
        document.getElementById('gsdSecret').value = '';
        if (d.stand.zugang && d.url) {
          gsdSag('Gespeichert. Jetzt „Mit Google verbinden".');
          location.reload();
        } else {
          gsdSag('Es fehlt noch Client-ID oder Secret.', true);
        }
      }).catch(function (err) {
        gsdSpeichern.disabled = false;
        gsdSag(err.message, true);
      });
    });
  }

  /* Die Anmelde-Adresse wird erst beim Klick geholt: sie enthält einen
     Einmalwert gegen untergeschobene Rückmeldungen, und der soll frisch sein. */
  var gsdVerbinden = document.getElementById('gsdVerbinden');
  if (gsdVerbinden) {
    gsdVerbinden.addEventListener('click', function (e) {
      e.preventDefault();
      gsdSag('Adresse wird vorbereitet …');
      sendeJson({ action: 'gesundheit.zugang' }).then(function (d) {
        if (d.url) { location.href = d.url; } else { gsdSag('Kein Zugang hinterlegt.', true); }
      }).catch(function (err) { gsdSag(err.message, true); });
    });
  }

  /* Je Datentyp anzeigen, was ankam. Ein Konto fuehrt nicht zwangslaeufig
     alles — so sieht man auf einen Blick, ob es an der Verbindung liegt oder
     nur an einem einzelnen Wert. */
  var NAMEN = {
    'steps': 'Schritte', 'distance': 'Strecke', 'total-calories': 'Kalorien',
    'heart-rate': 'Puls', 'active-minutes': 'aktive Minuten',
    'daily-resting-heart-rate': 'Ruhepuls', 'weight': 'Gewicht'
  };

  function malBericht(bericht) {
    if (!gsdHinweis || !bericht) return;
    gsdHinweis.innerHTML = Object.keys(bericht).map(function (typ) {
      var b = bericht[typ];
      var gut = b.tage > 0;
      return '<span class="pz ' + (gut ? 'ja' : 'nein') + '">' + (gut ? '✓' : '○') +
             ' <b>' + (NAMEN[typ] || typ) + '</b> — ' +
             (gut ? b.tage + ' Tage' : (b.fehler ? b.fehler : 'nichts geliefert')) + '</span>';
    }).join('');
    gsdHinweis.classList.remove('fehler');
  }

  var gsdHolen = document.getElementById('gsdHolen');
  if (gsdHolen) {
    gsdHolen.addEventListener('click', function () {
      gsdHolen.disabled = true;
      gsdSag('wird geholt — das dauert ein paar Sekunden …');
      sendeJson({ action: 'gesundheit.holen' }).then(function (d) {
        gsdHolen.disabled = false;
        if (d.bericht) { malBericht(d.bericht); } else { gsdSag(d.meldung); }
        if (d.tage) setTimeout(function () { location.reload(); }, 2500);
      }).catch(function (err) {
        gsdHolen.disabled = false;
        gsdSag(err.message, true);
      });
    });
  }

  var gsdTrennen = document.getElementById('gsdTrennen');
  if (gsdTrennen) {
    gsdTrennen.addEventListener('click', function () {
      if (!confirm('Verbindung zu Google trennen? Die schon geholten Tage bleiben erhalten.')) return;
      sendeJson({ action: 'gesundheit.trennen' })
        .then(function () { location.reload(); })
        .catch(function (err) { gsdSag(err.message, true); });
    });
  }

  /* ================= Schlüssel ========================================== */

  /* Ein Schlüssel, der erst auf dem Camino zum ersten Mal benutzt wird und dann
     nicht geht, ist schlimmer als keiner. Deshalb wird gleich beim Speichern
     ein echter, winziger Aufruf gemacht. */
  var hinweis = document.getElementById('keyHinweis');

  function zeigePruefung(p) {
    if (!hinweis || !p) return;
    var zeilen = [
      ['Claude (Ausbau)', p.claude],
      ['Whisper (Transkription)', p.whisper]
    ];
    hinweis.innerHTML = zeilen.map(function (z) {
      var e = z[1] || { ok: false, meldung: 'nicht geprüft' };
      return '<span class="pz ' + (e.ok ? 'ja' : 'nein') + '">' +
             (e.ok ? '✓' : '✗') + ' <b>' + z[0] + '</b> — ' + e.meldung + '</span>';
    }).join('');
    hinweis.classList.remove('fehler');
  }

  var keySpeichern = document.getElementById('keySpeichern');
  if (keySpeichern) {
    keySpeichern.addEventListener('click', function () {
      var nutzlast = { action: 'schluessel.setzen' };
      var o = document.getElementById('keyOpenAi').value.trim();
      var a = document.getElementById('keyAnthropic').value.trim();
      var m = document.getElementById('keyModell').value.trim();
      if (o) nutzlast.openai_key = o;
      if (a) nutzlast.anthropic_key = a;
      if (m) nutzlast.claude_model = m;

      keySpeichern.disabled = true;
      hinweis.textContent = 'wird gespeichert und ausprobiert …';
      hinweis.classList.remove('fehler');

      sendeJson(nutzlast).then(function (d) {
        keySpeichern.disabled = false;
        document.getElementById('keyOpenAi').value = '';
        document.getElementById('keyAnthropic').value = '';
        zeigePruefung(d.pruefe);
      }).catch(function (err) {
        keySpeichern.disabled = false;
        hinweis.textContent = err.message;
        hinweis.classList.add('fehler');
      });
    });
  }

  var keyPruefen = document.getElementById('keyPruefen');
  if (keyPruefen) {
    keyPruefen.addEventListener('click', function () {
      keyPruefen.disabled = true;
      hinweis.textContent = 'wird ausprobiert …';
      hinweis.classList.remove('fehler');
      sendeJson({ action: 'schluessel.pruefen' }).then(function (d) {
        keyPruefen.disabled = false;
        zeigePruefung(d.pruefe);
      }).catch(function (err) {
        keyPruefen.disabled = false;
        hinweis.textContent = err.message;
        hinweis.classList.add('fehler');
      });
    });
  }
})();
