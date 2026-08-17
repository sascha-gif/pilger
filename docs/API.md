# API

`public/api.php` — eine Adresse, alle Änderungen. Immer `POST` mit JSON-Body,
Antwort immer JSON.

Ist ein `write_password` gesetzt und die Sitzung nicht entsperrt, antwortet
jeder schreibende Aufruf mit **403**.

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

### `state` — Gesamtstand abfragen

```json
{ "action": "state" }
```
```json
{
  "ok": true,
  "pack":   { "done": 12, "total": 52 },
  "costs":  { "total": 696.73, "total_formatted": "696,73 €" },
  "weight": { "latest": 91.4 }
}
```

## Fehler

| Code | Wann |
|---|---|
| 400 | kein gültiges JSON, unbekannte Aktion, ID ≤ 0 |
| 403 | Schreibschutz aktiv, Sitzung nicht entsperrt |
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
