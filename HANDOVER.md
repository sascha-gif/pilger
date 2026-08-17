# HANDOVER — pilger.milsh.com

Lebendes Übergabedokument. Wer hier neu einsteigt (Mensch oder Claude-Session),
liest diese Datei zuerst. Stand: 17.08.2026.

---

## Was das Projekt ist

Der Camino-Masterplan (Porto → Santiago, 17.09.–01.10.2026) läuft als
dynamische Web-App unter **pilger.milsh.com**. Ursprung war die statische Datei
`camino-masterplan-2026.html`; die ist jetzt nur noch Referenz für Design und
Inhalt. Quelle der Wahrheit sind die Datenbank und dieses Repo.

Was gegenüber der statischen Version dazugekommen ist:

- **Packliste** — Häkchen liegen in der DB, gelten auf allen Geräten, mit Fortschrittsbalken
- **Kosten** — Beträge werden gespeichert (vorher gingen sie beim Schließen verloren), Summe rechnet live
- **Countdown** — Ist-Gewichte werden gespeichert
- **Etappen, Karte, Ernährung, Equipment** — komplett aus der DB gerendert, dadurch pflegbar
- **Schreibschutz** — optionales Passwort, solange die Seite öffentlich erreichbar ist

---

## Feste Absprachen

Aus der Ansage „merken": so wird hier gearbeitet.

1. **Alles läuft über GitHub.** Kein Terminal-Kram für Sascha. Änderungen gehen
   als Commit ins Repo, der Rest passiert automatisch.
2. **Merge macht Claude selbst.** Der Deploy passiert danach ohne Zutun.
3. **Wissen wird in `.md`-Dateien festgehalten**, nicht im Chatverlauf.
4. **Repo bleibt `sascha-gif/pilger` und bleibt öffentlich** — darauf beruht der
   anmeldefreie Abruf durch den Server. Reisedaten sind darin für jeden lesbar;
   Zugangsdaten stehen nirgends im Repo.

### Wie mit Sascha zu arbeiten ist

Er kennt die Werkzeuge nicht und will sie nicht lernen. Anleitungen brauchen
deshalb: **direkter Link** wo möglich, sonst der **Navigationspfad Ebene für
Ebene** mit den Bezeichnungen genau so, wie sie in der Oberfläche stehen,
**nummerierte Schritte**, **ein Befehl pro Zeile**, dazu **was danach zu sehen
sein muss** und **was zu tun ist, wenn es anders aussieht**.

---

## Der Server — das Wichtigste zuerst

Übernommen aus `UEBERGABE.md` des Projekts family.milsh.com, das auf derselben
Maschine läuft.

| | |
|---|---|
| Öffentlich | `46.224.19.41` — **SSH von außen ist zu**, Port 2222 und 22 laufen in die Zeitüberschreitung |
| Zugang | `ssh root@100.84.10.64` über **Tailscale**. Nur so kommt man hinein |
| Maschine | Ubuntu 24.04 LTS, `ubuntu-4gb-nbg1-1` (Hetzner Nürnberg), 15 GB RAM |
| Reverse-Proxy | Container `bcd_caddy`, gemeinsamer Eingang für rund 25 Projekte |
| Caddy-Konfiguration | auf dem Host unter `/opt/concierge-bot/server-config/Caddyfile` |
| Haus-Stil | alle anderen Projekte laufen in **Docker** |
| `milsh.com` | `213.133.121.96` — **anderer Server, unangetastet lassen** |
| `pilger.milsh.com` | `46.224.19.41`, A-Eintrag steht |

**Daraus folgt der ganze Aufbau:** Weil niemand von außen hineinkommt, kann kein
Deploy die Dateien hinschieben. Der Server **holt sie sich selbst** aus dem
öffentlichen Repository — dafür braucht er keine Anmeldung, und es muss kein
Port geöffnet werden. Details in `docs/DEPLOYMENT.md`.

> Zwei Sackgassen, die schon Zeit gekostet haben und nicht erneut probiert
> werden sollten: **GitHub Actions mit SSH-Deploy** scheitert daran, dass der
> Runner den Server nicht erreicht. **Deploy Keys und Tokens** sind bei
> `Pasaventures` per Organisationsrichtlinie gesperrt — für dieses Repo
> irrelevant, weil es öffentlich unter `sascha-gif` liegt und gar keine
> Anmeldung braucht.

