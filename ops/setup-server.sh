#!/usr/bin/env bash
#
# Einmalige Einrichtung von pilger.milsh.com auf dem Server.
#
# Aufruf als root:
#   curl -fsSL https://raw.githubusercontent.com/sascha-gif/pilger/main/ops/setup-server.sh | bash
#
# Das Skript ist wiederholbar: ein zweiter Aufruf richtet nichts doppelt ein,
# sondern bringt nur auf den neuesten Stand. Die erzeugten Passwörter in der
# .env bleiben dabei unangetastet — sonst passte die Datenbank nicht mehr dazu.

set -euo pipefail

REPO_URL="https://github.com/sascha-gif/pilger"
APP_DIR="/opt/pilger-milsh"
DOMAIN="pilger.milsh.com"
CADDY_CONTAINER="bcd_caddy"
CADDYFILE="/opt/concierge-bot/server-config/Caddyfile"

say()  { printf '\n\033[1m==> %s\033[0m\n' "$*"; }
ok()   { printf '    \033[32m✓\033[0m %s\n' "$*"; }
warn() { printf '    \033[33m!\033[0m %s\n' "$*"; }
die()  { printf '\n\033[31mAbbruch:\033[0m %s\n\n' "$*" >&2; exit 1; }

[ "$(id -u)" = "0" ] || die "Bitte als root ausführen."

say "1/7  Voraussetzungen prüfen"
command -v git >/dev/null    || die "git fehlt. Nachinstallieren: apt-get install -y git"
command -v docker >/dev/null || die "docker fehlt."
docker compose version >/dev/null 2>&1 || die "docker compose (v2) fehlt."
ok "git, docker und docker compose sind da"

docker inspect "$CADDY_CONTAINER" >/dev/null 2>&1 \
  || die "Container '$CADDY_CONTAINER' läuft nicht. Ohne ihn gibt es keinen Reverse-Proxy."
ok "Reverse-Proxy '$CADDY_CONTAINER' gefunden"

say "2/7  Code holen"
if [ -d "$APP_DIR/.git" ]; then
  git -C "$APP_DIR" remote set-url origin "$REPO_URL"
  git -C "$APP_DIR" fetch --quiet origin main
  git -C "$APP_DIR" reset --hard --quiet origin/main
  ok "vorhandene Arbeitskopie auf den neuesten Stand gebracht"
else
  [ -e "$APP_DIR" ] && die "$APP_DIR existiert, ist aber kein Git-Verzeichnis. Bitte von Hand ansehen."
  git clone --quiet "$REPO_URL" "$APP_DIR"
  ok "nach $APP_DIR geklont"
fi
cd "$APP_DIR"

say "3/7  Netz des Reverse-Proxy ermitteln"
CADDY_NETWORK="$(docker inspect -f '{{range $k, $v := .NetworkSettings.Networks}}{{$k}}{{"\n"}}{{end}}' "$CADDY_CONTAINER" | head -1)"
[ -n "$CADDY_NETWORK" ] || die "Konnte das Docker-Netz von $CADDY_CONTAINER nicht bestimmen."
ok "Netz: $CADDY_NETWORK"

say "4/7  Konfiguration anlegen"
if [ -f .env ]; then
  ok ".env ist bereits vorhanden — Passwörter bleiben unverändert"
  grep -q '^CADDY_NETWORK=' .env \
    && sed -i "s|^CADDY_NETWORK=.*|CADDY_NETWORK=$CADDY_NETWORK|" .env \
    || echo "CADDY_NETWORK=$CADDY_NETWORK" >> .env
else
  gen() { tr -dc 'A-Za-z0-9' < /dev/urandom | head -c 32; }
  cat > .env <<EOF
# Automatisch erzeugt von ops/setup-server.sh — nicht ins Repository geben.
CADDY_NETWORK=$CADDY_NETWORK
DB_NAME=pilger
DB_USER=pilger
DB_PASS=$(gen)
DB_ROOT_PASS=$(gen)
# Solange hier nichts steht, darf jeder Besucher Häkchen und Beträge ändern.
WRITE_PASSWORD=
EOF
  chmod 600 .env
  ok ".env mit frisch erzeugten Passwörtern angelegt"
fi

say "5/7  Container bauen und starten"
docker compose up -d --build
ok "pilger-app und pilger-db laufen"

