# Deployment — pilger.milsh.com

## Warum der Server sich den Code selbst holt

Der Server nimmt **von außen keine SSH-Verbindungen an**. Port 2222 ist
öffentlich zu (Zeitüberschreitung), 22 ebenso; der Weg hinein führt
ausschließlich über Tailscale. Ein Deploy, der die Dateien hinschiebt —
GitHub Actions, rsync aus einer Sitzung — kann deshalb nicht funktionieren.

Umgekehrt geht es: **der Server darf hinaus.** `sascha-gif/pilger` ist ein
öffentliches Repository, ein `git fetch` von dort braucht keinerlei Anmeldung.
Genau darauf baut der Ablauf auf.

```
Commit ──► main ──► GitHub prüft (Container-Stack wird gebaut und getestet)
                          │
                          ▼
             pilger-update.timer auf dem Server, alle 5 Minuten
                          │  git fetch → Änderung? → git reset --hard
                          ▼
                 docker compose up -d --build
                          │
Browser ──HTTPS──► bcd_caddy ──► pilger-app:80 ──► pilger-db (MariaDB)
```

Damit ist nach einem Merge nichts mehr zu tun: spätestens fünf Minuten später
läuft die neue Fassung. Wer nicht warten will, ruft auf dem Server
`pilger-update` auf.

## Die Container

| | |
|---|---|
| `pilger-app` | PHP 8.3 mit Apache, Document-Root auf `public/` |
| `pilger-db` | MariaDB 11, Daten im Volume `pilger-db` |
| Netz `intern` | nur zwischen den beiden, von außen nicht erreichbar |
| Netz `caddy` | das bestehende Netz von `bcd_caddy`, wird beim Einrichten ermittelt |

Nach außen ist **kein Port veröffentlicht**. Der Reverse-Proxy erreicht die App
über ihren Namen im gemeinsamen Netz — dasselbe Muster wie bei den übrigen
Projekten auf der Maschine. Eine `ufw`-Regel ist deshalb nicht nötig; die
brauchte das family-Projekt nur, weil es als Dienst auf dem Host läuft.

Die App bekommt ihre Zugangsdaten über Umgebungsvariablen (`PILGER_DB_*`),
eine `config.php` gibt es im Container nicht.

## Einrichtung (einmalig)

Auf dem Server, als `root` — der Zugang läuft über Tailscale:

```
ssh root@100.84.10.64
curl -fsSL https://raw.githubusercontent.com/sascha-gif/pilger/main/ops/setup-server.sh | bash
```

Das Skript ist wiederholbar und macht der Reihe nach:

1. prüft git, docker, docker compose und ob `bcd_caddy` läuft
2. klont nach `/opt/pilger-milsh` (oder aktualisiert eine vorhandene Kopie)
3. ermittelt das Docker-Netz von `bcd_caddy`
4. legt `.env` mit frisch erzeugten Datenbank-Passwörtern an — **eine bestehende
   `.env` bleibt unangetastet**, sonst passte die Datenbank nicht mehr dazu
5. baut und startet die Container
6. ergänzt den Eintrag im Caddyfile (mit Sicherung der alten Fassung) und liest
   Caddy neu ein
7. richtet `pilger-update.timer` ein
8. ruft zum Schluss `https://pilger.milsh.com` auf und meldet das Ergebnis

Der Caddy-Eintrag, falls er von Hand nachgetragen werden muss — in
`/opt/concierge-bot/server-config/Caddyfile`:

```
pilger.milsh.com {
    reverse_proxy pilger-app:80
}
```

## Voraussetzungen

Auf dem Server: Docker mit Compose v2, git, der Container `bcd_caddy`. **Kein
PHP auf dem Host** — das steckt im Image. Kein Node, kein Composer, kein Build.

Im DNS: `pilger.milsh.com` → `46.224.19.41`. Steht bereits.

## Was GitHub noch tut

Der Workflow `.github/workflows/ci.yml` deployt nicht, er prüft:

