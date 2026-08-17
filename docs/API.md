# API

`public/api.php` — eine Adresse, alle Änderungen. Immer `POST` mit JSON-Body,
Antwort immer JSON.

Ohne Anmeldung antwortet **jeder** Aufruf mit **403**. Die Seite als Ganzes
steht hinter einem Passwort, nicht nur das Speichern — siehe `src/Auth.php`.

Zwei Endpunkte sprechen kein JSON, weil sie Dateien bewegen:
`upload.php` nimmt `multipart/form-data` an, `media.php` liefert Bilder und
Aufnahmen aus. Beide prüfen die Anmeldung genauso.

## Aktionen

### `pack.toggle` — Häkchen der Packliste

```json
{ "action": "pack.toggle", "id": 17, "checked": true }
```
```json
{ "ok": true, "checked": true, "done": 12, "total": 52 }
```

### `cost.set` — Betrag eintragen

`amount` darf `null` sein (Feld geleert). Komma und Punkt sind beide erlaubt.
Gültig: 0 bis 1.000.000.

```json
{ "action": "cost.set", "id": 2, "amount": 249.90 }
```
```json
{ "ok": true, "amount": 249.9, "total": 696.73, "total_formatted": "696,73 €" }
```

### `weight.set` — Ist-Gewicht eintragen

`actual` darf `null` sein. Gültig: 30 bis 300 kg.

```json
{ "action": "weight.set", "id": 2, "actual": "91,4" }
```
```json
{ "ok": true, "actual": 91.4, "latest": 91.4 }
```

### `stage.update` — Etappe pflegen

Änderbar sind `target`, `note`, `booking_url`, `booking_label`. Gedacht fürs
Nachtragen gebuchter Unterkünfte; eine Oberfläche dafür fehlt noch.

```json
{ "action": "stage.update", "id": 6, "target": "<b>Gebucht:</b> Hostal Sarga · 78 €" }
```

### `stage.done` — Etappentag abhaken

```json
{ "action": "stage.done", "id": 4, "done": true }
```
```json
{ "ok": true, "done": true,
  "weg": { "gelaufen": 69, "gesamt": 266, "rest": 197, "prozent": 26,
           "etappen": 3, "etappen_gesamt": 12 },
  "stempel": { "noetig": 21, "da": 3, "fehlt": 0 } }
```

`fehlt` zählt nur an bereits abgehakten Tagen — was noch vor einem liegt,
fehlt nicht.

### `stage.stamps` — gesammelte Stempel eines Tages

Wird auf 0 bis `stamps_needed` begrenzt; ein zu großer Wert ist kein Fehler,
sondern wird gekappt.

```json
{ "action": "stage.stamps", "id": 4, "stamps": 2 }
```
```json
{ "ok": true, "stempel": { "noetig": 21, "da": 5, "fehlt": 0 } }
```

### `equip.toggle` — Ausrüstungspunkt abhaken

```json
{ "action": "equip.toggle", "id": 7, "checked": true }
```
```json
{ "ok": true, "checked": true, "done": 4, "total": 13 }
```

### Google Health — Schritte, Kalorien, Puls

| Aktion | Felder | Zweck |
|---|---|---|
| `gesundheit.zugang` | `client_id`, `client_secret` | OAuth-Zugang hinterlegen; gibt die Anmelde-URL zurück |
| `gesundheit.holen` | `von`, `bis` (optional) | Tage holen und ablegen, Standard letzte 30 Tage |
| `gesundheit.trennen` | — | Token widerrufen und löschen; geholte Tage bleiben |

Die Rückkehr von Google landet auf `public/gesundheit.php` — diese Adresse muss
in der Cloud Console als autorisierte Weiterleitungs-URI stehen.

Warum überhaupt Google Health und nicht Google Fit: die Fit-REST-Schnittstelle
nimmt **seit dem 1. Mai 2024 keine neuen Entwickler mehr an** und wird Ende 2026
abgeschaltet. Health Connect, der offizielle Nachfolger, läuft ausschließlich
auf dem Gerät — ein Server kommt da nie heran. Bleibt die Google Health API
(`health.googleapis.com/v4`), die das Fitbit-Konto liest.

Aufruf je Datentyp, Aufbau aus Googles Discovery-Dokument:

```
POST https://health.googleapis.com/v4/users/me/dataTypes/steps/dataPoints:dailyRollUp
{ "range": { "start": {"date": {"year":2026,"month":9,"day":19}},
             "end":   {"date": {"year":2026,"month":9,"day":20}} },
  "windowSizeDays": 1, "pageSize": 1000 }
```

Das Ende ist **ausschließend** — wer den letzten Tag mit haben will, fragt einen
Tag weiter. Höchstens 14 Tage je Anfrage bei `heart-rate`, `active-minutes` und
`total-calories`, sonst 90; der Code schneidet den Zeitraum selbst passend.

Fehlt ein Tag in der Antwort, hat die Uhr nicht synchronisiert. Das ist
**nicht** dasselbe wie null Schritte und wird deshalb auch nicht als Null
gespeichert, sondern gar nicht.

### `wetter` / `hoehen` — was von außen kommt

Beides ohne weitere Felder. Der Server holt bei Bedarf bei Open-Meteo nach und
legt die Antwort in `ext_cache` ab; danach kommt sie von dort. `erneuern: true`
erzwingt das Nachholen.

