# pilger.milsh.com

Camino Portugués da Costa 2026 — Porto → Santiago de Compostela.
266 km · 12 Etappen · 17.09.–01.10.2026

Der Masterplan als Web-App mit Datenbank: Packliste, Kosten, Gewicht, abgehakte
Etappen, Stempel, Wetter, Höhenprofil und ein Tagebuch mit Sprachnotizen und
Fotos — gespeichert statt beim Schließen des Tabs vergessen.

Die Seite steht komplett hinter einem Passwort. Solange keins gesetzt ist, zeigt
sie nichts als die Einrichtung.

- **[HANDOVER.md](HANDOVER.md)** — Projektstand, Absprachen, offene Punkte. **Hier anfangen.**
- **[CLAUDE.md](CLAUDE.md)** — alle Eckdaten der Reise als Projektkontext
- **[docs/DEPLOYMENT.md](docs/DEPLOYMENT.md)** — Server, Secrets, Deploy-Ablauf
- **[docs/DATENBANK.md](docs/DATENBANK.md)** — Schema und Tabellen
- **[docs/API.md](docs/API.md)** — die JSON-Schnittstelle

## Aufbau

```
public/         Document-Root
  index.php     die Seite
  api.php       JSON-Schnittstelle für alle Änderungen
  upload.php    Fotos und Sprachaufnahmen annehmen
  media.php     dieselben ausliefern — nur nach Anmeldung
  sw.js         Service Worker, hält die Seite ohne Netz lesbar
src/            Database, Schema, Repo, Auth, Aussen, Tagebuch, Helfer
db/seed.php     der komplette Masterplan als Startdaten
db/migrations/  spätere Schemaänderungen, laufen beim ersten Aufruf selbst
config/         config.example.php (im Container über Umgebungsvariablen ersetzt)
docs/           Detaildokumentation
```

PHP 8 mit PDO, sonst nichts: kein Composer, kein Build-Schritt, kein Node.
Leaflet und die Schriften kommen per CDN; ohne Netz fällt beides weg, die Seite
bleibt lesbar.

Für Fotos braucht das Image `gd` und `exif`, für Wetter und Transkription
`curl` — alles im Dockerfile.

## Lokal starten

```bash
php -S 127.0.0.1:8765 -t public
```

Ohne `config/config.php` legt die App automatisch eine SQLite-Datenbank unter
`var/pilger.sqlite` an, spielt Schema und Startdaten ein und ist sofort
benutzbar — es ist kein Datenbankserver nötig. Beim ersten Aufruf fragt sie nach
einem Passwort; danach ist sie zu.

Sprachaufnahmen brauchen im Browser HTTPS oder `localhost` — über `127.0.0.1`
gibt der Browser das Mikrofon nicht frei. Alles andere geht auch dort.

Zum Zurücksetzen die Datei löschen; beim nächsten Aufruf wird neu aufgebaut.

Für einen MariaDB-Test `config/config.example.php` nach `config/config.php`
kopieren und die Zugangsdaten eintragen. Die Datei ist über `.gitignore`
ausgeschlossen und gehört nie in einen Commit.

## Deploy

Der Server holt sich den Code selbst: ein systemd-Zeitgeber prüft alle fünf
Minuten, ob sich `main` geändert hat, und baut die Container neu. Von außen
nimmt die Maschine keine SSH-Verbindungen an — ein Deploy aus GitHub Actions
heraus ist deshalb nicht möglich und war der erste, falsche Anlauf.

GitHub Actions prüft nur: PHP-Syntax, Start ohne Datenbankserver, den
Container-Stack samt MariaDB, dass die Tür wirklich zu ist, und dass eine Datei
im Foto-Volume einen kompletten Neubau des Containers übersteht. Details in
[docs/DEPLOYMENT.md](docs/DEPLOYMENT.md).

## Inhalte ändern

Texte, Etappen, Packliste und Kosten liegen in der Datenbank. Für Beträge,
Häkchen und Gewicht reicht die Seite selbst. Alles andere wird derzeit über
`db/seed.php` gepflegt und greift bei einer frischen Datenbank — oder direkt per
`UPDATE` auf der bestehenden.

`camino-masterplan-2026.html` bleibt als Referenz der ursprünglichen statischen
Fassung liegen und wird nicht mehr gepflegt.
