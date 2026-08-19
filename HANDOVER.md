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
| App (PHP 8, PDO) | **fertig**, live |
| Datenbankschema + Startdaten | **fertig**, installiert sich beim ersten Seitenaufruf selbst |
| Zutritt: sechsstelliger Code für die ganze Seite | **fertig**, Ziffernblock wie am iPad |
| Speichern von Häkchen / Beträgen / Gewicht | **fertig**, End-to-End getestet |
| Etappen abhaken, offen/erledigt als Reiter | **fertig** |
| Fortschrittsbalken gelaufene km | **fertig**, rechnet aus den abgehakten Tagen |
| Stempel-Checkliste (ab Spanien zwei/Tag) | **fertig** |
| Packliste als Reiter | **fertig** |
| „Vor der Abreise": Termine zum Abhaken | **fertig**, Punkte selbst ergänzbar |
| Equipment & Gelenkschutz | **ausgeblendet** auf Wunsch — siehe unten |
| Ernährung & Supplements | **ausgeblendet** auf Wunsch — siehe unten |
| Wetter je Etappe (Open-Meteo) | **fertig**, Vorhersage bzw. Vorjahresmittel |
| Höhenprofil je Etappe | **fertig**, Geländemodell entlang der Küstenlinie |
| Tagebuch: Sprachnotiz, Text, Fotos | **fertig**, offline-fähig |
| Foto-Zeitleiste über alle Etappen | **fertig** |
| Offline-Betrieb (Service Worker) | **fertig** |
| Transkription (Whisper) + Glättung (Claude) | **fertig**, wartet auf hinterlegte Schlüssel |
| Karte (Leaflet, 13 Stopps + Senda Litoral) | **fertig**, Daten aus der DB |
| Container-Stack (Dockerfile, compose) | **fertig**, wird bei jedem Push geprüft |
| Selbstaktualisierung (systemd-Zeitgeber) | **fertig**, läuft |
| Google Health: Schritte, Kalorien, Puls | **fertig**, wartet auf die Freigabe in der Cloud Console |
| Live auf pilger.milsh.com | **fertig** |

Getestet gegen SQLite wurde vollständig: Rendern, Speichern, Neuladen, Anmelden,
Abmelden, Wertebereichsprüfungen, Kaltstart aus leerem Zustand, Upload von Bild
und Aufnahme, Auslieferung über `media.php` samt 403 ohne Anmeldung. Die
Offline-Warteschlange wurde im echten Browser mit abgeschaltetem Netz geprüft:
Eintrag liegt lokal, Warteschlange zeigt ihn an, nach Rückkehr des Netzes ist er
oben und die Schlange leer. Der Container-Stack samt MariaDB wird auf dem
GitHub-Runner geprüft — dort wird ein Betrag über die API geschrieben und direkt
aus der Datenbank zurückgelesen, und eine Datei im Foto-Volume muss einen
kompletten Neubau des Containers überstehen.

---

## Was jetzt als Erstes zu tun ist

**Code setzen.** Beim ersten Aufruf zeigt die Seite nichts als die
Einrichtung: sechs Ziffern auf dem Ziffernblock, danach zur Bestätigung noch
einmal — wie die Gerätesperre am Handy. Kein Benutzername, keine Mailadresse. Danach ist die Seite für alle
anderen zu — samt Tagebuch, Fotos, Kosten und Gewicht. Solange kein Passwort
gesetzt ist, zeigt die Seite ausschließlich diese Einrichtung; offen steht sie
nie.

Wer das Passwort lieber auf dem Server pflegt, trägt `WRITE_PASSWORD=` in
`/opt/pilger-milsh/.env` ein — dieser Wert gewinnt dann, und die Oberfläche
kann ihn nicht mehr ändern.

Zurücksetzen geht nur an der Datenbank:

```
docker compose exec pilger-db mariadb -upilger -p"$DB_PASS" pilger \
  -e "delete from settings where skey='auth_hash'; delete from auth_tokens;"
```