say "6/7  Reverse-Proxy eintragen"
if [ ! -f "$CADDYFILE" ]; then
  warn "$CADDYFILE nicht gefunden — der Eintrag muss von Hand ergänzt werden:"
  printf '\n%s {\n    reverse_proxy pilger-app:80\n}\n\n' "$DOMAIN"
elif grep -q "^${DOMAIN}[[:space:]]*{" "$CADDYFILE"; then
  ok "Eintrag für $DOMAIN steht bereits"
else
  cp -a "$CADDYFILE" "${CADDYFILE}.bak-$(date +%Y%m%d-%H%M%S)"
  printf '\n%s {\n    reverse_proxy pilger-app:80\n}\n' "$DOMAIN" >> "$CADDYFILE"
  ok "Eintrag ergänzt (Sicherung der alten Fassung daneben abgelegt)"

  if docker exec "$CADDY_CONTAINER" caddy reload --config /etc/caddy/Caddyfile 2>/dev/null; then
    ok "Caddy neu eingelesen"
  else
    warn "caddy reload ging nicht — starte den Container neu"
    docker restart "$CADDY_CONTAINER" >/dev/null
    ok "Caddy neu gestartet"
  fi
fi

say "7/7  Selbstaktualisierung einrichten"
install -m 755 ops/pilger-update.sh /usr/local/bin/pilger-update
install -m 644 ops/pilger-update.service /etc/systemd/system/pilger-update.service
install -m 644 ops/pilger-update.timer   /etc/systemd/system/pilger-update.timer
systemctl daemon-reload
systemctl enable --now pilger-update.timer >/dev/null
ok "Zeitgeber aktiv — der Server holt sich neue Fassungen künftig alle 5 Minuten selbst"

say "Prüfung"

# Drei Ebenen, von innen nach außen. Nur die erste beiden sind aussagekräftig:
# der Aufruf der öffentlichen Adresse scheitert auf vielen Servern daran, dass
# sie ihre eigene öffentliche IP von innen nicht erreichen — das sagt nichts
# über die Erreichbarkeit aus dem Internet.

printf '    … warte auf die App'
for _ in $(seq 1 24); do
  APP_CODE="$(docker run --rm --network "$CADDY_NETWORK" curlimages/curl:latest \
    -sS -o /tmp/pilger-check.html -w '%{http_code}' --max-time 10 "http://pilger-app/" 2>/dev/null || echo 000)"
  [ "$APP_CODE" = "200" ] && break
  printf '.'
  sleep 5
done
printf '\n'

if [ "$APP_CODE" = "200" ]; then
  ok "Die App antwortet im Netz des Reverse-Proxy mit HTTP 200"
  if docker run --rm --network "$CADDY_NETWORK" curlimages/curl:latest \
       -sS --max-time 10 "http://pilger-app/" 2>/dev/null | grep -q 'costTotal'; then
    ok "Die Seite ist vollständig aufgebaut, die Datenbank ist eingespielt"
  fi
else
  warn "Die App antwortet nicht (HTTP $APP_CODE). Das ist der eigentliche Fehler."
  warn "Protokoll ansehen:  docker logs --tail 40 pilger-app"
fi

PROXY_CODE="$(curl -sk -o /dev/null -w '%{http_code}' --max-time 15 \
  -H "Host: $DOMAIN" "https://127.0.0.1/" 2>/dev/null || echo 000)"
if [ "$PROXY_CODE" = "200" ]; then
  ok "Caddy liefert $DOMAIN aus"
else
  warn "Caddy antwortet für $DOMAIN mit HTTP $PROXY_CODE."
  warn "Zertifikat und Fehler ansehen:  docker logs --tail 30 $CADDY_CONTAINER"
fi

PUB_CODE="$(curl -sS -o /dev/null -w '%{http_code}' --max-time 15 "https://$DOMAIN/" 2>/dev/null || echo 000)"
if [ "$PUB_CODE" = "200" ]; then
  ok "https://$DOMAIN ist von hier aus erreichbar"
else
  warn "https://$DOMAIN ist vom Server aus nicht erreichbar (HTTP $PUB_CODE) — das ist oft normal,"
  warn "weil ein Server seine eigene öffentliche Adresse von innen nicht erreicht. Im Browser prüfen."
fi

printf '\n\033[1mFertig.\033[0m  https://%s\n\n' "$DOMAIN"