```json
{ "action": "wetter" }
```
```json
{ "ok": true, "stand": "2026-09-16T08:12:03+02:00",
  "tage": { "5": { "quelle": "vorhersage", "datum": "2026-09-23", "code": 3,
                   "max": 23.1, "min": 15.4, "regen": 0.4, "regenp": 20, "wind": 18.9 } } }
```

`quelle` ist entweder `vorhersage` (echte Vorhersage, bis 16 Tage voraus) oder
`mittel` (Durchschnitt derselben Kalendertage der Vorjahre, dann steht `jahre`
dabei und `code`/`wind` sind `null`). Das ist kein Schönheitsfehler, sondern der
Punkt: eine Vorhersage für in fünf Wochen gibt es nicht.

```json
{ "action": "hoehen" }
```
```json
{ "ok": true, "etappen": { "2": { "punkte": [12, 15, 9, ...], "auf": 210,
                                  "ab": 195, "min": 3, "max": 88 } } }
```

### Tagebuch

| Aktion | Felder | Zweck |
|---|---|---|
| `tagebuch.text` | `stage`, `tag`, `text`, `client_id` | getippten Eintrag anlegen |
| `tagebuch.aendern` | `id`, `text` | Text eines Eintrags überschreiben |
| `tagebuch.veredeln` | `id` | Aufnahme transkribieren und glätten |
| `tagebuch.loeschen` | `id` | Eintrag samt Aufnahme und Bildern |
| `foto.bildtext` | `id`, `text` | Bildunterschrift |
| `foto.loeschen` | `id` | Bild und Vorschaubild |
| `schluessel.setzen` | `openai_key`, `anthropic_key`, `claude_model` | Zugänge hinterlegen |

`client_id` vergibt der Browser schon beim Aufnehmen. Kommt derselbe Eintrag
ein zweites Mal an — weil das Handy zwischendurch das Netz verloren hat —,
erkennt der Server das daran und legt ihn nicht doppelt an.

`schluessel.setzen` gibt nie einen Schlüssel zurück, nur ob einer da ist:

```json
{ "ok": true, "kann": { "transkription": true, "glaettung": false,
                        "modell": "claude-opus-5" } }
```

`tagebuch.veredeln` ist zweistufig und jede Stufe darf ausfallen. Fehlt der
OpenAI-Schlüssel, kommt 422 und die Aufnahme bleibt liegen. Fehlt nur der
Anthropic-Schlüssel, kommt 200 mit dem Rohtext und einem `hinweis`.

### `upload.php` — Fotos und Aufnahmen

`multipart/form-data`, kein JSON. Felder: `art` (`foto` oder `audio`),
`stage`, `entry`, `tag`, `client_id`, `sekunden`, `aufgenommen`, `datei`.

Bilder werden auf 1600 px verkleinert, nach EXIF gedreht und bekommen ein
Vorschaubild mit 480 px. Kann `gd` das Format nicht lesen (etwa HEIC), wird die
Datei unverändert abgelegt statt verworfen.

### `media.php` — Auslieferung

`GET media.php?art=foto|klein|audio&id=…`. Ohne Anmeldung **403**. Die Dateien
liegen außerhalb des ausgelieferten Verzeichnisses; über den Webserver sind sie
nur hier zu erreichen.

### `state` — Gesamtstand abfragen

```json
{ "action": "state" }
```
```json
{
  "ok": true,
  "pack":    { "done": 12, "total": 52 },
  "equip":   { "done": 4, "total": 13 },
  "weg":     { "gelaufen": 69, "gesamt": 266, "rest": 197, "prozent": 26,
               "etappen": 3, "etappen_gesamt": 12 },
  "stempel": { "noetig": 21, "da": 5, "fehlt": 0 },
  "costs":   { "total": 696.73, "total_formatted": "696,73 €" },
  "weight":  { "latest": 91.4 }
}
```

## Fehler

| Code | Wann |
|---|---|
| 400 | kein gültiges JSON, unbekannte Aktion, ID ≤ 0 |
| 403 | nicht angemeldet |
| 404 | Datensatz existiert nicht |
| 405 | kein POST |
| 422 | Wert außerhalb des gültigen Bereichs |
| 500 | Serverfehler (Details nur bei `debug = true`) |

Fehlerformat:

```json
{ "ok": false, "error": "Gewicht außerhalb des gültigen Bereichs (30–300 kg)." }
```

## Verhalten im Frontend

`public/assets/app.js` schickt Änderungen selbst:

- Häkchen sofort, Textfelder gebündelt nach 600 ms Tipppause
- unten rechts erscheint kurz eine Bestätigung
- schlägt das Speichern fehl, springt das Feld auf den alten Wert zurück und die
  Meldung bleibt sechs Sekunden rot stehen — es geht also nichts still verloren

## Offline

`public/assets/tagebuch.js` schickt Tagebucheinträge nicht direkt, sondern legt
sie zuerst in der IndexedDB des Geräts ab (`pilger-tagebuch`, Store
`warteschlange`). Erst danach geht das Paket raus — und wenn kein Netz da ist,
eben später, ausgelöst durch das `online`-Ereignis oder alle 45 Sekunden.

Ein Paket merkt sich, was davon schon durch ist. Bricht die Verbindung mitten
in einem Paket mit fünf Fotos ab, fängt der nächste Versuch nicht wieder bei
null an.
