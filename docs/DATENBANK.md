# Datenbank

Läuft auf MariaDB/MySQL (Server milsh.com) oder — ohne DB-Server — auf SQLite.
Das Schema wird von `src/Schema.php` erzeugt, die Startdaten kommen aus
`db/seed.php`. Beides läuft automatisch beim ersten Seitenaufruf; auf dem Server
ist dafür kein Kommandozeilenzugriff nötig.

Der Stand wird in `schema_migrations` vermerkt, also läuft die Installation
genau einmal.

## Tabellen

### Inhalt der Seite

| Tabelle | Zeilen | Inhalt |
|---|---:|---|
| `settings` | 9 | Titel, Kopfzeilen, Fußzeile, Kartenmittelpunkt |
| `hero_facts` | 4 | die vier Kennzahlen im Kopf (266 km, 12 Etappen, …) |
| `profile_facts` | 6 | Abschnitt 01 Profil |
| `nutrition_pills` | 3 | kcal / Protein / Fastenfenster |
| `nutrition_slots` | 6 | Tagesprotokoll 12:00 bis 21:30 |
| `travel_cards` | 3 | Hinflug, Orga-Tag, Rückflug |
| `stages` | 13 | Porto + 12 Etappen, inklusive Koordinaten für die Karte |
| `map_routes` | 1 | Senda Litoral als eigene Linie |
| `equipment_cards` / `equipment_items` | 4 / 13 | Abschnitt 05 |
| `pack_categories` / `pack_items` | 12 / 52 | Packliste |
| `cost_items` | 20 | Kostentabelle |
| `weight_weeks` | 7 | Countdown Start bis W6 |
| `notes` | 4 | die vier Hinweisboxen |

### Was Besucher verändern

Nur drei Spalten werden im Betrieb geschrieben — alles andere ist Inhalt, der
über das Repo gepflegt wird:

| Tabelle | Spalte | Bedeutung |
|---|---|---|
| `pack_items` | `checked`, `updated_at` | Häkchen der Packliste |
| `cost_items` | `amount`, `updated_at` | eingetragener Betrag |
| `weight_weeks` | `actual`, `updated_at` | gewogenes Ist-Gewicht |

Zusätzlich erlaubt die API `stage.update` für `target`, `note`, `booking_url`
und `booking_label` — gedacht fürs Nachtragen gebuchter Unterkünfte. Eine
Oberfläche dafür gibt es noch nicht.

## `stages` im Detail

Die wichtigste Tabelle, weil sie Etappenliste **und** Karte speist.

| Spalte | Zweck |
|---|---|
| `seq` | Reihenfolge, 0 = Porto, 1–12 = E1–E12 |
| `code` | Aufdruck am Meilenstein, z. B. `E5 · 23.09.` |
| `title`, `title_suffix` | „Caminha → Oia" + „(Spanien)" |
| `dist` | Zeile unter der Überschrift |
| `target`, `note`, `alt_note` | Budget-Ziel, Hinweis, Senda-Litoral-Variante |
| `booking_url`, `booking_label` | Booking-Link, nach Preis sortiert |
| `km_big`, `km_sub` | die große Restkilometerzahl |
| `variant` | `normal`, `special` (gelber Rahmen) oder `anchor` (dunkle Kachel) |
| `lat`, `lng`, `map_name`, `map_eyebrow`, `map_meta`, `map_hub` | Kartenmarker |
| `on_map` | Marker ein-/ausblenden |
| `done`, `done_at` | Tag abgehakt |
| `km_walk` | die Kilometer des Tages als Zahl (Summe 266) — `dist` ist nur Text und lässt sich nicht rechnen |
| `date_iso` | `2026-09-23`, für Wetter und Tagebuch |
| `stamps_needed`, `stamps_done` | Stempel: in Portugal einer, ab dem Minho zwei |

### Zutritt

