# pilger.milsh.com

Camino Portugués da Costa 2026 — Porto → Santiago de Compostela.
266 km · 12 Etappen · 17.09.–01.10.2026

Der Masterplan als Web-App mit Datenbank: Packliste, Kosten und Gewicht werden
gespeichert statt beim Schließen des Tabs vergessen.

- **[HANDOVER.md](HANDOVER.md)** — Projektstand, Absprachen, offene Punkte. **Hier anfangen.**
- **[CLAUDE.md](CLAUDE.md)** — alle Eckdaten der Reise als Projektkontext
- **[docs/DEPLOYMENT.md](docs/DEPLOYMENT.md)** — Server, Secrets, Deploy-Ablauf
- **[docs/DATENBANK.md](docs/DATENBANK.md)** — Schema und Tabellen
- **[docs/API.md](docs/API.md)** — die JSON-Schnittstelle

## Aufbau

```
public/      Document-Root — index.php, api.php, assets/
src/         Anwendungscode: Database, Schema, Repo, Helfer
db/seed.php  der komplette Masterplan als Startdaten
config/      config.example.php (die echte config.php baut der Deploy)
docs/        Detaildokumentation
```

PHP 8 mit PDO, sonst nichts: kein Composer, kein Build-Schritt, kein Node.
Leaflet und die Schriften kommen per CDN.

## Lokal starten

```bash
php -S 127.0.0.1:8765 -t public
```

Ohne `config/config.php` legt die App automatisch eine SQLite-Datenbank unter
`var/pilger.sqlite` an, spielt Schema und Startdaten ein und ist sofort
benutzbar — es ist kein Datenbankserver nötig.

Zum Zurücksetzen die Datei löschen; beim nächsten Aufruf wird neu aufgebaut.

Für einen MariaDB-Test `config/config.example.php` nach `config/config.php`
kopieren und die Zugangsdaten eintragen. Die Datei ist über `.gitignore`
ausgeschlossen und gehört nie in einen Commit.

## Deploy

Merge nach `main` startet den GitHub-Actions-Workflow, der die Dateien per rsync
auf den Server spielt und anschließend prüft, ob die Seite antwortet. Details
und die Liste der Secrets stehen in [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md).

## Inhalte ändern

Texte, Etappen, Packliste und Kosten liegen in der Datenbank. Für Beträge,
Häkchen und Gewicht reicht die Seite selbst. Alles andere wird derzeit über
`db/seed.php` gepflegt und greift bei einer frischen Datenbank — oder direkt per
`UPDATE` auf der bestehenden.

`camino-masterplan-2026.html` bleibt als Referenz der ursprünglichen statischen
Fassung liegen und wird nicht mehr gepflegt.
