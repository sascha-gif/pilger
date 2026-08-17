# Deployment — pilger.milsh.com

## Wie es funktioniert

Merge nach `main` → GitHub Actions startet → Dateien landen per rsync auf dem
Server → die App installiert ihr Schema beim ersten Aufruf selbst.

Der Deploy läuft **auf GitHub**, nicht in einer Claude-Session: Claude-Sessions
kommen per Netzwerkpolicy nicht auf Port 22 hinaus. Der GitHub-Runner hat freien
Ausgang und übernimmt die Verbindung zum Server.

```
Commit ──► main ──► Actions-Runner ──► rsync über SSH ──► /var/www/pilger.milsh.com
                                                              │
                                                    erster HTTP-Aufruf
                                                              │
                                              Schema + Startdaten in MariaDB
```

## Schritte im Workflow

| # | Schritt | Was passiert |
|---|---|---|
| 1 | Syntax-Check | `php -l` über alle PHP-Dateien |
| 2 | Smoke-Test | App startet gegen SQLite, Startseite muss HTTP 200 liefern |
| 3 | Secrets prüfen | bricht mit klarer Meldung ab, wenn etwas fehlt |
| 4 | SSH einrichten | Key aus Secret, `ssh-keyscan` für `known_hosts` |
| 5 | Konfiguration bauen | `config/config.php` aus den DB-Secrets erzeugen |
| 6 | Datenbank anlegen | nur wenn `DB_ADMIN_USER` hinterlegt ist |
| 7 | Dateien übertragen | `rsync -az --delete`, ohne `.git`, `docs/`, `*.md`, `var/` |
| 8 | Rechte setzen | `var/` beschreibbar für den SQLite-Fallback |
| 9 | Live prüfen | `curl` auf die Domain, 5 Versuche — löst zugleich die Installation aus |

Schlägt Schritt 9 fehl, ist der Deploy rot und in den Actions-Logs steht, woran es lag.

## Benötigte Secrets

`Settings → Secrets and variables → Actions → New repository secret`

**Pflicht**

- `SSH_HOST` — `46.224.19.41`
- `SSH_USER` — SSH-Benutzer
- `DEPLOY_PATH` — Zielverzeichnis, z. B. `/var/www/pilger.milsh.com`
- **entweder** `SSH_KEY` — privater Schlüssel, kompletter Text mit `-----BEGIN …-----` und `-----END …-----`
  **oder** `SSH_PASSWORD` — das SSH-Passwort des Benutzers

Der Workflow erkennt selbst, welcher der beiden Wege hinterlegt ist: mit `SSH_KEY`
läuft er über den Schlüssel, sonst über `sshpass`. Ist beides gesetzt, gewinnt der
Schlüssel. Ein eigener Deploy-Key ist einem persönlichen Schlüssel vorzuziehen.

**Optional**

- `SSH_PORT` — falls nicht 22
- `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_HOST`, `DB_PORT` — für MariaDB statt SQLite
- `DB_ADMIN_USER`, `DB_ADMIN_PASS` — MySQL-Admin zum Anlegen der Datenbank
- `WRITE_PASSWORD` — schaltet den Schreibschutz scharf
- `SITE_URL` — abweichende Prüf-URL

## Webserver

Am saubersten zeigt das Document-Root der Domain direkt auf `…/public`:

```nginx
server {
    server_name pilger.milsh.com;
    root /var/www/pilger.milsh.com/public;
    index index.php;

    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    }
}
```

Zeigt das Root stattdessen auf das Projektverzeichnis, greift die mitgelieferte
`.htaccess` und schreibt alles nach `public/` um (Apache). Zusätzlich liegen in
`config/`, `src/` und `db/` eigene `.htaccess`-Dateien, die den Abruf sperren.

**Voraussetzungen auf dem Server:** PHP ≥ 8.0 mit `pdo_mysql` (oder `pdo_sqlite`),
`rsync`, SSH-Zugang. Kein Composer, kein Build-Schritt, kein Node.

## Datenbank

Die App bringt ihr Schema selbst mit (`src/Schema.php` + `db/seed.php`) und legt
es beim ersten Aufruf an. Nötig ist nur eine **leere Datenbank plus Benutzer**.

Sobald `DB_NAME` gesetzt ist, legt der Workflow Datenbank und Benutzer selbst an.
Er versucht dafür zwei Wege, in dieser Reihenfolge:

1. mit `DB_ADMIN_USER` / `DB_ADMIN_PASS`, falls hinterlegt
2. sonst `sudo mysql` auf dem Server — funktioniert auf den meisten
   Debian/Ubuntu-Installationen ohne zusätzliches Passwort

Der angelegte Benutzer bekommt genau das Passwort aus `DB_PASS`; das ist also
frei wählbar und muss nirgendwo vorher existieren. Klappt keiner der beiden Wege,
bricht der Deploy mit einer Meldung ab und die Datenbank wird einmalig von Hand
angelegt:

```sql
CREATE DATABASE pilger CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'pilger'@'localhost' IDENTIFIED BY '…';
GRANT ALL PRIVILEGES ON pilger.* TO 'pilger'@'localhost';
```

Ohne `DB_NAME` läuft alles über SQLite in `var/pilger.sqlite` — voll funktionsfähig,
aber die Datei muss beschreibbar bleiben und gehört ins Backup.

## Rollback

Actions → gewünschten früheren Lauf öffnen → *Re-run all jobs*. Der Stand des
damaligen Commits wird erneut übertragen. Die Datenbank bleibt dabei unberührt —
Inhalte gehen bei einem Rollback also nicht verloren.

## Manuell auslösen

Actions → *Deploy pilger.milsh.com* → *Run workflow*. Nützlich nach dem
Nachtragen von Secrets, ohne dass ein neuer Commit nötig ist.
