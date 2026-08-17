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

  function malSchlange() {
    return schlange().then(function (offen) {
      if (!queueEl) return offen;
      if (!offen.length) {
        queueEl.hidden = true;
        queueEl.innerHTML = '';
        return offen;
      }
      queueEl.hidden = false;
      queueEl.innerHTML = '<b>' + offen.length + (offen.length === 1 ? ' Eintrag wartet' : ' Einträge warten') +
        ' auf Netz.</b> Sie liegen auf diesem Gerät und gehen von selbst raus, sobald wieder Empfang da ist.' +
        '<ul>' + offen.map(function (p) {
          var was = [];
          if (p.audio) was.push('Sprachnotiz');
          if (p.text) was.push('Text');
          if (p.fotos && p.fotos.length) was.push(p.fotos.length + ' Foto' + (p.fotos.length > 1 ? 's' : ''));
          return '<li>' + (p.etappe || 'ohne Tag') + ' — ' + was.join(' + ') +
                 (p.versuche ? ' <em>(' + p.versuche + ' Versuche)</em>' : '') + '</li>';
        }).join('') + '</ul>';
      return offen;
    }).catch(function () { return []; });
  }

  function netzstand() {
    if (netzEl) netzEl.hidden = navigator.onLine;
  }

  /* ================= Hochladen ========================================== */

  function sendeJson(nutzlast) {
    return fetch(API, {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(nutzlast)
    }).then(function (r) {
      return r.json().then(function (d) {
        if (!r.ok || !d.ok) throw new Error(d && d.error ? d.error : 'HTTP ' + r.status);
        return d;
      });
    });
  }

  function sendeDatei(felder, datei, dateiname) {
    var fd = new FormData();
    Object.keys(felder).forEach(function (k) {
      if (felder[k] !== null && felder[k] !== undefined) fd.append(k, felder[k]);
    });
    fd.append('datei', datei, dateiname);
    return fetch(UPLOAD, { method: 'POST', body: fd }).then(function (r) {
      return r.json().then(function (d) {
        if (!r.ok || !d.ok) throw new Error(d && d.error ? d.error : 'HTTP ' + r.status);
        return d;
      });
    });
  }

  /* Ein Paket abarbeiten. Was durch ist, wird im Paket vermerkt — bricht die
     Verbindung mitten in einem Paket mit fünf Fotos ab, fängt der nächste
     Versuch nicht wieder bei null an. */
  function schickePaket(p) {
    var kette = Promise.resolve(p.entryId || null);

    if (p.audio && !p.audioFertig) {
      kette = kette.then(function () {
        return sendeDatei({
          art: 'audio', stage: p.stage, tag: p.tag, client_id: p.id, sekunden: p.sekunden
        }, p.audio, 'notiz.' + (p.audioEndung || 'webm')).then(function (d) {
          p.audioFertig = true;
          p.entryId = d.eintrag && d.eintrag.id;
          return inDieSchlange(p).then(function () { return p.entryId; });
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
          return inDieSchlange(p).then(function () { return p.entryId; });
        });
      });
    }

    (p.fotos || []).forEach(function (datei, i) {
      kette = kette.then(function (entryId) {
        p.fotosFertig = p.fotosFertig || [];
        if (p.fotosFertig[i]) return entryId;
        return sendeDatei({
          art: 'foto', stage: p.stage, entry: entryId || '',
          client_id: p.id + '-f' + i,
          aufgenommen: datei.lastModified ? new Date(datei.lastModified).toISOString() : ''
        }, datei, datei.name || ('bild' + i + '.jpg')).then(function () {
          p.fotosFertig[i] = true;
          return inDieSchlange(p).then(function () { return entryId; });
        });
      });
    });

    return kette.then(function () { return ausDerSchlange(p.id); });
  }

  var laeuft = false;

  function abarbeiten(stillschweigend) {
    if (laeuft || !navigator.onLine) return Promise.resolve();
    laeuft = true;

    return schlange().then(function (offen) {
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
      recorder = new MediaRecorder(spur, typ ? { mimeType: typ } : undefined);
      brocken = [];
      recorder.ondataavailable = function (e) { if (e.data && e.data.size) brocken.push(e.data); };
      recorder.onstop = function () {
        spur.getTracks().forEach(function (t) { t.stop(); });
        var typJetzt = recorder.mimeType || typ || 'audio/webm';
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
      };
      recorder.start();
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
  var gewaehlteFotos = [];

  if (fotoEingabe) {
    fotoEingabe.addEventListener('change', function () {
      gewaehlteFotos = Array.prototype.slice.call(fotoEingabe.files || []);
      sag(gewaehlteFotos.length
        ? gewaehlteFotos.length + ' Bild(er) ausgewählt — mit „Eintrag speichern" übernehmen.'
        : '');
    });
  }

  var speichern = document.getElementById('tbSpeichern');
  var textFeld = document.getElementById('tbText');
  var tagWahl = document.getElementById('tbTag');

  if (speichern) {
    speichern.addEventListener('click', function () {
      var text = (textFeld.value || '').trim();
      if (!text && !fertigeAufnahme && !gewaehlteFotos.length) {
        sag('Da ist noch nichts zum Speichern.', true);
        return;
      }

      var gewaehlt = tagWahl.options[tagWahl.selectedIndex];
      var paket = {
        id: kennung(),
        stage: tagWahl.value,
        tag: gewaehlt ? gewaehlt.dataset.tag : '',
        etappe: gewaehlt ? gewaehlt.textContent.trim() : '',
        text: text || null,
        audio: fertigeAufnahme ? fertigeAufnahme.blob : null,
        sekunden: fertigeAufnahme ? fertigeAufnahme.sekunden : null,
        audioEndung: fertigeAufnahme ? fertigeAufnahme.endung : null,
        fotos: gewaehlteFotos,
        erstellt: new Date().toISOString(),
        versuche: 0
      };

      inDieSchlange(paket).then(function () {
        textFeld.value = '';
        gewaehlteFotos = [];
        if (fotoEingabe) fotoEingabe.value = '';
        fertigeAufnahme = null;
        if (knopf) {
          knopf.classList.remove('fertig');
          knopf.querySelector('.beschriftung').textContent = 'Sprachnotiz aufnehmen';
        }
        sag(navigator.onLine ? 'Gespeichert — wird hochgeladen.' : 'Auf dem Gerät gemerkt — geht raus, sobald Netz da ist.');
        return malSchlange();
      }).then(function () {
        return abarbeiten();
      }).catch(function () {
        sag('Konnte nicht einmal auf dem Gerät gespeichert werden. Bitte den Text kopieren!', true);
      });
    });
  }

  /* ================= Einträge bearbeiten ================================ */

  listeEl.addEventListener('click', function (e) {
    var karte = e.target.closest('.tbe');
    if (!karte) return;
    var id = Number(karte.dataset.id);

    if (e.target.classList.contains('veredeln')) {
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

  /* ================= Schlüssel ========================================== */

  /* Ein Schlüssel, der erst auf dem Camino zum ersten Mal benutzt wird und dann
     nicht geht, ist schlimmer als keiner. Deshalb wird gleich beim Speichern
     ein echter, winziger Aufruf gemacht. */
  var hinweis = document.getElementById('keyHinweis');

  function zeigePruefung(p) {
    if (!hinweis || !p) return;
    hinweis.textContent = (p.ok ? '✓ ' : '✗ ') + p.meldung;
    hinweis.classList.toggle('fehler', !p.ok);
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
