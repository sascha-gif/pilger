# HANDOVER — pilger.milsh.com

Lebendes Übergabedokument. Wer hier neu einsteigt (Mensch oder Claude-Session),
liest diese Datei zuerst. Stand: 17.08.2026.

---

## Was das Projekt ist

Der Camino-Masterplan (Porto → Santiago, 17.09.–01.10.2026) läuft als
dynamische Web-App unter **pilger.milsh.com**. Ursprung war die statische Datei
`camino-masterplan-2026.html`; die ist jetzt nur noch Referenz für Design und
Inhalt. Die Quelle der Wahrheit sind die Datenbank und dieses Repo.

Was gegenüber der statischen Version dazugekommen ist:

- **Packliste** — Häkchen liegen in der DB, gelten auf allen Geräten, mit Fortschrittsbalken
- **Kosten** — Beträge werden gespeichert (vorher gingen sie beim Schließen verloren), Summe rechnet live
- **Countdown** — Ist-Gewichte werden gespeichert
- **Etappen, Karte, Ernährung, Equipment** — komplett aus der DB gerendert, dadurch pflegbar
- **Schreibschutz** — optionales Passwort, solange die Seite öffentlich erreichbar ist

---

## Feste Absprachen (Stand 17.08.2026)

Aus der Ansage „merken": so wird hier gearbeitet.

1. **Alles läuft über GitHub.** Kein Terminal-Kram für Sascha. Änderungen gehen
   als Commit ins Repo, der Rest passiert automatisch.
2. **Deploy und Merge macht Claude selbst.** Merge nach `main` löst den Deploy aus.
3. **Wissen wird in `.md`-Dateien festgehalten**, nicht im Chatverlauf.
   Diese Datei ist der Einstieg, `CLAUDE.md` der Projektkontext, `docs/` das Detail.
4. **Zielserver:** milsh.com bei Hetzner, Domain `pilger.milsh.com` existiert
   bereits und zeigt auf **46.224.19.41**.
5. **Datenbank** liegt auf demselben Server (MariaDB/MySQL).

---

## Aktueller Stand

| Baustein | Status |
|---|---|
| App (PHP 8, PDO) | **fertig**, lokal getestet |
| Datenbankschema + Startdaten | **fertig**, installiert sich beim ersten Seitenaufruf selbst |
| Speichern von Häkchen / Beträgen / Gewicht | **fertig**, End-to-End getestet |
| Schreibschutz per Passwort | **fertig**, getestet |
| Karte (Leaflet, 13 Stopps + Senda Litoral) | **fertig**, Daten aus der DB |
| Deploy-Workflow (GitHub Actions) | **fertig**, wartet auf Secrets |
| Live auf pilger.milsh.com | **offen** — siehe „Was noch fehlt" |

Getestet wurde gegen SQLite (vollständig durchgespielt: Rendern, Speichern,
Neuladen, Sperren, Entsperren, Validierung). Die MySQL-Variante ist bislang nur
per DDL-Prüfung kontrolliert, weil in der Bauumgebung kein MySQL-Server läuft —
der erste echte Test passiert beim ersten Deploy.

---

## Was noch fehlt

Genau eine Sache blockiert den Live-Gang: **die Zugangsdaten zum Server.**
Claude-Sessions haben von sich aus keinen Zugriff (Port 22 ist aus der
Session-Umgebung gesperrt), deshalb deployt der GitHub-Actions-Runner.

Einzutragen unter
`GitHub → Repo → Settings → Secrets and variables → Actions → New repository secret`
(reines Web-UI, kein Terminal):

