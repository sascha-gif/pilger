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
| „Vor der Abreise": Termine zum Abhaken | gebaut, auf Wunsch **ausgeblendet** |
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
| Journal (`/journal.php`) | **Prototyp**, verlinkt im Seitenfuß — siehe unten |
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
  journal.php      das Journal — dieselben Daten, zum Lesen statt zum Bedienen
  sw.js            Service Worker, hält die Seite ohne Netz lesbar
  assets/          app.css, app.js, tagebuch.js, journal.css, journal.js
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
db/unterkuenfte.php wo geschlafen wurde — Quelle der Tabelle `lodgings`
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
2. Die **5 offenen Unterkünfte** buchen; Beträge direkt auf der Seite
   eintragen. E1 bis E5 und E12 sind gebucht, siehe `CLAUDE.md` — damit auch
   Oia und Santiago, die beiden kritischen.
   Eine Buchung heißt hier: Kostenzeile **und** Etappe (`target`, `note`,
   `booking_url` raus) in `db/seed.php` **und** eine Migration — Vorlage dafür
   ist `db/migrations/019_vila_do_conde.php`.
3. Fährfahrplan Caminha → A Guarda prüfen.
4. Vor der Abreise die Tagebuch-Schlüssel hinterlegen, wenn aus den
   Sprachnotizen von selbst Text werden soll. Ohne sie bleiben die Aufnahmen
   erhalten und abspielbar — es wird nur nichts verschriftlicht.
5. Am ersten Abend in Porto einmal ausprobieren: aufnehmen, speichern,
   Flugmodus an, noch einen Eintrag, Flugmodus aus. Wenn beides oben landet,
   trägt die Funktion auch die zwölf Tage danach.
6. Offen geblieben: Etappen direkt auf der Seite bearbeitbar machen
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

## Wenn die Prüfung rot wird, ohne dass sich Code geändert hat

Am 03.09.2026 war die CI dreimal rot, obwohl an der Anwendung nichts fehlte —
die Seite lief, alle Migrationen griffen. Der Grund lag außerhalb des Repos:
`curlimages/curl:latest` ist ein **gleitender Tag**, und in curl 8.22.0 steht

> `cookie: refuse to load cookies set against a PSL domain`

Der Container heißt im Prüfnetz `pilger-app` — ein Name **ohne Punkt**. Den
behandelt curl seither wie eine öffentliche Endung und lädt das Sitzungs-Cookie
nicht mehr aus der Datei. Die Anmeldung hielt dadurch genau einen Aufruf lang,
und die Prüfung sah statt der Seite die Anmeldemaske. Behoben, indem der
Prüf-Container `pilger-app.example.com` per `--add-host` auf dieselbe Adresse
gelegt bekommt. An `docker-compose.yml` ändert das nichts — dort steht die
Produktion, nicht die Prüfung.

Zwei Lehren, die über diesen Fall hinausgehen:

- **Ein rot gewordener Lauf ohne Codeänderung heißt: draußen hat sich etwas
  bewegt.** Erst die Umgebung prüfen, dann den eigenen Code verdächtigen.
- **Nackte `grep -q` in einem Prüfschritt sind eine Falle.** Schlägt eines
  fehl, endet der Lauf stumm mit „exit code 1", und niemand weiß, welches.
  Jede Zusicherung sagt jetzt ihren Namen, und bei einem Fehlschlag kommen
  Größe, Anfang und Ende der Seite sowie das Apache-Protokoll dazu. Genau das
  hat den Fall dann in einem einzigen Lauf aufgeklärt.

Der Deploy hängt **nicht** an der CI: der Server holt sich `main` alle fünf
Minuten selbst. Eine rote Prüfung hält also nichts auf — sie ist ein Warnlicht,
keine Schranke.

## Unterwegs bedienbar — was am Telefon zählt

Die Seite wird auf dem Camino mit einer Hand bedient, oft im Gehen. Zwei
Regeln, die dabei mehr zählen als alles andere; beide stehen im
`@media (max-width:560px)`-Block:

- **Keine Texteingabe unter 16 px.** iOS zoomt beim Antippen in jedes kleinere
  Feld hinein und von selbst nicht wieder heraus. Fünf Felder hatten eigene,
  spezifischere Regeln (`.ctbl input.cost`, `.tb-kopf select`,
  `.tb-neu textarea`, `.gsd label input`, `.tb-eform input`) und mussten
  einzeln nachgezogen werden.
- **Häkchen sind die häufigste Geste** — abhaken, was gepackt, gelaufen und
  gestempelt ist. Sie waren 16 bis 17 px groß; jetzt 22 px, und der Flex-Kasten
  darf sie nicht mehr schmalquetschen (`flex:none`). Aufklapper und Links in
  den Stempelorten waren 17 px hoch und haben Polsterung bekommen.

Nachgemessen wird mit einem Skript, das bei 390 × 844 nach drei Dingen sucht:
Elemente, die über den Rand ragen (ohne die, die in einem Scrollkasten sitzen),
Texteingaben unter 16 px, und Tippflächen unter 40 px. Querscrollen gibt es
nicht.

## Mehrere Notizen pro Tag