| Auftrag | Inhalt |
|---|---|
| Code prüfen | PHP-Syntax, Shell-Syntax der Server-Skripte, Start über den SQLite-Rückfall |
| Container-Stack prüfen | baut das Image, startet App und MariaDB, wartet auf HTTP 200, zählt die Packlisten-Einträge, schreibt über die API einen Betrag und liest ihn direkt aus der Datenbank zurück |

Der zweite Auftrag stellt genau das nach, was auf dem Server läuft. Ist er grün,
zieht sich der Server denselben Stand.

## Nachsehen und eingreifen

```
systemctl list-timers pilger-update.timer      # wann kommt der nächste Lauf
journalctl -u pilger-update.service -n 30      # was die letzten Läufe getan haben
pilger-update                                  # sofort aktualisieren
docker logs --tail 50 pilger-app               # Protokoll der App
docker compose -f /opt/pilger-milsh/docker-compose.yml ps
```

## Rollback

```
cd /opt/pilger-milsh
systemctl stop pilger-update.timer     # sonst zieht der Zeitgeber sofort wieder vor
git reset --hard <commit>
docker compose up -d --build
```

Zurück in den Normalbetrieb mit `systemctl start pilger-update.timer`. Wichtiger
ist meist der andere Weg: den Fehler auf `main` beheben — der Server holt sich
die Korrektur von allein.

Die Datenbank bleibt bei alldem unberührt, sie liegt im Volume `pilger-db`.
Eingetragene Beträge und Häkchen überstehen jedes Rollback.

## Sicherung

Zwei Dinge, nicht eins:

```
# Datenbank
docker exec pilger-db mariadb-dump -upilger -p"$DB_PASS" pilger > pilger-$(date +%F).sql

# Fotos und Sprachaufnahmen
docker run --rm -v pilger-milsh_pilger-data:/daten -v "$PWD":/ab alpine \
  tar czf /ab/pilger-fotos-$(date +%F).tar.gz -C /daten .
```

`$DB_PASS` steht in `/opt/pilger-milsh/.env`. Diese Datei gehört mit ins
Backup — ohne sie ist das Volume nicht mehr zu öffnen.

Die Datenbank allein reicht nicht: dort stehen nur die Dateinamen. Ohne das
Volume `pilger-data` kennt sie Bilder, die es nicht mehr gibt.

## Zutritt

Die Seite steht komplett hinter einem **sechsstelligen Zahlencode** — nicht nur
das Speichern, und nicht nur das Bearbeiten. Ohne Anmeldung gibt es 401 und
sonst nichts. Kein Benutzername, keine Mailadresse: nur der Code, eingegeben auf
einem Ziffernblock wie bei der Gerätesperre am Handy. Unterwegs mit klammen
Fingern ist das der einzige brauchbare Weg.

Gesetzt wird er **in der Oberfläche**: Solange keiner hinterlegt ist, zeigt die
Seite ausschließlich die Einrichtung. Eine frisch angelegte Datenbank steht
damit zu und nicht offen. Auf dem Server ist dafür keine Zeile Terminal nötig.

Wer es lieber in der `.env` pflegt, füllt `WRITE_PASSWORD=` aus und ruft
`docker compose up -d` auf. Dieser Wert gewinnt dann, wird als **Passwort**
behandelt (nicht als Code) und lässt sich in der Oberfläche nicht mehr ändern.

Zurücksetzen (Code vergessen):

```
docker compose exec pilger-db mariadb -upilger -p"$DB_PASS" pilger \
  -e "delete from settings where skey in ('auth_hash','auth_kind'); delete from auth_tokens;"
```

## Schlüssel für das Tagebuch

Transkription (Whisper) und Glättung (Claude) sind optional. Ihre Schlüssel
werden in der Oberfläche hinterlegt und liegen danach in `settings` — im
Klartext, in der eigenen Datenbank auf dem eigenen Server hinter dem eigenen
Passwort. Ein Schlüssel, den der Server benutzen soll, muss für ihn lesbar
sein; anders geht es nicht.

Wer das nicht will, lässt die Felder leer: aufnehmen, speichern und abspielen
geht ohne alles, es wird dann nur nichts verschriftlicht.
