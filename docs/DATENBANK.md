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

1. Neue Migration in `src/Schema.php` ergänzen und `VERSION` hochzählen.
2. Änderungen mit `ALTER TABLE` fahren, nicht mit `CREATE TABLE IF NOT EXISTS` —
   bestehende Tabellen werden sonst nicht angefasst.
3. Vor dem Merge lokal gegen SQLite prüfen (siehe README).