Das ging von Anfang an — jedes Speichern legt einen eigenen Eintrag an, eine
Beschränkung pro Tag gab es nie. Zu sehen war es nur nicht: mehrere Einträge
desselben Tages sahen identisch aus. Sie tragen jetzt **Nummer und Uhrzeit**
(„2. von 3 · 19:42"), und unter dem Speichern-Knopf steht, dass das so gedacht
ist. Die Nummer zählt in der Reihenfolge des Anlegens, die Liste zeigt die
neueste zuerst.

## Das Tagebuch ist ein Zeitstrahl, kein Formular

Es liest ja jemand: Sascha später, und die Familie während der Reise. Deshalb
sind die Einträge nach Tagen gebündelt — eine Schiene mit einem Punkt je Tag,
Kopfzeile aus Wochentag, Datum und Ort, darunter die Notizen dieses Tages von
früh nach spät. Dieselbe Formensprache wie die Foto-Zeitleiste darunter.

**Lesen ist der Normalzustand.** Löschkreuze, Bildunterschrift-Felder und die
Knopfleisten unter den Einträgen erscheinen erst über „Bearbeiten" oben rechts
(setzt `data-edit="1"` auf `#tagebuch`). Nicht gemerkt: nach jedem Neuladen ist
wieder Tagebuch, nicht Werkstatt. Das löst zugleich ein echtes Problem — das
Löschkreuz war vorher nur bei Mausberührung sichtbar und am Rechner schlicht
nicht zu finden.

Zwei Fallen, die dabei aufgefallen sind:

- **`nl2br()` und `white-space:pre-wrap` zusammen** zählten jeden Absatz
  doppelt; zwischen zwei Sätzen klaffte ein halber Bildschirm. Der Text kommt
  jetzt über `absaetze()` in `helpers.php` als echte `<p>`.
- **`.tagblock` ist ein `<section>`** und erbte damit `section{padding:56px 0}`.
  Der Tagespunkt hing 56 px über seiner Überschrift. Padding ausgeschrieben.

## Hochgeladen und trotzdem nicht zu finden

Am 21.09.2026 war eine Sprachnotiz mit Bildern hochgeladen und nirgends zu
sehen. Sie war da — nur hinter einer zugeklappten Tagesüberschrift. Zwei Dinge
kamen zusammen:

1. **Der Eintrag bekommt nicht immer den heutigen Tag.** Liegt das Datum der
   gewählten Etappe in der Vergangenheit, gewinnt es (siehe oben, „Der Tag
   eines Eintrags"). Das ist gewollt — abends eine abgehakte Etappe zu wählen
   heißt „das war gestern". Nur stand nirgends, was dabei herauskommt.
2. **Ein Tag, der nicht der neueste ist, ist zugeklappt.** Der Eintrag landete
   also in einem Block, der beim Neuladen zu blieb.

Beides ist behoben:

- Unter der Tagesauswahl steht jetzt **„Kommt auf Montag, 21. September
  (heute)"** und ändert sich mit der Auswahl mit. Keine Überraschung mehr.
- Nach einem erfolgreichen Upload meldet `schickePaket()` den Tag per
  `CustomEvent('tag-aufklappen')`, `app.js` schreibt ihn in `pilger-tage` und
  klappt den Block auf — die Seite lädt direkt danach neu, und der Eintrag ist
  zu sehen.

Der Zuhörer für das Ereignis steht **außerhalb** der Prüfung auf vorhandene
Tagblöcke. Beim allerersten Eintrag gibt es noch keinen einzigen — und genau
dann wird er gebraucht. Beim ersten Anlauf stand er drinnen, und der Test fiel
prompt darauf herein.

## Jeder Tag im Tagebuch klappt für sich auf

Zwölf Etappen mit Notizen und Fotos sind zugeklappt ein Inhaltsverzeichnis der
Reise und aufgeklappt eine Bildschirmlänge ohne Ende. Der Tagblock ist deshalb
ein `<details>` mit dem Tageskopf als `<summary>` — dieselbe Machart wie die
Abschnitte oben, nur mit kleinerem Haken.

Was zugeklappt trotzdem dasteht: Wochentag, Datum, Zielort, Etappencode und
**was drinsteckt** — „3 Notizen · 5 Bilder". Ohne die letzte Angabe klickt man
sich durch zwölf Tage, um ein Bild wiederzufinden.

**Offen ist der neueste Tag.** Den sucht man, wenn man die Seite aufmacht.
Alles weitere merkt sich das Gerät in `localStorage` unter `pilger-tage` — und
zwar als **Ja oder Nein je Tag**, nicht als Liste der offenen. Der Unterschied
ist der Fall, der sonst weh tut: ein Tag, von dem nichts gespeichert ist, ist
ein *neuer* Tag. Er folgt der Vorgabe der Seite statt zugeklappt zu erscheinen,
nur weil er beim letzten Besuch noch nicht existierte — sonst verschwände die
gerade gespeicherte Notiz hinter einer zugeklappten Überschrift.

Der Tagespunkt an der Schiene sitzt weiter am Kopf, auch zugeklappt. Und weil
der Block kein `<section>` mehr ist, erbt er auch das `section{padding:56px 0}`
von weiter oben nicht mehr — das ausgeschriebene `padding` in `.tagblock`
bleibt trotzdem stehen, sonst rutscht der Punkt.

## Die Warteschlange sagt, warum es klemmt

Der Kasten über dem Tagebuch zeigt, was noch auf dem Gerät liegt. Er zeigt
jetzt auch **den Grund**: `p.fehler` wird beim fehlgeschlagenen Versuch schon
seit jeher gespeichert, stand aber nirgends. Man sah nur „5 Versuche" und
konnte nur raten.

Dazu drei Kleinigkeiten, die beim Festhängen helfen:

- **Wie weit es gekommen ist** — „10 Fotos (3 schon oben)". Ein Paket lädt Bild
  für Bild hoch; ohne die Zahl sieht ein halb erledigtes Paket aus wie ein gar
  nicht begonnenes.
- **Ein Knopf zum sofortigen Wiederholen.** Der Selbstlauf versucht es alle
  45 Sekunden — wer gerade sieht, dass wieder Balken da sind, will nicht warten.
- **Anderer Text ab dem zweiten Fehlversuch.** „Wartet auf Netz" ist dann die
  falsche Auskunft; es steht stattdessen da, dass es klemmt und nichts verloren
  ist.

## Bilder werden auf dem Handy verkleinert, nicht erst auf dem Server

Bis zum 18.09.2026 ging jedes Foto in voller Größe über die Leitung, und erst
der Server rechnete es auf 1600 px herunter. Das war an drei Stellen falsch:

1. **Die Datenmenge.** Ein heutiges Handyfoto hat 12 bis 48 Megapixel und 3 bis
   6 MB. Fünfzehn davon sind 60 bis 90 MB — im portugiesischen Mobilnetz, an
   einem Tag mit 25 gelaufenen Kilometern. Gespeichert wurde davon am Ende
   ohnehin nur die 1600-px-Fassung. Die vollen Megabyte zu senden hat also
   nichts gebracht außer Wartezeit und Gelegenheiten für den Abbruch.
2. **Der Speicher auf dem Server.** `gd` legt ein Bild unkomprimiert ab, vier
   Byte je Pixel. 24 Megapixel sind damit 97 MB, und `imagerotate()` für die
   EXIF-Lage hält kurz zwei Fassungen gleichzeitig. Das PHP-Bild bringt 128 MB
   `memory_limit` mit — der Prozess starb also mitten im Upload, und was beim
   Browser ankam, war keine verwertbare Antwort. Das Limit steht jetzt im
   `Dockerfile` auf 512 MB.
3. **Der iOS-Fall mit den 0 Bytes.** In der Warteschlange lag bisher der
   `File`-Verweis aus der Galerie. Verliert iOS den Zugriff darauf — nach einem
   Neustart, nach Speicherdruck —, liefert derselbe Verweis später 0 Bytes, und
   das Paket scheitert bis in alle Ewigkeit.

`verkleinere()` in `public/assets/tagebuch.js` rechnet deshalb jedes Bild schon
bei der Auswahl auf **2000 px lange Kante, JPEG-Güte 0,85** herunter. Aus 5 MB
werden ungefähr 400 KB. Die 2000 px sind Absicht: der Server macht seine
1600 px trotzdem, die Reserve kostet nichts und erlaubt später ein höheres
`MAX_KANTE` ohne Änderung am Client.

Was dabei sonst noch herausfällt:

- Das Ergebnis ist ein **frischer Blob im Speicher**, kein Verweis auf die
  Galerie. Der 0-Byte-Fall von oben kann damit gar nicht mehr auftreten.
- **HEIC wird zu JPEG.** Vorher landete HEIC unverändert auf der Platte, weil
  `gd` das Format nicht kennt — in voller Größe und ohne Vorschaubild.
- Die **EXIF-Lage wird angewandt**, nicht mitgeschleppt:
  `createImageBitmap(datei, { imageOrientation: 'from-image' })`. Das fertige
  JPEG hat kein EXIF mehr, steht aber richtig herum. Geprüft mit einem Bild
  800 × 400 und Orientation 6 — heraus kommen 400 × 800.

Vier Wege gehen bewusst am Verkleinern vorbei, jeder mit Grund:

| Fall | Was passiert |
|---|---|
| Kein Bild (Sprachnotiz) | unverändert |
| Kleiner als 600 KB | unverändert — das Umrechnen lohnt nicht |
| Browser kann das Format nicht | Original, langsam ist besser als gar nicht |
| Ergebnis größer als das Original | Original |

Gerechnet wird **eins nach dem anderen**. Fünfzehn Handyfotos gleichzeitig zu
dekodieren bringt Safari auf dem iPhone zuverlässig um.

`schickePaket()` ruft `verkleinere()` vor jedem Foto noch einmal auf. Pakete,
die schon vor dieser Änderung in der Warteschlange lagen, enthalten nämlich
noch das volle Handyfoto und würden sonst ewig weiterscheitern. Damit das nicht
bei jedem Wiederholversuch ein Stück Bildqualität kostet, steigt `verkleinere()`
bei einem JPEG innerhalb der langen Kante sofort wieder aus — der zweite und
dritte Aufruf geben dasselbe Objekt zurück, unverändert.

Beide Wege ins Tagebuch gehen darüber: der neue Eintrag und das Nachreichen an
einem bestehenden (`.tbe-fotos`). Für die Doppelt-Erkennung reicht deshalb ein
Blick auf die fertige Datei nicht mehr — `wahlQuellen` merkt sich Name, Größe
und Zeitstempel der **Originale**, Index für Index parallel zu
`gewaehlteFotos`. Wer beides anfasst, muss beides anfassen.

## „Stand unbekannt" im Seitenfuß

Der Commit kommt als Build-Argument ins Image: `ops/pilger-update.sh` exportiert
`GIT_COMMIT` und `BUILD_TIME`, `docker-compose.yml` reicht sie an den Build
weiter, das `Dockerfile` macht `ENV PILGER_COMMIT` daraus. Am 22.09.2026 stand
im Fuß trotzdem **„Stand unbekannt"** — und damit war die Frage „läuft mein
Stand schon?" wieder unbeantwortbar, genau in dem Moment, in dem sie am meisten
zählte.

Woran es liegt, lässt sich von hier aus nicht klären: der Server ist aus dieser
Umgebung nicht erreichbar. Deshalb steht jetzt **daneben, wann die Dateien
zuletzt angefasst wurden**. Das kommt ohne Build-Argument aus: `git reset
--hard` schreibt geänderte Dateien mit der aktuellen Zeit, und `filemtime()`
über `index.php`, `app.js`, `tagebuch.js`, `app.css` und `src/Tagebuch.php`
nimmt davon die jüngste. Fast jeder Deploy fasst eine dieser Dateien an.

    Stand unbekannt · Dateien vom 22.09. 16:10
    Stand abc1234 · gebaut 22.09. 21:05 · Dateien vom 22.09. 16:10

Dazu liest der Fuß die Umgebung nicht mehr nur über `getenv()`, sondern auch
aus `$_SERVER`, `$_ENV` und `apache_getenv()` — je nachdem, wie PHP unter
Apache läuft, steht sie in einem davon.

## Die Warteschlange darf nicht nur wachsen

Am 22.09.2026 stand in der Warteschlange ein Dutzend Pakete mit bis zu
**76 Fehlversuchen** — und mit jedem neuen Anlauf ueber „Bearbeiten → Fotos
hinzufügen" kam eins dazu. Das ist folgerichtig (jeder Griff in die Galerie ist
ein neues Paket) und trotzdem falsch: die Liste wurde länger statt kürzer, der
Selbstlauf zog alle 45 Sekunden Akku, und wegwerfen konnte man nichts.

Drei Dinge dagegen:

- **Verwerfen je Paket** (`.qweg`) und **„Alle verwerfen"**, sobald mehr als eins
  wartet. Gefragt wird unterschiedlich: bei Fotos liegen die Bilder weiter in
  der Kamerarolle, bei einer **Sprachnotiz ist die Aufnahme danach weg**. Das
  steht so in der Rückfrage.
- **Nach `AUFGEBEN_AB = 5` Fehlversuchen läuft nichts mehr von selbst.** Ein
  Paket, das 76-mal gescheitert ist, geht beim 77. Mal auch nicht durch. Der
  Kasten sagt das auch so: „Nach 5 Fehlversuchen wird nicht mehr von selbst
  weiterprobiert — das kostet nur Akku."
- **Der Knopf setzt den Zähler zurück.** Sonst käme ein aufgegebenes Paket auch
  von Hand nicht mehr los. `abarbeiten(stillschweigend)` filtert aufgegebene
  Pakete nur im Selbstlauf heraus — von Hand laufen alle mit.

## Fotos kommen nicht an, Sprachnotizen schon — der zweite Weg

Stand 22.09.2026: Auf dem Server scheitern **Fotos** seit Tagen mit
`upload HTTP 400 — Es kam keine Datei an. [IMG_1130.jpg, 0,9 MB]`, bei bis zu
76 Versuchen je Paket. **Sprachnotizen gehen über denselben Endpunkt durch** —
in jedem Paket „Sprachnotiz + N Fotos" nennt der Fehler ein Foto, nie die
Aufnahme. Und 0,7 bis 1,3 MB sind weit unter jeder Grenze; an der Größe liegt
es nicht.

Damit ist ausgeschlossen: Anmeldung (die wäre 403), Größe (0,9 MB gegen 64 MB),
`post_max_size` (die Prüfung in `upload.php` hätte die Zahlen genannt) und der
Weg an sich (Audio geht ja).

**Zwei Dinge dagegen:**

1. **Die Diagnose.** Scheitert die Dateiprüfung, hängt `upload.php` jetzt an,
   was der Server über sich selbst sieht: `CONTENT_LENGTH`, `CONTENT_TYPE`, die
   Feldnamen in `$_POST` und `$_FILES`, den `UPLOAD_ERR_*`-Code und die
   Einstellungen `post_max_size`, `upload_max_filesize`, `file_uploads`,
   `max_file_uploads` sowie das Temp-Verzeichnis samt Schreibrecht. Steht alles
   in der Warteschlangen-Meldung. Damit ist die nächste Runde keine Raterei
   mehr: `files_felder=` leer bei gefülltem `post_felder` heißt, PHP hat den
   Rumpf zerlegt und den Dateiteil verworfen; beides leer heißt, der Rumpf kam
   nie an.
2. **Ein zweiter Weg, der nachweislich funktioniert.** Texteinträge kommen als
   JSON an — also kommt ein Bild als Base64 im JSON auch an. Scheitert ein Foto
   mit genau „Es kam keine Datei an", schickt `schickePaket()` denselben Inhalt
   noch einmal an `api.php` mit `action: 'foto.daten'`. Der Endpunkt legt die
   Bytes in eine Temp-Datei und gibt sie an dasselbe `Tagebuch::nimmFoto()` —
   Ergebnis identisch: geprüft 1600 × 1066, Vorschaubild da, gleiche Bytezahl
   wie über den normalen Weg.

Base64 bläht die Daten um ein Drittel auf. Deshalb ist das der **Rückfall** und
nicht der Normalweg: erst multipart, und nur bei genau dieser Meldung JSON.
Steht die Ursache fest, kann der Rückfall bleiben — er kostet nichts, solange
er nicht gebraucht wird.

Geprüft mit abgefangenem `upload.php`, das genau Saschas 400er zurückgibt: das
Bild landet trotzdem im Tagebuch, die Warteschlange ist danach leer.

## „Es kam keine Datei an." — und was dahintersteckt

Am 18.09. hingen zwei Fotopakete (15 und 10 Bilder) mit genau dieser Meldung.
Sie kommt aus `public/upload.php` und heißt: der Server hat die Anfrage
bekommen, die Anmeldung galt, aber `$_FILES['datei']` war leer.

`UPLOAD_ERR_NO_FILE` hat dafür nur wenige Ursachen, und eine davon ist die
wahrscheinlichste: **liegt die Sendung über `post_max_size`, verwirft PHP den
kompletten Rumpf.** `$_POST` und `$_FILES` sind dann beide leer — es sieht aus,
als wäre nie eine Datei mitgeschickt worden. Das ist auch der Grund, warum ein
Paket komplett stehen bleibt: die Fotos gehen eins nach dem anderen hoch, und
das erste, das scheitert, bricht die Kette ab.

Deshalb drei Dinge:

1. **Der Endpunkt rechnet nach.** Sind `$_POST` und `$_FILES` leer, obwohl ein
   `Content-Length` ankam, das über der Grenze liegt, steht die Grenze und die
   tatsächliche Größe in der Antwort statt „keine Datei". `ini_bytes()` in
   `src/helpers.php` übersetzt dafür die INI-Kurzschreibweise (`72M`).
2. **Die Meldung im Browser nennt Datei und Größe** — `[IMG_4711.jpg, 0,4 MB]`.
   „Es kam keine Datei an" heißt bei 0,4 MB etwas völlig anderes als bei 45 MB,
   und ohne die Zahl ist das nicht zu unterscheiden. Konnte der Browser das
   Bild nicht verkleinern, steht `nicht verkleinerbar` dabei.
3. **Die Grenzen stehen höher** — `upload_max_filesize = 64M`,
   `post_max_size = 72M`. Nach dem Verkleinern auf dem Gerät sind Fotos
   ohnehin unter 1 MB; die Grenze greift nur noch für Dateien, die der Browser
   nicht lesen konnte, und die sollen dann wenigstens durchkommen.

Was in derselben Nacht **funktioniert** hat: die Sprachnotiz. Sie lag mit zwei
Fehlversuchen in derselben Warteschlange und war am Morgen durch. Das Problem
war also von Anfang an nur bei den Bildern, bei der Größe — nicht bei der
Anmeldung und nicht beim Weg an sich.

## Der Tag eines Eintrags — und warum er nicht aus der Etappe kommt

Bis zum 18.09.2026 hing der Tag eines Tagebucheintrags an der **Etappe**. Das
ging solange gut, wie eine Etappe genau einen Tag bedeutet — und ging schief,
sobald sie das nicht tut. Das Basislager Porto deckt den 17. und den 18.09. mit
einem `date_iso` ab (dem 18.). Eine Notiz vom Ankunftstag bekam damit die Zahlen
des Orga-Tags, und im Zeitstrahl standen beide Tage unter einer Überschrift.

Jetzt gilt überall der **Tag des Eintrags** (`diary_entries.day_iso`), und die
Etappe ist nur noch der Rückfall:

- `tagebuch.js` schickt beim Speichern das **Datum des Geräts**. Nur wenn die
  gewählte Etappe ein Datum trägt, das schon vorbei ist, gewinnt dieses — dann
  hat er bewusst einen früheren Tag ausgesucht, genau dafür gibt es im
  Tagesfeld die Gruppe „Gerade abgehakt".
- `Tagebuch::tagesfakten()` liest `day_iso` zuerst.
- Der Zeitstrahl in `index.php` bündelt nach `day_iso` und nimmt die Überschrift
  daher.
- Am Eintrag steht im Bearbeiten-Modus ein **Datumsfeld**. Eine Notiz, die
  morgens über gestern gesprochen wird, lässt sich damit auf gestern schieben —
  und ein „Neu ausbauen" rechnet dann mit den richtigen Zahlen.
  API: `tagebuch.tag` mit `id`, `tag`, optional `stage`.

Überall `?:` statt `??`: ein leeres Feld ist hier kein Wert, und `'' ?? $x` gibt
den leeren String zurück.

## Wie genau die Linie auf der Karte ist

Die Linie kommt aus `db/kuestenroute.php`: **35 Stützpunkte** über 266 km, mit
einer Geraden dazwischen. Je weiter zwei Punkte auseinanderliegen, desto mehr
schneidet sie ab. Am 21.09.2026 gemessen waren die schlimmsten Lücken
Pontevedra → Caldas de Reis mit **19,3 km**, Padrón → Santiago mit 18,5 km und
Caldas → Padrón mit 14,7 km — im galicischen Binnenland sah die Route aus wie
mit dem Lineal gezogen.

Dort stehen jetzt **Barro/A Portela, Valga, Pontecesures und O Milladoiro**
dazwischen. Die größte Lücke sinkt damit auf 13,5 km. Alle vier sind
nachgeschlagene Orte am Weg, keine geratenen Zwischenpunkte.

**Weiter kommt man von hier aus nicht.** Die Quellen mit dem echten Track sind
aus dieser Umgebung gesperrt (403 vom Egress-Proxy): Overpass/OpenStreetMap,
waymarkedtrails, gronze.com, caminodesantiago.gal, santiagoways.com, dazu jeder
offene Geocoder (Nominatim, Photon, Geoapify). Für die restlichen Zwischenorte
— Saiáns, Bouzas, Chapela, Cesantes, Viladesuso — war über die Websuche keine
belegte Koordinate zu bekommen. Geraten wird hier nichts; wer das nachholen
will, braucht einen erreichbaren Geocoder oder eine GPX-Datei.

## GPX-Import — der Weg, wie er wirklich läuft

Unter der Karte steht (nur angemeldet, zugeklappt) **„Weg genauer machen — GPX
hochladen"**. Eine echte Aufzeichnung ersetzt die Stützpunkte vollständig.

Der Ablauf: `upload.php` mit `art=gpx` → `Route::ausGpx()` liest die Punkte →
`Route::eindampfen()` dünnt sie aus → eine Zeile in `map_routes` mit
`quelle = 'gpx'`.

Ein paar Entscheidungen, die nicht offensichtlich sind:

- **Ausdünnen ist keine Kür.** Eine GPX-Datei über 266 km hat schnell
  hunderttausend Punkte. Douglas-Peucker mit 10 m Toleranz wirft alles weg, was
  die Form nicht ändert; reicht das nicht, verdoppelt sich die Toleranz, bis
  höchstens 3000 Punkte übrig sind. Gemessen an einer 3,4-MB-Testdatei:
  60.001 Punkte → 234 Punkte, 5 KB JSON, 0,7 Sekunden.
- **Iterativ statt rekursiv.** Douglas-Peucker rekursiv zu schreiben ist
  kürzer, legt aber bei hunderttausend Punkten den Stack um — und die Datei
  kommt von außen.
- **Getrennte Zeile statt Überschreiben.** `quelle` unterscheidet `plan` von
  `gpx`. So darf `025_route_stuetzpunkte` die Planlinie weiter neu einspielen,
  ohne einen hochgeladenen Track zu zerstören. `Repo::mapRoutes()` zeigt den
  Track allein, sobald einer da ist — eine Näherung daneben zu zeichnen hilft
  niemandem.
- **Keine Entities beim Parsen.** `loadXML()` bekommt `LIBXML_NONET` und
  ausdrücklich **nicht** `LIBXML_NOENT`. Eine hochgeladene Datei darf den
  Server nicht dazu bringen, `/etc/passwd` zu lesen. Mit einem XXE-Versuch
  geprüft: die Punkte kommen an, die Entity wird nicht aufgelöst.
- **GPX 1.0 und 1.1, mit und ohne Präfix.** Gelesen wird mit
  `getElementsByTagNameNS('*', …)`, der Namensraum ist also egal. Fehlen
  `trkpt`, werden `rtept` und `wpt` probiert.

Mit „Track entfernen" (`route.gpx.loeschen`) geht es zurück auf die
Stützpunkte.

## Die Karte zeigt, wo er steht

Bis zum 20.09.2026 hatte die Karte genau zwei Sorten Punkte: `map_hub = 1`
(Porto und Santiago) in Gelb, alles andere in Atlantikblau. Am fünften Tag sah
sie damit aus wie am ersten — der gelbe Punkt stand die ganze Reise lang auf
Porto, als wäre er nie losgegangen.

Jetzt liefert `Repo::mapStops()` `done`, `date_from` und `date_iso` mit, und
`index.php` rechnet daraus vier Stände:

| Stand | Punkt | heißt |
|---|---|---|
| `fertig` | grün `#2e7d32` | abgehakt — das ist gelaufen |
| `heute` | gelb `#f4b400`, dunkler Rand, größer | der heutige Tag fällt in diese Etappe |
| `vorbei` | rostrot `#a4341f` | Datum durch, Häkchen fehlt |
| `offen` | anthrazit `#232a2e` | kommt noch |

**Vier Farbtöne, keine zwei aus derselben Familie.** Die erste Fassung hatte
`offen` in Atlantikblau `#1f5d6c` und `vorbei` als weißen Punkt mit grünem
Ring. Auf dem Handy, bei 7 Pixeln Radius und in der Sonne, sahen Grün und
Dunkelpetrol gleich aus — und der Ringunterschied war gar nicht zu erkennen.
Jetzt stehen Gelb, Grün, Rostrot und Anthrazit nebeneinander, und keine zwei
lassen sich verwechseln.

**`vorbei` ist mit Absicht ein eigener Stand** und wird nicht stillschweigend zu
`fertig` gerechnet. Es kann heißen „vergessen abzuhaken" oder „einen Tag
hinterher" — beides will man sehen, und an den Häkchen hängen die Stempel. Das
Rostrot ist dieselbe Farbe, die auch eine klemmende Warteschlange bekommt: hier
ist etwas nachzutragen.

`map_hub` bleibt, entscheidet aber nur noch über die Größe: Start und Ziel sind
etwas dicker als die Zwischenstationen.

Die Legende zeigt nur Stände, die es auch gibt (`array_count_values` über die
Stopps) — vor der Abreise gibt es kein „geschafft", und „vorbei, nicht
abgehakt" ist hoffentlich meistens leer.

**Ein Häkchen färbt den Punkt sofort um**, ohne Neuladen: der Etappen-Haken
schickt ein `CustomEvent('etappe-abgehakt')` los, die Karte hört darauf. Damit
das Wegnehmen auch wieder richtig landet, liefert der Server je Stopp **zwei**
Stände mit — `st` (mit Häkchen) und `sz` (nur nach dem Kalender). Ohne `sz`
wäre nach dem Wegnehmen nicht mehr bekannt, ob der Tag heute, vorbei oder noch
vor einem liegt.

## Eine Etappe kann mehr als einen Tag dauern

`stages.date_iso` hält genau ein Datum. Für E1 bis E12 stimmt das — ein Tag,
eine Etappe. Das Basislager Porto steht aber für den 17. **und** den 18.09., und
weil dort der 18. eingetragen ist, zeigte die Etappenkarte am Morgen des 18. nur
die 146 Schritte der ersten Stunden dieses Tages. Der 17. — Anreisetag mit Flug,
Metro und erstem Gang durch Porto — kam nirgends vor.

`date_from` (Migration `020_tagespanne`) sagt jetzt, wo eine Etappe anfängt. Bei
allen Etappen mit einem Tag ist das dasselbe Datum wie `date_iso`; nur Porto
fängt früher an. `etappen_tage()` in `src/helpers.php` zählt daraus die Tage auf.

Die Etappenkarte zeigt seitdem **eine Zeile je Tag**, mit dem Datum davor, wenn
es mehr als einer ist. Nicht summiert: eine Summe aus einem fertigen und einem
laufenden Tag ist keine Zahl, mit der sich etwas anfangen lässt.

Der laufende Tag ist dabei eigens gekennzeichnet — gestrichelter Rahmen, und
statt „gemessen" steht „Zwischenstand · Stand 09:48 Uhr". Der Vergleich
„+3,8 km gegenüber Plan" entfällt dort, aus demselben Grund wie beim Ausbau.

Auf dem Handy nimmt so eine Zeile die volle Breite, und die Beschriftung rutscht
darunter: neben dem Meilenstein bleiben keine 250 px, und mit der Beschriftung
daneben brachen die Zahlen nach jedem zweiten Wort um.

## Ein laufender Tag hat keine Tagessumme

Am Morgen des 18.09. baute der Ausbau eine Notiz von 9:04 Uhr zu „meine Uhr hat
am Ende ganze 146 Schritte gezählt" aus. Die Zahl stimmte — sie war der Stand um
kurz nach neun. Als Tagesbilanz gelesen war sie Unsinn.

`tagesfakten()` prüft deshalb, ob der Tag des Eintrags der heutige ist. Wenn ja:

- Die Zahlen der Uhr stehen als **„Zwischenstand, Stand HH:MM — keine
  Tagessumme"** im Rahmen, nicht als „Von der Uhr gemessen".
- Ein zusätzlicher Satz sagt, dass der Tag noch läuft und wie spät es ist.
- Der Vergleich „x km mehr als die geplante Etappe" **entfällt**. Eine halb
  gelaufene Strecke gegen eine ganze Etappe zu rechnen ergibt nichts.

Dazu zwei Regeln im Systemtext: aus einem Zwischenstand wird keine Bilanz, und
umgekehrt gehören die Zahlen eines **abgeschlossenen** Tages in den Text — ein
gelaufener Tag ohne seine Kilometer ist ein halber Eintrag.

## Die Uhrzeit ist die, die er am Handgelenk sieht

`date_default_timezone_set()` stand auf `Europe/Berlin`. Portugal geht dem im
Sommer eine Stunde nach, also stand über einer Notiz von 9:04 die Zeit „10:04".

`reise_zeitzone()` in `src/helpers.php` entscheidet nach dem Datum:

| Zeitraum | Zone | warum |
|---|---|---|
| 17.–22.09.2026 | `Europe/Lisbon` | Porto bis Caminha, UTC+1 |
| 23.09.–01.10.2026 | `Europe/Madrid` | ab dem Minho, UTC+2 |
| sonst | `Europe/Berlin` | zu Hause |

Spanien und Deutschland haben dieselbe Uhr — die mittlere Zeile ändert nichts
und steht trotzdem da, weil sie den Grund festhält. Welcher Tag gerade ist,
wird in UTC bestimmt: sonst müsste man die Zone kennen, um die Zone zu wählen.
Am Wechseltag geht das um höchstens eine Stunde daneben.

Die Zeitstempel in der Datenbank tragen ihren Versatz mit (`date('c')`), die
Anzeige rechnet also richtig um — auch für Einträge, die vor der Umstellung
entstanden sind.

## Ein Paket loswerden — `stelleEin()`

Alles, was ins Tagebuch geht — neuer Eintrag, nachgereichte Bilder —, läuft
durch **eine** Funktion. Der sichere Weg ist die Warteschlange: erst auf dem
Gerät merken, dann hochladen. Fällt sie aus (Safaris privates Fenster gibt der
IndexedDB keinen Platz, ein volles Gerät auch nicht), geht das Paket direkt
raus, solange Netz da ist. Nur ohne Netz **und** ohne Zwischenspeicher ist
wirklich Schluss — und dann sagt die Meldung, warum.

Das war schon einmal an zwei Stellen umgesetzt, und prompt hatte die eine den
Rückfall und die andere nicht: nachgereichte Bilder gingen im privaten Fenster
wortlos verloren. Deshalb eine Funktion, nicht zwei.

## Welcher Tag im Tagebuch zur Auswahl steht

Abgehakte Tage stehen **nicht** mehr zur Auswahl — die Liste soll mit der Reise
kürzer werden, nicht länger. Eine Ausnahme, und die ist wichtig: **heute und
gestern bleiben drin, auch abgehakt.** Man kommt an, hakt den Tag ab, duscht —
und will erst danach die Notiz sprechen. Ohne die Ausnahme wäre der Tag dann
weg und man müsste ihn wieder aufmachen, um über ihn zu schreiben. Dasselbe
gilt für den Morgen danach, wenn man abends zu müde war.

## Sprachnotizen — was daran schon kaputt war

Zwei Fehler, die zusammen dafür gesorgt haben, dass **keine einzige Aufnahme
ankam**. Wer daran etwas ändert, sollte beide kennen.

1. **`onstop` hing an der äußeren Variablen.** Das Beenden setzte
   `recorder = null`, und `onstop` läuft danach — im Handler stand aber
   `recorder.mimeType`. Das warf jedes Mal, `fertigeAufnahme` blieb leer, der
   Knopf blieb auf „Aufnahme läuft", und der Ton war weg. Der Recorder wird
   jetzt in einer eigenen Variablen festgehalten.
2. **Speichern bei laufender Aufnahme lief ins Leere.** `fertigeAufnahme`
   entsteht erst in `onstop`; wer vorher auf „Eintrag speichern" tippte,
   bekam einen Eintrag ohne Ton. Jetzt wird die Aufnahme erst beendet, und
   das Speichern wartet darauf.

Dazu eine Härtung: **ein Ausfall der IndexedDB wirft den Eintrag nicht mehr
weg.** In Safaris privatem Fenster bekommt sie keinen Platz, auf einem vollen
Gerät auch nicht. Vorher endete das mit „Bitte den Text kopieren!" und der
Eintrag war verloren. Jetzt geht er direkt hoch, solange Netz da ist; die
Warteschlange ist dabei nur noch eine Bequemlichkeit für den Fall ohne Netz,
und ihre Schreibfehler sind innerhalb von `schickePaket` folgenlos.

## Die Seite ist ein Akkordeon

Alle acht Abschnitte sind zugeklappt, bis man sie antippt. Zugeklappt ist die
Seite ein Inhaltsverzeichnis aus acht Zeilen — auf dem Handy scrollte man
sonst durch den halben Plan, nur um zu den Kosten zu kommen.

Gebaut mit `<details>`/`<summary>`, nicht mit JavaScript: das klappt auch ohne
Skript auf, lässt sich mit der Tastatur bedienen, und die Suchfunktion des
Browsers findet Text in geschlossenen Blöcken. Mehrere dürfen gleichzeitig
offen sein — beim Buchen will man Etappen und Kosten nebeneinander.

**Die Einblend-Animation beim Scrollen ist deshalb abgeschafft.** Sie stammte
aus der Zeit der langen Seite: ein `IntersectionObserver` setzte `.in`, sobald
man einen Abschnitt erreichte, vorher stand er auf `opacity:0`. Mit dem
Akkordeon wurde daraus ein Fehler — ein aufgeklappter Abschnitt weiter unten
stand durchsichtig da und nahm trotzdem seine volle Höhe ein. Auf dem Handy sah
das aus wie ein Bildschirm voll Nichts, und die Etappen schienen verschwunden.
`.reveal` ist jetzt von Haus aus sichtbar; das Aufklappen ist die Animation.

Dazu `section[id]{scroll-margin-top:62px}`: die Navigationsleiste klebt oben
und ist 50 px hoch. Ohne den Abstand springt „04 · Etappen" zwar richtig, die
Überschrift liegt danach aber unter der Leiste.

Drei Dinge kommen vom JavaScript dazu:

- **Der Zustand wird gemerkt**, im `localStorage` des Geräts unter
  `pilger-bloecke`. Nicht in der Datenbank: welche Blöcke offen sind, ist eine
  Sache des Geräts, nicht des Plans. Fällt der Speicher aus, startet die Seite
  eben zugeklappt.
- **Sprungmarken klappen auf.** Ein Klick auf „06 · Kosten" oder eine Adresse
  mit `#kosten` würde sonst auf einer Überschrift landen.
- **Die Karte wird neu vermessen.** Leaflet misst in einem zugeklappten
  Abschnitt 0 × 0 und lädt Kacheln für ein Fenster, das es nicht gibt. Beim
  ersten Aufklappen also `invalidateSize()` und der Ausschnitt noch einmal —
  ohne das bliebe die Karte grau.

## Ausgeblendete Abschnitte

Vier Abschnitte sind auf Wunsch aus der Seite genommen; die übrigen sind
nachgerückt. Aktuelle Nummerierung:

```
01 Profil · 02 Anreise · 03 Ankunft · 04 Etappen
05 Packliste · 06 Kosten · 07 Tagebuch
```

- **Equipment & Gelenkschutz** — war weitgehend eine Kurzfassung der
  Packliste, die dieselben Punkte ausführlicher führt.
- **Ernährung & Supplements** — Tagesprotokoll und Pillen-Zeile.
- **Vor der Abreise** — Termine und Erledigungen zum Abhaken (Zahnarzt,
  Frisör, Infusion, Osteopath, Decathlon, Rezept, Apotheke).
- **Countdown –5 kg** — Wochentabelle mit Zielgewicht. Ab dem Start auf dem
  Camino erledigt.

**Der Google-Health-Block ist dabei umgezogen**, vom Countdown ins Tagebuch.
Er musste bleiben: die Schritte und Kilometer daraus stehen auf den
Etappenkarten (`health_days` über `$healthTage`), und der Tagebuch-Ausbau baut
darauf auf. Ohne ihn gäbe es kein „Jetzt aktualisieren" mehr, während die
Zahlen weiter angezeigt würden. Im Tagebuch steht er ohnehin am richtigen
Platz — direkt über den Schlüsseln für Transkription und Ausbau.

**Gelöscht wurde nichts.** `equipment_cards`, `equipment_items` (samt
Häkchen), `nutrition_pills`, `nutrition_slots` und `todos` (samt Häkchen und
Notizen) stehen unverändert in der Datenbank, die Repo-Methoden und die
API-Aktionen `equip.toggle` und `todo.*` ebenfalls. Das JavaScript dazu
prüft, ob es die Elemente überhaupt gibt, und tut sonst nichts.
Wieder einblenden heißt: den jeweiligen Commit rückgängig machen, mehr nicht.

Die Sprungziele (`#packliste`, `#kosten`, …) haben sich nie geändert — nur die
Nummern davor.

## Das Journal — Prototyp

`pilger.milsh.com/journal.php`, verlinkt unten im Seitenfuß der Hauptseite.

Der Abschnitt „Tagebuch" auf der Hauptseite ist ein **Werkzeug**: aufnehmen,
hochladen, abhaken, Warteschlange leeren. Das Journal ist das Gegenteil — eine
Seite **ohne einen einzigen Knopf**, zum Lesen von vorn bis hinten, auch für
jemanden, der nicht dabei war. Gleiche Datenbank, gleiche Fotos, andere Absicht.

**Was es zeigt.** Titelseite mit Kilometern, Etappen, notierten Tagen und
Bildern. Darunter je Tag ein Kapitel: das erste Foto bildschirmfüllend als
Aufmacher, Wochentag und Datum, Etappe und Ziel, eine Zahlenzeile aus
`health_days` plus Wetter und Höhenmetern, der Text der Einträge, eine Karte
auf den Zielort und zum Schluss die übrigen Fotos als Mosaik. Oben klebt eine
Tagesleiste, die mitläuft und sagt, wo man gerade ist.

**Reihenfolge andersherum.** Im Tagebuch steht der neueste Tag oben, weil man
ihn gerade geschrieben hat. Ein Journal liest man von vorn — deshalb `ksort()`
statt der Sortierung aus `Tagebuch::eintraege()`. Innerhalb eines Tages ebenso:
die frühe Notiz zuerst.

**Eigene Datei, eigenes CSS.** `journal.css` erbt nichts von `app.css`. Das ist
Absicht: die Hauptseite ist ein Bedienpanel und soll eins bleiben, das Journal
darf großzügig sein — breite Bilder, eine schmale Lesespalte von 38 rem,
Initiale am Absatzanfang. Beide Dateien anzufassen, wenn sich am Journal etwas
ändert, wäre der sichere Weg, die Hauptseite kaputtzumachen.

**Zwei Kleinigkeiten, die schon einmal schiefgingen:**

- Das Mosaik ist `column-count`, kein Grid. Mit Grid hinterlassen hochkant
  fotografierte Bilder Löcher, sobald sie zwei Zeilen hoch sind. Am Telefon
  eine Spalte, sonst zwei.
- Die Zahlen auf der Titelseite sind am Telefon 2 × 2, nicht 4 × 1. Vier
  nebeneinander schoben die vierte aus dem Bild — sichtbar war nur noch ein
  Strich am rechten Rand.

**Wenn Leaflet nicht lädt**, steht im Kartenkasten der Ortsname und „Karte
braucht Internet" statt eines leeren Rahmens. `journal.js` räumt den Satz weg,
bevor es die Karte hineinbaut. Karten entstehen erst, wenn das Kapitel in die
Nähe des Bildschirms kommt — zwölf Leaflet-Instanzen auf einmal macht kein
Telefon mit.

**Zutritt und Sichtbarkeit.** Die Seite hängt an derselben `gate.php` wie alles
andere, dazu `noindex, nofollow`. Sie ist also vorerst nur für ihn lesbar.

**Was für die fertige Fassung noch fehlt:**

- ~~Wo ein Foto entstanden ist~~ — **gebaut**, siehe „Fotos wissen jetzt, wo
  sie entstanden sind". Für die Bilder von vorher bleibt es leer.
- **Videos.** Dieselbe Warteschlange, dieselbe Ablage — aber drei Dinge fehlen:
  die Dateiauswahl nimmt nur `image/*`, `media.php` schickt **immer die ganze
  Datei** (es setzt zwar `Accept-Ranges: bytes`, beantwortet einen
  `Range:`-Kopf aber nicht — für Audio geht das durch, ein Video spielt auf
  iOS damit gar nicht erst an), und `mp4` steht in der Typentabelle als
  `audio/mp4`. Dazu die Größe: ein Handyvideo ist schnell 100 MB, und
  verkleinern kann der Browser es nicht so nebenbei wie ein Bild.
- **Ein Text über die Tage hinweg.** Bisher baut Claude jede Notiz für sich
  aus. Ein Journal verträgt mehr: Rückgriffe auf vorgestern, ein Kapitelanfang,
  der weiß, was vorher war. Dieselbe Regel gilt weiter — nur was in den Daten
  steht oder auf den Bildern zu sehen ist.
- **Für die Familie lesbar machen.** Ein zweiter, langer Link ohne Passwort,
  der nur auf das Journal zeigt, nicht auf die Bedienseite.

## Fotos wissen jetzt, wo sie entstanden sind

Die Koordinaten stehen im Bild selbst, im EXIF-Block. Auf dem Server kamen sie
nie an — und das lag nicht am Server: **das Handy verkleinert das Bild vor dem
Hochladen über eine Leinwand, und die malt nur Pixel ab.** Die Metadaten
bleiben dabei liegen. Was ankam, war ein sauberes JPEG ohne jede Herkunft.

Gelesen wird jetzt **vor** dem Verkleinern, im Browser, aus den ersten 512 KB
der Datei (`bildHerkunft()` in `tagebuch.js`). Der EXIF-Block steht im ersten
Segment nach dem Dateikopf und ist auf 64 KB begrenzt — mehr zu lesen bringt
nichts. Heraus kommen Breite, Länge und die Aufnahmezeit; mitgeschickt werden
sie als eigene Felder `lat`, `lng` und `aufgenommen`.

**Zwei Wege, und das mit Absicht.** Der Server sieht sich die Datei ebenfalls
an (`Tagebuch::gpsAusExif()`), falls das Gerät nichts geschickt hat. Das ist
kein doppelter Boden aus Vorsicht, sondern deckt einen echten Fall ab: Bilder
unter 600 KB und JPEGs, die die lange Kante schon einhalten, werden gar nicht
erst verkleinert — bei denen liegt der EXIF-Block noch in der Datei, die auf
dem Server ankommt. Der Wert vom Gerät hat Vorrang.

**Die Zeit aus dem Bild ist die bessere.** Bisher stand in `taken_at`, was
`lastModified` sagte — und das kann das Kopieren aus der Galerie gewesen sein,
nicht der Moment der Aufnahme. Steht `DateTimeOriginal` im Bild, gewinnt das,
samt Zeitversatz, wenn die Kamera ihn geschrieben hat.

**Was nicht gespeichert wird:** genau 0/0. Das liegt im Atlantik vor Afrika und
heißt in der Praxis „das Gerät hatte keinen Empfang". Ebenso alles außerhalb
±90 / ±180. Gerundet wird auf sechs Nachkommastellen — rund zehn Zentimeter;
mehr täuscht eine Genauigkeit vor, die ein Handy-GPS nie hat.

Im Journal liegen die Bilder eines Tages als kleine rote Punkte auf der
Tageskarte, das Etappenziel bleibt der große gelbe. Sind die Punkte weit vom
Ziel weg — ein Wandertag ist ja lang —, zieht sich der Ausschnitt so weit auf,
dass alle hineinpassen. Hat kein Bild des Tages Koordinaten, steht auch keine
Legende darunter.

**Die Bilder von vorher haben nichts davon.** Ihre Originale liegen noch auf
dem Telefon, mit allem drin — auf dem Server ist die Herkunft nie angekommen
und lässt sich dort auch nicht rekonstruieren. Wer sie nachtragen will,
bräuchte einen Weg, die Originale noch einmal einzulesen.

**An zwei Stellen steht, ob es geklappt hat.** Schon bei der Auswahl, bevor
irgendetwas hochgeht: „Alle mit Ort" oder „1 von 2 mit Ort" oder „Keins davon
hat einen Ort im Bild". Und am fertigen Eintrag noch einmal, mit einem Link ins
Journal. Das ist kein Schmuck: hinterher lässt es sich nicht mehr feststellen,
und steht dort „keins", liegt es fast immer daran, dass am Telefon der
Standort für die Kamera aus ist — das gehört gesehen, solange das Original noch
da ist.

**Wenn nach einem Deploy gar nichts ankommt**, lohnt der Blick auf den Service
Worker. Die Seite selbst wird netzwerk-zuerst geholt, die Dateien darunter
hängen am `?v=`-Zeitstempel — ein Neuladen holt also beides frisch. Eine Seite,
die seit Stunden offen im Browser steht, läuft aber weiter mit dem Code von
vorhin.

---

## Wo geschlafen wurde — als Tabelle, nicht als Absatz

Die Buchungen stehen auf der Etappenkarte als fertig formulierter HTML-Absatz
in `stages.note` und `stages.target`. Zum Nachschlagen am Abend ist das genau
richtig. Das Journal will aber etwas daraus erzählen, und dafür muss es an die
einzelnen Angaben herankommen, ohne HTML auseinanderzunehmen.

Die Tabelle heißt `lodgings`, die Daten liegen in **`db/unterkuenfte.php`** und
werden von Migration 031 eingespielt. In `db/seed.php` stehen sie absichtlich
**nicht**: eine frische Datenbank läuft ohnehin jede Migration durch, und die
Tabelle entsteht auch erst dort — zwei Stellen mit denselben Zeilen wären nur
eine Stelle mehr, die veralten kann. Für Etappen und Kosten gilt die
Doppelpflege weiter, die stehen schon im Seed.

**Nicht alles davon sind Hotels**, und das steht jetzt sauber drin. Ein
*Residencial* ist ein portugiesisches Gästehaus, ein *Hostal* das spanische
Gegenstück, ein *Albergue* wäre die Pilgerherberge mit Schlafsaal gewesen — die
kam nie infrage. Wo der Name die Art nennt („Residencial Galo d'Ouro", „B&B
HOTEL"), steht sie im Feld `art`. Wo er es nicht tut — Carpe Diem, Hello
Esposende, Alda Estación, Lemonade Stays —, bleibt das Feld **leer** und im
Journal steht schlicht „Übernachtung". Ein Haus zum Hotel zu erklären, weil es
sich so anhört, wäre geraten.

**Keine Koordinaten.** Die Adressen stehen fest, die Punkte dazu müsste man
nachschlagen, und ein um zweihundert Meter danebenliegender Stecknadelkopf ist
schlechter als gar keiner. Verlinkt wird deshalb auf die Adresse.

`quelle` sagt, woher es stammt: `bestaetigung` ist die Buchungsbestätigung,
`uebersicht` nur der Bildschirm davor. **Vigo steht auf `uebersicht`** — dort
fehlt die Buchungsnummer, und das soll man sehen.

**Zwei Stellen, solange er läuft.** Die Etappenkarte umzustellen, während er
jeden Abend darauf schaut, wo sein Bett steht, wäre der falsche Moment. Bis
dahin gilt: eine neue Buchung wird an beiden Stellen eingetragen — im
Etappenabsatz **und** in `db/unterkuenfte.php` plus einer Migration. Nach dem
Camino gehört die Etappenkarte aus der Tabelle erzeugt, dann ist es wieder eine
Stelle.

Noch offen und deshalb nicht in der Tabelle: **Arcade, Pontevedra, Caldas de
Reis, Padrón.** Baiona ist keins mehr — dort wird nicht übernachtet, das Bett
steht in Nigrán, siehe unten.

---

## E6 endet in Nigrán, nicht in Baiona

Gebucht — oder jedenfalls fast, siehe unten — ist die Nacht in **Playa
América**. Das gehört zur Gemeinde Nigrán und liegt an der Carretera
Vigo–Baiona, also **zwischen Baiona und Vigo**, in Laufrichtung. Der Weg
dorthin stand schon vorher im Plan, als Küstenvariante von E7: „Wer am Wasser
bleibt, geht über Nigrán, Praia América und Saiáns nach Vigo." Genau dort ist
jetzt das Bett.

**Das macht die beiden Tage erst vernünftig.** Vorher standen 14 km und 27 km
nebeneinander — ein halber Tag und der längste der ganzen Reise. Jetzt sind es
rund 21 und 20. Die Summe bleibt, 266 km stimmen weiter. Damit liegt die
längste Etappe des Camino hinter ihm (E5, 27 km); das Längste, was noch kommt,
ist der letzte Tag nach Santiago mit 25 km. Die Markierung „längste Etappe" ist
deshalb von E7 auf E5 gewandert, „kürzeste Etappe" ist von E6 verschwunden —
kürzeste ist jetzt E9 mit 15 km.

**Die Kilometer sind geschätzt, nicht gemessen.** Gerechnet aus der Küstenlinie
in `db/kuestenroute.php`: Baiona → Panxón/Nigrán sind gut ein Viertel des
Stücks Baiona → Vigo, also rund 7 der 27 km. Dieselbe Art Schätzung wie überall
sonst im Plan. Der Kartenpunkt ist ebenfalls der Nigrán-Stützpunkt der Linie,
keine nachgeschlagene Adresse — einen genaueren gibt es hier nicht, und
Nominatim und Co. sind von hier aus nicht erreichbar.

**Das Datum war zuerst falsch.** Auf der Vorlage stand als Anreise der 25.09.
— das ist die Nacht *nach* E7, und die ist in Vigo gebucht. Gebraucht wurde die
Nacht Do, 24.09. → Fr, 25.09. Das ist **telefonisch geändert** worden, und er
hat dort übernachtet; bestätigt ist es damit so gut, wie es geht.

Eine **Buchungsnummer gibt es trotzdem nicht**: die Vorlage war die
Buchungs*übersicht* mit dem Knopf „Letzter Schritt", nicht die Bestätigung.
Beim Hotel in Vigo war es genau dasselbe, und dort fehlt die Nummer bis heute.
Das steht in der Etappennotiz und in `lodgings`, sonst wundert sich später
jemand, warum ausgerechnet bei diesen beiden Nächten keine dasteht.

Solange das offen war, stand auf der Etappenkarte „Zimmer gefunden, Buchung
noch nicht bestätigt", in den Kosten „zu prüfen" und **kein Betrag** — und in
`lodgings` gar nichts, weil eine Zeile mit dem falschen Datum schlechter
gewesen wäre als keine. Das ist die Regel für den nächsten Fall auch: ein
Haken, wo keiner hingehört, ist schlimmer als eine offene Zeile. Nach 21 km vor
einer verschlossenen Tür zu stehen, weil die Seite „gebucht" sagte, wäre der
schlechteste denkbare Fehler dieses Projekts.

Dafür gibt es in `cost_items` jetzt den Status `warn` („zu prüfen"). Er liegt
zwischen `open` (gar nichts da) und `ok` (gebucht) und ist kräftiger eingefärbt
als beide, damit er nicht übersehen wird — für die vier Orte, die noch
kommen.

---

## Der Weg zum Bett — ein Knopf, kein Link im Fließtext

Die Kartenlinks gab es schon. In jedem Etappenabsatz stand hinter der Adresse
ein „Karte", bei den meisten noch eine Telefonnummer daneben. Gefunden hat er
sie trotzdem nicht und stattdessen jedes Mal den Hotelnamen von Hand in Google
Maps getippt.

Das ist keine Nachlässigkeit, sondern eine Lehre über die Seite: es waren zwei
Wörter in kleiner grauer Schrift, mitten in einem Absatz, am Ende eines
Wandertags, auf einem Telefon. **Was benutzt werden soll, muss aussehen wie
etwas, das man antippt** — und dort stehen, wo man es sucht, nämlich direkt
unter der Buchung.

Jetzt steht unter jeder gebuchten Etappe eine Fläche mit Namen und Adresse, die
Google Maps öffnet, und daneben die Telefonnummer als Wählfläche. Gebaut wird
beides in `bett_karte()` aus **`lodgings`** — also aus Feldern, nicht aus HTML.
Es erscheint genau dann, wenn für die Etappe eine Unterkunft hinterlegt ist,
und verschwindet von selbst, wenn nicht.

Gesucht wird mit **Name und Adresse zusammen**. Nur der Name reicht nicht —
„Hello Esposende" und „Lemonade Stays" finden ohne Ort halb Europa —, und nur
die Adresse setzt den Stift zwar richtig, sagt aber nicht, ob man vor dem
richtigen Haus steht.

**Migration 034 räumt die alten Links aus dem Text.** Zwei Kartenlinks
nebeneinander wären schlechter als einer: dann fragt man sich, ob sie auf
dasselbe zeigen. Die Adresse als *Wort* bleibt im Satz stehen — sie gehört
dorthin („76 m vom Ortszentrum, Rua da Corredoura 15") und sagt auch ohne Netz
noch, wo es hingeht.

In `db/seed.php` stehen die alten Links weiter drin, und das ist in Ordnung:
eine frische Datenbank läuft nach dem Seed durch alle Migrationen, 034
eingeschlossen. Geprüft ist das — Kaltstart und laufende Datenbank liefern für
alle dreizehn Etappen Feld für Feld dasselbe.

**Noch nicht aufgeräumt:** bei einigen Etappen fängt die Notiz mit derselben
Adresse an, die zwei Zeilen darüber schon im Knopf steht. Das liest sich
doppelt. Die Notizen umzuschreiben, während er unterwegs ist und jeden Abend
darauf schaut, ist es aber nicht wert — das kann nach dem Camino weg.

---

## Stempel suchen, wo man steht — nicht, wo man hinwill

Die Stempelsuche gab es von Anfang an, aber sie war auf den **Zielort**
gerichtet: „Albergue de Peregrinos Vigo". Das hilft am Abend. Es hilft nicht um
halb drei, wenn man seit zwanzig Minuten durch ein Gewerbegebiet läuft, noch
acht Kilometer vor sich hat und heute noch zwei Stempel braucht.

Deshalb steht jetzt oben im Kasten eine Reihe Knöpfe, die **von der aktuellen
Position aus** suchen: Albergue, Turismo, Kirche, Concello, Café/Bar, Apotheke.

**Zwei Stufen, damit es auch ohne Erlaubnis funktioniert.** Unangetastet geht
der Link als `?api=1&query=…` ohne Koordinate raus — Google Maps nimmt dann von
sich aus den Standort des Geräts, was meistens reicht und keine Rückfrage
kostet. Wer „Meine Position nehmen" drückt, bekommt es genau: die Koordinate
wird einmal geholt und als `/@lat,lng,15z` in **alle** Blöcke der Seite
geschrieben. Sie bleibt im Speicher der Seite, wird nicht gesendet, nicht
gespeichert, und beim nächsten Laden wieder gefragt.

Der Kasten steht am **laufenden Tag offen** und an allen anderen zugeklappt.
Wer unterwegs einen Stempel sucht, soll nicht erst eine Überschrift antippen.

**Eine offizielle Stempelkarte gibt es nicht.** Weder die Xunta noch das
Pilgerbüro veröffentlichen eine Liste von Stempelstellen — es gibt die
offizielle Liste der **öffentlichen Albergues**, und die stempeln alle, aber
das ist etwas anderes. Die dichtesten Sammlungen stehen in den Wander-Apps
(Buen Camino, Wise Pilgrim, Camino Ninja) und auf gronze.com, und die sind von
Hand gepflegt, nicht amtlich. Von hier aus ließ sich keine davon abrufen — der
Ausgangsproxy sperrt sie —, also steht in der Datenbank **keine abgeschriebene
Liste**. Was dort steht, sind Suchen; die zeigen, was es gerade gibt.

**Das Concello ist dazugekommen** (Migration 035, nur für die spanischen
Etappen). In Galicien ist das Rathaus die verlässlichere Adresse als die
Kirche: es hat Öffnungszeiten, liegt im Ortskern und stempelt ohne
Gegenleistung. In Portugal heißt es *Câmara Municipal* und ist dort nicht die
übliche Anlaufstelle — deshalb steht es da nicht.

**Der eigentliche Rat steht im Fußtext**, und der ist wichtiger als jede
Suchfunktion: zwei Stempel am Tag bekommt man fast nebenbei. Einen von der
eigenen Unterkunft beim Einchecken — dafür muss man nirgends hin —, einen von
der Kaffeepause unterwegs. In Spanien hängt es kaum jemand ins Fenster, aber
die meisten Bars am Weg haben einen im Tresen; man muss fragen. Dafür steht der
Satz dabei: **„¿Tienen sello para la credencial, por favor?"** Das Albergue
stempelt immer, auch ohne dort zu schlafen und ohne etwas zu kaufen.

---

## Der „Heute"-Knopf

Vorn in der Kopfleiste, gelb, vor allen Kapitelnummern. Er springt auf die
Etappe, in die der heutige Tag fällt. Gibt es keine — vor dem 17.09., nach dem
01.10. —, steht er gar nicht erst da.

Bis dahin waren es drei Handgriffe: den Abschnitt „Etappen" aufklappen, den
richtigen Reiter wählen, scrollen. Unterwegs, im Gehen, mit klammen Fingern ist
das zwei zu viel.

**Die Reihenfolge im Klick ist nicht beliebig.** Erst wird das `details`
aufgeklappt, dann der Reiter umgestellt, und erst im nächsten Frame gesprungen
— ein Ziel in einem zugeklappten `details` hat keine Höhe, und
`scrollIntoView` landet sonst irgendwo.

Der Reiter wird **nur dann** auf „alle" gestellt, wenn die heutige Etappe im
gerade gezeigten sonst unsichtbar wäre (Reiter „offen" und Tag abgehakt, oder
umgekehrt). Wer „offen" eingestellt hat und dessen heutiger Tag offen ist,
behält seine Einstellung.

Nach dem Sprung leuchtet die Karte kurz auf. Die Etappenkarten sehen alle
gleich aus; ohne das weiß man nicht, wo man gelandet ist. `scroll-margin-top`
hält sie unter der klebenden Leiste hervor, und bei
`prefers-reduced-motion` wird aus dem Pulsieren ein stehender Rahmen.

---

## `media.php` beantwortet jetzt Bereichsanfragen

Vorher stand dort `Accept-Ranges: bytes` und darunter ein `readfile()`. Die
Zusage wurde also gegeben und nie eingelöst: egal was angefragt war, es kam die
ganze Datei mit einer 200.

Bei Audio fällt das kaum auf — der Browser lädt die Aufnahme eben ganz und
spult im Speicher. **Ein Video auf dem iPhone fängt so gar nicht erst an.**
Safari holt zuerst ein kleines Stück vom Anfang, und wer darauf mit der vollen
Datei antwortet, bekommt einen schwarzen Rahmen. Das war der eigentliche Grund,
warum Videos nicht einfach „auch gingen".

Jetzt: `206` mit `Content-Range` für einen angefragten Bereich, `416` für einen
unsinnigen, `200` sonst. Unterstützt sind offene (`bytes=500-`) und
nachlaufende Bereiche (`bytes=-500`). Mehrteilige Bereiche kommen von
Videoplayern nicht vor — darauf mit der ganzen Datei zu antworten ist erlaubt.

Ausgegeben wird **stückweise** (256 KB), nicht mit `readfile()`: ein Handyvideo
sind schnell dreihundert Megabyte, und die durch den Speicher zu ziehen killt
PHP am `memory_limit` mitten in der Antwort.

**Der Typ hängt nicht mehr an der Endung allein.** `mp4`, `webm` und `ogg` gibt
es als Ton und als Bild; was ausgeliefert wird, entscheidet `photos.kind`. Ein
Video als `audio/mp4` auszuliefern heißt, dass der Browser nur den Ton
abspielt. Das Standbild eines Videos geht weiterhin als Bild heraus.

Geprüft mit echten Anfragen über alle Fälle — offen, nachlaufend, über das
Ende hinaus — samt byte-genauem Vergleich gegen die ganze Datei.

---

## Videos

Sie liegen in **derselben Tabelle wie die Fotos**, unterschieden durch
`photos.kind`. Alles, was an einem Foto hängt, hängt auch an einem Video: der
Eintrag, die Etappe, die Bildunterschrift, die Aufnahmezeit, die Koordinaten.
Eine zweite Tabelle hieße, jede Abfrage und jede Anzeige zweimal zu schreiben,
und Zeitleiste, Mosaik und Tageskarte müssten beides wieder zusammenführen.
Die Dateien selbst liegen unter `videos/`, das Standbild bei den Bildern.

**Hochgeladen wird stückweise** (`src/Stueckweise.php`, Brocken von 1,5 MB).
Nicht aus Eleganz, sondern weil es anders nicht geht: im Container stehen
`post_max_size = 72M`, davor sitzt ein Reverse-Proxy mit eigenen Grenzen, und
dazwischen liegt ein portugiesisches Mobilnetz. Ein Handyvideo sind
dreihundert Megabyte. Am Stück heißt: es geht nicht, und wenn doch, bricht es
bei achtzig Prozent ab und fängt wieder bei null an. Jeder Brocken ist eine
eigene Anfrage, die für sich gelingt; was liegt, bleibt liegen.

Drei Aktionen: `datei.anfang` gibt eine zufällige Marke, `datei.stueck` hängt
an, `datei.fertig` legt den Eintrag an. Die Marke wird streng geprüft
(`^[0-9a-f]{32}$`) — ein Name vom Gerät gerät nie in einen Pfad. Es gibt eine
Gesamtgrenze von 400 MB, sonst füllt ein Fehler in der Schleife die Platte, und
angefangene Übertragungen räumen sich nach zwölf Stunden selbst weg.

**Das Standbild kommt vom Gerät.** Im Container steckt kein ffmpeg, und eins
dazuzunehmen hieße, ein Programm mit eigener Angriffsfläche an fremde Dateien
zu lassen. Der Browser kann das Video ohnehin abspielen — also greift er eine
Sekunde hinein ein Bild ab (das allererste ist oft schwarz) und liest die Länge
mit. Daraus entstehen Vorschaubild, Breite und Höhe; ohne die fiele das Video
im Mosaik aus dem Raster. Misslingt es, geht das Video trotzdem hoch.

**`preload="none"` überall.** Ein Tagesblock mit fünf Videos würde beim
Aufklappen sonst fünf Videos ziehen. Auf dem Camino ist das Netz knapp und das
Datenvolumen auch; geladen wird beim Drücken.

Im Tagebuch sind Videokacheln breiter als die 104 px der Bilder — sonst passt
die Bedienleiste des Abspielers nicht hinein und man trifft den Knopf nicht.
Beschnitten wird nichts: bei einem Bild ist ein Ausschnitt in Ordnung, bei
einem Video fehlte dann der halbe Inhalt.

**Geprüft** mit einem echten 4,8-MB-Video (im Browser aufgezeichnet, weil es
hier auch kein ffmpeg gibt): vier Brocken, byte-gleich angekommen, Standbild
und Länge erkannt, Zwischenspeicher leer, `206` mit `Content-Range` beim
Abruf, und es spielt.

**Bilder gehen denselben Weg** — siehe unten, „Bilder gehen jetzt stückweise".

---

## Das Journal für die Familie — ein langer Link

Unten im Seitenfuß, direkt beim Journal-Link: **„Zum Mitlesen für die
Familie"**. Ein Knopf erzeugt einen Link mit einem 64-stelligen Geheimnis darin
(`journal.php?g=…`), ein zweiter beendet die Freigabe. Ein neuer Link macht den
alten sofort tot — das steht in der Rückfrage, bevor es passiert.

Kein Konto, kein zweites Passwort, keine Benutzerverwaltung. Wer den Link hat,
darf lesen; wer ihn nicht mehr haben soll, für den wird ein neuer erzeugt. Für
fünf Leute in einer Familie ist alles andere zu viel Maschinerie.

**Was der Gast darf, ist eng gezogen — und das ist der wichtige Teil:**

| | Gast mit Link |
|---|---|
| Journal lesen | ja |
| Bilder und Videos darin | ja |
| **Sprachaufnahmen** | **nein, 403** |
| Bedienseite (`index.php`) | nein, 401 |
| Schnittstelle (`api.php`) | nein, 403 |
| Datei ohne Marke | nein, 403 |

Die Sprachaufnahmen sind der Punkt, an dem es nicht bequem sein darf: der
Rohton ist das, was er unterwegs vor sich hin gesprochen hat. Die ausgebaute
Fassung im Journal ist zum Lesen gedacht, die Aufnahme nicht. `media.php` lehnt
`art=audio` für Gäste ab, auch mit gültiger Marke.

**Zwei Dinge, die leicht zu übersehen sind:**

- Verglichen wird mit `hash_equals`, nicht mit `===` — sonst ließe sich das
  Geheimnis über die Antwortzeit Zeichen für Zeichen erraten.
- Die Seite trägt `<meta name="referrer" content="no-referrer">`. Ohne das
  schickt der Browser die komplette Adresse **samt Marke** im `Referer` an
  jeden Kartenkachel-, Schrift- und Skript-Server mit. Der Link soll bei der
  Familie bleiben und nicht in fremden Protokollen landen.

Im Gastblick trägt jede Datei-Adresse die Marke mit (`media.php?g=…`), und der
Fuß zeigt statt „Zurück zum Plan" den Satz „Ein privater Link. Bitte nicht
weitergeben." — ein Link, der vor einer Tür endet, ist schlechter als keiner.

**Geprüft** mit zwei getrennten Browsersitzungen: ohne Link und mit falschem
Link steht die Tür, mit Link werden alle Bilder und das Video geladen, und
jeder der oben genannten Grenzfälle antwortet mit dem erwarteten Fehlercode.

---

## Bilder gehen jetzt stückweise — und Abbrüche gehen weiter

**Das Problem, das damit erledigt ist.** Ein Bild als Multipart-Formular kam auf
diesem Server seit Tagen mit **„Es kam keine Datei an"** zurück, während
derselbe Inhalt als JSON durchging und Sprachnotizen über denselben Endpunkt
anstandslos ankamen. Woran das liegt, ist bis heute nicht geklärt — die
Diagnose in `upload.php` steht bereit, aber es kam nie ein Screenshot davon.

Statt weiter zu raten: den Weg nehmen, der nachweislich ankommt. Bilder gehen
jetzt wie Videos über `datei.anfang` / `datei.stueck` / `datei.fertig`. Ein Bild
von 700 KB ist dabei **ein einziges Stück** — drei kurze Anfragen statt einer,
die scheitert. Der alte JSON-Weg (`foto.daten`) bleibt als Rückfall stehen, weil
er auf diesem Server erprobt ist; Multipart wird für Bilder gar nicht mehr
versucht. Für **Sprachnotizen** bleibt `upload.php`: die gehen durch, und was
funktioniert, wird nicht angefasst.

**Die Stückgröße ist nicht geraten.** 1 MB roh, als Base64 gut ein Drittel mehr.
Der JSON-Rückfall geht auf diesem Server seit Tagen mit rund einem Megabyte
durch — was nachweislich ankommt, ist das Maß.

**Und jetzt bricht ein Upload wirklich nicht mehr auf null zurück.** Das stand
vorher schon in der Beschreibung, gebaut war es nicht: die Marke lebte nur
innerhalb eines Versuchs, ein Fehlschlag warf die ganze Kette weg und der
nächste Anlauf fing von vorn an. Jetzt wandert die Marke ins Paket in der
Warteschlange. Beim nächsten Versuch fragt der Browser mit `datei.stand`, wie
viel schon liegt, und schickt nur den Rest. Liefert der Server −1 — weil die
angefangene Übertragung nach zwölf Stunden weggeräumt wurde —, geht es von
vorn los.

Eine Verwechslung kann daraus nicht entstehen: hat `datei.fertig` beim ersten
Versuch schon durchgeschlagen und nur die Rückmeldung ist verlorengegangen,
erkennt `nimmFoto`/`nimmVideo` die `client_id` wieder und legt nichts doppelt an.

**Geprüft** an einem 4,8-MB-Video mit abgeschnittener Leitung mitten im zweiten
Stück: nach dem Abriss `datei.stand`, dann nur noch die fehlenden vier Stücke
statt aller fünf — und die Datei ist am Ende byte-gleich mit dem Original.

---

## Das Bett liegt nicht immer am Etappenziel

Pontevedra ist für die **Nacht nach E8** gebucht — das Etappenziel des 26. ist
aber Arcade, rund 15 km davor. Zum zweiten Mal auf dieser Reise passt die
Buchung nicht zum Plan, und zum zweiten Mal ist die Lösung nicht, den Plan
umzuwerfen.

**Zwei Wege, und sie sind nicht gleichwertig.** Alles an einem Tag laufen sind
37 km — das Doppelte des Vortags und zehn Kilometer mehr als die längste
Etappe der ganzen Reise. Oder: laufen wie geplant bis Arcade, die 15 km zum
Quartier fahren, am nächsten Morgen zurückfahren und sie zu Fuß gehen. Dann
fehlt **kein Meter**, und der Rucksack bleibt im Zimmer liegen, wenn die
Unterkunft um eine zweite Nacht verlängert wird.

**Warum das mehr ist als Bequemlichkeit.** Ab Vigo sind es nach den Zahlen
dieses Plans **103 km**, und für die Compostela müssen die **letzten 100
gelaufen** sein. Das sind drei Kilometer Luft, mehr nicht. Ein Taxi *über die
Strecke* frisst sie in einem Zug auf; eine Fahrt zum Quartier und morgens
zurück an dieselbe Stelle kostet davon nichts. Der Unterschied zwischen beiden
Fahrten ist die Urkunde am Ende — das gehört auf die Etappenkarte und nicht in
eine Unterhaltung, die man in vier Tagen vergessen hat.

Deshalb steht die Warnung jetzt farbig in `alt_note` von E8, zusammen mit der
Empfehlung. Die Kilometer der Etappen bleiben **unangetastet**: gelaufen wird,
was geplant war, nur geschlafen wird woanders.

**Was aus dem Screenshot nicht hervorging**, steht auch nicht in der Datenbank:
der **Name des Hauses** war nicht darauf, nur Adresse und Telefonnummer. In
`lodgings` heißt es deshalb schlicht „Unterkunft in Pontevedra". Der Preis von
103,50 € stammt aus der Zeile der Stornogebühr — die Buchung kostet bei
Stornierung den Gesamtpreis, also ist das der Gesamtpreis.

---

## Was bewusst nicht gebaut wurde

- **Kein Speichern des Originalfotos.** Bilder werden auf 1600 px verkleinert.
  Zwölf Tage Handyfotos in voller Größe sprengen jedes Volume, und für ein
  Reisetagebuch reicht die Kante. Verkleinert wird bereits auf dem Handy
  (2000 px, JPEG 0,85) — siehe unten.
- **Keine ausgedachten Wetterwerte.** Weiter als 16 Tage voraus gibt es keine
  Vorhersage. Statt eine zu erfinden, steht dort das Mittel derselben
  Kalendertage der Vorjahre — und es steht auch dran.
- **Keine erfundenen Höhenmeter.** Die Höhen stammen aus dem Geländemodell
  entlang der hinterlegten Küstenlinie. Das ist keine GPX-Spur, und auf den
  letzten vier Etappen liegt zwischen zwei Stützpunkten eine Gerade. Der
  Quellenhinweis unter den Etappen sagt das.
- **Das Original bleibt.** `text_raw` wird nie überschrieben — weder beim
  Ausbauen noch beim Bearbeiten von Hand. Am Eintrag steht es unter „Original
  ansehen", die Sprachaufnahme liegt ohnehin daneben. Jeder erneute Ausbau
  setzt wieder auf `text_raw` auf, nie auf der schon ausgebauten Fassung —
  sonst driftet der Text mit jedem Durchgang weiter vom Gesagten weg.
- **Ausbauen ist nicht Ausdenken.** Der Knopf am Eintrag baut die Notiz aus,
  und zwar mit dem, was über den Tag belegt ist: Etappe und Zielort, die
  Zahlen der Uhr aus `health_days`, Wetter und Höhenmeter, die Fotos des
  Eintrags. Alles davon steht in der Datenbank. Was das Modell **nicht** darf:
  Gefühle, Begegnungen, Gespräche und Bewertungen ergänzen, die nicht gesagt
  wurden, oder Orte und Zahlen nennen, die nirgends stehen. Beim Wetter ist
  vermerkt, ob es eine Vorhersage war oder das Mittel der Vorjahre — sonst
  schreibt das Modell „es regnete" über einen Tag, an dem das niemand gemessen
  hat.