| Tabelle | Zweck |
|---|---|
| `auth_tokens` | gemerkte Geräte. Im Cookie steht ein Zufallswert, hier nur dessen SHA-256 — wer die Tabelle liest, kann sich damit nicht anmelden |
| `login_attempts` | Fehlversuche je IP, für die Bremse (10 je Viertelstunde) |

Das Passwort selbst steht als Hash in `settings` unter `auth_hash`. Es
zurückzusetzen heißt: diese Zeile und alle `auth_tokens` löschen — danach fragt
die Seite beim nächsten Aufruf nach einem neuen.

### Tagebuch und Fotos

| Tabelle | Zweck |
|---|---|
| `diary_entries` | Einträge: getippt oder Sprachnotiz. `text_raw` ist die Transkription, `text_clean` die geglättete Fassung |
| `photos` | Bilder mit Etappe, optional Eintrag, Bildunterschrift, Maßen |
| `ext_cache` | Wetter und Höhen von Open-Meteo, mit Ablaufdatum |

Die Dateien liegen **nicht** in der Datenbank, sondern im Volume `pilger-data`
unter `/var/www/data/fotos` bzw. `/var/www/data/audio`. In der Datenbank steht
nur, wie sie heißen. Beides gehört zusammen ins Backup — die Datenbank allein
kennt Bilder, die es nicht mehr gibt.

`client_id` in beiden Tabellen ist der Schlüssel für die Offline-Fähigkeit: der
Browser vergibt ihn schon beim Aufnehmen, lange bevor Netz da ist. Kommt
derselbe Upload später ein zweites Mal an, erkennt der Server daran, dass es
derselbe Eintrag ist.

`status` eines Eintrags: `neu` (Aufnahme liegt, noch kein Text),
`transkribiert` (Rohtext da), `fertig`, `fehler`.

## Beide Datenbanken

`src/Schema.php` erzeugt dasselbe Schema in zwei Dialekten:

| | MariaDB/MySQL | SQLite |
|---|---|---|
| Primärschlüssel | `INT AUTO_INCREMENT PRIMARY KEY` | `INTEGER PRIMARY KEY AUTOINCREMENT` |
| Beträge | `DECIMAL(10,2)` | `REAL` |
| Zeichensatz | `utf8mb4` / `utf8mb4_unicode_ci` | UTF-8 |

Der Anwendungscode kennt den Unterschied nicht — alles läuft über PDO mit
vorbereiteten Statements.

## Sicherung

MariaDB:

```
mysqldump -u pilger -p pilger > pilger-$(date +%F).sql
```

SQLite: `var/pilger.sqlite` kopieren.

Ein Rollback des Codes berührt die Daten nicht — Schema und Inhalte bleiben stehen.

## Schema ändern

1. Neue Datei unter `db/migrations/` anlegen, durchnummeriert.
2. In `src/Schema.php` einen weiteren `$apply('00X_name', …)`-Block ergänzen.
   Was einmal gelaufen ist, steht in `schema_migrations` und läuft nie wieder.
3. Spalten mit `$db->addColumn()` ergänzen — das prüft vorher, ob es sie schon
   gibt. Eine Migration, die beim zweiten Anlauf scheitert, wird nie als
   erledigt vermerkt und versucht es bei jedem Seitenaufruf wieder.
4. Vor dem Merge lokal gegen SQLite prüfen (siehe README).

Bisher gefahren:

| Version | Inhalt |
|---|---|
| `001_init` | Tabellen und der komplette Masterplan als Startdaten |
| `002_kuestenroute` | 31 Punkte entlang der Küste, Koordinaturkorrekturen |
| `003_ankunft` | `plan_steps` — Ankunft in Porto, Heimreise ab Santiago |
| `004_zutritt` | `auth_tokens`, `login_attempts` |
| `005_erledigt` | Häkchen für Etappen und Ausrüstung, `km_walk`, Stempel |
| `006_aussen` | `ext_cache` für Wetter und Höhen |
| `007_tagebuch` | `diary_entries`, `photos` |