| Secret | Pflicht | Inhalt |
|---|---|---|
| `SSH_HOST` | ja | `46.224.19.41` |
| `SSH_USER` | ja | SSH-Benutzer auf dem Server |
| `SSH_PASSWORD` | ja¹ | SSH-Passwort — **oder** stattdessen `SSH_KEY` |
| `SSH_KEY` | ja¹ | privater SSH-Schlüssel (kompletter Text inkl. BEGIN/END-Zeilen) |
| `DEPLOY_PATH` | ja | Zielverzeichnis, z. B. `/var/www/pilger.milsh.com` |
| `SSH_PORT` | nein | nur falls nicht 22 |
| `DB_NAME` | nein² | Name der Datenbank, z. B. `pilger` |
| `DB_USER` / `DB_PASS` | nein² | DB-Benutzer; das Passwort ist frei wählbar und wird so angelegt |
| `DB_HOST` / `DB_PORT` | nein | Standard `localhost` / `3306` |
| `DB_ADMIN_USER` / `DB_ADMIN_PASS` | nein³ | MySQL-Admin zum Anlegen der Datenbank |
| `WRITE_PASSWORD` | nein | Passwort für den Bearbeiten-Modus |
| `SITE_URL` | nein | Standard `https://pilger.milsh.com/` |

¹ Eines von beiden genügt. Der Workflow erkennt selbst, welcher Weg hinterlegt ist;
sind beide gesetzt, gewinnt der Schlüssel.

² Ohne `DB_NAME` läuft die App mit SQLite unter `var/pilger.sqlite` — funktioniert
vollständig, braucht keinen DB-Server. Sobald `DB_NAME` gesetzt ist, wird MariaDB benutzt.

³ Der Workflow legt Datenbank und Benutzer selbst an: zuerst über
`DB_ADMIN_USER`/`DB_ADMIN_PASS`, sonst über `sudo mysql` auf dem Server. Klappt
keines von beidem, bricht er mit einer klaren Meldung ab.

Sobald die Secrets stehen: `main` anstoßen (Actions → *Deploy pilger.milsh.com*
→ *Run workflow*) oder einfach den nächsten Merge abwarten.

---

## Repo-Situation — wichtig für die nächste Session

Gearbeitet wird in **`sascha-gif/pilger`**, Branch
`claude/pilger-milsh-project-setup-b8hd89`.

In der Aufgabenstellung war von `Pasaventures/nexus`, Branch
`claude/pilger-milsh-project-setup-7kt3ss` und einer `HANDOVER.md` die Rede.
Beides gibt es hier nicht: Eine Claude-Session kann nur Repos desselben Owners
nachladen, `Pasaventures/nexus` liegt also außer Reichweite, und eine
`HANDOVER.md` existierte in `sascha-gif/pilger` nirgends — der einzige
Projektstand war der Camino-Masterplan auf Branch
`claude/anschauen-merken-f9qdrp`, auf dem dieser Branch aufsetzt.

**Falls der eigentliche milsh.com-Kontext in `Pasaventures/nexus` liegt:** eine
neue Session mit diesem Repo als Quelle starten, oder die betreffenden Inhalte
hier einkippen.

---

## Wo was liegt

```
public/            Document-Root
  index.php        die Seite, rendert alles aus der DB
  api.php          JSON-Schnittstelle für alle Änderungen
  assets/          app.css, app.js
src/               Anwendungscode (Database, Schema, Repo, Helfer)
db/seed.php        kompletter Masterplan-Inhalt als Startdaten
config/            config.example.php — echte config.php baut der Deploy
docs/              DEPLOYMENT.md · DATENBANK.md · API.md
.github/workflows/ deploy.yml
camino-masterplan-2026.html   Referenz der statischen Ursprungsfassung
```

---

## Nächste sinnvolle Schritte

1. Secrets eintragen → erster Deploy → live prüfen.
2. Die 12 offenen Unterkünfte buchen; Beträge direkt auf der Seite eintragen
   (Oia und Santiago zuerst — dünnes Angebot bzw. hohe Nachfrage).
3. Rückflugzeiten SCQ→FRA aus der Buchung nachtragen.
4. Fährfahrplan Caminha → A Guarda prüfen.
5. Erst wenn das steht: Etappen direkt auf der Seite bearbeitbar machen
   (`stage.update` liegt in der API schon bereit, die Oberfläche dazu fehlt).