Danach fragt die Seite beim nächsten Aufruf wieder nach einem neuen Passwort.

---

## Wo was liegt

```
public/            Document-Root
  index.php        die Seite, rendert alles aus der DB
  api.php          JSON-Schnittstelle für alle Änderungen
  upload.php       Annahme von Fotos und Sprachaufnahmen (multipart)
  media.php        Auslieferung derselben — nur nach Anmeldung
  gesundheit.php   Rückkehr von Google nach der OAuth-Anmeldung
  sw.js            Service Worker, hält die Seite ohne Netz lesbar
  assets/          app.css, app.js, tagebuch.js
src/               Anwendungscode
  bootstrap.php    Konfiguration, DB, Sitzung, data_path()
  Auth.php         Zutritt: Passwort, Merken-Cookie, Bremse
  gate.php         die Tür vor der Seite (Einrichtung / Anmeldung)
  Database.php     PDO-Hülle, MariaDB und SQLite
  Schema.php       Migrationen, laufen beim ersten Aufruf selbst
  Repo.php         alle Datenbankzugriffe der Seite
  Aussen.php       Wetter und Höhen von Open-Meteo, mit Zwischenspeicher
  Tagebuch.php     Aufnahmen, Bilder, Transkription, Glättung
  Gesundheit.php   Schritte, Kalorien und Puls aus dem Google-Health-Konto
db/seed.php        kompletter Masterplan-Inhalt als Startdaten
db/migrations/     002 Küstenroute · 003 Ankunft · 004 Zutritt ·
                   005 Erledigt/km/Stempel · 006 Wetter+Höhen · 007 Tagebuch ·
                   008 Gesundheitsdaten
config/            config.example.php — im Container über Umgebungsvariablen ersetzt
ops/               setup-server.sh, pilger-update.sh, systemd-Einheiten
docs/              DEPLOYMENT.md · DATENBANK.md · API.md
Dockerfile         PHP 8.3 + Apache, mit gd und exif
docker-compose.yml App + MariaDB, Volumes pilger-db und pilger-data
.github/workflows/ ci.yml — prüft, deployt nicht
camino-masterplan-2026.html   Referenz der statischen Ursprungsfassung
```

Zwei Volumes, beide müssen ins Backup:

| Volume | Einhängepunkt | Inhalt |
|---|---|---|
| `pilger-db` | `/var/lib/mysql` | die Datenbank |
| `pilger-data` | `/var/www/data` | Fotos und Sprachaufnahmen |

---

## Verwandte Projekte auf derselben Maschine

- **family.milsh.com** — Next.js + Supabase, läuft als systemd-Dienst auf dem
  Host (nicht im Container) auf `172.19.0.1:3000`. Repos: `Pasaventures/family`
  und `sascha-gif/family`. Dessen `UEBERGABE.md` ist die beste Quelle zum Server.
- **nexus** (`sascha-gif/nexus`) — anderer Server (`nexus.helpingbrands.de`),
  hat mit milsh.com nichts zu tun.

---

## Nächste sinnvolle Schritte

1. **Passwort setzen** — beim nächsten Aufruf der Seite, dauert zehn Sekunden.
2. Die 12 offenen Unterkünfte buchen; Beträge direkt auf der Seite eintragen
   (Oia und Santiago zuerst — dünnes Angebot bzw. hohe Nachfrage).
3. Rückflugzeiten SCQ→FRA aus der Buchung nachtragen.
4. Fährfahrplan Caminha → A Guarda prüfen.
5. Vor der Abreise die Tagebuch-Schlüssel hinterlegen, wenn aus den
   Sprachnotizen von selbst Text werden soll. Ohne sie bleiben die Aufnahmen
   erhalten und abspielbar — es wird nur nichts verschriftlicht.