---

## Aktueller Stand

| Baustein | Status |
|---|---|
| App (PHP 8, PDO) | **fertig**, lokal getestet |
| Datenbankschema + Startdaten | **fertig**, installiert sich beim ersten Seitenaufruf selbst |
| Speichern von Häkchen / Beträgen / Gewicht | **fertig**, End-to-End getestet |
| Schreibschutz per Passwort | **fertig**, getestet |
| Karte (Leaflet, 13 Stopps + Senda Litoral) | **fertig**, Daten aus der DB |
| Container-Stack (Dockerfile, compose) | **fertig**, wird bei jedem Push auf GitHub gebaut und geprüft |
| Selbstaktualisierung (systemd-Zeitgeber) | **fertig**, wartet auf die Einrichtung |
| Live auf pilger.milsh.com | **offen** — einmalige Einrichtung auf dem Server steht aus |

Getestet gegen SQLite wurde vollständig: Rendern, Speichern, Neuladen, Sperren,
Entsperren, Wertebereichsprüfungen, Kaltstart aus leerem Zustand. Der
Container-Stack samt MariaDB wird auf dem GitHub-Runner geprüft — dort wird ein
Betrag über die API geschrieben und direkt aus der Datenbank zurückgelesen.

---

## Was noch fehlt

Ein einziger Aufruf auf dem Server. Über Tailscale, als `root`:

```
ssh root@100.84.10.64
curl -fsSL https://raw.githubusercontent.com/sascha-gif/pilger/main/ops/setup-server.sh | bash
```

Das Skript richtet alles ein — Klonen, Passwörter, Container, Caddy-Eintrag,
Zeitgeber — und prüft am Ende selbst, ob die Seite antwortet. Es ist
wiederholbar; ein zweiter Aufruf bringt nur auf den neuesten Stand und rührt die
erzeugten Passwörter nicht an.

Danach ist nichts mehr zu tun: jeder Merge auf `main` ist spätestens fünf
Minuten später live.

---

## Wo was liegt

```
public/            Document-Root
  index.php        die Seite, rendert alles aus der DB
  api.php          JSON-Schnittstelle für alle Änderungen
  assets/          app.css, app.js
src/               Anwendungscode (Database, Schema, Repo, Helfer)
db/seed.php        kompletter Masterplan-Inhalt als Startdaten
config/            config.example.php — im Container über Umgebungsvariablen ersetzt
ops/               setup-server.sh, pilger-update.sh, systemd-Einheiten
docs/              DEPLOYMENT.md · DATENBANK.md · API.md
Dockerfile         PHP 8.3 + Apache
docker-compose.yml App + MariaDB
.github/workflows/ ci.yml — prüft, deployt nicht
camino-masterplan-2026.html   Referenz der statischen Ursprungsfassung
```

---

## Verwandte Projekte auf derselben Maschine

- **family.milsh.com** — Next.js + Supabase, läuft als systemd-Dienst auf dem
  Host (nicht im Container) auf `172.19.0.1:3000`. Repos: `Pasaventures/family`
  und `sascha-gif/family`. Dessen `UEBERGABE.md` ist die beste Quelle zum Server.
- **nexus** (`sascha-gif/nexus`) — anderer Server (`nexus.helpingbrands.de`),
  hat mit milsh.com nichts zu tun.

---

## Nächste sinnvolle Schritte

1. Einrichtung auf dem Server → live prüfen.
2. Die 12 offenen Unterkünfte buchen; Beträge direkt auf der Seite eintragen
   (Oia und Santiago zuerst — dünnes Angebot bzw. hohe Nachfrage).
3. Rückflugzeiten SCQ→FRA aus der Buchung nachtragen.
4. Fährfahrplan Caminha → A Guarda prüfen.
5. Erst wenn das steht: Etappen direkt auf der Seite bearbeitbar machen
   (`stage.update` liegt in der API schon bereit, die Oberfläche dazu fehlt).