6. Am ersten Abend in Porto einmal ausprobieren: aufnehmen, speichern,
   Flugmodus an, noch einen Eintrag, Flugmodus aus. Wenn beides oben landet,
   trägt die Funktion auch die zwölf Tage danach.
7. Offen geblieben: Etappen direkt auf der Seite bearbeitbar machen
   (`stage.update` liegt in der API bereit, die Oberfläche dazu fehlt).

## Google Health — die drei Stolpersteine

Alle drei sind Einstellungen in der Google Cloud Console, keine Codefragen.
Sie stehen auch in der Anleitung auf der Seite selbst.

1. **`redirect_uri_mismatch`** — unter *Autorisierte Weiterleitungs-URIs* muss
   genau `https://pilger.milsh.com/gesundheit.php` stehen. Fehlt der Eintrag,
   bricht Google schon vor dem Zustimmungsbildschirm ab.
2. **`Fehler 403: org_internal`** — der Zustimmungsbildschirm steht auf
   Zielgruppe *Intern*, damit dürfen nur Konten der eigenen
   Workspace-Organisation die App benutzen. Auf *Extern* umstellen, oder sich
   mit einem Konto der Organisation anmelden.
3. **Sieben Tage** — bleibt der Veröffentlichungsstatus auf *Testing*, macht
   Google das Dauer-Token nach einer Woche ungültig. Auf *In production*
   stellen; dann gilt es unbegrenzt.

Eine Freigabe durch Google braucht es **nicht**. Die Prüfung entfernt nur den
Warnbildschirm „Google hat diese App nicht überprüft" und ist bei *sensitive*
Rechten keine Voraussetzung — sie wäre es nur bei *restricted* Rechten wie
Gmail. Ungeprüft veröffentlicht gilt eine Grenze von 100 Nutzern; gebraucht
wird einer.

## Ausgeblendete Abschnitte

Zwei Abschnitte sind auf Wunsch aus der Seite genommen; die übrigen sind
nachgerückt. Aktuelle Nummerierung:

```
01 Profil · 02 Anreise · 03 Ankunft · 04 Etappen
05 Packliste · 06 Kosten · 07 Countdown · 08 Tagebuch
```

- **Equipment & Gelenkschutz** — war weitgehend eine Kurzfassung der
  Packliste, die dieselben Punkte ausführlicher führt.
- **Ernährung & Supplements** — Tagesprotokoll und Pillen-Zeile.

**Gelöscht wurde nichts.** `equipment_cards`, `equipment_items` (samt
Häkchen), `nutrition_pills` und `nutrition_slots` stehen unverändert in der
Datenbank, die Repo-Methoden und die API-Aktion `equip.toggle` ebenfalls.
Wieder einblenden heißt: den jeweiligen Commit rückgängig machen, mehr nicht.

Die Sprungziele (`#packliste`, `#kosten`, …) haben sich nie geändert — nur die
Nummern davor.

## Was bewusst nicht gebaut wurde

- **Kein Speichern des Originalfotos.** Bilder werden auf 1600 px verkleinert.
  Zwölf Tage Handyfotos in voller Größe sprengen jedes Volume, und für ein
  Reisetagebuch reicht die Kante.
- **Keine ausgedachten Wetterwerte.** Weiter als 16 Tage voraus gibt es keine
  Vorhersage. Statt eine zu erfinden, steht dort das Mittel derselben
  Kalendertage der Vorjahre — und es steht auch dran.
- **Keine erfundenen Höhenmeter.** Die Höhen stammen aus dem Geländemodell
  entlang der hinterlegten Küstenlinie. Das ist keine GPX-Spur, und auf den
  letzten vier Etappen liegt zwischen zwei Stützpunkten eine Gerade. Der
  Quellenhinweis unter den Etappen sagt das.
- **Kein Dazuerfinden beim Glätten.** Das Modell darf kürzen und Versprecher
  wegräumen, aber keine Orte, Zahlen oder Erlebnisse ergänzen.
